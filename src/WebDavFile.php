<?php
namespace WebDav;
/**
 * 文件存储
 * Class WebDavFile
 */
class WebDavFile extends WebDavFileAbstract
{
    const TMP_DIR_NAME = '.tmp';

    private $dir, $tempDir;
    private $dirLen;
    //文件锁
    private $lockFile, $lockHandle;

    public function lock($path)
    {
        $this->lockFile = $this->tempDir . crc32($path) . '.lock';
        $this->lockHandle = @fopen($this->lockFile, 'w');
        if (!$this->lockHandle) return false;
        //LOCK_EX 获取独占锁
        //LOCK_NB 无法建立锁定时，不阻塞
        return @flock($this->lockHandle, LOCK_EX | LOCK_NB);
    }

    public function unlock($path)
    {
        if (!$this->lockHandle) {
            return;
        }
        @flock($this->lockHandle, LOCK_UN);
        @fclose($this->lockHandle);
        @unlink($this->lockFile); //这里会报错
    }

    public function __construct($davPath)
    {
        $this->dir = realpath(rtrim($davPath, '/'));
        $this->dirLen = strlen($this->dir);
        $this->tempDir = $this->dir . self::TMP_DIR_NAME;
        if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
        if (!is_dir($this->tempDir)) mkdir($this->tempDir, 0755);
    }

    public function isValid($path)
    {
        return strpos($path, '../') === false && strpos($path, '..\\') === false;

        $realpath = realpath($this->dir . $path);
        $real_dir = realpath($this->dir);
        return strncmp($realpath, $real_dir, strlen($real_dir)) === 0;
    }

    public function space($path)
    {
        return ['free' => disk_free_space($this->dir . $path), 'total' => disk_total_space($this->dir . $path)];
    }

    /**
     * @param resource|string $path
     * @return array|false|mixed
     */
    public function stat($path)
    {
        if (is_string($path)) {
            if (!$this->exists($path)) return false;
            return stat($this->dir . $path);
        }
        return fstat($path);
    }

    public function exists($path)
    {
        return file_exists($this->dir . $path);
    }

    public function type($path)
    {
        return mime_content_type($this->dir . $path);
    }

    public function copyMove($old, $new, $copy = false)
    {
        if ($this->isDir($old)) {
            if ($copy) {
                $this->toCopyDir($this->dir . $old, $this->dir . $new);
            } else {
                $this->toMoveDir($this->dir . $old, $this->dir . $new);
            }
        } else {
            try {
                $dir = dirname($this->dir . $new);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                //文件存在则覆盖
                if ($copy) {
                    copy($this->dir . $old, $this->dir . $new);
                } else {
                    rename($this->dir . $old, $this->dir . $new);
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), 502);
            }
        }
    }

    protected function toMoveDir($source, $dest)
    {
        if (file_exists($dest)) {
            if (!is_dir($dest)) {
                throw new \Exception('目标不是一个目录', 409);
            }

            if (($directory = opendir($source)) === false) {
                throw new \Exception('源目录读取失败', 502);
            }
            try {
                while (($file = readdir($directory)) !== false) {
                    if ($file === '.' || $file === '..') continue;
                    $srcPath = $source . '/' . $file;
                    $dstPath = $dest . '/' . $file;
                    if (file_exists($dstPath)) {
                        if (is_dir($dstPath)) { //存在的目录不支持rename
                            $this->toMoveDir($srcPath, $dstPath);
                        } else {
                            rename($srcPath, $dstPath);
                        }
                    } else {
                        rename($srcPath, $dstPath);
                    }
                }
                closedir($directory);
                @rmdir($source);
            } catch (\Exception $e) {
                closedir($directory);
                throw new \Exception($e->getMessage(), 502);
            }
        } else {
            $dir = dirname($dest);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true); //创建上级目录
            }
            rename($source, $dest);
        }
    }

    protected function toCopyDir($source, $dest)
    {
        if (file_exists($dest)) {
            if (!is_dir($dest)) {
                throw new \Exception('目标不是一个目录', 409);
            }
        } else {
            mkdir($dest, 0777, true); //创建上级目录
        }

        if (($directory = opendir($source)) === false) {
            throw new \Exception('源目录读取失败', 502);
        }
        try {
            while (($file = readdir($directory)) !== false) {
                if ($file === '.' || $file === '..') continue;
                $srcPath = $source . '/' . $file;
                $dstPath = $dest . '/' . $file;
                if (is_dir($srcPath)) {
                    $this->toCopyDir($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
            closedir($directory);
        } catch (\Exception $e) {
            closedir($directory);
            throw new \Exception($e->getMessage(), 502);
        }
    }

    //9.3.1.  MKCOL Status Codes
    public function mkcol($path)
    {
        $fullPath = $this->dir . $path;
        $parent = dirname($fullPath);
        $fullPath = $parent . '/' . basename($fullPath);

        if (!file_exists($parent)) {
            return 409;
        }
        if (!is_dir($parent)) {
            return 403;
        }
        if (file_exists($fullPath)) {
            return 405;
        }
        if (!empty($_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
            return 415;
        }
        if (!mkdir($fullPath, 0777)) {
            return 403;
        }
        return 201;
    }

    public function isDir($path)
    {
        return is_dir($this->dir . $path);
    }

    public function open($path, $mode)
    {
        if (is_dir($this->dir . $path)) return false;
        return fopen($this->dir . $path, $mode);
    }

    public function close($fp)
    {
        return fclose($fp);
    }

    public function depth($path, $infinity = false, $search = '')
    {
        $fullPath = $this->dir . rtrim($path, '/');
        $stat = stat($fullPath);
        $list = [];//new SplFixedArray($this->limit);
        $list[0] = ['path' => $path, 'is_dir' => true, 'size' => $stat['size'], 'mtime' => $stat['mtime'], 'ctime' => $stat['ctime'], 'type' => ''];
        $num = 1;
        $this->depthRecursive($list, $num, $fullPath, $infinity, $search);
        return $list;
    }

    protected function depthRecursive(&$list, &$num, $path, $infinity = false, $search = '')
    {
        if (($directory = opendir($path)) === false) {
            return;
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $path . '/' . $file;
            $stat = stat($fullPath);
            $isDir = is_dir($fullPath);

            if ($search === '' || stripos($file, $search) !== false) {
                $list[$num] = ['path' => substr($fullPath, $this->dirLen), 'is_dir' => $isDir, 'size' => $stat['size'], 'mtime' => $stat['mtime'], 'ctime' => $stat['ctime'], 'type' => $isDir ? '' : mime_content_type($fullPath)];
                $num++;
            }

            if ($num >= $this->limit) break;
            if ($isDir && $infinity) {
                $this->depthRecursive($list, $num, $fullPath, $infinity, $search);
            }
        }
        closedir($directory);
    }

    public function write($in, $path, $maxSize = -1)
    {
        $fp = fopen($this->dir . $path, 'wb+');
        if ($maxSize === 0) {
            fclose($fp);
            return 0;
        }
        if (is_string($in)) { //传递的写入内容
            $bytes = fwrite($fp, $in);
            fclose($fp);
            return $bytes;
        }
/*
        $bytes = 0;
        while (!feof($in)) { //按分块读取写入是为了支持断点续传
            $data = fread($in, $this->chunkSize);
            $size = fwrite($fp, $data, $this->chunkSize);
            $bytes += $size;
        }*/
        $bytes = stream_copy_to_stream($in, $fp, $maxSize); //, $offset
        fclose($fp);
        return $bytes;
    }

    public function put($out, $fp, $offset = 0, $size = -1)
    {
        stream_copy_to_stream($fp, $out, $size, $offset);
        fclose($fp);
    }

    public function delete($path)
    {
        return $this->delRecursive($this->dir . $path);
    }

    protected function delRecursive($path)
    {
        if (is_file($path)) {
            return @unlink($path);
        }
        if (($directory = opendir($path)) === false) {
            return false;
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $path . '/' . $file;
            if (is_dir($fullPath)) {
                $this->delRecursive($fullPath);
            } else {
                @unlink($fullPath);
            }
        }
        closedir($directory);
        return @rmdir($path);
    }
}
