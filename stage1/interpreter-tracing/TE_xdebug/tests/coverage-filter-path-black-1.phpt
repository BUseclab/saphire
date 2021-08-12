--TEST--
Filtered code coverage: path blacklist [1]
--INI--
xdebug.auto_trace=0
xdebug.collect_return=1
xdebug.collect_params=4
xdebug.collect_assignments=0
xdebug.trace_format=0
--FILE--
<?php
$cwd = __DIR__; $s = DIRECTORY_SEPARATOR;
xdebug_set_filter(XDEBUG_FILTER_CODE_COVERAGE, XDEBUG_PATH_BLACKLIST, [ "{$cwd}{$s}filter{$s}xdebug" ] );

$tf = xdebug_start_code_coverage( XDEBUG_CC_DEAD_CODE | XDEBUG_CC_UNUSED );

include "$cwd/filter/foobar/foobar.php";
include "$cwd/filter/xdebug/xdebug.php";

Foobar::foo("hi");
XDEBUG::foo("hi");
Xdebug::foo("hi");
	
$result = xdebug_get_code_coverage();
ksort( $result );

var_dump( $result );
?>
--EXPECTF--
ello!
ello!
ello!
array(2) {
  ["%scoverage-filter-path-black-1.php"]=>
  array(7) {
    [5]=>
    int(1)
    [7]=>
    int(1)
    [8]=>
    int(1)
    [10]=>
    int(1)
    [11]=>
    int(1)
    [12]=>
    int(1)
    [14]=>
    int(1)
  }
  ["%sfilter%efoobar%efoobar.php"]=>
  array(6) {
    [2]=>
    int(1)
    [6]=>
    int(1)
    [7]=>
    int(1)
    [11]=>
    int(-1)
    [12]=>
    int(-1)
    [14]=>
    int(1)
  }
}
