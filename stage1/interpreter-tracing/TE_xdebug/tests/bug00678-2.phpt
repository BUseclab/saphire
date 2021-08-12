--TEST--
Test for bug #678: Xdebug segfault when IDE send "eval NonExistsClass"
--SKIPIF--
<?php if (getenv("SKIP_DBGP_TESTS")) { exit("skip Excluding DBGp tests"); } ?>
--FILE--
<?php
require 'dbgp/dbgpclient.php';
$data = file_get_contents(dirname(__FILE__) . '/bug00678-2.inc');

$commands = array(
	'run',
	'stack_get',
	'eval -- bmV3IE5vbkV4aXN0c0NsYXNz',
	'stack_get',
	'run',
);

dbgpRun( $data, $commands );
?>
--EXPECTF--
<?xml version="1.0" encoding="iso-8859-1"?>
<init xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" fileuri="file:///%sxdebug-dbgp-test.php" language="PHP" xdebug:language_version="" protocol_version="1.0" appid="" idekey=""><engine version=""><![CDATA[Xdebug]]></engine><author><![CDATA[Derick Rethans]]></author><url><![CDATA[http://xdebug.org]]></url><copyright><![CDATA[Copyright (c) 2002-%d by Derick Rethans]]></copyright></init>

-> run -i 1
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="run" transaction_id="1" status="break" reason="ok"><xdebug:message filename="file:///%sxdebug-dbgp-test.php" lineno="5"></xdebug:message></response>

-> stack_get -i 2
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="stack_get" transaction_id="2"><stack where="test" level="0" type="file" filename="file:///%sxdebug-dbgp-test.php" lineno="5"></stack><stack where="{main}" level="1" type="file" filename="file:///%sxdebug-dbgp-test.php" lineno="7"></stack></response>

-> eval -i 3 -- bmV3IE5vbkV4aXN0c0NsYXNz
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="eval" transaction_id="3" status="break" reason="ok"><error code="206"><message><![CDATA[error evaluating code]]></message></error></response>

-> stack_get -i 4
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="stack_get" transaction_id="4"><stack where="test" level="0" type="file" filename="file:///%sxdebug-dbgp-test.php" lineno="5"></stack><stack where="{main}" level="1" type="file" filename="file:///%sxdebug-dbgp-test.php" lineno="7"></stack></response>

-> run -i 5
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="run" transaction_id="5" status="stopping" reason="ok"></response>
