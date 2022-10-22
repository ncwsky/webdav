<?php
namespace WebDav;
/**
 * Class WebDavReadFile 文件映射
 */
class WebDavReadFile
{
    public $file = '';
    public $offset = 0;
    public $size = 0;

    public function __construct($file, $offset = 0, $size = 0)
    {
        if ($size < 0) $size = 0;
        $this->file = $file;
        $this->offset = $offset;
        $this->size = $size;
    }
}