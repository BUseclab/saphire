--TEST--
Test for tracing array assignments in user-readable function traces (>= PHP 7.3, opcache)
--SKIPIF--
<?php
if ( ! ( version_compare(phpversion(), "7.3", '>=') && extension_loaded('zend opcache'))) { echo "skip >= PHP 7.3 && opcache loaded needed\n"; };
?>
--INI--
xdebug.default_enable=1
xdebug.profiler_enable=0
xdebug.auto_trace=0
xdebug.trace_format=0
xdebug.collect_vars=1
xdebug.collect_params=3
xdebug.collect_return=0
xdebug.collect_assignments=1
xdebug.dump.GET=
xdebug.dump.SERVER=
xdebug.show_local_vars=0
xdebug.force_error_reporting=0
--FILE--
<?php
$tf = xdebug_start_trace(sys_get_temp_dir() . '/'. uniqid('xdt', TRUE));

function test()
{
	$t = array( 'a' => 4, 'b' => 9, 'c' => 13 );
	$t['d'] = 89;
	$t['a'] += $b;
	@$t['a'] += $b;
	$t['c'] /= 7;
	$t['b'] *= 9;
}
$t = array();
$t['a'] = 98;
$t['b'] = 4;
$t['b'] -= 8;
$t['b'] *= -0.5;
$t['b'] <<= 1;
$t['c'] = $t['b'] / 32;

test(1, 2, 3);

xdebug_stop_trace();
echo file_get_contents($tf);
unlink($tf);
?>
--EXPECTF--
Notice: Undefined variable: b in %sassignment-trace2-php73-opcache.php on line 8

Call Stack:
%w%f %w%d   1. {main}() %sassignment-trace2-php73-opcache.php:0
%w%f %w%d   2. test(1, 2, 3) %sassignment-trace2-php73-opcache.php:21

TRACE START [%d-%d-%d %d:%d:%d]
                           => $tf = '%s.xt' %sassignment-trace2-php73-opcache.php:2
                           => $t = array () %sassignment-trace2-php73-opcache.php:13
                           => $t['a'] = 98 %sassignment-trace2-php73-opcache.php:14
                           => $t['b'] = 4 %sassignment-trace2-php73-opcache.php:15
                           => $t['b'] -= 8 %sassignment-trace2-php73-opcache.php:16
                           => $t['b'] *= -0.5 %sassignment-trace2-php73-opcache.php:17
                           => $t['b'] <<= 1 %sassignment-trace2-php73-opcache.php:18
                           => $t['c'] = 0.125 %sassignment-trace2-php73-opcache.php:19
%w%f %w%d     -> test(1, 2, 3) %sassignment-trace2-php73-opcache.php:21
                             => $t = array ('a' => 4, 'b' => 9, 'c' => 13, 'd' => 89) %sassignment-trace2-php73-opcache.php:7
                             => $t['a'] += %r(NULL|\*uninitialized\*)%r %sassignment-trace2-php73-opcache.php:8
                             => $t['a'] += %r(NULL|\*uninitialized\*)%r %sassignment-trace2-php73-opcache.php:9
%w%f %w%d     -> xdebug_stop_trace() %sassignment-trace2-php73-opcache.php:23
%w%f %w%d
TRACE END   [%d-%d-%d %d:%d:%d]
