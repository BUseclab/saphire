--TEST--
Test for bug #847: %s doesn't work in xdebug.trace_output_name (xdebug_start_trace)
--INI--
xdebug.trace_output_name=trace.%s
xdebug.trace_options=1
xdebug.auto_trace=0
--FILE--
<?php
xdebug_start_trace();
$trace_file = xdebug_get_tracefile_name();
echo $trace_file, "\n";
xdebug_stop_trace();
?>
--EXPECTF--
%strace.%s_tests_bug00847-002_php.xt
