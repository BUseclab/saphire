--TEST--
Test for bug #1232: HTML trace format with show_mem_delta
--INI--
xdebug.default_enable=1
xdebug.auto_trace=0
xdebug.collect_params=3
xdebug.collect_return=0
xdebug.collect_assignments=0
xdebug.auto_profile=0
xdebug.profiler_enable=0
xdebug.show_mem_delta=1
xdebug.trace_format=2
--FILE--
<?php
$tf = xdebug_start_trace(sys_get_temp_dir() . '/'. uniqid('xdt', TRUE));

function bar()
{
	return "bar";
}

function foo()
{
	return bar();
}

foo();

eval("\$foo = foo();\nbar();\nfoo();\n");
echo $foo, "\n";
xdebug_stop_trace();
echo file_get_contents($tf);
unlink($tf);
?>
--EXPECTF--
bar
<table class='xdebug-trace' dir='ltr' border='1' cellspacing='0'>
	<tr><th>#</th><th>Time</th><th>Mem</th><th>&#948; Mem</th><th colspan='2'>Function</th><th>Location</th></tr>
	<tr><td>4</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;-&gt;</td><td>foo()</td><td>%sbug01232.php:14</td></tr>
	<tr><td>5</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>bar()</td><td>%sbug01232.php:11</td></tr>
	<tr><td>6</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;-&gt;</td><td>eval('$foo = foo();<br />bar();<br />foo();<br />')</td><td>%sbug01232.php:16</td></tr>
	<tr><td>7</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>foo()</td><td>%sbug01232.php(16) : eval()'d code:1</td></tr>
	<tr><td>8</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>bar()</td><td>%sbug01232.php:11</td></tr>
	<tr><td>9</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>bar()</td><td>%sbug01232.php(16) : eval()'d code:2</td></tr>
	<tr><td>10</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>foo()</td><td>%sbug01232.php(16) : eval()'d code:3</td></tr>
	<tr><td>11</td><td>%f</td><td align='right'>%d</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>bar()</td><td>%sbug01232.php:11</td></tr>
	<tr><td>12</td><td>%f</td><td align='right'>%d</td><td align='right'>-%d</td><td align='left'>&nbsp; &nbsp;-&gt;</td><td>xdebug_stop_trace()</td><td>%sbug01232.php:18</td></tr>
</table>
