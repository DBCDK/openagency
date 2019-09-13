<?php

/*
 * Wrapper class for Pg_database: Extend class so it has same functions, and function names, as oci_class.php
 * NB: Pg_database class methods throws fetException.
 */

require_once('pg_database_class.php');
require_once('IDatabase_class.php');

/**
 * Class pgClass
 */
class psqlClass extends Pg_database {

    var $error = FALSE;

    /** \brief Replace "Open new OCI connection"
     * @param $connect_count integer
     * @return boolean.
     * @throws fetException
     */
    function connect($connect_count = FALSE) {
        try {
            $this->open();
            return TRUE;
        }
        catch (Exception $e) {
            $message = pg_last_error();
            throw new fetException($message);
        }
    }


    /** \brief
     * pg_pconnect has been altered to pg_connect.
     * We have had a lot of database connections before the altering.
     * Hopefully this will solve the problem.
     * From php manuaL
     * "You should not use pg_pconnect - it's broken. It will work but it doesn't really pool,
     * and it's behaviour is unpredictable. It will only make you rise the max_connections
     * parameter in postgresql.conf file until you run out of resources (which will slow
     * your database down)."
     */
    public function open() {
        // NB: set_error_handler) require php 5.5.0 or greater.
        $old_error_handler = set_error_handler(array($this, "catch_open_error"));
        if (($this->connection = pg_connect(self::connectionstring())) === FALSE) {
            set_error_handler($old_error_handler);
            throw new fetException($this->open_error_message);
        }
        set_error_handler($old_error_handler);
    }


    /** \brief Set the query.
     * @param $sql string - SQL string.
     * @return bool
     */
    public function set_query($query) {
        $this->query = $query;
        return TRUE;
    }


    /** \brief Fetches current data into an associative array.
     * @param $sql string - SQL string
     * @return array | bool
     * @throws fetException
     */
    function fetch_into_assoc($sql = '') {
        if ($sql) {
            $this->set_query($sql);
        }
        // Dump query for MBD to peruse.
        // VerboseJson::log(ERROR, "query: '" . $this->get_query() . "' + binds: ");
        // foreach ($this->bind_list as $binds) {
        //     VerboseJson::log(ERROR, $binds['name'] . ' => ' . $binds["value"]);
        // }
        try {
            $this->execute();
            $buf = $this->get_row();
        }
        catch (Exception $e) {
            $message = pg_last_error();
            throw new fetException($message);
            return FALSE;
        }
        if (is_array($buf)) {
            return $result = array_change_key_case($buf, CASE_UPPER);
        }
        return FALSE;
    }


    /** \brief Fetches all data into an associative array
     * @param $sql string - SQL string
     * @return array | boolean
     * @throws ociException
     */
    function fetch_all_into_assoc($sql = '') {
        if ($sql) {
            $this->set_query($sql);
        }
        try {
            $this->execute();
            $buf = $this->get_all_rows();
        }
        catch (Exception $e) {
            $this->error = $e->__toString();
            throw new ociException(["message" => "\$rows is not of type array", "sqltext" => $e->__toString()]);
        }
        if (is_array($buf)) {
            $result = array();
            foreach ($buf as $key => $val) {
                $result[$key] = array_change_key_case($val, CASE_UPPER);
            }
            return $result;
        }
        return FALSE;
    }


    /** \brief Closes PostgreSQL connection
     */
    function disconnect() {
        $this->close();
    }


    /** \brief -
     */
    private function connectionstring() {
        $ret = "";
        if ($this->host)
            $ret.="host=" . $this->host;
        if ($this->port)
            $ret.=" port=" . $this->port;
        if ($this->database)
            $ret.=" dbname=" . $this->database;
        if ($this->username)
            $ret.=" user=" . $this->username;
        if ($this->password)
            $ret.=" password=" . $this->password;
        if ($this->connect_timeout)
            $ret.=" connect_timeout=" . $this->connect_timeout;
        return $ret;
    }


    /** \brief Rollback outstanding statements
     * @return boolean
     * @throws fetException
     */
    function rollback() {
        try {
            self::set_transaction_mode('ROLLBACK');
        }
        catch (Exception $e) {
            $message = pg_last_error();
            throw new fetException($message);
            return FALSE;
        }
        return TRUE;
    }


    /** \brief
    Because pg_connect writes error messages to php error_handler such messages is for a short moment caught by this function
    Since it returns false, the normal error_handler also get the message
     */
    function catch_open_error($errno, $errstr, $errfile, $errline) {
        $this->open_error_message = "errorno : $errno, $errstr, $errfile, $errline";
        $this->error = $this->open_error_message;
        return false;
    }


    /** \brief Get error
     * @return string.
     */
    function get_error() {
        return $this->error;
    }

    /** \brief Get error
     * @return string.
     */
    function get_error_string() {
        return $this->error;
    }

    /** \brief sets charset
     * @param $charset string
     * @return string
     */
    function set_charset($charset) {
        return $this->charset = $charset;
    }

    /** \brief return a proper key for the query
     */
    function get_queryname() {
        return str_replace(array(' ', ',', '(', ')'), '_', $this->query);
    }

}
