<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('objconvert_class.php');

class TestOfObjectConvertClass extends UnitTestCase {

  private $conv;

  function __construct() {
    parent::__construct();
    $this->conv = new Objconvert();
  }

  function __destruct() { 
  }

  function test_convert() {
    $head = '<?xml version="1.0" encoding="UTF-8"?>';

    $obj->tagname->_value = 'tag&value';
    list($xml, $xmlNS, $xmlSoap, $json, $php) = self::convert($obj);
    $this->assertEqual($xml, '<tagname>tag&amp;value</tagname>');
    $this->assertEqual($xmlNS, $head . '<tagname>tag&amp;value</tagname>');
    $this->assertEqual($xmlSoap, $head . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body><tagname>tag&amp;value</tagname></SOAP-ENV:Body></SOAP-ENV:Envelope>');
    $this->assertEqual($json, '{"tagname":{"$":"tag&value"},"@namespaces":null}');
    $this->assertEqual($php, serialize($obj));

    $obj->tagname->_namespace = 'http://some.namespace.com/';
    list($xml, $xmlNS, $xmlSoap, $json, $php) = self::convert($obj);
    $this->assertEqual($xml, '<ns1:tagname>tag&amp;value</ns1:tagname>');
    $this->assertEqual($xmlNS, $head . '<ns1:tagname xmlns:ns1="http://some.namespace.com/">tag&amp;value</ns1:tagname>');
    $this->assertEqual($xmlSoap, $head . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://some.namespace.com/"><SOAP-ENV:Body><ns1:tagname>tag&amp;value</ns1:tagname></SOAP-ENV:Body></SOAP-ENV:Envelope>');
    $this->assertEqual($json, '{"tagname":{"$":"tag&value","@":"ns1"},"@namespaces":{"ns1":"http:\/\/some.namespace.com\/"}}');
    $this->assertEqual($php, serialize($obj));

    $obj->tagname->_attributes->attr->_value = "ATTR";
    list($xml, $xmlNS, $xmlSoap, $json, $php) = self::convert($obj);
    $this->assertEqual($xml, '<ns1:tagname attr="ATTR">tag&amp;value</ns1:tagname>');
    $this->assertEqual($xmlNS, $head . '<ns1:tagname attr="ATTR" xmlns:ns1="http://some.namespace.com/">tag&amp;value</ns1:tagname>');
    $this->assertEqual($json, '{"tagname":{"$":"tag&value","@attr":{"$":"ATTR"},"@":"ns1"},"@namespaces":{"ns1":"http:\/\/some.namespace.com\/"}}');
    $this->assertEqual($php, serialize($obj));

    $obj->tagname->_cdata = TRUE;
    list($xml, $xmlNS, $xmlSoap, $json, $php) = self::convert($obj);
    $this->assertEqual($xml, '<ns1:tagname attr="ATTR"><![CDATA[tag&value]]></ns1:tagname>');
    $this->assertEqual($xmlNS, $head . '<ns1:tagname attr="ATTR" xmlns:ns1="http://some.namespace.com/"><![CDATA[tag&value]]></ns1:tagname>');
    $this->assertEqual($json, '{"tagname":{"$":"tag&value","@attr":{"$":"ATTR"},"@":"ns1"},"@namespaces":{"ns1":"http:\/\/some.namespace.com\/"}}');
    $this->assertEqual($php, serialize($obj));

    $this->conv->set_default_namespace('http://some.namespace.com/');
    list($xml, $xmlNS, $xmlSoap, $json, $php) = self::convert($obj);
    $this->assertEqual($xml, '<ns1:tagname attr="ATTR"><![CDATA[tag&value]]></ns1:tagname>');
    $this->assertEqual($xmlNS, $head . '<ns1:tagname attr="ATTR" xmlns:ns1="http://some.namespace.com/" xmlns="http://some.namespace.com/"><![CDATA[tag&value]]></ns1:tagname>');
    $this->assertEqual($json, '{"tagname":{"$":"tag&value","@attr":{"$":"ATTR"},"@":"ns1"},"@namespaces":{"ns1":"http:\/\/some.namespace.com\/"}}');
    $this->assertEqual($php, serialize($obj));

  }

  function convert($obj) {
    return array($this->conv->obj2xml($obj), 
                 $this->conv->obj2xmlNS($obj),
                 $this->conv->obj2soap($obj),
                 $this->conv->obj2json($obj),
                 $this->conv->obj2phps($obj));
  }

}
?>
