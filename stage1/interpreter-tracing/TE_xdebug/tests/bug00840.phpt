--TEST--
Test for bug #840: Xdebug crashes when a class is returned by a __call method with a static var and more than 2 props
--SKIPIF--
<?php if (getenv("SKIP_DBGP_TESTS")) { exit("skip Excluding DBGp tests"); } ?>
--FILE--
<?php
require 'dbgp/dbgpclient.php';
$data = file_get_contents(dirname(__FILE__) . '/bug00840.inc');

$commands = array(
	'step_into',
	'breakpoint_set -t line -n 31',
	'run',
	'context_get -d 0 -c 0',
);

dbgpRun( $data, $commands );
?>
--EXPECTF--
<?xml version="1.0" encoding="iso-8859-1"?>
<init xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" fileuri="file:///%sxdebug-dbgp-test.php" language="PHP" xdebug:language_version="" protocol_version="1.0" appid="" idekey=""><engine version=""><![CDATA[Xdebug]]></engine><author><![CDATA[Derick Rethans]]></author><url><![CDATA[http://xdebug.org]]></url><copyright><![CDATA[Copyright (c) 2002-%d by Derick Rethans]]></copyright></init>

-> step_into -i 1
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="step_into" transaction_id="1" status="break" reason="ok"><xdebug:message filename="file:///%sxdebug-dbgp-test.php" lineno="%r(4|3)%r"></xdebug:message></response>

-> breakpoint_set -i 2 -t line -n 31
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="breakpoint_set" transaction_id="2" id=""></response>

-> run -i 3
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="run" transaction_id="3" status="break" reason="ok"><xdebug:message filename="file:///%sxdebug-dbgp-test.php" lineno="31"></xdebug:message></response>

-> context_get -i 4 -d 0 -c 0
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="context_get" transaction_id="4" context="0"><property name="$b" fullname="$b" type="object" classname="B" children="0" numchildren="0" page="0" pagesize="32"></property><property name="$x" fullname="$x" type="object" classname="A" children="1" numchildren="4" page="0" pagesize="32"><property name="_staticvar" fullname="$x::_staticvar" facet="static public" type="null"></property><property name="var_1" fullname="$x-&gt;var_1" facet="protected" type="null"></property><property name="var_2" fullname="$x-&gt;var_2" facet="protected" type="null"></property><property name="var_3" fullname="$x-&gt;var_3" facet="protected" type="null"></property></property></response>
