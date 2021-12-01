<?php
namespace WebDav;
/**
 * 文件存储定义
 * Interface WebDavFileInterface
 */
interface WebDavFileInterface
{
    /**
     * 验证路径有效性
     * @param $path
     * @return bool
     */
    public function isValid($path);

    /**
     * 给出文件的信息
     * @param resource|string $path
     * @return mixed
     */
    public function stat($path);

    /**
     * 文件类型
     * @param $path
     * @return string|false
     */
    public function type($path);

    /**
     * 是否存在
     * @param $path
     * @return bool
     */
    public function exists($path);

    /**
     * 复制移动
     * @param $old
     * @param $new
     * @param bool $copy
     * @return mixed
     */
    public function copyMove($old, $new, $copy=false);

    /**
     * 创建一层目录
     * @param $path
     * @return int  true=201
     */
    public function mkcol($path);

    /**
     * @param $path
     * @return bool
     */
    public function isDir($path);

    /**
     * @param $path
     * @param $mode
     * @return resource|bool
     */
    public function open($path, $mode);

    /**
     * @param resource $fp
     * @return bool
     */
    public function close($fp);

    /**
     * @param $id
     * @return bool
     */
    public function lock($id);

    /**
     * @param $id
     * @return void
     */
    public function unlock($id);

    /**
     * @param $path
     * @param bool $infinity
     * @return array
     */
    public function depth($path, $infinity = false);

    /**
     * 写入文件
     * @param resource|string $in
     * @param $path
     * @param int $maxSize 读取数据的最大值 0不限制
     * @return int
     */
    public function write($in, $path, $maxSize = 0);

    /**
     * 输出文件到$out
     * @param resource $out
     * @param resource $fp
     * @param int $offset 读取偏移 0不限制
     * @param int $size 读取大小 -1不限制
     * @return void
     */
    public function put($out, $fp, $offset = 0, $size = -1);

    /**
     * @param $path
     * @return void
     */
    public function delete($path);
}