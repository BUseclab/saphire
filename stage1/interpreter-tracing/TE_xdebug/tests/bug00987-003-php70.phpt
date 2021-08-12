--TEST--
Test for bug #987: Hidden property names not shown with var_dump (CLI colours) (< PHP 7.2)
--SKIPIF--
<?php
if (!version_compare(phpversion(), "7.2", '<')) echo "skip < PHP 7.2 needed\n";
?>
--INI--
html_errors=0
xdebug.cli_color=2
xdebug.default_enable=1
xdebug.overload_var_dump=1
--FILE--
<?php
$object = (object) array('key' => 'value', 1 => 0, -4 => "foo", 3.14 => false);

var_dump($object);
?>
--EXPECTF--
[1mclass[22m [31mstdClass[0m#1 ([32m4[0m) {
  [32m[1mpublic[22m[0m $key [0m=>[0m
  [1mstring[22m([32m5[0m) "[31mvalue[0m"
  [32m[1mpublic[22m[0m ${1} [0m=>[0m
  [1mint[22m([32m0[0m)
  [32m[1mpublic[22m[0m ${-4} [0m=>[0m
  [1mstring[22m([32m3[0m) "[31mfoo[0m"
  [32m[1mpublic[22m[0m ${3} [0m=>[0m
  [1mbool[22m([35mfalse[0m)
}
