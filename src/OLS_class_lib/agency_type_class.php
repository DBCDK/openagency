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
 * NB!! this should be replaced by functions in open_agency_v2_class.php
**/

require_once('OLS_class_lib/memcache_class.php');
require_once('OLS_class_lib/curl_class.php');

class agency_type {

  private $agency_status;         // service status (init, ready or fatal)
  private $agency_type_tab;
  private $agency_cache;		  // cache object
  private $agency_uri;	          // uri of openagency service
  private $tracking_id;	          // 

  public function __construct($open_agency, $cache_host, $cache_port='', $cache_seconds = 0) {
    if ($cache_host) {
      $this->agency_cache = new cache($cache_host, $cache_port, $cache_seconds);
    }
    $this->agency_uri = $open_agency;
    $this->agency_status = 'init';
    if (class_exists('verbose')) {
      $this->tracking_id = verbose::$tracking_id;
    }
    if (!defined('AGENCY_TIMEOUT')) {
      define('AGENCY_TIMEOUT', 10);
    }
  }

  /**
  * \brief Get a given bracnh_type for the agency
  *
  * @param $agency       name of agency
  *
  * @returns bracnh_type if found, NULL otherwise
  **/
  public function get_branch_type($agency) {
    if ($this->agency_status == 'init') {
      $this->fetch_agency_type_tab();
    }
    return $this->agency_type_tab[$agency]['branchType'];
  }

  /**
  * \brief Get a given agency_type for the agency
  *
  * @param $agency       name of agency
  *
  * @returns agency_type if found, NULL otherwise
  **/
  public function get_agency_type($agency) {
    if ($this->agency_status == 'init') {
      $this->fetch_agency_type_tab();
    }
    return $this->agency_type_tab[$agency]['agencyType'];
  }

  /**
  * \brief Fetch agencyType and branchType using openAgency::findLibrary
  *
  **/
  private function fetch_agency_type_tab() {
    $this->agency_status = 'ready';
    if ($this->agency_cache) {
      $cache_key = md5('BRANCH_TYPES_' . $this->agency_uri);
      $this->agency_type_tab = $this->agency_cache->get($cache_key);
    }

    if (!$this->agency_type_tab) {
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, constant('AGENCY_TIMEOUT'));
      $res_json = $curl->get(sprintf($this->agency_uri, $this->tracking_id));
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        self::report_fatal_error(__FUNCTION__ . '():: Cannot fetch agencies from ' . sprintf($this->agency_uri));
      }
      else {
        $libs = json_decode($res_json);
        if (is_object($libs)) {
          if (isset($libs->libraryTypeListResponse)) {   // if libraryTypeList operation is used
            $struct = @$libs->libraryTypeListResponse->libraryTypeInfo;
          }
          else {          // if findLibrary operation is used
            $struct = @$libs->findLibraryResponse->pickupAgency;
          }
          foreach ($struct as $agency) {
            $this->agency_type_tab[$agency->branchId->{'$'}] =
              array('agencyType' => $agency->agencyType->{'$'},
                    'branchType' => $agency->branchType->{'$'});
          }
        }
        else {
          self::report_fatal_error(__FUNCTION__ . '():: No agencies found ' . sprintf($this->agency_uri));
        }
        $curl->close();
      }
      if ($this->agency_cache) {
        $this->agency_cache->set($cache_key, $this->agency_type_tab);
      }
      if (method_exists('verbose','log')) {
        verbose::log(TRACE, __CLASS__ . ':: Cache miss');
      }
    }
  }

  private function report_fatal_error($msg) {
    if (method_exists('verbose','log')) {
      verbose::log(FATAL, $msg);
    }
    $this->agency_status = 'fatal';
  }

}
?>
