--TEST--
Test with Code Coverage with path and branch checking (>= PHP 7.1)
--SKIPIF--
<?php if (!version_compare(phpversion(), "7.1", '>=')) echo "skip >= PHP 7.1 needed\n"; ?>
<?php if (!extension_loaded('zend opcache')) echo "skip opcache required\n"; ?>
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
include 'dump-branch-coverage.inc';

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);

include 'coverage7.inc';

xdebug_stop_code_coverage(false);
$c = xdebug_get_code_coverage();
dump_branch_coverage($c);
?>
--EXPECTF--
A NOT B
2
1
foo->loop_test
- branches
  - 00; OP: 00-01; line: 12-15 HIT; out1: 02 HIT
  - 02; OP: 02-07; line: 15-16 HIT; out1: 08 HIT; out2: 02 HIT
  - 08; OP: 08-09; line: 17-17 HIT; out1: EX  X 
- paths
  - 0 2 8:  X 
  - 0 2 2 8: HIT

foo->ok
- branches
  - 00; OP: 00-04; line: 03-05 HIT; out1: 05 HIT; out2: 09  X 
  - 05; OP: 05-05; line: 05-05 HIT; out1: 06 HIT; out2: 09  X 
  - 06; OP: 06-08; line: 06-06 HIT; out1: 14 HIT
  - 09; OP: 09-10; line: 07-07  X ; out1: 11  X ; out2: 14  X 
  - 11; OP: 11-11; line: 07-07  X ; out1: 12  X ; out2: 14  X 
  - 12; OP: 12-13; line: 08-10  X ; out1: 14  X 
  - 14; OP: 14-15; line: 10-10 HIT; out1: EX  X 
- paths
  - 0 5 6 14: HIT
  - 0 5 9 11 12 14:  X 
  - 0 5 9 11 14:  X 
  - 0 5 9 14:  X 
  - 0 9 11 12 14:  X 
  - 0 9 11 14:  X 
  - 0 9 14:  X 

foo->test_closure
- branches
  - 00; OP: 00-12; line: 19-26 HIT; out1: EX  X 
- paths
  - 0: HIT

{closure:%scoverage7.inc:21-23}
- branches
  - 00; OP: 00-05; line: 21-22 HIT; out1: EX  X 
- paths
  - 0: HIT

{main}
- branches
  - 00; OP: 00-25; line: 02-35 HIT; out1: EX  X 
- paths
  - 0: HIT
