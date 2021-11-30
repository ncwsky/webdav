#!/usr/bin/env php
<?php
$_SERVER['SCRIPT_FILENAME'] = __FILE__; //重置运行
define('APP_PATH', __DIR__ .'/app');

require __DIR__ .'/conf.php';
$cfg = array_merge($cfg, require(__DIR__ . '/wokerman.conf.php'));
require __DIR__ . '/../myphp/srv/Load.php';

$srv = new WorkerManSrv($cfg);
$srv->run($argv);