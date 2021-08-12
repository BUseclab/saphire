--TEST--
Test for function traces with variadics (0)
--INI--
xdebug.default_enable=1
xdebug.profiler_enable=0
xdebug.auto_trace=0
xdebug.trace_format=0
xdebug.dump_globals=0
xdebug.show_mem_delta=0
xdebug.collect_vars=0
xdebug.collect_params=0
xdebug.collect_return=0
xdebug.collect_assignments=0
xdebug.force_error_reporting=0
--FILE--
<?php
$tf = xdebug_start_trace(sys_get_temp_dir() . '/'. uniqid('xdt', TRUE));

function foo( $a, ...$b )
{
	// do nothing really
}

foo( 42 );
foo( 1, false );
foo( "foo", "bar", 3.1415 );

xdebug_stop_trace();
echo file_get_contents($tf);
unlink($tf);
?>
--EXPECTF--
TRACE START [%d-%d-%d %d:%d:%d]
%w%f %w%d     -> foo() %sfunctrace_variadics_0.php:9
%w%f %w%d     -> foo() %sfunctrace_variadics_0.php:10
%w%f %w%d     -> foo() %sfunctrace_variadics_0.php:11
%w%f %w%d     -> xdebug_stop_trace() %sfunctrace_variadics_0.php:13
%w%f %w%d
TRACE END   [%d-%d-%d %d:%d:%d]
