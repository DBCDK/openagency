<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('oci_class.php');

class TestOfOciDatabaseClass extends UnitTestCase {

  private $oci;
  private $credentials;

  function __construct() {
    parent::__construct();
    if (!$credentials = getenv('OCI_CREDENTIALS')) {
      $this->construct_error = 'Environment variable OCI_CREDENTIALS is undefined';
    }
  }

  function __destruct() { }

  function test_connect() {
    $credentials = getenv('OCI_CREDENTIALS');
    $this->assertTrue($credentials != '');
    $mess = 'ok';
    try {
      $this->oci = new Oci($credentials);
      $this->oci->connect();
    } catch (Exception $e) {
      $mess = $e->getMessage();
    }
    $this->assertEqual($mess, 'ok', 'Error calling connect: ' . $mess . ' Check OCI_CREDENTIALS');
  }

  function test_create() {
    $this->assertEqual(self::do_sql('DROP TABLE oci_test'), 'ORA-00942: table or view does not exist --- DROP TABLE oci_test');
    $this->assertEqual(self::do_sql('CREATE TABLE oci_test (tal integer, str varchar(40))'), 'ok');
    $this->oci->commit();
  }

  function test_insert() {
    $this->oci->bind('tal', 1);
    $this->oci->bind('str', 'en');
    $this->assertEqual(self::do_sql("INSERT INTO oci_test VALUES (:tal, :str)"), 'ok');
    $this->assertEqual(self::do_sql("INSERT INTO oci_test VALUES (2, 'to')"), 'ok');
    $this->oci->commit();
  }

  function test_select() {
    $this->assertEqual(self::do_sql('SELECT * FROM oci_test WHERE tal = 1'), 'ok');
    $this->assertEqual($this->oci->fetch_into_assoc(), array('TAL' => 1, 'STR' => 'en'));
    $this->assertEqual(self::do_sql('SELECT * FROM oci_test'), 'ok');
    $this->assertEqual($this->oci->fetch_into_assoc(), array('TAL' => 1, 'STR' => 'en'));
    $this->assertEqual($this->oci->fetch_into_assoc(), array('TAL' => 2, 'STR' => 'to'));
    $this->assertEqual($this->oci->fetch_into_assoc(), FALSE);
    $this->assertEqual(self::do_sql('SELECT * FROM oci_test WHERE tal = 3'), 'ok');
  }

  function test_select_with_bind() {
    $this->oci->bind('tal', 1);
    $this->oci->bind('str', 'en');
    $this->assertEqual(self::do_sql('SELECT tal FROM oci_test WHERE tal = :tal AND str = :str'), 'ok');
    $this->assertEqual($this->oci->fetch_into_assoc(), array('TAL' => 1));
    $this->oci->bind('tal', 2);
    $this->oci->bind('str', 'to');
    $this->assertEqual(self::do_sql('SELECT tal FROM oci_test WHERE tal = :tal AND str = :str'), 'ok');
    $this->assertEqual($this->oci->fetch_into_assoc(), array('TAL' => 2));
  }

  function test_update() {
    $this->assertEqual(self::do_sql("UPDATE oci_test SET str = 'toto' WHERE tal = 2"), 'ok');
    $this->assertEqual(self::do_sql('SELECT * FROM oci_test WHERE tal = 2'), 'ok');
    $this->assertEqual($this->oci->fetch_into_assoc(), array('TAL' => 2, 'STR' => 'toto'));
  }

  function test_delete() {
    $this->assertEqual(self::do_sql('DELETE FROM oci_test WHERE tal = 1'), 'ok');
    $this->assertEqual(self::do_sql('SELECT * FROM oci_test'), 'ok');
  }

  function test_rollback() {
    $this->assertEqual(self::do_sql("INSERT INTO oci_test VALUES (3, 'tre')"), 'ok');
    $this->assertEqual(self::do_sql("INSERT INTO oci_test VALUES (4, 'fire')"), 'ok');
    $this->oci->rollback();
    $this->assertEqual(self::do_sql('SELECT * FROM oci_test WHERE tal = 3'), 'ok');
    $this->assertFalse($this->oci->fetch_into_assoc());
  }

  function test_commit() {
    $this->assertEqual(self::do_sql("INSERT INTO oci_test VALUES (3, 'tre')"), 'ok');
    $this->assertEqual(self::do_sql("INSERT INTO oci_test VALUES (4, 'fire')"), 'ok');
    $this->oci->commit();
    $this->assertEqual(self::do_sql('SELECT * FROM oci_test WHERE tal = 3'), 'ok');
    $this->assertEqual($this->oci->fetch_into_assoc(), array('TAL' => 3, 'STR' => 'tre'));
  }

  function test_wrong_table() {
    $this->assertPattern('/table or view does not exist/', self::do_sql('SELECT * FROM oci_not_there'));
  }

  function test_cleanup() {
    $this->assertEqual(self::do_sql('DROP TABLE oci_test'), 'ok');
  }

  function do_sql($sql) {
    try {
      @ $this->oci->set_query($sql);
    } catch (ociException $e) {
      return trim($e->getMessage());
    }
    return 'ok';
  }


}
?>
