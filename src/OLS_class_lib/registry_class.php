<?php

/**
 *
 * This file is part of Open Library System.
 * Copyright © 2015, Dansk Bibliotekscenter a/s,
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
 * \brief singleton for sending service info to registry
 *
 * Usage: \n
 * Registry::set($service, $version, $settings); \n
 *
 * @author Finn Stausgaard - DBC
 * */

require_once('OLS_class_lib/curl_class.php');

class Registry {

  public static $response = FALSE;

  private function __construct() { }
  private function __destruct() { }
  private function __clone() { }

  /**
   * \brief Sets loglevel and logfile
   * @param service_name (string)
   * @param version (string)
   * @param setting (array) 
   * */
  static public function set($service_name, $operation, $version, $settings) {
    if ($registry = $settings['registry']) {
      if (empty($settings['ignore_in_url']) || (strpos($_SERVER['QUERY_STRING'], $settings['ignore_in_url']) === FALSE)) {
        if (!$freq = intval($settings['frequency'])) {
          $freq = 1000;
        }
        if (rand(0, $freq) == 0) {
          $url = sprintf($registry, $service_name, $operation, $version, $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'], $_SERVER['SCRIPT_NAME']);
          self::do_curl($url);
        }
      }
    }
  }

  /**
   * \brief Handle reply - will newer get called unless set_wait_for_connections is set to 1 
   * @param handle (string)
   * @param answer (string)
   * */
  static public function receiveResponse($handle, $answer) {
    self::$response = $answer;
  }

  /**
   * \brief to return response set by receiveResponse() above
   * @retval (string)
   * */
  static public function get_response() {
    return self::$response;
  }

  /**
   * \brief Send the http request and do no wait for an answer
   * @param url (string)
   * */
  static private function do_curl($url) {
    static $curl;
    if (empty($curl)) {
      $curl = new Curl();
      $curl->set_wait_for_connections(0);    // not wasting time waiting for an answer
      $curl->set_multiple_options(self::curl_options());
    }
    $curl->get($url);
  }

  /**
   * \brief Send the http request and do no wait for an answer
   * @param url (string)
   * */
  static private function curl_options() {
    return array(CURLOPT_RETURNTRANSFER => TRUE,
                 CURLINFO_HEADER_OUT => TRUE,
                 CURLOPT_HTTPHEADER => array('Content-Type: text/html; charset=utf-8'),
                 CURLOPT_HEADER => FALSE,
                 CURLOPT_TIMEOUT_MS => 1000,
                 CURLOPT_CONNECTTIMEOUT_MS => 1000,
                 CURLOPT_WRITEFUNCTION => array(__CLASS__, 'receiveResponse'));
  }
 
}


