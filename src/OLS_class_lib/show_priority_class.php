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
 * need curl_class and memcache_class or memcachedb_class to be defined
 *
 * @author Finn Stausgaard - DBC
 *
 * NB!! this should be replaced with get_show_priority() in open_agency_v2_class.php
**/

require_once('OLS_class_lib/memcache_class.php');
require_once('OLS_class_lib/curl_class.php');

class ShowPriority {

  private $agency_cache;		    // cache object
  private $agency_uri;	        // uri of openagency service
  private $tracking_id;           // 

  /**
  * \brief constructor
  *
  * @param $open_agency string -
  * @param $cache_host string -
  * @param $cache_port string - 
  * @param $cache_seconds integer - 
  */
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
  * \brief Get a given prority list for the agency
  *
  * @param $agency string - agency-id
  * @retval array - array with agency as index and priority as value
  **/
  public function get_priority($agency) {
    if ($this->agency_cache) {
      $cache_key = md5('PRIORITY_' . $agency . $this->agency_uri);
      $agency_list = $this->agency_cache->get($cache_key);
    }

    if (empty($agency_list)) {
      $agency_list = array();
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, constant('AGENCY_TIMEOUT'));
      $res_xml = $curl->get(sprintf($this->agency_uri, $agency, $this->tracking_id));
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        $agency_list = FALSE;
        if (method_exists('verbose','log')) {
          verbose::log(FATAL, __FUNCTION__ . '():: Cannot fetch show priority for ' . sprintf($this->agency_uri, $agency));
        }
      }
      else {
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        if (@ $dom->loadXML($res_xml)) {
          foreach ($dom->getElementsByTagName('agencyId') as $id) {
            $agency_list[$id->nodeValue] = count($agency_list) + 1;
          }
        }
        if (!isset($agency_list[$agency])) {  
          $agency_list[$agency] = 0;
        }
      }
      if ($this->agency_cache) {
        $this->agency_cache->set($cache_key, $agency_list);
      }
      if (method_exists('verbose','log')) {
        verbose::log(TRACE, __CLASS__ . ':: Cache miss, agency: ' . $agency);
      }
    }

    return $agency_list;
  }

}
?>
