<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('timer_class.php');

class TestOfTimerClass extends UnitTestCase {

  private $timer;

  function __construct() {
    parent::__construct();
    $this->timer = new Stopwatch('', ' ', '', '%s:%01.3f');
  }

  function __destruct() { 
  }

  function test_splitime() {
    $this->timer->start('timer1');
    $split = $this->timer->splittime('timer1');
    $this->assertTrue(is_float($split));
    $this->assertTrue($split < 0.1);
  }

  function test_stop() {
    $this->timer->start('timer2');
    $this->timer->stop('timer2');
    $split1 = $this->timer->splittime('timer2');
    $split2 = $this->timer->splittime('timer2');
    $this->assertEqual($split1, $split2);
  }

  function test_dump() {
    $dump1 = $this->timer->dump();
    $this->assertPattern('/Total:0.[0-9]{3} timer1:0.[0-9]{3} timer2:0.[0-9]{3}/', $dump1);
    $dump2 = $this->timer->dump();
    $this->assertEqual($dump1, $dump2);
  }

}
?>
