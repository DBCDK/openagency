<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('xml_func_class.php');

define('XMLHEADER', '<?xml version="1.0" encoding="utf-8"?>');

class TestOfXmlFuncClass extends UnitTestCase {

  function __construct() {
    parent::__construct();
  }

  function __destruct() { }

  function test_empty() {
    $xml = XMLHEADER;
    $buf = trim(xml_func::object_to_xml(NULL));
    $this->assertEqual($buf, $xml);
  }

  function test_empty_obj() {
    $obj = new stdClass;
    $xml = XMLHEADER . '
<stdClass>
</stdClass>';
    $buf = trim(xml_func::object_to_xml($obj));
    $this->assertEqual($buf, $xml);
  }

  function test_simple_to_xml() {
    $obj->test = 'Test';
    $xml = XMLHEADER . '
<stdClass>
  <test>Test</test>
</stdClass>';
    $buf = trim(xml_func::object_to_xml($obj));
    $this->assertEqual($buf, $xml);
  }

  function test_repeated() {  // Strange behaviour
    $obj[]->name = 'Text1';
    $obj[]->name = 'Text2';
    $xml = XMLHEADER . '
<stdClass>
  <name>Text2</name>
</stdClass>';
    $buf = trim(xml_func::object_to_xml($obj));
    $this->assertEqual($buf, $xml);
  }

  function test_multiple() {
    $obj->name1 = 'Text1';
    $obj->name2 = 'Text2';
    $xml = XMLHEADER . '
<stdClass>
  <name1>Text1</name1>
  <name2>Text2</name2>
</stdClass>';
    $buf = trim(xml_func::object_to_xml($obj));
    $this->assertEqual($buf, $xml);
  }

}

