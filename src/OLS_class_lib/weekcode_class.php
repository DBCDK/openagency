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
class weekcodeException extends Exception {
//  public function __toString() {
// return "marcException -->".$this-
}

/**
 * Class weekcode
 */
class weekcode {

    private $db;
    private $tablename;
    private $parametertable;
    private $errormsg;

    /**
     * weekcode constructor.
     * @param pg_database $db
     */
    public function __construct(pg_database $db) {
        $this->db = $db;
        $this->tablename = 'weekcodes';
        $this->parametertable = 'weekcodeparameters';

        $sql = "select tablename from pg_tables where tablename = $1";
        $arr = $db->fetch($sql, array($this->tablename));
        if (!$arr) {
            $sql = "create table " . $this->tablename . "( "
                . "type varchar(10), "
                . "date timestamp with time zone, "
                . "weekcode integer) ";
            $this->db->exe($sql);
        }
        $sql = "select tablename from pg_tables where tablename = $1";
        $arr = $db->fetch($sql, array($this->parametertable));
        if (!$arr) {
            $sql = "create table " . $this->parametertable . "( "
                . "days int, "
                . "numbers integer) ";
            $this->db->exe($sql);

            $default = "insert into " . $this->parametertable . " "
                . "(days,numbers) values (4,1)";
            $this->db->exe($default);
        }
    }

    /**
     * @param string $days
     * @param string $numbers
     * @throws fetException
     */
    function updateparameters($days, $numbers) {
        $update = "update " . $this->parametertable . " "
            . "set days = $days, numbers = $numbers ";
        $this->db->exe($update);
    }

    /**
     * @return bool
     */
    function getparameters() {
        $select = "select days, numbers "
            . "from " . $this->parametertable;
        $rows = $this->db->fetch($select);
        if ($rows) {
            return $rows[0];
        } else {
            return false;
        }
    }

    /**
     * Insert or update the weekcode in the date specified.
     *
     * @param string $date
     * @param string $weekcode
     */
    function insertcode($date, $weekcode, $type) {
//        $weekc = $this->getweekcode($date);
        $sql = "select weekcode from " . $this->tablename . " "
            . "where to_char(date,'YYYYMMDD') = '$date' "
            . "and type = '$type' ";
        $rows = $this->db->fetch($sql);
        if (!$rows) {
            $insert = "insert into " . $this->tablename . " "
                . "(type, date,weekcode) values "
                . "('$type', to_timestamp('$date','YYYYMMDD'), "
                . "$weekcode )";
            $this->db->exe($insert);
        } else {
            $update = "update " . $this->tablename . " "
                . "set weekcode =  '$weekcode' "
                . "where to_char(date,'YYYYMMDD') = '$date' "
                . "and type = '$type' ";
            $this->db->exe($update);
        }
    }


    /**
     *
     * @param string $weekcode
     *
     * returning an array with all the codes from the week "weekcode"
     * both in dat and week
     */
    function getAllWeek($weekcode) {
        $year = substr($weekcode, 0, 4);
        $week_no = substr($weekcode, 4, 2);
        $weekcodes = $weekDat = array();
        $week_start = new DateTime();
        for ($day = 1; $day < 8; $day++) {
            $week_start->setISODate($year, $week_no, $day);
            $date = $week_start->format('Ymd');
//            echo "date:$date\n";

            $weekDat[$date] = $this->getDatWeekcode($date);

            $weekcodes[$date] = $this->getweekcode($date);
        }

        return array('week' => $weekcodes, 'dat' => $weekDat);
    }

    /**
     * @param string $today
     * @param bool $straight
     * @return string
     */
    function calculateWeekCode($today, $straight) {
        $numbers = 2;
        $days = $sec = 0;
        $year = substr($today, 0, 4);
        $month = substr($today, 4, 2);
        $day = substr($today, 6, 2);
        $ts = mktime(0, 0, 0, $month, $day, $year);
//        $tsd = time();

        if (!$straight) {
            $par = $this->getparameters();
            $days = $par['days'] * 24 * 60 * 60;
            $numbers = $par['numbers'];
        }

        $sec = (60 * 60 * 24 * 7) * $numbers;
        $weekcode = date('YW', $ts + $days + $sec);

        return $weekcode;
    }

    /**
     * get the week code for a date. If no date is set, take the current date
     *
     * @param string $date
     * @return string
     */
    function getDatWeekcode($date = '') {
        if ($date) {
            $today = $date;
        } else {
            $today = date('Ymd');
        }

        // is there an exception in the table?
        $sql = "select weekcode from " . $this->tablename . " "
            . "where to_char(date,'YYYYMMDD') = '$today' ";
//            . "and type = 'dat' ";
        $rows = $this->db->fetch($sql);
        if ($rows) {
            return $rows[0]['weekcode'];
        }
        $weekcode = $this->calculateWeekCode($today, $straight = false);

        return $weekcode;
    }

    /**
     * get the week code for a date. If no date is set, take the current date
     *
     * @param string $date
     * @return string
     */
    function getweekcode($date = '') {

        if ($date) {
            $today = $date;
        } else {
            $today = date('Ymd');
        }

        // is there an exception in the table?
        $sql = "select weekcode from " . $this->tablename . " "
            . "where to_char(date,'YYYYMMDD') = '$today' ";
//            . "and type = 'week' ";
        $rows = $this->db->fetch($sql);
        if ($rows) {
            return $rows[0]['weekcode'];
        }
        $weekcode = $this->calculateWeekCode($today, $straight = true);

        return $weekcode;
    }

    /**
     * @param string $weekcode
     * @return bool
     */
    function checkWeekcode($weekcode) {
        if (strlen($weekcode) != 6) {
            return false;
        }
        $year = substr($weekcode, 0, 4);
        if ($year > 2050 or $year < 2015) {
            return false;
        }
        $week = substr($weekcode, 4, 2);
        if ($week < 1 or $week > 53) {
            return false;
        }
        return true;
    }

    /**
     * remove the date from the table.
     * @param string $date (format YYYYMMDD )
     * @param string $type (values 'dat' or 'week')
     */
    function deleteWeekCode($date, $type) {
        $sql = "select weekcode from " . $this->tablename . " "
            . "where to_char(date,'YYYYMMDD') = '$date' "
            . "and type = '$type' ";
        $rows = $this->db->fetch($sql);
        if ($rows) {
            $del = "delete from " . $this->tablename . " "
                . "where to_char(date,'YYYYMMDD') = '$date' "
                . "and type = '$type' ";
            $this->db->exe($del);
        }
    }

    /**
     * updateWeekCodes updates the database if any changes.
     *
     *  $wcodes is an array with index "date" and value "weekcode". The
     * date is in 'yyyymmdd' format.
     *
     * @param array $wcodes
     * @param string $type (values 'dat' or 'week')
     */
    function updateWeekCodes($wcodes, $type) {
        foreach ($wcodes as $date => $newcode) {
            $valid = $this->checkWeekcode($newcode);
            if (!$valid) {
                $this->errormsg = "dat eller ugekode er ikke valid. \"$newcode\"";
                return false;
//                throw new weekcodeException("dat/week code is not vbalid: $newcode");
            }

        }
        if ($type == 'week') {
            $straight = true;
        } else {
            $straight = false;
        }
        $this->db->start_transaction();
        foreach ($wcodes as $date => $newcode) {
            $this->deleteWeekCode($date, $type);
            $calcode = $this->calculateWeekCode($date, $straight);
            if ($calcode == $newcode) {
                continue;
            }
//            $currentcode = $this->getweekcode($date);
//            if ($currentcode != $newcode) {
            $this->insertcode($date, $newcode, $type);
//            }
        }
        $this->db->end_transaction();
        return true;
    }

    function getErrMsg() {
        return $this->errormsg;
    }

}

?>