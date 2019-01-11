<?php
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    die('require PHP > 5.3.0 !');
}

define('MYSQL_USER', 'root');
define('MYSQL_PASSWORD', '7c0801410e94');

define('APP_DEBUG', true);
define('RUNTIME_PATH', './Runtime/');
define('APP_PATH', './Application/');
define('ROOT_PATH',__DIR__);

require './ThinkPHP/ThinkPHP.php';
