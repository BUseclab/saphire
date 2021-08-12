--TEST--
Trace test with fibonacci numbers (format=2)
--INI--
xdebug.enable=1
xdebug.auto_trace=0
xdebug.collect_params=3
xdebug.collect_return=0
xdebug.collect_assignments=0
xdebug.auto_profile=0
xdebug.profiler_enable=0
xdebug.show_mem_delta=0
xdebug.trace_format=0
--FILE--
<?php
	$tf = xdebug_start_trace(sys_get_temp_dir() . '/'. uniqid('xdt', TRUE), XDEBUG_TRACE_HTML);
    function fibonacci_cache ($n)
    {
        if (isset ($GLOBALS['fcache'][$n])) {
            return $GLOBALS['fcache'][$n];
        }

        if ($n == 0) {
            return 0;
        } else if ($n == 1) {
            return 1;
        } else if ($n == 2) {
            return 1;
        } else {
            $t = fibonacci_cache($n - 1) + fibonacci_cache($n - 2);
            $GLOBALS['fcache'][$n] = $t;
            return $t;
        }
    }

	fibonacci_cache(10);
	xdebug_stop_trace();
	echo file_get_contents($tf);
	unlink($tf);
?>
--EXPECTF--
<table class='xdebug-trace' dir='ltr' border='1' cellspacing='0'>
	<tr><th>#</th><th>Time</th><th>Mem</th><th colspan='2'>Function</th><th>Location</th></tr>
	<tr><td>4</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:22</td></tr>
	<tr><td>5</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>6</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>7</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>8</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>9</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>10</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>11</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>12</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>13</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>14</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>15</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>16</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>17</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>18</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>19</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>20</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;&nbsp; &nbsp;-&gt;</td><td>fibonacci_cache()</td><td>%strace2.php:16</td></tr>
	<tr><td>21</td><td>%f</td><td align='right'>%d</td><td align='left'>&nbsp; &nbsp;-&gt;</td><td>xdebug_stop_trace()</td><td>%strace2.php:23</td></tr>
</table>
