--TEST--
Test for tracing property assignments in user-readable function traces
--INI--
xdebug.default_enable=1
xdebug.profiler_enable=0
xdebug.auto_trace=0
xdebug.trace_format=0
xdebug.collect_vars=1
xdebug.collect_params=4
xdebug.collect_return=0
xdebug.collect_assignments=1
--FILE--
<?php
$tf = xdebug_start_trace(sys_get_temp_dir() . '/'. uniqid('xdt', TRUE));

$a = new StdClass;

xdebug_stop_trace();
echo file_get_contents($tf);
unlink($tf);
?>
--EXPECTF--
TRACE START [%d-%d-%d %d:%d:%d]
                           => $tf = '%s.xt' %sassignment-trace11.php:2
                           => $a = class stdClass {  } %sassignment-trace11.php:4
%w%f %w%d     -> xdebug_stop_trace() %sassignment-trace11.php:6
%w%f %w%d
TRACE END   [%d-%d-%d %d:%d:%d]
