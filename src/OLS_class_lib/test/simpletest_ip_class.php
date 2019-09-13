<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('ip_class.php');

class TestOfIpClass extends UnitTestCase {

  function __construct() {
    parent::__construct();
  }

  function __destruct() { 
  }

  function test_ip() {

    $this->assertFalse(ip_func::ip_in_interval('', ''));
    $this->assertFalse(ip_func::ip_in_interval('', '1.2.3.4'));
    $this->assertFalse(ip_func::ip_in_interval('1.2.3.4', ''));
    $this->assertTrue(ip_func::ip_in_interval('1.2.3.4', '1.2.3.4'));
    $this->assertFalse(ip_func::ip_in_interval('1.2.3.4', '1.2.3.5'));
    $this->assertTrue(ip_func::ip_in_interval('1.2.3.4', '1.2.3.4-1.2.3.4'));
    $this->assertTrue(ip_func::ip_in_interval('1.2.3.4', '1.2.3.3-1.2.3.5'));
    $this->assertFalse(ip_func::ip_in_interval('1.2.3.4', '1.2.3.5-1.2.3.3'));
    $this->assertTrue(ip_func::ip_in_interval('1.2.3.4', '1.1.1.1-1.1.1.2;1.2.3.3-1.2.3.5'));
    $this->assertTrue(ip_func::ip_in_interval('1.2.3.4', '0.0.0.0-255.255.255.255'));
    $this->assertTrue(ip_func::ip_in_interval('0.0.0.1', '0.0.0.0-255.255.255.255'));
    $this->assertFalse(ip_func::ip_in_interval('0.0.0.0', '0.0.0.0-255.255.255.255'));
    $this->assertTrue(ip_func::ip_in_interval('255.255.255.255', '0.0.0.0-255.255.255.255'));
    $this->assertFalse(ip_func::ip_in_interval('255.255.255.255.255', '0.0.0.0-255.255.255.255'));
    $this->assertTrue(ip_func::ip_in_interval('130.185.135.200', '109.70.55.170; 195.90.100.62; 89.221.161.174;130.185.135.197; 130.185.135.199; 130.185.135.200; 130.185.135.204'));
  }

}
?>
