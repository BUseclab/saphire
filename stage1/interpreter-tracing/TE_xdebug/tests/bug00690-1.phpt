--TEST--
Test for bug #690: Function traces are not appended to file when xdebug_start_trace() is used with xdebug.trace_options=1 (step 1 of 2)
--INI--
xdebug.trace_output_name=trace.bug690
xdebug.trace_options=1
xdebug.auto_trace=0
--FILE--
<?php
xdebug_start_trace();
$trace_file = xdebug_get_tracefile_name();
echo $trace_file, "\n";
xdebug_stop_trace();
unlink($trace_file);
file_put_contents($trace_file, "DONE\n");
?>
--EXPECTF--
%strace.bug690.xt
