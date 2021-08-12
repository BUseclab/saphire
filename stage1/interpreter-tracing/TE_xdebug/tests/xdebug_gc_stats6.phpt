--TEST--
GC Stats: Start with xdebug_start_gcstats() and filename
--INI--
zend.enable_gc=1
xdebug.gc_stats_enable=0
--FILE--
<?php
var_dump(xdebug_get_gcstats_filename());

$filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gcstats.txt';
$actual = xdebug_start_gcstats($filename);

var_dump($actual === $filename);
var_dump(xdebug_get_gcstats_filename());
xdebug_stop_gcstats();
unlink(xdebug_get_gcstats_filename());
?>
--EXPECTF--
bool(false)
bool(true)
string(%d) "%sgcstats.txt"
