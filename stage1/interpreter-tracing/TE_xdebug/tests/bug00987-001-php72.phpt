--TEST--
Test for bug #987: Hidden property names not shown with var_dump (CLI) (>= PHP 7.2)
--SKIPIF--
<?php
if (!version_compare(phpversion(), "7.2", '>=')) echo "skip >= PHP 7.2 needed\n";
?>
--INI--
html_errors=0
xdebug.cli_color=0
xdebug.default_enable=1
xdebug.overload_var_dump=2
xdebug.filename_format=
--FILE--
<?php
$object = (object) array('key' => 'value', 1 => 0, -4 => "foo", 3.14 => false);

var_dump($object);
?>
--EXPECTF--
%sbug00987-001-php72.php:4:
class stdClass#1 (4) {
  public $key =>
  string(5) "value"
  public $1 =>
  int(0)
  public $-4 =>
  string(3) "foo"
  public $3 =>
  bool(false)
}
