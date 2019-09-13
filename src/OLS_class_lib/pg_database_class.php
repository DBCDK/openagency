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

require_once("IDatabase_class.php");
/** \brief
 * Class handles transactions for a postgres database;
 *
 * sample usage:
 *
 * $db=new pg_database('host=visoke port=5432 dbname=kvtestbase user=fvs password=fvs connect_timeout=1');
 *
 * SELECT
 * $db->open();
 * $db->bind('bind_timeid', 'xxxx');
 * $db->bind('bind_seconds', 25);
 * $db->set_query('SELECT time FROM stats_opensearch WHERE timeid = :bind_timeid AND seconds = :bind_seconds');
 * $db->execute();
 * EITHER:
 * $rowcount = $pg->num_rows()
 * while ($rowcount--) {
 * $row = $pg->get_row();
 * }
 * OR:
 * $rows = $pg->get_all_rows();
 * $db->close();
 *
 * SELECT without bind
 * $db->open();
 * $db->set_query('SELECT time FROM stats_opensearch WHERE timeid = \'xxxx\' AND seconds = 25');
 * $db->execute();
 * $row = $pg->get_row();
 * $db->close();
 *
 * INSERT
 * 1. with sql
 * $db->set_query("INSERT INTO stats_opensearch VALUES('2010-01-01', 'xxxx', '12.2')");
 * $db->open();
 * $db->execute();
 * $db->close();
 * 2. with bind
 * $db->open();
 * $db->bind('bind_time', '2010-01-01');
 * $db->bind('bind_timeid', 'xxxx');
 * $db->bind('bind_seconds', 12.2);
 * $db->set_query('INSERT stats_opensearch ( time, timeid, seconds) VALUES ( :time, :bind_timeid, :bind_seconds');
 * $db->execute();
 *
 * UPDATE
 * 1. with sql
 * $db->set_query("UPDATE stats_opensearch SET seconds=25, time=2009 WHERE timeid='xxxx'");
 * $db->open();
 * $db->execute();
 * $db->close();
 * 2. with bind
 * $db->open();
 * $db->bind('bind_time', '2009');
 * $db->bind('bind_timeid', 'xxxx');
 * $db->bind('bind_seconds', 25);
 * $db->set_query('UPDATE stats_opensearch SET seconds=:bind_seconds, time=:bind_time WHERE timeid=:bind_timeid');
 * $db->execute();
 *
 * DELETE
 * 1. with sql
 * $db->set_query("DELETE FROM stats_opensearch WHERE timeid='xxxx' AND seconds='12.2'");
 * $db->open();
 * $db->execute();
 * $db->close();
 * 2. with bind
 * $db->open();
 * $db->bind('bind_timeid', 'xxxx');
 * $db->bind('bind_seconds', 12.2);
 * $db->set_query('DELETE FROM stats_opensearch WHERE timeid=:bind_timeid AND seconds=:bind_seconds');
 * $db->execute();
 */

/** DEVELOPER NOTES
 * postgres-database class
 * TO REMEMBER
 * // to escape characters
 * string pg_escape_string([resource $connection], string $data)
 *
 * // for blobs, clobs etc (large objects).
 * pg_query($database, 'START TRANSACTION');
 * $oid = pg_lo_create($database);
 * $handle = pg_lo_open($database, $oid, 'w');
 * pg_lo_write($handle, 'large object data');
 * pg_lo_close($handle);
 * pg_query($database, 'commit');
 *
 * // for error recovering
 * bool pg_connection_reset(resource $connection)
 */
class Pg_database extends Fet_database
{

    private $query_name;
    private $open_error_message;

    /** \brief constructor
     *
     * The parameter to this function can be iether in FET form, og (partial) URL form
     * FET form: host=<host> port=<port> dbname=<dbname> user=<user> password=<password> connect_timeout=<timeout>
     * URL form: psql://<username>:<password>@<database.server.navn>:<port>/<database_navn>
     * The "psql://" can be omitted from the URL form. Note, the URL is not validated, but sent to the PSQL server.
     *
     * @param $connectionstring string in either FET og URL form
     */
    public function __construct($connectionstring)
    {
        // VerboseJson::log(ERROR, "__construct, connectionstring: '" . $connectionstring . "'");
        // if credentials has the form: "[psql://]<username>:<password>@<database.server.navn>:<port>/<database_navn>"
        $credentials = $connectionstring;
        if (strpos($credentials, '@') !== false) {
            if (strpos($credentials, '://') === false) {
                $credentials = "psql://" . $credentials;
            }
            $connect_arr = parse_url($credentials);
            $connect_arr['path'] = trim($connect_arr['path'], ' /'); // dbname
            $str = 'host=%2$s port=%3$s dbname=%6$s user=%4$s password=%5$s connect_timeout=1';
            $credentials = vsprintf($str, $connect_arr);
        }

        $cred = array('user' => '', 'password' => '', 'dbname' => '', 'host' => '', 'port' => '', 'connect_timeout' => '5');
        $part = explode(" ", $credentials);
        foreach ($part as $key => $val) {
            if (!trim($val))
                continue;
            $pair = explode('=', $val);
            $cred[$pair[0]] = $pair[1];
        }
        parent::__construct($cred["user"], $cred["password"], $cred["dbname"], $cred["host"], $cred["port"], $cred["connect_timeout"]);
    }

    /** \brief destructor
     */
    public function __destruct()
    {
    }

    /** \brief
     * Because pg_connect writes error messages to php error_handler such messages is for a short moment caught by this function
     * Since it returns false, the normal error_handler also get the message
     */
    function catch_open_error($errno, $errstr, $errfile, $errline)
    {
        $this->open_error_message = "errorno : $errno, $errstr, $errfile, $errline";
        return false;
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
    public function open()
    {
        // TODO : When changing to php 5.5.0, restore_error_handler() should be changed to set_error_handler($old_error_handler) at both places
        $old_error_handler = set_error_handler(array($this, "catch_open_error"));
        if (($this->connection = pg_connect(self::connectionstring())) === FALSE) {
            restore_error_handler();
            throw new fetException($this->open_error_message);
        }
        restore_error_handler();
    }

    /** \brief
     * @param statement_name string -
     * @param query string -
     */
    public function prepare($statement_name, $query)
    {
        if (pg_prepare($this->connection, $statement_name, $query) === FALSE) {
            $message = pg_last_error();
            throw new fetException("Prepare fejler : $message\n");
            // Følgende giver ikke rigtig nogen mening idet det også vil blive
            // udført hvis man kommer til at prepare samme statement to gange.
            // if ($this->transaction)
            // @pg_query($this->connection, "ROLLBACK");
            // @pg_query($this->connection, "DEALLOCATE ".$this->query_name);
        }
    }

    /** \brief wrapper for private function _execute
     * @param statement_name string -
     */
    public function execute($statement_name = NULL)
    {
        // set pagination
        if ($this->offset > -1 && $this->limit)
            $this->query .= ' LIMIT ' . $this->limit . ' OFFSET ' . $this->offset;

        try {
            self::_execute($statement_name);
        } catch (Exception $e) {
            throw new fetException($e->__toString());
        }
    }

    /** \brief
     * @param query string -
     * @param params array -
     */
    public function query_params($query = "", $params = array())
    {
        if (($this->result = @pg_query_params($this->connection, $query, $params)) === FALSE) {
            $message = pg_last_error();
            self::set_transaction_mode('ROLLBACK');
            throw new fetException($message);
        }
    }

    /** \brief Start a transaction and disable autocommit
     */
    public function start_transaction()
    {
        $this->transaction = TRUE;
        self::set_transaction_mode('START TRANSACTION');
    }

    /** \brief End the transaction and commit or rollback
     */
    public function end_transaction($commit = TRUE)
    {
        if (!$this->transaction) {
            throw new fetException('No transaction is found');
        }
        if ($commit) {
            self::commit();
        } else {
            self::rollback();
        }
        $this->transaction = FALSE;
    }

    /** \brief -
     */
    public function num_rows()
    {
        return pg_num_rows($this->result);
    }

    /** \brief -
     */
    public function get_row()
    {
        return pg_fetch_assoc($this->result);
    }

    /** \brief -
     */
    public function get_all_rows()
    {
        return pg_fetch_all($this->result);
    }

    /** \brief -
     */
    public function commit()
    {
        self::set_transaction_mode('COMMIT');
        // postgres has autocommit enabled by default
        // use only if TRANSACTIONS are used
    }

    /** \brief -
     */
    public function rollback()
    {
        self::set_transaction_mode('ROLLBACK');
    }

    /** \brief -
     */
    public function close()
    {
        if (!empty($this->query_name)) {
            @pg_query($this->connection, 'DEALLOCATE ' . $this->query_name);
        }
        if ($this->connection)
            pg_close($this->connection);
    }

    /** \brief -
     * @param sql string -
     * @param arr string -
     * @retval array
     */
    public function fetch($sql, $arr = "")
    {
        if ($arr)
            $this->query_params($sql, $arr);
        else
            $this->exe($sql);

        $data_array = pg_fetch_all($this->result);
        return $data_array;
    }

    /** \brief -
     * @param sql string -
     */
    public function exe($sql)
    {
        if (!$this->result = @pg_query($this->connection, $sql)) {
            $message = pg_last_error();
            throw new fetException("sql failed:$message \n $sql\n");
        }
    }

    /* --------------------------------------------------------------------------------- '/

    /** \brief -
     */
    private function set_large_object()
    {
        // TODO implement
    }

    /** \brief -
     */
    private function connectionstring()
    {
        $ret = "";

        if ($this->host)
            $ret .= "host=" . $this->host;
        if ($this->port)
            $ret .= " port=" . $this->port;
        if ($this->database)
            $ret .= " dbname=" . $this->database;
        if ($this->username)
            $ret .= " user=" . $this->username;
        if ($this->password)
            $ret .= " password=" . $this->password;
        if ($this->connect_timeout)
            $ret .= " connect_timeout=" . $this->connect_timeout;

        // set connection timeout
        return $ret;
    }

    /** \brief return a proper key for the query
     */
    private function _queryname()
    {
        $bindname = str_replace(array(' ', ',', '(', ')', "\n", "\r"), '_', $this->query);
        return preg_replace('/__+/', '_', $bindname);
    }

    /** \brief -
     * @param statement_name string -
     */
    private function _execute($statement_name = NULL)
    {
        static $prepared = array();

        if (empty($this->bind_list)) {
            if (($this->result = @pg_query($this->connection, $this->query)) === FALSE) {
                $message = pg_last_error();
                throw new fetException($message);
            }
        } else {
            $bind = self::set_bind_and_alter_query($statement_name === NULL);
            if (isset($statement_name)) {
                $this->query_name = $statement_name;
            } else {
                $this->query_name = self::_queryname();
                if (empty($prepared[$this->query_name])) {
                    $prepared[$this->query_name] = TRUE;
                    if (@pg_prepare($this->connection, $this->query_name, $this->query) === FALSE) {
                        $message = pg_last_error();
                        throw new fetException($message);
                    }
                }
            }
            if (($this->result = @pg_execute($this->connection, $this->query_name, $bind)) === FALSE) {
                $message = pg_last_error();
                throw new fetException($message);
            }
        }
    }

    /** \brief -
     * @param use_bind_name boolean -
     * @retval array - of bind values
     */
    private function set_bind_and_alter_query($use_bind_name)
    {
        $bind_array = array();
        foreach ($this->bind_list as $binds) {
            array_push($bind_array, $binds["value"]);
            if ($use_bind_name) {
                $this->query = preg_replace('/(' . $binds['name'] . ')([^a-zA-Z0-9_]|$)/', '\$' . count($bind_array) . '\\2', $this->query);
            }
        }
        unset($this->bind_list);
        return $bind_array;
    }

    /** \brief set transaction mode if transaction is required
     * @param transaction_parm string -
     */
    private function set_transaction_mode($transaction_mode)
    {
        if ($this->transaction)
            @pg_query($this->connection, $transaction_mode);
    }


}

//*
//* Local variables:
//* tab-width: 2
//* c-basic-offset: 2
//* End:
//* vim600: sw=2 ts=2 fdm=marker expandtab
//* vim<600: sw=2 ts=2 expandtab
//*/
