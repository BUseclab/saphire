--TEST--
Test for xdebug.force_error_reporting (display_errors) [1]
--INI--
xdebug.default_enable=1
display_errors=1
log_errors=0
xdebug.force_error_reporting=E_USER_WARNING
xdebug.collect_params=0
--FILE--
<?php
ini_set('error_reporting', 0);

trigger_error('two', E_USER_WARNING);
trigger_error('three');
strlen();
?>
--EXPECTF--
Warning: two in %sforce_error_reporting-display_errors-001.php on line 4

Call Stack:
%w%f %w%d   1. {main}() %sforce_error_reporting-display_errors-001.php:0
%w%f %w%d   2. trigger_error() %sforce_error_reporting-display_errors-001.php:4
