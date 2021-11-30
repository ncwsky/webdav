<?php
// https://yandex.com/dev/disk/doc/dg/reference/copy.html https://www.ietf.org/rfc/rfc4918.txt
class WebDav
{
    const STATUS_CODE_100 = 100;
    const STATUS_CODE_102 = 102;

    const STATUS_CODE_200 = 200;
    const STATUS_CODE_201 = 201;
    const STATUS_CODE_202 = 202;
    const STATUS_CODE_203 = 203;
    const STATUS_CODE_204 = 204;
    const STATUS_CODE_205 = 205;
    const STATUS_CODE_206 = 206;
    const STATUS_CODE_207 = 207;

    const STATUS_CODE_300 = 300;
    const STATUS_CODE_301 = 301;
    const STATUS_CODE_302 = 302;
    const STATUS_CODE_303 = 303;
    const STATUS_CODE_304 = 304;
    const STATUS_CODE_305 = 305;
    const STATUS_CODE_307 = 307;

    const STATUS_CODE_400 = 400;
    const STATUS_CODE_401 = 401;
    const STATUS_CODE_402 = 402;
    const STATUS_CODE_403 = 403;
    const STATUS_CODE_404 = 404;
    const STATUS_CODE_405 = 405;
    const STATUS_CODE_406 = 406;
    const STATUS_CODE_407 = 407;
    const STATUS_CODE_408 = 408;
    const STATUS_CODE_409 = 409;
    const STATUS_CODE_410 = 410;
    const STATUS_CODE_412 = 412;
    const STATUS_CODE_413 = 413;
    const STATUS_CODE_414 = 414;
    const STATUS_CODE_415 = 415;
    const STATUS_CODE_416 = 416;
    const STATUS_CODE_417 = 417;
    const STATUS_CODE_422 = 422;
    const STATUS_CODE_423 = 423; // Locked (WebDAV) (RFC 4918)
    const STATUS_CODE_424 = 424;
    const STATUS_CODE_425 = 425;
    const STATUS_CODE_460 = 460;

    const STATUS_CODE_500 = 500;
    const STATUS_CODE_501 = 501;
    const STATUS_CODE_502 = 502;
    const STATUS_CODE_503 = 503;
    const STATUS_CODE_505 = 505;
    const STATUS_CODE_507 = 507;

    public static $httpCodeStatus = [
        // INFORMATIONAL CODES
        self::STATUS_CODE_100 => 'Continue',
        self::STATUS_CODE_102 => 'Processing', // RFC2518
        // SUCCESS CODES
        self::STATUS_CODE_200 => 'OK',
        self::STATUS_CODE_201 => 'Created',
        self::STATUS_CODE_202 => 'Accepted',
        self::STATUS_CODE_203 => 'Non-Authoritative Information',
        self::STATUS_CODE_204 => 'No Content',
        self::STATUS_CODE_205 => 'Reset Content',
        self::STATUS_CODE_206 => 'Partial Content',
        self::STATUS_CODE_207 => 'Multi-Status',          // RFC4918

        self::STATUS_CODE_300 => 'Multiple Choices',
        self::STATUS_CODE_301 => 'Moved Permanently',
        self::STATUS_CODE_302 => 'Found',
        self::STATUS_CODE_303 => 'See Other',
        self::STATUS_CODE_304 => 'Not Modified',
        self::STATUS_CODE_305 => 'Use Proxy',
        self::STATUS_CODE_307 => 'Temporary Redirect',
        // CLIENT ERROR
        self::STATUS_CODE_400 => 'Bad Request',
        self::STATUS_CODE_401 => 'Unauthorized',
        self::STATUS_CODE_402 => 'Payment Required',
        self::STATUS_CODE_403 => 'Forbidden',
        self::STATUS_CODE_404 => 'Not Found',
        self::STATUS_CODE_405 => 'Method Not Allowed',
        self::STATUS_CODE_406 => 'Not Acceptable',
        self::STATUS_CODE_407 => 'Proxy Authentication Required',
        self::STATUS_CODE_408 => 'Request Timeout',
        self::STATUS_CODE_409 => 'Conflict',
        self::STATUS_CODE_410 => 'Gone',
        self::STATUS_CODE_412 => 'Precondition Failed',
        self::STATUS_CODE_413 => 'Request Entity Too Large',
        self::STATUS_CODE_415 => 'Unsupported Media Type',
        self::STATUS_CODE_416 => 'Range Not Satisfiable',
        self::STATUS_CODE_417 => 'Expectation Failed',
        self::STATUS_CODE_422 => 'Unprocessable Entity', // RFC4918
        self::STATUS_CODE_423 => 'Locked', // RFC4918
        self::STATUS_CODE_424 => 'Failed Dependency', // RFC4918
        self::STATUS_CODE_425 => 'Too Early',
        self::STATUS_CODE_460 => 'Checksum Mismatch',
        // SERVER ERROR
        self::STATUS_CODE_500 => 'Internal Server Error',
        self::STATUS_CODE_501 => 'Not Implemented',
        self::STATUS_CODE_502 => 'Bad Gateway',
        self::STATUS_CODE_503 => 'Service Unavailable',
        self::STATUS_CODE_505 => 'HTTP Version Not Supported',
        self::STATUS_CODE_507 => 'Insufficient Storage', // RFC4918
    ];

    //可浏览器渲染的MIME类型
    public static $mimeTypeInline = [
        "text/plain" => 1,
        "image/png" => 1,
        "image/jpeg" => 1,
        "image/gif" => 1,
        "image/bmp" => 1,
        "image/webp" => 1,
        "audio/wave" => 1,
        "audio/wav" => 1,
        "audio/x-wav" => 1,
        "audio/x-pn-wav" => 1,
        "audio/webm" => 1,
        "video/webm" => 1,
        "audio/ogg" => 1,
        "video/ogg " => 1,
        "application/ogg" => 1,
    ];

    protected $protocol = '';
    protected $req_header = null;
    protected $res_header = [];
    protected $res_code = self::STATUS_CODE_200;
    protected $res_body = null;

    public $isSend = false;
    public $maxUploadSize = 0; // 0 无限制, 217483648 2GB
    public $prefix = ''; //要从WebDAV资源路径中删除的URL路径前缀

    /**
     * 是否支持使用 Upload-Metadata+Upload-Length 生成上传key
     * 必需 Upload-Metadata包含[filename|name], Upload-Length>0
     * @var bool
     */
    public $logFile = '';
    public $logSize = 4194304;
    public $isLog = false;
    /**
     * @var null|Closure ($path, $length)
     * @return int
     */
    public $writeInStream = null;
    /**
     * @var null|Closure ($list, $pathList)
     * @return string
     */
    public $dirGetCallBack = null;

    public $customInStream = ''; //指定自定上传资源路径

    /**
     * @var WebDavFile|WebDavFileInterface|null
     */
    public $file = null;
    protected $reqPath;

    public function __construct(WebDavFileInterface $store)
    {
        $this->file = $store;
        $this->file->dav = $this;
        $this->logFile = dirname(__DIR__) . '/webdav.log';
    }

    // 401 Unauthorized
    // WWW-Authenticate: xxx
    public static $realm = 'HttpAuth';
    public static $authUsers = [];
    public static $authBasic = true; //Basic Digest

    public static function auth($logout = false)
    {
        if (empty(self::$authUsers)) return true;
        if ($logout) {
            unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['PHP_AUTH_DIGEST']);
            return false;
        }

        $auth = 'Basic realm="' . self::$realm . '"';
        if (self::$authBasic) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authInfo = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)); //Basic
                if (strpos($authInfo, ':')) {
                    list($user, $password) = explode(':', $authInfo);
                    $_SERVER['PHP_AUTH_USER'] = $user;
                    $_SERVER['PHP_AUTH_PW'] = $password;
                }
            }

            if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
                return $auth; //未输入认证信息
            }
            if (!isset(self::$authUsers[$_SERVER['PHP_AUTH_USER']]) || self::$authUsers[$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW']) {
                return $auth; //认证不匹配
            }
            return true;
        } else {
            $opaque = md5(self::$realm . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
            $auth = 'Digest realm="' . self::$realm . '",qop="auth",nonce="' . uniqid() . '",opaque="' . $opaque . '"';
        }

        if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                return $auth; //未输入认证信息
            }
            $_SERVER['PHP_AUTH_DIGEST'] = $_SERVER['HTTP_AUTHORIZATION'];
        }

        $needed_parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
        $data = array();
        $keys = implode('|', array_keys($needed_parts));

        preg_match_all('/(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))/', $_SERVER['PHP_AUTH_DIGEST'], $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[3] ? $m[3] : $m[4];
            unset($needed_parts[$m[1]]);
        }
        if ($needed_parts || !isset(self::$authUsers[$data['username']])) {
            return $auth; //认证无效
        }

        $login = ['name' => $data['username'], 'password' => self::$authUsers[$data['username']]];

        $password = md5($login['name'] . ':' . self::$realm . ':' . $login['password']);
        $response = md5($password . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']));

        if ($data['response'] != $response) {
            return $auth; //认证不匹配
        }

        return true;
    }

    public static function json($content)
    {
        return json_encode($content, defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES : 0);
    }

    public static function getMethod()
    {
        return isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) : (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    }

    public static function getSiteUrl()
    {
        $scheme = 'http';
        if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') {
            $scheme = 'https';
        } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }
        return $scheme . '://' . (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ':' . $_SERVER['SERVER_PORT'])));
    }

    public static function getPathInfo()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $pos = strpos($_SERVER['REQUEST_URI'], '?');
            return $pos ? substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'];
        }
        return isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
    }

    public static function cut($str, $s_flag, $e_flag, $offset=0, $case=true, &$pos_e=0){
        $pos_s = $case ? stripos($str, $s_flag, $offset) : strpos($str, $s_flag, $offset);
        if($pos_s===false) return '';
        $pos_s += strlen($s_flag);
        $pos_e = $case ? stripos($str, $e_flag, $pos_s) : strpos($str, $e_flag, $pos_s);
        return $pos_e ? substr($str, $pos_s, $pos_e-$pos_s) : substr($str, $pos_s);
    }
    public static function uuid()
    {
        $uuid = md5(uniqid(mt_rand(), true));
        return substr($uuid, 0, 8).'-'
            .substr($uuid, 8, 4).'-'
            .substr($uuid,12, 4).'-'
            .substr($uuid,16, 4).'-'
            .substr($uuid,20,12);
    }
    //名称是否有效
    public static function isValidName($name)
    {
        $forbidden = ['\\', '/', ':', '*', '?', '"', '<', '>', '|'];
        foreach ($forbidden as $v) {
            if (strpos($name, $v) !== false) return false;
        }
        return true;
    }

    //仅记录指定大小的日志 超出大小重置重新记录
    public function log($content)
    {
        if (!$this->isLog) return;

        if (is_file($this->logFile) && $this->logSize <= filesize($this->logFile)) {
            file_put_contents($this->logFile, '', LOCK_EX);
            clearstatcache(true, $this->logFile);
        }
        if (func_num_args() > 1) {
            $args = func_get_args();
            $content = '';
            foreach ($args as $v) {
                $content .= (is_scalar($v) ? $v : self::json($v)) . ' ';
            }
        }
        file_put_contents($this->logFile, "[" . date("Y-m-d H:i:s").'.'.substr(microtime(), 2,3) . "]" . (is_scalar($content) ? $content : self::json($content)) . "\n", FILE_APPEND);
    }

    public function getReqHeader($name = null, $default = null)
    {
        if ($this->req_header === null) {
            if (function_exists('getallheaders')) {
                $this->req_header = getallheaders();
            } else {
                foreach ($_SERVER as $name => $value) {
                    if (strncmp($name, 'HTTP_', 5) === 0) {
                        $_name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $this->req_header[$_name] = $value;
                    } elseif (strncmp($name, 'CONTENT_', 8) === 0) {
                        $_name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 8)))));
                        $this->req_header[$_name] = $value;
                    }
                }
                if (!isset($this->req_header['Authorization'])) {
                    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                        $this->req_header['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                    } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                        $this->req_header['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
                    } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                        $this->req_header['Authorization'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
                    }
                }
            }
        }

        if ($name === null) return $this->req_header;
        if (is_array($name)) {
            $values = [];
            foreach ($name as $item) {
                $values[$item] = isset($this->req_header[$item]) ? $this->req_header[$item] : $default;
            }
            return $values;
        }
        return isset($this->req_header[$name]) ? $this->req_header[$name] : $default;
    }

    public function getReqPath()
    {
        $uri = self::getPathInfo();
        return urldecode($this->stripPrefix($uri));
    }

    protected function stripPrefix($srcPath)
    {
        if (!$this->file->isValid($srcPath)) {
            throw new Exception('Unsupported', self::STATUS_CODE_502);
        }
        if ($this->prefix === '') {
            return $srcPath === '' ? '/' : $srcPath;
        }
        $path = strpos($srcPath, $this->prefix) === 0 ? substr($srcPath, strlen($this->prefix)) : $srcPath;

        if (strlen($path) < strlen($srcPath)) {
            return $path === '' ? '/' : $path;
        }
        throw new Exception('prefix mismatch', self::STATUS_CODE_404);
    }

    public function setResCode($code)
    {
        $this->res_code = $code;
        return $this;
    }

    public function setResHeader($name, $value = null)
    {
        if (is_array($name)) {
            $this->res_header = $value === true ? $name : array_merge($this->res_header, $name);
        } elseif ($name === null) {
            $this->res_header = [];
        } else {
            $this->res_header[$name] = $value;
        }
        return $this;
    }

    public function reqHandle()
    {
        set_time_limit(10240);
        $method = ucfirst(strtolower(self::getMethod()));

        $this->log('req', $method, self::getPathInfo() , $this->getReqHeader(), PHP_EOL); //, $_SERVER

        if (!$this->middleware($method)) {
            return $this->response();
        }

        $method = 'handle' . $method;
        if (method_exists($this, $method)) {
            try {
                call_user_func(array($this, $method));
            } catch (Exception $e) {
                $code = $e->getCode();
                $this->setResCode($code ? $code : self::STATUS_CODE_500);

                if ($code != self::STATUS_CODE_404) {
                    $this->log($e->getCode() . ':' . $e->getMessage());
                    $this->res_body = $e->getMessage();
                    //$this->log($e->getTraceAsString());
                }
            }
        } else {
            $this->setResCode(self::STATUS_CODE_405);
        }
        return $this->response();
    }

    protected function response()
    {
        if (!isset(self::$httpCodeStatus[$this->res_code])) $this->res_code = self::STATUS_CODE_200;
        if ($this->res_code == self::STATUS_CODE_404) {
            //$this->res_body = 'Not Found';
            $this->setResHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        $this->log('res', $this->res_code, self::$httpCodeStatus[$this->res_code], $this->res_header, is_scalar($this->res_body) ? substr($this->res_body, 0, 50) : self::json($this->res_body), PHP_EOL);

        if ($this->isSend === false) return [$this->res_code, $this->res_header, $this->res_body];

        if (headers_sent()) {
            return null;
        }

        header($this->protocol . ' ' . $this->res_code . ' ' . self::$httpCodeStatus[$this->res_code]);

        foreach ($this->res_header as $name => $value) {
            header($name . ': ' . $value);
        }
        if ($this->res_body instanceof Closure) {
            call_user_func($this->res_body);
        } elseif ($this->res_body !== null) {
            $out = fopen('php://output', 'w');
            stream_set_chunk_size($out, $this->file->chunkSize);
            fwrite($out, is_scalar($this->res_body) ? $this->res_body : self::json($this->res_body));
            fclose($out);
            //echo is_scalar($this->res_body) ? $this->res_body : self::json($this->res_body);
        }
    }

    protected function middleware($method)
    {
        //重置初始
        $this->protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
        $this->req_header = null;
        $this->res_header = [
            //'X-Content-Type-Options' => 'nosniff',
        ];
        $this->res_code = self::STATUS_CODE_200;
        $this->res_body = null;
        $this->customInStream = '';

        $logout = isset($_GET['auth']) && $_GET['auth'] == 'logout';
        $auth = self::auth($logout);
        if ($auth !== true) {
            if ($auth === false) { //logout
                $this->setResCode(self::STATUS_CODE_302);
                //$this->setResHeader('Location', self::getSiteUrl());
                $this->setResHeader('WWW-Authenticate', $auth);
                $this->setResCode(self::STATUS_CODE_401);
                $this->res_body = '<div style="text-align: center;padding:1rem 0;font-size:1rem;"><a href="'.self::getSiteUrl().'">Login</a></div>';
                return false;
            }
            $this->setResHeader('WWW-Authenticate', $auth);
            $this->setResCode(self::STATUS_CODE_401);
            return false;
        }

        return true;
    }

    // WebDAV 中定义的方法中，PUT、PROPPATCH、LOCK、UNLO​​CK、MOVE、COPY（对于目标资源）、DELETE 和 MKCOL 受写锁影响。迄今为止定义的所有其他 HTTP/WebDAV 方法——特别是 GET——功能独立于写锁。
    // 修改相关的操作主要涉及 PUT/DELETE/PROPPATCH/MKCOL/COPY/MOVE 等几个方法。我们只要将这几个方法屏蔽了就可以实现一个只读的WebDAV服务。
    /**
     * 获取有关服务器目前配置的信息
     * OPTIONS /path
     */
    protected function handleOptions()
    {
        $allow = "OPTIONS, LOCK, PUT, MKCOL";
        $this->reqPath = $this->getReqPath();
        if ($this->file->stat($this->reqPath)) {
            if ($this->file->isDir($this->reqPath)) {
                $allow = "OPTIONS, LOCK, DELETE, COPY, MOVE, UNLOCK, PROPFIND"; //PROPPATCH
            } else {
                $allow = "OPTIONS, LOCK, GET, HEAD, DELETE, COPY, MOVE, UNLOCK, PROPFIND, PUT"; //POST, PROPPATCH
            }
        }
        $headers = [
            'Allow' => $allow,
            'DAV' => '1, 2',
            'MS-Author-Via' => 'DAV',
            'Content-Length'=>0,
        ];

        $this->setResCode(self::STATUS_CODE_200);
        $this->setResHeader($headers);
    }

    protected function getRanges(){
        if(!isset($_SERVER['HTTP_RANGE'])) return false;

        $ranges = [];
        $range = substr($_SERVER['HTTP_RANGE'], 6);
        if (strpos($range, ',')) { // 多个 bytes=0-5,6-10
            $list = explode(',', $range);
            foreach ($list as $item) {
                $ranges[] = explode('-', $item);
            }
        } else { //单个 bytes=0-5  bytes=-1  bytes=500-
            $ranges[] = explode('-', $range);
        }
        return $ranges;
    }

    /**
     * 获取文件
     * GET /path
     * @throws Exception
     */
    protected function handleGet()
    {
        $this->reqPath = $this->getReqPath();

        $stat = $this->file->stat($this->reqPath);
        if (!$stat) {
            return $this->setResCode(self::STATUS_CODE_404);
        }

        if ($this->file->isDir($this->reqPath)) {
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $list = $this->file->depth($this->reqPath, $search!=='', $search); //有搜索递归
            //取路径层次
            $prefix = $this->prefix;
            $path = $this->reqPath;
            $pathList = []; //排除根目录
            if ($this->reqPath !== '/') {
                $pathList = [$prefix . $path];
                while ($path = dirname($path)) {
                    if ($path == DIRECTORY_SEPARATOR) break;
                    $pathList[] = $prefix . $path;
                }
                sort($pathList);
            }
            if ($this->dirGetCallBack) {
                $this->res_body = call_user_func($this->dirGetCallBack, $list, $pathList);
            } else {
                $data = [];
                foreach ($list as $k => $v) {
                    $data[$v['is_dir'] . '_' . $v['path']] = $v;
                }
                unset($list);
                ksort($data, SORT_STRING | SORT_FLAG_CASE); //文件名排序

                ob_start();
                include __DIR__ . "/WebDavGetList.php";
                $this->res_body = ob_get_clean();
            }
            return $this->setResCode(self::STATUS_CODE_200);
        }

        $fp = $this->file->open($this->reqPath, 'r');
        if (!$fp) {
            return $this->setResCode(self::STATUS_CODE_502);
        }
        $etag = sprintf('%x%x', $stat['mtime'], $stat['size']);

        $this->setResHeader('ETag', $etag);
        $this->setResHeader('Last-Modified', gmdate('D, d M Y H:i:s', $stat['mtime']) . ' GMT');

        if (isset($_GET['down'])) { //stripos($this->getReqHeader('User-Agent'), 'dav') === false
            $contentType = $this->file->type($this->reqPath);
            //$disposition = (isset(self::$mimeTypeInline[$contentType]) ? 'inline' : 'attachment') . ';filename="' . basename($this->reqPath) . '"';
            $this->setResHeader('Content-Type', $contentType);
            $this->setResHeader('Content-Disposition', 'attachment; filename="' . basename($this->reqPath) . '"');
        }

        $offset = 0;
        $readSize = -1; //读取的大小
        $endOffset = $stat['size'] - 1;
        $range = $this->getReqHeader('HTTP_RANGE');
        if ($range) {
            $ranges = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
            $offset = (int)$ranges[0];
            if ($offset < 0 || $offset > $stat['size']) {
                return $this->setResCode(self::STATUS_CODE_416);
            }
            if ($ranges[1] !== '') {
                $endOffset = (int)$ranges[0];
                if ($endOffset < 0 || $endOffset > $stat['size']) {
                    return $this->setResCode(self::STATUS_CODE_416);
                }
            }
            $readSize = $endOffset - $offset + 1;

            $this->setResHeader('Content-Range', 'bytes ' . $offset . '-' . $endOffset . '/' . $stat['size']);
            $this->setResHeader('Content-Length', $readSize);
            $this->setResCode(self::STATUS_CODE_206);
        } else {
            $this->setResHeader('Content-Length', $stat['size']);
            $this->setResCode(self::STATUS_CODE_200);
        }
        $this->setResHeader('Content-Type', $this->file->type($this->reqPath));

        //todo 大文件读取优化
        $this->res_body = function () use($fp, $offset, $readSize){
            $out = fopen('php://output', 'w');
            $this->file->put($out, $fp, $offset, $readSize);
            fclose($out);
        };
    }

    /**
     * 检查上传的文件是否合法
     * HEAD /files/{upload-key}
     */
    protected function handleHead()
    {
        $this->reqPath = $this->getReqPath();

        if ($this->file->isDir($this->reqPath)) {
            return $this->setResCode(self::STATUS_CODE_405);
        }

        $stat = $this->file->stat($this->reqPath);
        if (!$stat) {
            return $this->setResCode(self::STATUS_CODE_404);
        }
        $etag = sprintf('%x%x', $stat['mtime'], $stat['size']);

        $this->setResHeader('ETag', $etag);
        $this->setResHeader('Content-length', $stat['size']);
        $this->setResHeader('Last-Modified', gmdate('D, d M Y H:i:s', $stat['mtime']) . ' GMT');
    }

    /**
     * 创建上传
     * POST /files
     * @throws Exception
     */
    protected function handlePost()
    {
        if (!empty($_GET['name'])) { //有普通方式上传文件 模拟put
            $name = $_GET['name'];
            if (!isset($_FILES[$name])) {
                throw new Exception('未选择上传文件', self::STATUS_CODE_400);
            }
            if ($_FILES[$name]['error'] > 0) {
                throw new Exception('上传失败:' . $_FILES[$name]['error'], self::STATUS_CODE_400);
            }
            if (!self::isValidName($_FILES[$name]['name'])) {
                throw new Exception('文件名无效', self::STATUS_CODE_400);
            }

            $this->customInStream = $_FILES[$name]['tmp_name']; //上传数据
            $this->req_header['Content-Type'] = $_FILES[$name]['type'];
            $this->req_header['Content-Length'] = $_FILES[$name]['size'];
            $this->reqPath = $this->getReqPath() . '/' . $_FILES[$name]['name'];
            if ($this->file->exists($this->reqPath)) {
                throw new Exception('文件已存在', self::STATUS_CODE_400);
            }
            $this->handlePut($this->reqPath);
            return $this->setResCode(self::STATUS_CODE_200);
        }
        return $this->setResCode(self::STATUS_CODE_405);
    }

    /**
     * 删除
     * DELETE /path
     */
    protected function handleDelete()
    {
        $this->reqPath = $this->getReqPath();

        $stat = $this->file->stat($this->reqPath);
        if (!$stat) {
            return $this->setResCode(self::STATUS_CODE_404);
        }
        //todo  锁定占用判定  fail-all:404, fail-part:207, ok:204

        if (!$this->file->delete($this->reqPath)) {
            return $this->setResCode(self::STATUS_CODE_405);
        }

        $this->setResCode(self::STATUS_CODE_204);
    }

    /**
     * 上传文件
     * Put /path
     * @param string $reqPath
     * @throws Exception
     */
    protected function handlePut($reqPath = '')
    {
        $this->reqPath = $reqPath === '' ? $this->getReqPath() : $reqPath;
        $length = (int)$this->getReqHeader('Content-Length');

        $bytesWritten = 0;
        if ($this->customInStream==='' && $this->writeInStream) {
            $bytesWritten = call_user_func($this->writeInStream, $this->reqPath, $length);
        } else {
            $fp = fopen($this->inStream(), 'rb');
            if ($fp) {
                $bytesWritten = $this->file->write($fp, $this->reqPath, $length);
                fclose($fp);
            }
        }
        if($bytesWritten==0) return $this->setResCode(self::STATUS_CODE_200);
        $this->setResCode(self::STATUS_CODE_201);
    }

    /**
     *
     * MKCOL /path
     * @throws Exception
     */
    protected function handleMkcol()
    {
        $this->reqPath = $this->getReqPath();
        $code = $this->file->mkcol($this->reqPath);
        $this->setResCode($code);
    }

    /**
     *
     * COPY /path
     * @throws Exception
     */
    protected function handleCopy()
    {
        $this->toCopyMove(true);
    }

    /**
     *
     * MOVE /path
     * @throws Exception
     */
    protected function handleMove()
    {
        $this->toCopyMove();
    }

    protected function toCopyMove($copy=false){
        $this->reqPath = $this->getReqPath();
        $overwrite = $this->getReqHeader('Overwrite', 'T'); //默认覆盖
        $dst = parse_url($this->getReqHeader('Destination'), PHP_URL_PATH); //目标名

        if (!$dst) {
            return $this->setResCode(self::STATUS_CODE_400);
        }
        $dst = urldecode($this->stripPrefix($dst));

        if ($dst == $this->reqPath) {
            return $this->setResCode(self::STATUS_CODE_403);
        }

        if ($overwrite == 'F' && $this->file->exists($dst)) {
            return $this->setResCode(self::STATUS_CODE_412);
        }

        $this->file->copyMove($this->reqPath, $dst, $copy);
        $this->setResCode($copy ? self::STATUS_CODE_204 : self::STATUS_CODE_201);
    }

    /**
     *
     * LOCK /path
     * @throws Exception
     */
    protected function handleLock()
    {
        $this->reqPath = $this->getReqPath();
        //todo 锁 关联uuid
        if(!$this->file->lock($this->reqPath)){
            $this->setResCode(self::STATUS_CODE_423);
        }
        $this->file->unlock($this->reqPath);

        $rawBody = file_get_contents($this->inStream());
        //$rawBody = str_replace(['xmlns:D="DAV:"','D:'],['','D-'], $rawBody); //去掉xml的命名空间
        //$xml = json_decode(json_encode(simplexml_load_string($rawBody)), true);
        $timeout = $this->getReqHeader('Timeout');
        $depth = $this->getReqHeader('Depth', 'infinity');

        $this->res_body = $this->xmlVersion().'<D:prop xmlns:D="DAV:">
         <D:lockdiscovery>
          <D:activelock>
           <D:lockscope>'.self::cut($rawBody, '<D:lockscope>', '</D:lockscope>').'</D:lockscope>
           <D:locktype>'.self::cut($rawBody, '<D:locktype>', '</D:locktype>').'</D:locktype>
           <D:depth>'.$depth.'</D:depth>
           <D:owner>'.self::cut($rawBody, '<D:owner>', '</D:owner>').'</D:owner>
           <D:timeout>'.$timeout.'</D:timeout>
           <D:locktoken><D:href>urn:uuid:'.self::uuid().'</D:href></D:locktoken>
          </D:activelock>
         </D:lockdiscovery>
        </D:prop>';
    }

    /**
     *
     * UNLOCK /path
     * @throws Exception
     */
    protected function handleUnlock()
    {
        //todo 解锁 关联uuid

        $lockToken = $this->getReqHeader('Lock-Token');
        if (!$lockToken) {
            return $this->setResCode(self::STATUS_CODE_400);
        }

        if (0) { //$lockToken 不存在 //todo
            return $this->setResCode(self::STATUS_CODE_409);
        }

        $this->setResCode(self::STATUS_CODE_204);
    }

    protected function xmlDResponse($list){
        // [0:path, 1:is_dir, 2:size, 3:mtime, 4:ctime, 5:mime_content_type];
        $dResponse = '';
        foreach ($list as $item){
            $dResponse .= '<D:response>
        <D:href>' . $this->prefix . $item['path'] . '</D:href>
        <D:propstat>
            <D:prop>
                <D:displayname>' . basename($item['path']) . '</D:displayname>
                <D:creationdate>' . gmdate('D, d M Y H:i:s', $item['ctime']) . ' GMT' . '</D:creationdate>
                <D:getlastmodified>' . gmdate('D, d M Y H:i:s', $item['mtime']) . ' GMT' . '</D:getlastmodified>
                ' . ($item['is_dir'] ? '<D:resourcetype><D:collection/></D:resourcetype>' : '<D:getcontenttype>' . $item['type'] . '</D:getcontenttype><D:getcontentlength>' . $item['size'] . '</D:getcontentlength><D:resourcetype/><D:getetag>'.sprintf("%x%x", $item['mtime'], $item['size']).'</D:getetag>') . '
            </D:prop>
            <D:status>' . $this->protocol . ' 200 OK</D:status>
        </D:propstat>
    </D:response>';
        }

        return $dResponse;
        //       <D:getetag>'.sprintf('%x%x', $stat['mtime'], $stat['size']).'</D:getetag>
    }
    protected function xmlDResponseFail($path, $code){
        return '<D:response>
        <D:href>' . $this->prefix . $path . '</D:href>
        <D:propstat>
            <D:prop>
                <D:displayname>' . basename($path) . '</D:displayname>
            </D:prop>
            <D:status>' . $this->protocol . ' ' . $code . ' ' . (isset(self::$httpCodeStatus[$code]) ? self::$httpCodeStatus[$code] : 'Unknown').'</D:status>
        </D:propstat>
    </D:response>';
    }

    protected function xmlDMultistatus($dResponse){
        return $this->xmlVersion().'<D:multistatus xmlns:D="DAV:">'.$dResponse.'</D:multistatus>';
    }
    protected function xmlVersion(){
        $this->setResHeader('Content-Type', 'text/xml; charset=utf-8');
        return '<?xml version="1.0" encoding="UTF-8"?>';
    }
    /**
     * 目录列表
     * PROPFIND /path
     * @throws Exception
     */
    protected function handlePropfind()
    {
        $depth = $this->getReqHeader('Depth', '1');
        if (!in_array($depth, ['0', '1', 'infinity'])) {
            return $this->setResCode(self::STATUS_CODE_400);
        }

        $this->reqPath = $this->getReqPath();
/*        if (substr($this->reqPath, -5) === '/HEAD') { // /path/HEAD
            $this->reqPath = substr($this->reqPath, 0, -5);
            $depth = '0';
        }*/

        $stat = $this->file->stat($this->reqPath);
        if (!$stat) {
            $this->setResHeader('Content-Length', 0);// Not Found
            return $this->setResCode(self::STATUS_CODE_404);
            //$this->res_body = $this->xmlDMultistatus($this->xmlDResponseFail($this->reqPath, self::STATUS_CODE_404));
        }
        $isDir = $this->file->isDir($this->reqPath);
        if ($depth == '0' || !$isDir) {
            $list = [['path'=>$this->reqPath, 'is_dir'=>$isDir, 'size'=>$stat['size'], 'mtime'=>$stat['mtime'], 'ctime'=>$stat['ctime'], 'type'=>$isDir ? '' : 'application/octet-stream']];
        } else {
            $list = $this->file->depth($this->reqPath, $depth == 'infinity');
        }
        $this->setResCode(self::STATUS_CODE_207);
        $this->res_body = $this->xmlDMultistatus($this->xmlDResponse($list));
        //$this->setResHeader('Content-Length', strlen($this->res_body));
    }

    /**
     * 修改文件或目录属性
     * PROPPATCH /path
     * @throws Exception
     */
    protected function handleProppatch()
    {
        $this->reqPath = $this->getReqPath();
        $this->setResCode(self::STATUS_CODE_207);
        $this->res_body = $this->xmlDMultistatus($this->xmlDResponseFail($this->reqPath, self::STATUS_CODE_403));
/*        $this->res_body = $this->xmlDMultistatus('<D:response>
			<D:href>' . self::getPathInfo() . '</D:href>
			<D:propstat>
				<D:prop>
					<m:Win32LastAccessTime xmlns:m="urn:schemas-microsoft-com:" />
					<m:Win32CreationTime xmlns:m="urn:schemas-microsoft-com:" />
					<m:Win32LastModifiedTime xmlns:m="urn:schemas-microsoft-com:" />
					<m:Win32FileAttributes xmlns:m="urn:schemas-microsoft-com:" />
				</D:prop>
				<D:status>HTTP/1.1 200 OK</D:status>
			</D:propstat>
		</D:response>');*/
    }


    /**
     * 返回上传读取资源地址
     * @return string
     */
    protected function inStream()
    {
        return $this->customInStream === '' ? 'php://input' : $this->customInStream;
    }
}

if( !function_exists ('mime_content_type')) {
    /**
    +----------------------------------------------------------
     * 获取文件的mime_content类型
    +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    function mime_content_type($filename)
    {
        static $contentType = array(
            'ai'	=> 'application/postscript',
            'aif'	=> 'audio/x-aiff',
            'aifc'	=> 'audio/x-aiff',
            'aiff'	=> 'audio/x-aiff',
            'asc'	=> 'application/pgp', //changed by skwashd - was text/plain
            'asf'	=> 'video/x-ms-asf',
            'asx'	=> 'video/x-ms-asf',
            'au'	=> 'audio/basic',
            'avi'	=> 'video/x-msvideo',
            'bcpio'	=> 'application/x-bcpio',
            'bin'	=> 'application/octet-stream',
            'bmp'	=> 'image/bmp',
            'c'	=> 'text/plain', // or 'text/x-csrc', //added by skwashd
            'cc'	=> 'text/plain', // or 'text/x-c++src', //added by skwashd
            'cs'	=> 'text/plain', //added by skwashd - for C# src
            'cpp'	=> 'text/x-c++src', //added by skwashd
            'cxx'	=> 'text/x-c++src', //added by skwashd
            'cdf'	=> 'application/x-netcdf',
            'class'	=> 'application/octet-stream',//secure but application/java-class is correct
            'com'	=> 'application/octet-stream',//added by skwashd
            'cpio'	=> 'application/x-cpio',
            'cpt'	=> 'application/mac-compactpro',
            'csh'	=> 'application/x-csh',
            'css'	=> 'text/css',
            'csv'	=> 'text/comma-separated-values',//added by skwashd
            'dcr'	=> 'application/x-director',
            'diff'	=> 'text/diff',
            'dir'	=> 'application/x-director',
            'dll'	=> 'application/octet-stream',
            'dms'	=> 'application/octet-stream',
            'doc'	=> 'application/msword',
            'dot'	=> 'application/msword',//added by skwashd
            'dvi'	=> 'application/x-dvi',
            'dxr'	=> 'application/x-director',
            'eps'	=> 'application/postscript',
            'etx'	=> 'text/x-setext',
            'exe'	=> 'application/octet-stream',
            'ez'	=> 'application/andrew-inset',
            'gif'	=> 'image/gif',
            'gtar'	=> 'application/x-gtar',
            'gz'	=> 'application/x-gzip',
            'h'	=> 'text/plain', // or 'text/x-chdr',//added by skwashd
            'h++'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hh'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hpp'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hxx'	=> 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hdf'	=> 'application/x-hdf',
            'hqx'	=> 'application/mac-binhex40',
            'htm'	=> 'text/html',
            'html'	=> 'text/html',
            'ice'	=> 'x-conference/x-cooltalk',
            'ics'	=> 'text/calendar',
            'ief'	=> 'image/ief',
            'ifb'	=> 'text/calendar',
            'iges'	=> 'model/iges',
            'igs'	=> 'model/iges',
            'jar'	=> 'application/x-jar', //added by skwashd - alternative mime type
            'java'	=> 'text/x-java-source', //added by skwashd
            'jpe'	=> 'image/jpeg',
            'jpeg'	=> 'image/jpeg',
            'jpg'	=> 'image/jpeg',
            'js'	=> 'application/x-javascript',
            'kar'	=> 'audio/midi',
            'latex'	=> 'application/x-latex',
            'lha'	=> 'application/octet-stream',
            'log'	=> 'text/plain',
            'lzh'	=> 'application/octet-stream',
            'm3u'	=> 'audio/x-mpegurl',
            'man'	=> 'application/x-troff-man',
            'me'	=> 'application/x-troff-me',
            'mesh'	=> 'model/mesh',
            'mid'	=> 'audio/midi',
            'midi'	=> 'audio/midi',
            'mif'	=> 'application/vnd.mif',
            'mov'	=> 'video/quicktime',
            'movie'	=> 'video/x-sgi-movie',
            'mp3'	=> 'audio/mpeg',
            'mp4'	=> 'video/mp4',
            'mpe'	=> 'video/mpeg',
            'mpeg'	=> 'video/mpeg',
            'mpg'	=> 'video/mpeg',
            'mpga'	=> 'audio/mpeg',
            'ms'	=> 'application/x-troff-ms',
            'msh'	=> 'model/mesh',
            'mxu'	=> 'video/vnd.mpegurl',
            'nc'	=> 'application/x-netcdf',
            'oda'	=> 'application/oda',
            'patch'	=> 'text/diff',
            'pbm'	=> 'image/x-portable-bitmap',
            'pdb'	=> 'chemical/x-pdb',
            'pdf'	=> 'application/pdf',
            'pgm'	=> 'image/x-portable-graymap',
            'pgn'	=> 'application/x-chess-pgn',
            'pgp'	=> 'application/pgp',//added by skwashd
            'php'	=> 'application/x-httpd-php',
            'php3'	=> 'application/x-httpd-php3',
            'pl'	=> 'application/x-perl',
            'pm'	=> 'application/x-perl',
            'png'	=> 'image/png',
            'pnm'	=> 'image/x-portable-anymap',
            'po'	=> 'text/plain',
            'ppm'	=> 'image/x-portable-pixmap',
            'ppt'	=> 'application/vnd.ms-powerpoint',
            'ps'	=> 'application/postscript',
            'qt'	=> 'video/quicktime',
            'ra'	=> 'audio/x-realaudio',
            'rar'=>'application/octet-stream',
            'ram'	=> 'audio/x-pn-realaudio',
            'ras'	=> 'image/x-cmu-raster',
            'rgb'	=> 'image/x-rgb',
            'rm'	=> 'audio/x-pn-realaudio',
            'roff'	=> 'application/x-troff',
            'rpm'	=> 'audio/x-pn-realaudio-plugin',
            'rtf'	=> 'text/rtf',
            'rtx'	=> 'text/richtext',
            'sgm'	=> 'text/sgml',
            'sgml'	=> 'text/sgml',
            'sh'	=> 'application/x-sh',
            'shar'	=> 'application/x-shar',
            'shtml'	=> 'text/html',
            'silo'	=> 'model/mesh',
            'sit'	=> 'application/x-stuffit',
            'skd'	=> 'application/x-koan',
            'skm'	=> 'application/x-koan',
            'skp'	=> 'application/x-koan',
            'skt'	=> 'application/x-koan',
            'smi'	=> 'application/smil',
            'smil'	=> 'application/smil',
            'snd'	=> 'audio/basic',
            'so'	=> 'application/octet-stream',
            'spl'	=> 'application/x-futuresplash',
            'src'	=> 'application/x-wais-source',
            'stc'	=> 'application/vnd.sun.xml.calc.template',
            'std'	=> 'application/vnd.sun.xml.draw.template',
            'sti'	=> 'application/vnd.sun.xml.impress.template',
            'stw'	=> 'application/vnd.sun.xml.writer.template',
            'sv4cpio'	=> 'application/x-sv4cpio',
            'sv4crc'	=> 'application/x-sv4crc',
            'swf'	=> 'application/x-shockwave-flash',
            'sxc'	=> 'application/vnd.sun.xml.calc',
            'sxd'	=> 'application/vnd.sun.xml.draw',
            'sxg'	=> 'application/vnd.sun.xml.writer.global',
            'sxi'	=> 'application/vnd.sun.xml.impress',
            'sxm'	=> 'application/vnd.sun.xml.math',
            'sxw'	=> 'application/vnd.sun.xml.writer',
            't'	=> 'application/x-troff',
            'tar'	=> 'application/x-tar',
            'tcl'	=> 'application/x-tcl',
            'tex'	=> 'application/x-tex',
            'texi'	=> 'application/x-texinfo',
            'texinfo'	=> 'application/x-texinfo',
            'tgz'	=> 'application/x-gtar',
            'tif'	=> 'image/tiff',
            'tiff'	=> 'image/tiff',
            'tr'	=> 'application/x-troff',
            'tsv'	=> 'text/tab-separated-values',
            'txt'	=> 'text/plain',
            'ustar'	=> 'application/x-ustar',
            'vbs'	=> 'text/plain', //added by skwashd - for obvious reasons
            'vcd'	=> 'application/x-cdlink',
            'vcf'	=> 'text/x-vcard',
            'vcs'	=> 'text/calendar',
            'vfb'	=> 'text/calendar',
            'vrml'	=> 'model/vrml',
            'vsd'	=> 'application/vnd.visio',
            'wav'	=> 'audio/x-wav',
            'wax'	=> 'audio/x-ms-wax',
            'wbmp'	=> 'image/vnd.wap.wbmp',
            'wbxml'	=> 'application/vnd.wap.wbxml',
            'wm'	=> 'video/x-ms-wm',
            'wma'	=> 'audio/x-ms-wma',
            'wmd'	=> 'application/x-ms-wmd',
            'wml'	=> 'text/vnd.wap.wml',
            'wmlc'	=> 'application/vnd.wap.wmlc',
            'wmls'	=> 'text/vnd.wap.wmlscript',
            'wmlsc'	=> 'application/vnd.wap.wmlscriptc',
            'wmv'	=> 'video/x-ms-wmv',
            'wmx'	=> 'video/x-ms-wmx',
            'wmz'	=> 'application/x-ms-wmz',
            'wrl'	=> 'model/vrml',
            'wvx'	=> 'video/x-ms-wvx',
            'xbm'	=> 'image/x-xbitmap',
            'xht'	=> 'application/xhtml+xml',
            'xhtml'	=> 'application/xhtml+xml',
            'xls'	=> 'application/vnd.ms-excel',
            'xlt'	=> 'application/vnd.ms-excel',
            'xml'	=> 'application/xml',
            'xpm'	=> 'image/x-xpixmap',
            'xsl'	=> 'text/xml',
            'xwd'	=> 'image/x-xwindowdump',
            'xyz'	=> 'chemical/x-xyz',
            'z'	=> 'application/x-compress',
            'zip'	=> 'application/zip',
        );
        $type = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (isset($contentType[$type])) {
            $mime = $contentType[$type];
        } else {
            $mime = 'application/octet-stream';
        }
        return $mime;
    }
}