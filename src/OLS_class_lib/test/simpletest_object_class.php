<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('object_class.php');

class TestOfObjectClass extends UnitTestCase {

  function __construct() {
    parent::__construct();
  }

  function __destruct() { 
  }

  function test_obj() {
    Object::set_object($o_obj, 'name', 'value');
    $obj = new stdClass();
    $obj->name = 'value';
    $this->assertEqual($o_obj, $obj);
    Object::set_object($o_obj, 'name', 'value1');
    $obj->name = 'value1';
    $this->assertEqual($o_obj, $obj);
  }

  function test_obj_value() {
    Object::set_object_value($o_obj, 'name', 'value');
    $obj = new stdClass();
    $obj->name = new stdClass();
    $obj->name->_value = 'value';
    $this->assertEqual($o_obj, $obj);
    Object::set_object_value($o_obj, 'name', 'value1');
    $obj->name->_value = 'value1';
    $this->assertEqual($o_obj, $obj);
  }

  function test_obj_namespace() {
    Object::set_object_value($o_obj, 'ns', 'http://some.server/ns/');
    $obj = new stdClass();
    $obj->ns = new stdClass();
    $obj->ns->_value = 'http://some.server/ns/';
    $this->assertEqual($o_obj, $obj);
    Object::set_object_value($o_obj, 'ns', 'http://some.server/ns2/');
    $obj->ns->_value = 'http://some.server/ns2/';
    $this->assertEqual($o_obj, $obj);
  }

  function test_obj_element() {
    Object::set_object_element($o_obj, 'name', 'tag', 'value');
    $obj = new stdClass();
    $obj->name = new stdClass();
    $obj->name->tag = 'value';
    $this->assertEqual($o_obj, $obj);
    Object::set_object_element($o_obj, 'name', 'tag', 'value1');
    $obj->name->tag = 'value1';
    $this->assertEqual($o_obj, $obj);
  }

}
