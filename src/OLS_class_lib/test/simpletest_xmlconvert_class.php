<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('xmlconvert_class.php');

class TestOfXmlConvertClass extends UnitTestCase {

  private $conv;

  function __construct() {
    parent::__construct();
    $this->conv = new Xmlconvert();
  }

  function __destruct() { 
  }

  function test_convert() {
    $head = '<?xml version="1.0" encoding="UTF-8"?>';

    $this->assertFalse(self::convert(''));

    $res->tag->_value = 'value';
    $this->assertEqual(self::convert('<tag>value</tag>'), $res);
    $this->assertEqual(self::convert('<tag><![CDATA[value]]></tag>'), $res);

    $res->tag->_namespace = 'http://somenamespace.com';
    $this->assertEqual(self::convert('<ns1:tag xmlns:ns1="http://somenamespace.com">value</ns1:tag>'), $res);

    $res->tag->_attributes->attr->_value = 'ATTR';
    $this->assertEqual(self::convert('<ns1:tag xmlns:ns1="http://somenamespace.com" attr="ATTR">value</ns1:tag>'), $res);
    $this->assertEqual(self::convert('<tag xmlns="http://somenamespace.com" attr="ATTR">value</tag>'), $res);

    $res->tag->_attributes->attr2->_value = 'ATTR2';
    $this->assertEqual(self::convert('<ns1:tag xmlns:ns1="http://somenamespace.com" attr="ATTR" attr2="ATTR2">value</ns1:tag>'), $res);
    $this->assertEqual(self::convert('<tag xmlns="http://somenamespace.com" attr="ATTR" attr2="ATTR2">value</tag>'), $res);
  }

  function convert($xml) {
    return $this->conv->soap2obj($xml);
  }

}
?>
