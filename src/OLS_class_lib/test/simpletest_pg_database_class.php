<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('pg_database_class.php');

class TestOfPgDatabaseClass extends UnitTestCase {

  private $pg;
  private $construct_error = '';

  function __construct() {
    parent::__construct();
    if ($credentials = getenv('PG_CREDENTIALS')) {
      $this->pg = new Pg_database($credentials . ' connect_timeout=1');
      if (!is_object($this->pg)) {
        $this->construct_error = 'Cannot open databse. Is PG_CREDENTIALS set correct?';
      }
    }
    else {
      $this->construct_error = 'Environment variable PG_CREDENTIALS is undefined';
    }
  }

  function __destruct() { }

  function test_constructor() {
    $this->assertEqual($this->construct_error, '', $this->construct_error);
  }

  function test_open() {
    $mess =  'ok';
    try {
      @ $this->pg->open();
    } catch (Exception $e) {
      $mess = $e->getMessage();
    }
    $this->assertEqual($mess, 'ok', 'Error calling pg->open: ' . $mess . ' Check PG_CREDENTIALS');
  }

  function test_create() {
    $this->assertEqual(self::do_sql('DROP TABLE pg_simple_test'), 'ERROR:  table "pg_simple_test" does not exist');
    $this->assertEqual(self::do_sql('CREATE TABLE pg_simple_test (tal integer, str varchar(40))'), 'ok');
  }

  function test_insert() {
    $this->pg->bind('tal', 1);
    $this->pg->bind('str', 'en');
    $this->assertEqual(self::do_sql("INSERT INTO pg_simple_test VALUES (:tal, :str)"), 'ok');
    $this->assertEqual(self::do_sql("INSERT INTO pg_simple_test VALUES (2, 'to')"), 'ok');
  }

  function test_select() {
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test WHERE tal = 1'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 1);
    $this->assertEqual($this->pg->get_row(), array('tal' => 1, 'str' => 'en'));
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 2);
    $this->assertEqual($this->pg->get_row(), array('tal' => 1, 'str' => 'en'));
    $this->assertEqual($this->pg->get_row(), array('tal' => 2, 'str' => 'to'));
    $this->assertEqual($this->pg->get_row(), FALSE);
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test'), 'ok');
    $this->assertEqual($this->pg->get_all_rows(), array(array('tal' => 1, 'str' => 'en'), array('tal' => 2, 'str' => 'to')));
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test WHERE tal = 3'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 0);
  }

  function test_select_with_bind() {
    $this->pg->bind('tal', 1);
    $this->pg->bind('str', 'en');
    $this->assertEqual(self::do_sql('SELECT tal FROM pg_simple_test WHERE tal = :tal AND str = :str'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 1);
    $this->assertEqual($this->pg->get_row(), array('tal' => 1));
    $this->pg->bind('tal', 2);
    $this->pg->bind('str', 'to');
    $this->assertEqual(self::do_sql('SELECT tal FROM pg_simple_test WHERE tal = :tal AND str = :str'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 1);
    $this->assertEqual($this->pg->get_row(), array('tal' => 2));
  }

  function test_update() {
    $this->assertEqual(self::do_sql("UPDATE pg_simple_test SET str = 'toto' WHERE tal = 2"), 'ok');
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test WHERE tal = 2'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 1);
    $this->assertEqual($this->pg->get_row(), array('tal' => 2, 'str' => 'toto'));
  }

  function test_delete() {
    $this->assertEqual(self::do_sql('DELETE FROM pg_simple_test WHERE tal = 1'), 'ok');
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 1);
  }

  function test_transaction() {
    $this->pg->start_transaction();
    $this->assertEqual(self::do_sql("INSERT INTO pg_simple_test VALUES (3, 'tre')"), 'ok');
    $this->assertEqual(self::do_sql("INSERT INTO pg_simple_test VALUES (4, 'fire')"), 'ok');
    $this->pg->end_transaction(FALSE);
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 1);

    $this->pg->start_transaction();
    $this->assertEqual(self::do_sql("INSERT INTO pg_simple_test VALUES (3, 'tre')"), 'ok');
    $this->assertEqual(self::do_sql("INSERT INTO pg_simple_test VALUES (4, 'fire')"), 'ok');
    $this->pg->end_transaction();
    $this->assertEqual(self::do_sql('SELECT * FROM pg_simple_test'), 'ok');
    $this->assertEqual($this->pg->num_rows(), 3);
  }

  function test_wrong_table() {
    $this->assertPattern('/ERROR:  relation "pg_not_there" does not exist/', self::do_sql('SELECT * FROM pg_not_there'));
  }

  function test_cleanup() {
    $this->assertEqual(self::do_sql('DROP TABLE pg_simple_test'), 'ok');
  }

  function do_sql($sql) {
    $this->pg->set_query($sql);
    return self::exe_catch();
  }

  function exe_catch() {
    try {
      $this->pg->execute();
    } catch (Exception $e) {
      return trim($e->getMessage());
    }
    return 'ok';
  }


}
?>
