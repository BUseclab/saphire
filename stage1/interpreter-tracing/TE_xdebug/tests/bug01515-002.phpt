--TEST--
Test for bug #1515: Object property names with a NULL char are cut off at NULL char (DBGp)
--SKIPIF--
<?php if (getenv("SKIP_DBGP_TESTS")) { exit("skip Excluding DBGp tests"); } ?>
--FILE--
<?php
require 'dbgp/dbgpclient.php';
$data = file_get_contents(dirname(__FILE__) . '/bug01515.inc');

$commands = array(
	'step_into',
	'breakpoint_set -t line -n 6',
	'run',
	'context_get',
	'property_get -d 0 -c 0 -n $obj',
	'feature_set -n extended_properties -v 1',
	'context_get',
	'property_get -d 0 -c 0 -n $obj',
);

dbgpRun( $data, $commands );
?>
--EXPECTF--
<?xml version="1.0" encoding="iso-8859-1"?>
<init xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" fileuri="file:///tmp/xdebug-dbgp-test.php" language="PHP" xdebug:language_version="" protocol_version="1.0" appid="" idekey=""><engine version=""><![CDATA[Xdebug]]></engine><author><![CDATA[Derick Rethans]]></author><url><![CDATA[http://xdebug.org]]></url><copyright><![CDATA[Copyright (c) 2002-2099 by Derick Rethans]]></copyright></init>

-> step_into -i 1
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="step_into" transaction_id="1" status="break" reason="ok"><xdebug:message filename="file:///tmp/xdebug-dbgp-test.php" lineno="2"></xdebug:message></response>

-> breakpoint_set -i 2 -t line -n 6
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="breakpoint_set" transaction_id="2" id=""></response>

-> run -i 3
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="run" transaction_id="3" status="break" reason="ok"><xdebug:message filename="file:///tmp/xdebug-dbgp-test.php" lineno="6"></xdebug:message></response>

-> context_get -i 4
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="context_get" transaction_id="4" context="0"><property name="$name" fullname="$name" type="string" size="16" encoding="base64"><![CDATA[d2l0aF8AX251bGxfY2hhcg==]]></property><property name="$obj" fullname="$obj" type="object" classname="stdClass" children="1" numchildren="1" page="0" pagesize="32"><property name="with_&#0;_null_char" fullname="$obj-&gt;with_&#0;_null_char" facet="public" type="int"><![CDATA[42]]></property></property></response>

-> property_get -i 5 -d 0 -c 0 -n $obj
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="property_get" transaction_id="5"><property name="$obj" fullname="$obj" type="object" classname="stdClass" children="1" numchildren="1" page="0" pagesize="32"><property name="with_&#0;_null_char" fullname="$obj-&gt;with_&#0;_null_char" facet="public" type="int"><![CDATA[42]]></property></property></response>

-> feature_set -i 6 -n extended_properties -v 1
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="feature_set" transaction_id="6" feature="extended_properties" success="1"></response>

-> context_get -i 7
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="context_get" transaction_id="7" context="0"><property name="$name" fullname="$name" type="string" size="16" encoding="base64"><![CDATA[d2l0aF8AX251bGxfY2hhcg==]]></property><property name="$obj" fullname="$obj" type="object" classname="stdClass" children="1" numchildren="1" page="0" pagesize="32"><property facet="public" type="int"><name encoding="base64"><![CDATA[d2l0aF8AX251bGxfY2hhcg==]]></name><fullname encoding="base64"><![CDATA[JG9iai0+d2l0aF8AX251bGxfY2hhcg==]]></fullname><value encoding="base64"><![CDATA[NDI=]]></value></property></property></response>

-> property_get -i 8 -d 0 -c 0 -n $obj
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="property_get" transaction_id="8"><property name="$obj" fullname="$obj" type="object" classname="stdClass" children="1" numchildren="1" page="0" pagesize="32"><property facet="public" type="int"><name encoding="base64"><![CDATA[d2l0aF8AX251bGxfY2hhcg==]]></name><fullname encoding="base64"><![CDATA[JG9iai0+d2l0aF8AX251bGxfY2hhcg==]]></fullname><value encoding="base64"><![CDATA[NDI=]]></value></property></property></response>
