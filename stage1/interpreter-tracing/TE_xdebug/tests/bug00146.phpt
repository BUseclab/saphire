--TEST--
Test for bug #146: Array key names with quotes in traces are not escaped
--INI--
xdebug.enable=1
xdebug.auto_trace=0
xdebug.collect_params=3
xdebug.collect_return=1
xdebug.collect_assignments=0
xdebug.auto_profile=0
xdebug.profiler_enable=0
xdebug.show_mem_delta=0
xdebug.trace_format=0
--FILE--
<?php
	$tf = xdebug_start_trace(sys_get_temp_dir() . '/'. uniqid('xdt', TRUE));

	function foo($a)
	{
		return $a;
	}

	$array = array("te\"st's" => 42);
	$a = foo($array);

	xdebug_stop_trace();
	echo file_get_contents($tf);
	unlink($tf);
?>
--EXPECTF--
TRACE START [%d-%d-%d %d:%d:%d]
%w%f %w%d     -> foo(array ('te"st\'s' => 42)) %sbug00146.php:10
%w%f %w%d      >=> array ('te"st\'s' => 42)
%w%f %w%d     -> xdebug_stop_trace() %sbug00146.php:12
%w%f %w%d
TRACE END   [%d-%d-%d %d:%d:%d]
