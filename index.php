<?php

if (!empty($_GET['debug'])) {
    if (extension_loaded('xhprof')) {
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    }
}
date_default_timezone_set('Asia/Shanghai');
ini_set('yaf.use_spl_autoload', 1);
define('APP_PATH', dirname(__FILE__));
$app = new Yaf_Application(APP_PATH . '/config/application.ini');
$app->bootstrap()->run();



