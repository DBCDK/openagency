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
**/

/**
 * \brief
 *
 *
 *
 * need curl_class and memcache_class to be defined
 *
 * @author Finn Stausgaard - DBC
 *
 * NB!! this should be replaced with get_library_rules() in open_agency_v2_class.php
 *
**/

require_once('OLS_class_lib/memcache_class.php');
require_once('OLS_class_lib/curl_class.php');

class OpenAgency {

  private $agency_cache;		  // cache object
  private $agency_uri;	      // uri of openagency service
  private $tracking_id;	      // 

  public function __construct($open_agency, $cache_host, $cache_port='', $cache_seconds = 0) {
    if ($cache_host) {
      $this->agency_cache = new cache($cache_host, $cache_port, $cache_seconds);
    }
    $this->agency_uri = $open_agency;
    if (class_exists('verbose')) {
      $this->tracking_id = verbose::$tracking_id;
    }
    if (!defined('AGENCY_TIMEOUT')) {
      define('AGENCY_TIMEOUT', 10);
    }
  }

  /**
  * \brief Fetch agency rules using openAgency::libraryRules
  *
  **/
  public function get_agency_rules($agency) {
    $agency_rules = FALSE;
    if ($this->agency_cache) {
      $cache_key = md5('AGENCY_RULES_' . $this->agency_uri . $agency);
      $agency_rules = $this->agency_cache->get($cache_key);
    }

    if ($agency_rules === FALSE) {
      $agency_rules = array();
      self::trace(__FUNCTION__ . ':: Cache miss (' . $agency . ')');
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, constant('AGENCY_TIMEOUT'));
      $url = sprintf($this->agency_uri, $agency, $this->tracking_id);
      $xml_rules = $curl->get($url);
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        self::fatal(__FUNCTION__ . '():: Cannot fetch agencies from ' . $url);
      }
      else {
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        if (@ $dom->loadXML($xml_rules)) {
          foreach ($dom->getElementsByTagName('libraryRule') as $rule) {
            $agency_rules[$rule->getElementsByTagName('name')->item(0)->nodeValue] = self::xs_boolean($rule->getElementsByTagName('bool')->item(0)->nodeValue);
          }
        }
      }
      $curl->close();
      if ($this->agency_cache) {
        $this->agency_cache->set($cache_key, $agency_rules);
      }
    }
    return $agency_rules;
  }

  /** \brief - xs:boolean to php bolean
   * @param string $str
   * @retval boolean - return true if xs:boolean is so
   */
  private function xs_boolean($str) {
    return (strtolower($str) == 'true' || $str == 1);
  }

  private function trace($msg) {
    self::local_verbose(TRACE, $msg);
  }

  private function fatal($msg) {
    self::local_verbose(FATAL, $msg);
  }

  private function local_verbose($level, $msg) {
    if (method_exists('verbose','log')) {
      verbose::log($level, $msg);
    }
  }

}
?>
