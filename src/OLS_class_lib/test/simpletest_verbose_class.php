<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('verbose_class.php');

class TestOfVerboseClass extends UnitTestCase {

  private $verbose_file = '/tmp/test_verbose.log';

  function __construct() {
    parent::__construct();
  }

  function __destruct() { 
  }

  function test_log_warning() {
    self::make_empty_log();
    verbose::open($this->verbose_file, 'WARNING+ERROR');
    verbose::log(WARNING, 'Warning test');
    $txt = file_get_contents($this->verbose_file);
    $this->assertPattern('/^WARNING [0-9:\-\/]+ [0-9:\-\/T]+ Warning test$/', $txt);
  }

  function test_not_in_mask() {
    verbose::log(FATAL, 'Fatal test');
    $txt = file_get_contents($this->verbose_file);
    // WARNING 14:01:54-16/09/14 2014-09-16T14:01:54:007936:32635 Warning test
    $this->assertPattern('/^WARNING [0-9:\-\/]+ [0-9:\-\/T]+ Warning test$/', $txt);
  }

  function test_set_prefix() {
    self::make_empty_log();
    verbose::open($this->verbose_file, 'WARNING+ERROR');
    verbose::set_tracking_id('someprefix');
    verbose::log(ERROR, 'Error test');
    $txt = file_get_contents($this->verbose_file);
    // ERROR 14:01:54-16/09/14 someprefix:2014-09-16T14:01:54:007936:32635 Error test
    $this->assertPattern('/^ERROR [0-9:\-\/]+ someprefix:[0-9:\-\/T]+ Error test$/', $txt);
  }

  function test_set_tracking_id() {
    self::make_empty_log();
    verbose::open($this->verbose_file, 'WARNING+ERROR');
    verbose::set_tracking_id('someprefix', 'my_tracking_id');
    verbose::log(ERROR, 'Error test');
    $txt = file_get_contents($this->verbose_file);
    // ERROR 14:04:20-16/09/14 someprefix:2014-09-16T14:04:20:216199:32739<my_tracking_id Error test
    $this->assertPattern('/^ERROR [0-9:\-\/]+ someprefix:[0-9:\-\/T]+<my_tracking_id Error test$/', $txt);
  }

  function test_sys_log() {
    // not tested
  }

  function make_empty_log() {
    if ($fp = fopen($this->verbose_file, 'w')) {
      fclose($fp);
    }
  }

}
?>
