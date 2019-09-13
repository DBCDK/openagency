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


/**
 * \brief 
 *       
 * password::generate($lenght); \n
 *
 * @author Finn Stausgaard - DBC
**/

class Password {
  private static $vowels = array("a", "e", "i", "o", "u", "y");
  private static $pre_cons = array("", "b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v", "bl", "br", "dr", "fl", "fr", "gl", "gr", "kl", "kr", "kv", "pl", "pr", "sk", "skr", "sl", "sm", "sn", "sp", "spr", "st", "str", "sv", "tr", "tv");
  private static $suf_cons = array("", "b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v", "nd", "nt", "rt", "st");
  //private static $suf_cons = array("", "b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v",  "ft", "gl", "gt", "ls", "mt", "nd", "ng", "ndt", "nt", "pt", "rt", "rv", "sk", "st");

  private function __construct() {}
  private function __destruct() {}
  private function __clone() {}

  /** \brief Generates a password 
   * @param $length integer
   **/

  public function generate($length) {
    srand((double)microtime()*1000000);

    $last_vowels = count(self::$vowels) - 1;
    $last_pre_cons = count(self::$pre_cons) - 1;
    $last_suf_cons = count(self::$suf_cons) - 1;

    $password = "";
    if (rand(0,1))
      $password .= self::$vowels[rand(0, $last_vowels)] . self::$suf_cons[rand(0, $last_suf_cons)];
    while (strlen($password) < $length) {
      $password .= self::$pre_cons[rand(0, $last_pre_cons)] . self::$vowels[rand(0, $last_vowels)];
      if (strlen($password) < $length)
        $password .= self::$suf_cons[rand(0, $last_suf_cons)];
    }

    return $password;
  }

}
?>
