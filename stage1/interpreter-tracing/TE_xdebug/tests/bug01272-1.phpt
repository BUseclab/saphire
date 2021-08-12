--TEST--
Test for bug #1272: property_get doesn't return attributes for SimpleXMLElement
--SKIPIF--
<?php if (getenv("SKIP_DBGP_TESTS")) { exit("skip Excluding DBGp tests"); } ?>
--FILE--
<?php
require 'dbgp/dbgpclient.php';
$data = file_get_contents(dirname(__FILE__) . '/bug01272.inc');

$commands = array(
	'feature_set -n max_depth -v 3',
	'step_into',
	'breakpoint_set -t line -n 7',
	'run',
	'property_get -n $e',
	'property_get -n $e->@attributes',
	'property_get -n $e->@attributes["att1"]',
	'property_get -n $e->b[0]->@attributes',
	'property_get -n $e->b[1]->@attributes["attb"]',
);

dbgpRun( $data, $commands );
?>
--EXPECTF--
<?xml version="1.0" encoding="iso-8859-1"?>
<init xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" fileuri="file:///%sxdebug-dbgp-test.php" language="PHP" xdebug:language_version="" protocol_version="1.0" appid="" idekey=""><engine version=""><![CDATA[Xdebug]]></engine><author><![CDATA[Derick Rethans]]></author><url><![CDATA[http://xdebug.org]]></url><copyright><![CDATA[Copyright (c) 2002-2099 by Derick Rethans]]></copyright></init>

-> feature_set -i 1 -n max_depth -v 3
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="feature_set" transaction_id="1" feature="max_depth" success="1"></response>

-> step_into -i 2
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="step_into" transaction_id="2" status="break" reason="ok"><xdebug:message filename="file:///%sxdebug-dbgp-test.php" lineno="3"></xdebug:message></response>

-> breakpoint_set -i 3 -t line -n 7
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="breakpoint_set" transaction_id="3" id=""></response>

-> run -i 4
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="run" transaction_id="4" status="break" reason="ok"><xdebug:message filename="file:///%sxdebug-dbgp-test.php" lineno="7"></xdebug:message></response>

-> property_get -i 5 -n $e
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="property_get" transaction_id="5"><property name="$e" fullname="$e" type="object" classname="SimpleXMLElement" children="1" numchildren="2" page="0" pagesize="32"><property name="@attributes" fullname="$e-&gt;@attributes" facet="public" type="array" children="1" numchildren="1" page="0" pagesize="32"><property name="att1" fullname="$e-&gt;@attributes[&quot;att1&quot;]" type="string" size="5" encoding="base64"><![CDATA[YXR0LWE=]]></property></property><property name="b" fullname="$e-&gt;b" facet="public" type="array" children="1" numchildren="2" page="0" pagesize="32"><property name="0" fullname="$e-&gt;b[0]" type="object" classname="SimpleXMLElement" children="1" numchildren="1" page="0" pagesize="32"><property name="@attributes" fullname="$e-&gt;b[0]-&gt;@attributes" facet="public" type="array" children="1" numchildren="1"></property></property><property name="1" fullname="$e-&gt;b[1]" type="object" classname="SimpleXMLElement" children="1" numchildren="1" page="0" pagesize="32"><property name="@attributes" fullname="$e-&gt;b[1]-&gt;@attributes" facet="public" type="array" children="1" numchildren="1"></property></property></property></property></response>

-> property_get -i 6 -n $e->@attributes
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="property_get" transaction_id="6"><property name="$e-&gt;@attributes" fullname="$e-&gt;@attributes" type="array" children="1" numchildren="1" page="0" pagesize="32"><property name="att1" fullname="$e-&gt;@attributes[&quot;att1&quot;]" type="string" size="5" encoding="base64"><![CDATA[YXR0LWE=]]></property></property></response>

-> property_get -i 7 -n $e->@attributes["att1"]
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="property_get" transaction_id="7"><property name="$e-&gt;@attributes[&quot;att1&quot;]" fullname="$e-&gt;@attributes[&quot;att1&quot;]" type="string" size="5" encoding="base64"><![CDATA[YXR0LWE=]]></property></response>

-> property_get -i 8 -n $e->b[0]->@attributes
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="property_get" transaction_id="8"><property name="$e-&gt;b[0]-&gt;@attributes" fullname="$e-&gt;b[0]-&gt;@attributes" type="array" children="1" numchildren="1" page="0" pagesize="32"><property name="attb" fullname="$e-&gt;b[0]-&gt;@attributes[&quot;attb&quot;]" type="string" size="6" encoding="base64"><![CDATA[YXR0Yi0x]]></property></property></response>

-> property_get -i 9 -n $e->b[1]->@attributes["attb"]
<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="property_get" transaction_id="9"><property name="$e-&gt;b[1]-&gt;@attributes[&quot;attb&quot;]" fullname="$e-&gt;b[1]-&gt;@attributes[&quot;attb&quot;]" type="string" size="6" encoding="base64"><![CDATA[YXR0Yi0y]]></property></response>
