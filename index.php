<?php
require __DIR__ . '/MyLoader.php';

use WebDav\WebDav;
use WebDav\WebDavFile;

$file = new WebDavFile(__DIR__); //设置目录
$dav = new WebDav($file);
$dav->prefix = '/index/webdav';
$dav->isLog = true;

WebDav::$authUsers = ['root'=>'123456'];

$dav->isSend = true;
$dav->reqHandle();