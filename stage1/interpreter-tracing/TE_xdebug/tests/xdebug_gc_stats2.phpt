--TEST--
GC Stats: Run gc_collect_cyles(); and collect stats
--INI--
zend.enable_gc=1
xdebug.gc_stats_enable=1
--FILE--
<?php
var_dump(ini_get("xdebug.gc_stats_enable"));

for ($i = 0; $i < 100; $i++) {
	$a = new stdClass();
	$b = new stdClass();
	$b->a = $a;
	$a->b = $b;
	unset($a, $b);
}
gc_collect_cycles();

echo file_get_contents(xdebug_get_gcstats_filename());
xdebug_stop_gcstats();
unlink(xdebug_get_gcstats_filename());
?>
--EXPECTF--
string(1) "1"
Garbage Collection Report
version: 1
creator: xdebug %d.%s (PHP %s)

Collected | Efficiency% | Duration | Memory Before | Memory After | Reduction% | Function
----------+-------------+----------+---------------+--------------+------------+---------
      200 |      2.00 % |  %s ms |        %d |       %d |   %s % | gc_collect_cycles
