<?php


echo 'xdebug extension: '.(extension_loaded('xdebug') ? 'Loaded' : 'NOT loaded').PHP_EOL;
echo 'xdebug default enabled: '.ini_get('xdebug.default_enable').PHP_EOL;
echo PHP_EOL;
echo "var_dump: ".PHP_EOL;
var_dump("test");
