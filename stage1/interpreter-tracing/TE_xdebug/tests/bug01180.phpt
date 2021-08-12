--TEST--
Test for bug #1180: Code coverage crashes with non-standard start/stops
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
xdebug.coverage_enable=1
xdebug.overload_var_dump=0
--FILE--
<?php
class TestHelper
{
    protected static $coverageStopped = false;

    public static function stopCodeCoverage()
    {
        if (self::$coverageStopped === false) {
            self::$coverageStopped = xdebug_stop_code_coverage(false);
        }
    }

    public static function resumeCodeCoverage()
    {
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

        self::$coverageStopped = false;
    }

    public static function stopAndResumeCoverage()
    {
        self::stopCodeCoverage();
        self::resumeCodeCoverage();
    }
}

function testWorksWithoutIntermediaryMethod()
{
	\TestHelper::stopCodeCoverage();
	\TestHelper::stopCodeCoverage();
	\TestHelper::resumeCodeCoverage();
	\TestHelper::resumeCodeCoverage();
}

function testIntermediaryMethodExplodesIfWeDoChangeStaticProperty()
{
	\TestHelper::stopCodeCoverage();
	\TestHelper::stopAndResumeCoverage();
}

testWorksWithoutIntermediaryMethod();
testIntermediaryMethodExplodesIfWeDoChangeStaticProperty();

echo "no crash\n";
?>
--EXPECT--
no crash
