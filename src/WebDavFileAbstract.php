<?php

/**
 * 存储
 * Class WebDavFileAbstract
 */
abstract class WebDavFileAbstract implements WebDavFileInterface
{
    /**
     * @var WebDav $dav
     */
    public $dav;
    public $limit = 500; //显示列表限制
    public $chunkSize = 1048576; //1024*1024; //块数据读取大小 1M
}
