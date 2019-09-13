<?php
/**
 *
 * This file is part of Open Library System.
 * Copyright © 2009, Dansk Bibliotekscenter a/s,
 * Tempovej 7-11, DK-2750 Ballerup, Denmark. CVR: 15149043
 *
 * Open Library System is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Library System is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Open Library System.  If not, see <http://www.gnu.org/licenses/>.
 */

interface IException {
  /* Protected (final) methods from Exception class */
  public function getMessage();                   ///< Exception message
  public function getCode();                      ///< User-defined Exception code
  public function getFile();                      ///< Source filename
  public function getLine();                      ///< Source line
  public function getTrace();                     ///< An array of the backtrace()
  public function getTraceAsString();             ///< Formated string of tracei

  /* Overrideable (public) methods from Exception class */
  public function __toString();                   ///< formated string for display
  public function __construct($message = NULL, $code = 0);  ///< constructor

  /** Custom methods */
  public function log($filename);
}

/** \brief
  implements IException-interface mostly by extending Exception.
 */
class FetException extends Exception implements IException {
  private $addMessage;
  /** constructor can be used to initialize custom objects(like logging)*/
  public function __construct($message = NULL, $code = 0) {
    parent::__construct($message, $code);
    $this->addMessage = '';
  }

  /** __toString-method can be used for custom messages */
  public function __toString() {
    return $this->getMessage() . (isset($this->addMessage) ? ' ' . $this->addMessage : '');
    //return parent::__toString()."\n";
  }

  /** - */
  public function __addToMessage($message) { 
    $this->addMessage = $message;
  }

  /** - */
  public function log($filename) {
  }
}


/** \brief
  Database interface. Common methods that a database-class MUST implement.
 */
interface IDatabase {
  /* methods to be implemented in extending classes (database-specific methods)*/
  Public function execute();      ///< -
  public function get_row();      ///< -
  public function commit();       ///< -
  public function rollback();     ///< -
  public function open();         ///< -
  public function close();        ///< -

  /*methods implemented in base class (general methods)*/
  public function deprecated_insert($table, $record);                        ///< -
  public function deprecated_update($table, $assign, $clause = NULL);        ///< -
  public function deprecated_delete($table, $clause = NULL);                 ///< -
  public function set_query($query);                                         ///< -
  public function set_pagination($offset, $limit);                           ///< -
  public function set_transaction($bool);                                    ///< -
  public function bind($name, $value, $maxlength = -1, $type = SQLT_CHR);    ///< -
}

/** \brief
  Abstract database-class. The basic methods for the databases we wish to use.
  DEPRECATED function for insert, update and delete. Use sql-statement and execute() instead
 */
abstract class Fet_database implements IDatabase {
  protected $username;              ///< -
  protected $password;              ///< -
  protected $database;              ///< -
  protected $host;                  ///< -
  protected $port;                  ///< -
  protected $connect_timeout;       ///< -
  protected $query;                 ///< -
  protected $connection;            ///< -
  protected $offset;                ///< -
  protected $limit;                 ///< -
  protected $bind_list;             ///< -
  protected $transaction = FALSE;   ///< bool

  /** constructor */
  public function __construct($username, $password, $database, $host = NULL, $port = NULL, $connect_timeout = NULL) {
    if ($host == '') $host = NULL;
    if ($port == '') $port = NULL;

    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
    $this->host = $host;
    $this->port = $port;
    $this->connect_timeout = $connect_timeout;
  }  


  /** \brief
    set the query.
   */
  public function set_query($query) {
    $this->query = $query;
  }

  /** \brief
    get the query.
   */
  public function get_query() {
    return $this->query;
  }

  /** \brief
    reset pagination, bind_list and query
   */
  public function reset() {
    $this->limit = NULL;
    $this->offset = NULL;
    $this->query = NULL;
  }

  /** \brief
    generate sql for inserting a row. call extending class's execute()-method

    $table; the table to insert into
    $record; an array or a comma-seperated list of values

    return nothing. throw fetException if execute fails.
   */
  public function deprecated_insert($table, $record) {
    $sql = '';
    $fields = '';
    $values = '';
    if (is_array($record)) {
      foreach( $record as $key => $val ) {
        if (strlen($fields))
          $fields .= ',';
        $fields .= $key;

        if (strlen($values))
          $values .= ',';
        $values .= "'" . $val . "'";
      }	    
      $sql = 'INSERT INTO ' . $table . '(' . $fields . ') VALUES(' . $values . ')';
    }
    else
      $sql = 'INSERT INTO ' . $table . ' VALUES(' . $record . ')';

    $this->query = $sql;
    $this->execute();

  }

  /** \brief
    generate sql for updating a row. call extending class's execute()-method

    $table; the table to update
    $record; an array or a comma-seperated list of key=value
    $clause; an array of clauses (should always be set) 
  // TODO should method abort if clause is not set

  return nothing. throw fetException if execute fails.
   */
  public function deprecated_update($table, $assign, $clause = NULL) {
    $sql = '';
    $where = '';

    if (is_array($assign)) {
      foreach ($assign as $key => $value) {
        if (strlen($sql)) 
          $sql .= ', ';

        $sql .= $key . "='" . $value . "'";
      }
    }  
    elseif (strlen($assign))
      $sql = $assign;
    else
      throw new fetExcetption("no update sql");

    $sql = 'UPDATE ' . $table . ' SET ' . $sql;

    if (is_array($clause)) 
      $where = $this->make_clause($clause);

    elseif ($clause) 
      $where = $clause;

    if (strlen($where)) 
      $sql .= ' WHERE ' . $where;

    $this->query = $sql;    
    $this->execute();
  }

  /** \brief
    generate sql for delete in a table. call extending class's execute()-method

    $table; the table to delete in.
    $clause; an array of clauses (should always be set) 
  // TODO should method abort if clause is not set

  return nothing. throw fetException if execute fails.
   */
  public function deprecated_delete($table, $clause = NULL) {
    $sql = '';
    $where = '';
    if (is_array($clause))
      $where = $this->make_clause($clause);
    elseif ($clause)
      $where = $clause;

    $sql = 'DELETE FROM ' . $table . ' WHERE ' . $where;
    $this->query = $sql;
    $this->execute();
  }

  /** \brief
    set pagination. - is only useable for selects
  // TODO check if query is select - abort method if not

  $offset; where should we start
  $limit; how many rows in result     
   */
  public function set_pagination($offset, $limit) {
    $this->offset = $offset;
    $this->limit = $limit;
  }

  /** \brief
    clear pagination.
   */
  public function clear_pagination() {
    $this->offset = NULL;
    $this->limit = NULL;
  }

  /** \brief
    if transaction is set commit and rollback methods can be used.

    $bool; transaction=$bool
   */
  public function set_transaction($bool) {
    $this->transaction = $bool;
  }

  /* DEVELOPER NOTE */
  // mysql and postgres uses placeholders for preparing a sql.
  // oracle uses bind_by_name

  /**
    bind a variable.

    $name; name of variable to bind
    $value; value of variable.
   */
  public function bind($name, $value, $maxlength = -1, $type = SQLT_CHR) {
    $bind_array["name"] = ($name[0] == ':' ? $name : ':' . $name);
    $bind_array["value"] = $value;
    $bind_array["maxlength"] = $maxlength;
    $bind_array["type"] = $type;
    $this->bind_list[] = $bind_array;    
  }

  /**
    \brief make sql AND clause from given array
   */
  private function deprecated_make_clause($clause) {
    $sql = "";    
    foreach ($clause as $key => $value) {
      if (strlen($sql)) 
        $sql .= ' AND ';

      $sql .= $key . "='" . $value . "'";
    }
    return $sql;
  }
}



?>
