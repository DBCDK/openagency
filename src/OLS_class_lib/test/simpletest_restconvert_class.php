<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('inifile_class.php');
require_once('restconvert_class.php');

class TestOfRestconvertClass extends UnitTestCase {
  private $config;
  private $cnv;
  private $test_ini_name = '/tmp/test_restconvert.ini';

  function __construct() {
    parent::__construct();
    if ($fp = fopen($this->test_ini_name, 'w')) {
      fwrite($fp, "[setup]\n\nsoapAction[operation] = operationRequest\n\n" .
                  "[rest]\n\naction[operation][] = par1\naction[operation][] = par2\n");
      fclose($fp);
    }
    $this->config = new inifile($this->test_ini_name);
    $this->cnv = new Restconvert('http://default.name.space');
  }

  function __destruct() { 
    unlink($this->test_ini_name);
  }

  function test_convert() {
    $_POST['action'] = 'operation';
    $_SERVER['QUERY_STRING'] = 'action=operation&par1=val1&par2=val2';
    $xml = $this->cnv->rest2soap($this->config);
    $this->assertPattern('/<operationRequest><par1>val1<\/par1><par2>val2<\/par2><\/operationRequest>/', $xml);
    $this->assertPattern('/xmlns="http:\/\/default.name.space"/', $xml);
    $this->assertPattern('/:Envelope.+:Body.*:Body.+:Envelope/', $xml);
  }

}
?>
