<?php

namespace nsfw;

use nsfw\cache\NullCache;
use nsfw\cache\MemCache;

define('NSFW_BASE_DIR', __DIR__);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Autoloader.php';
require_once __DIR__ . '/cache/NullCache.php';

libxml_use_internal_errors(true);

$loader = Autoloader::getInstance();
$loader->register();
$loader->setCache(new NullCache("autoloader"));
