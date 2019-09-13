<?php 

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR . 
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('memcache_class.php');

class TestOfMemcacheClass extends UnitTestCase {

  private $cache;

  function __construct() { }
  function __destruct() { }
  
  function test_instantiation() {
    $this->cache = new Cache('localhost', '11211', 1);
    $this->assertTrue(is_object($this->cache));
  }
    
  function test_not_set() {
    $data = $this->cache->get('someName');
    $this->assertFalse($data);
  }

  function test_set_and_get() {
    $this->cache->set('someName', 'someData');
    $data = $this->cache->get('someName');
    $this->assertEqual($data, 'someData');
  }

  function test_timeout() {
    $this->cache->set('someName', 'someData');
    sleep(2);
    $data = $this->cache->get('someName');
    $this->assertFalse($data);
  }

  function test_delete() {
    $this->cache->set('someName', 'someData');
    $this->cache->delete('someName');
    $data = $this->cache->get('someName');
    $this->assertFalse($data);
  }

  function test_flush() {
    $this->cache->set('someName', 'someData');
    $this->cache->set('someOtherName', 'someOtherData');
    $this->cache->flush();
    $data = $this->cache->get('someName');
    $this->assertFalse($data);
    $data = $this->cache->get('someOtherName');
    $this->assertFalse($data);
  }
  
}

?>
