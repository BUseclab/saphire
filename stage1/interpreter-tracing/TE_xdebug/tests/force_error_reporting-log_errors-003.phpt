--TEST--
Test for xdebug.force_error_reporting (log_errors) [3]
--INI--
xdebug.default_enable=1
display_errors=0
log_errors=1
xdebug.force_display_errors=0
xdebug.force_error_reporting=E_ALL
xdebug.collect_params=0
--FILE--
<?php
ini_set('error_reporting', 0);

trigger_error('six', E_USER_WARNING);
trigger_error('seven');
strlen();
?>
--EXPECTF--
PHP Warning:  six in %sforce_error_reporting-log_errors-003.php on line 4
PHP Stack trace:
PHP   1. {main}() %sforce_error_reporting-log_errors-003.php:0
PHP   2. trigger_error() %sforce_error_reporting-log_errors-003.php:4
PHP Notice:  seven in %sforce_error_reporting-log_errors-003.php on line 5
PHP Stack trace:
PHP   1. {main}() %sforce_error_reporting-log_errors-003.php:0
PHP   2. trigger_error() %sforce_error_reporting-log_errors-003.php:5
PHP Warning:  %s in %sforce_error_reporting-log_errors-003.php on line 6
PHP Stack trace:
PHP   1. {main}() %sforce_error_reporting-log_errors-003.php:0
PHP   2. strlen() %sforce_error_reporting-log_errors-003.php:6
