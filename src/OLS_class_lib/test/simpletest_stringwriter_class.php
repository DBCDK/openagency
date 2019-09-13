<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('stringwriter_class.php');

class TestOfStringWriterClass extends UnitTestCase {
  private $writer;

  function __construct() {
    parent::__construct();
    $this->writer = new StringWriter();
  }

  function __destruct() { }

  function test_instantiation() {
    $this->assertTrue(is_object($this->writer));
  }

  function test_write() {
    $this->writer->write('test');
    $buf = $this->writer->result();
    $this->assertEqual($buf, 'test');
    $this->writer->write(' string');
    $buf = $this->writer->result();
    $this->assertEqual($buf, 'test string');
  }

  function test_clear() {
    $buf = $this->writer->result();
    $this->assertEqual($buf, 'test string');
    $buf = $this->writer->clear();
    $buf = $this->writer->result();
    $this->assertEqual($buf, '');
  }

}
?>
