--TEST--
Test with Code Coverage with abstract methods (>= PHP 7.1.0, <= PHP 7.1.3)
--SKIPIF--
<?php if (!version_compare(phpversion(), "7.1.0", '>=')) echo "skip >= PHP 7.1.0, <= PHP 7.1.3 needed\n"; ?>
<?php if (!version_compare(phpversion(), "7.1.3", '<=')) echo "skip >= PHP 7.1.0, <= PHP 7.1.3 needed\n"; ?>
--INI--
xdebug.default_enable=1
xdebug.auto_trace=0
xdebug.trace_options=0
xdebug.trace_output_dir=/tmp
xdebug.collect_params=1
xdebug.collect_return=0
xdebug.collect_assignments=0
xdebug.auto_profile=0
xdebug.profiler_enable=0
xdebug.dump_globals=0
xdebug.show_mem_delta=0
xdebug.trace_format=0
xdebug.extended_info=1
xdebug.overload_var_dump=0
--FILE--
<?php
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

	include 'coverage4.inc';

    xdebug_stop_code_coverage(false);
    $c = xdebug_get_code_coverage();
	ksort($c);
	var_dump($c);
?>
--EXPECTF--
array(2) {
  ["%scoverage4-php70.php"]=>
  array(2) {
    [4]=>
    int(1)
    [6]=>
    int(1)
  }
  ["%scoverage4.inc"]=>
  array(2) {
    [2]=>
    int(1)
    [26]=>
    int(1)
  }
}
