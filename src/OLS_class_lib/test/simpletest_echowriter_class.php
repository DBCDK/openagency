<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('echowriter_class.php');

class TestOfEchoWriterClass extends UnitTestCase {
  private $writer;

  function __construct() {
    parent::__construct();
    $this->writer = new EchoWriter();
  }

  function __destruct() { }

  function test_instantiation() {
    $this->assertTrue(is_object($this->writer));
  }

  function test_write() {
    ob_start();
    $this->writer->write('test string');
    $buf = ob_get_contents();
    ob_end_clean();
    $this->assertEqual($buf, 'test string');
  }

}
?>
