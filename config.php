<?php
/** @noinspection PhpDefineCanBeReplacedWithConstInspection */

define('HIBP_ENDPOINT','https://api.pwnedpasswords.com/range/');

define('TOOL_USERAGENT','HibpPhpDownloader/0.1 (https://derakkilgo.com/hibpphp)');

define('APP_ROOT',__DIR__);

define('HIBP_SHA1_BIN', APP_ROOT . '/storage/hibp_all.sha1.bin');
define('HIBP_SHA1_TXT', APP_ROOT . '/storage/hibp_all.sha1.txt');
define('HIBP_SHA1_DATA', APP_ROOT . '/storage/data/');

define('LOGFILE', APP_ROOT . '/storage/app.log');
define('DISABLE_LOG',true);

require_once(__DIR__. '/src/lib.php');