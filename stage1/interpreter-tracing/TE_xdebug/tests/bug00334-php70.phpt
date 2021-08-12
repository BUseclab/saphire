--TEST--
Test for bug #334: Code Coverage Regressions (>= PHP 7.0, <= PHP 7.0.12)
--SKIPIF--
<?php
if (!version_compare(phpversion(), "7.0", '>=')) echo "skip >= PHP 7.0, <= PHP 7.0.12 needed\n";
if (version_compare(phpversion(), "7.0.12", '>')) echo "skip >= PHP 7.0, <= PHP 7.0.12 needed\n";
?>
--INI--
xdebug.default_enable=1
xdebug.dump_globals=0
xdebug.show_mem_delta=0
xdebug.profiler_enable=0
xdebug.trace_format=0
xdebug.overload_var_dump=0
--FILE--
<?php
// Run me from the PHP CLI
xdebug_start_code_coverage(XDEBUG_CC_DEAD_CODE | XDEBUG_CC_UNUSED);
// MUST be both code coverage options to cause problems
include(dirname(__FILE__).'/bug00334.inc'); // File with problem in it.
$c = xdebug_get_code_coverage();
ksort($c);
var_dump($c);
xdebug_stop_code_coverage();
?>
--EXPECTF--
array(2) {
  ["%sbug00334-php70.php"]=>
  array(2) {
    [5]=>
    int(1)
    [6]=>
    int(1)
  }
  ["%sbug00334.inc"]=>
  array(2) {
    [5]=>
    int(1)
    [7]=>
    int(1)
  }
}
