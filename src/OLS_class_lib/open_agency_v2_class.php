<?php
/**
 *
 * This file is part of Open Library System.
 * Copyright © 2016, Dansk Bibliotekscenter a/s,
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
 * makes open_agency_class, search_profile_class, show_priority_class and agency_type_class obsolete
 *
 * \brief
 *
 *
 *
 * need curl_class and memcache_class to be defined
 *
 * @author Finn Stausgaard - DBC
**/

require_once('OLS_class_lib/memcache_class.php');
require_once('OLS_class_lib/curl_class.php');

class OpenAgency {

  private $config;	
  private $agency_cache;		  // cache object
  private $agency_uri;	      // uri of openagency service
  private $tracking_id;	      // 
  private $library_type_tab = FALSE;

  public function __construct($config) {
    define('AGENCY_TYPE', 'a'); 
    define('BRANCH_TYPE', 'b'); 
    $this->config = $config;
    if ($this->config['cache_host']) {
      $this->agency_cache = new cache($this->config['cache_host'], $this->config['cache_port'], $this->config['cache_expire']);
    }
    if (!isset($this->config['timeout'])) { $this->config['timeout'] = 10; }
    if (class_exists('verbose')) {
      $this->tracking_id = verbose::$tracking_id;
    }
  }

  /**
  * \brief Fetch agencies by rule using openAgency::libraryRules
  *
  * @param string $rule
  * @param string $setting (0 or 1)
  * @param string $agency_type 
  * @retval array 
  *
  **/
  public function get_libraries_by_rule($rule, $setting='1', $agency_type='') {
    $libraries = FALSE;
    if ($this->agency_cache) {
      $cache_key = md5(__CLASS__ . __FUNCTION__ . $rule . $setting . $agency_type);
      $libraries = $this->agency_cache->get($cache_key);
    }

    if ($libraries === FALSE) {
      $libraries = array();
      self::trace(__CLASS__ . '::' . __FUNCTION__ . '(): Cache miss (' . $rule . ')');
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, $this->config['timeout']);
      $url = sprintf(self::oa_uri($this->config['librariesByRule']), $rule, $setting, $this->tracking_id);
      $res_xml = $curl->get($url);
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot fetch library Rules from ' . $url);
      }
      else {
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        if (@ $dom->loadXML($res_xml)) {
          foreach ($dom->getElementsByTagName('libraryRules') as $agency) {
            if ($agency_type == '' || $agency_type == $agency->getElementsByTagName('agencyType')->item(0)->nodeValue) {
            $libraries[$agency->getElementsByTagName('agencyId')->item(0)->nodeValue] = $agency->getElementsByTagName('agencyId')->item(0)->nodeValue;
            }
          }
        }
        if ($this->agency_cache) {
          if (!$this->agency_cache->set($cache_key, $libraries))
            self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot set cache. key: ' . $cache_key);
        }
      }
      $curl->close();
    }
    return $libraries;
  }

  /**
  * \brief Fetch agency rules using openAgency::libraryRules
  *
  * @param string $agency
  * @retval array 
  *
  **/
  public function get_library_rules($agency) {
    $library_rules = FALSE;
    if ($this->agency_cache) {
      $cache_key = md5(__CLASS__ . __FUNCTION__ . $agency);
      $library_rules = $this->agency_cache->get($cache_key);
    }

    if ($library_rules === FALSE) {
      $library_rules = array();
      self::trace(__CLASS__ . '::' . __FUNCTION__ . '(): Cache miss (' . $agency . ')');
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, $this->config['timeout']);
      $url = sprintf(self::oa_uri($this->config['libraryRules']), $agency, $this->tracking_id);
      $res_xml = $curl->get($url);
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot fetch library Rules from ' . $url);
      }
      else {
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        if (@ $dom->loadXML($res_xml)) {
          foreach ($dom->getElementsByTagName('libraryRule') as $rule) {
            $library_rules[$rule->getElementsByTagName('name')->item(0)->nodeValue] = self::xs_boolean($rule->getElementsByTagName('bool')->item(0)->nodeValue);
          }
        }
        if ($this->agency_cache) {
          if (!$this->agency_cache->set($cache_key, $library_rules))
            self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot set cache. key: ' . $cache_key);
        }
      }
      $curl->close();
    }
    return $library_rules;
  }

  /**
  * \brief Get a given prority list for the agency
  *
  * @param $agency string - agency-id
  * @retval array - array with agency as index and priority as value
  **/
  public function get_show_priority($agency) {
    if ($this->agency_cache) {
      $cache_key = md5(__CLASS__ . __FUNCTION__ . $agency);
      $agency_list = $this->agency_cache->get($cache_key);
    }

    if (empty($agency_list)) {
      $agency_list = array();
      self::trace(__CLASS__ . '::' . __FUNCTION__ . '(): Cache miss (' . $agency . ')');
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, $this->config['timeout']);
      $url = sprintf(self::oa_uri($this->config['showOrder']), $agency, $this->tracking_id);
      $res_xml = $curl->get($url);
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        $agency_list = FALSE;
        self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot fetch show order from ' . $url);
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
        if ($this->agency_cache) {
          if (!$this->agency_cache->set($cache_key, $agency_list))
            self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot set cache. key: ' . $cache_key);
        }
      }
    }

    return $agency_list;
  }

  /**
  * \brief Get a given profile for the agency
  *
  * @param $agency string - name of profile
  * @param $profile_name string - name of profile
  * @param $profile_version integer - version of profile: 2 or 3
  *
  * @retval mixed - profile if found, FALSE otherwise
  **/
  public function get_search_profile($agency, $profile_name, $profile_version = '3') {
    if ($this->agency_cache) {
      $cache_key = md5(__CLASS__ . __FUNCTION__ . $agency . '_' . $profile_version);
      $this->profiles = $this->agency_cache->get($cache_key);
    }

    if (!$this->profiles) {
      self::trace(__CLASS__ . '::' . __FUNCTION__ . '(): Cache miss (' . $agency . ')');
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, $this->config['timeout']);
      $url = sprintf(self::oa_uri($this->config['searchProfile']), $agency, $profile_version, $this->tracking_id);
      $res_xml = $curl->get($url);
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        $this->profiles[strtolower($profile__name)] = FALSE;
        self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot fetch search profile from ' . $url);
      }
      else {
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        if (@ $dom->loadXML($res_xml)) {
          foreach ($dom->getElementsByTagName('profile') as $profile) {
            $p_name = '';
            $p_val = array();
            foreach ($profile->childNodes as $p) {
              if ($p->localName == 'profileName') {
                $p_name = $p->nodeValue;
              }
              elseif ($p->localName == 'source') {
                foreach ($p->childNodes as $s) {
                  if ($s->localName == 'relation') {
                    foreach ($s->childNodes as $r) {
                      $rels[$r->localName] = $r->nodeValue;
                    }
                    $source[$s->localName][] = $rels;
                    unset($rels);
                  }
                  else {
                    $source[$s->localName] = $s->nodeValue;
                  }
                }
                if ($source) {
                  $p_val[] = $source;
                  unset($source);
                }
              }
            }
            $this->profiles[strtolower($p_name)] = $p_val;
            unset($p_val);
          }
        }
        else {
          $this->profiles = array();
        }
        if ($this->agency_cache) {
          if (!$this->agency_cache->set($cache_key, $this->profiles))
            self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot set cache. key: ' . $cache_key);
        }
      }
    }

    if ($p = &$this->profiles[strtolower($profile_name)]) {
      return $p;
    }
    else {
      return FALSE;
    }
  }

  /**
  * \brief Get a given agency_type for the agency
  *
  * @param $agency       name of agency
  * @returns agency_type if found, NULL otherwise
  **/
  public function get_agency_type($agency) {
    if ($this->library_type_tab === FALSE) {
      self::fetch_library_type_tab();
    }
    return $this->library_type_tab[$agency][AGENCY_TYPE];
  }

  /**
  * \brief Get a given branch_type for the agency
  *
  * @param $agency       name of agency
  * @returns branch_type if found, NULL otherwise
  **/
  public function get_branch_type($agency) {
    if ($this->library_type_tab === FALSE) {
      self::fetch_library_type_tab();
    }
    return $this->library_type_tab[$agency][BRANCH_TYPE];
  }

  /**
  * \brief Fetch agencyType and branchType using openAgency::findLibrary
  **/
  private function fetch_library_type_tab() {
    if ($this->agency_cache) {
      $cache_key = md5(__CLASS__ . __FUNCTION__);
      $this->library_type_tab = $this->agency_cache->get($cache_key);
    }

    if (!$this->library_type_tab) {
      $this->library_type_tab = array();
      $curl = new curl();
      $curl->set_option(CURLOPT_TIMEOUT, $this->config['timeout']);
      $url = sprintf(self::oa_uri($this->config['libraryType']), $this->tracking_id);
      $res_json = $curl->get($url);
      $curl_err = $curl->get_status();
      if ($curl_err['http_code'] < 200 || $curl_err['http_code'] > 299) {
        self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot fetch agencies from ' . $url);
      }
      else {
        $libs = json_decode($res_json);
        if (is_object($libs)) {
          if (isset($libs->libraryTypeListResponse)) {   // if libraryTypeList operation is used
            $struct = @$libs->libraryTypeListResponse->libraryTypeInfo;
          }
          else {          // if findLibrary operation is used - this is old style for openagency 2.18 or older
            $struct = @$libs->findLibraryResponse->pickupAgency;
          }
          foreach ($struct as $agency) {
            $this->library_type_tab[$agency->branchId->{'$'}] =
              array(AGENCY_TYPE => $agency->agencyType->{'$'},
                    BRANCH_TYPE => $agency->branchType->{'$'});
          }
        }
        else {
          self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): No agencies found ' . sprintf($this->agency_uri));
        }
        $curl->close();
        if ($this->agency_cache) {
          if (!$this->agency_cache->set($cache_key, $this->library_type_tab))
            self::fatal(__CLASS__ . '::' . __FUNCTION__ . '(): Cannot set cache. key: ' . $cache_key);
        }
      }
      self::trace(__CLASS__ . '::' . __FUNCTION__ . '(): Cache miss');
    }
  }


  /**
  * \brief Make a full url for the openagency call. 
  *        if $operation only conatin parameter part of the url, 'base_uri' is added to $operation 
  * @param string $operation
  * @retval string - full url
  *
  **/
  private function oa_uri($operation) {
    if (strpos($operation, '://') && (substr($operation, 0, 4) == 'http')) {
      return $operation;
    }
    return $this->config['base_uri'] . $operation;
  }

  /** \brief - xs:boolean to php bolean
   * @param string $str
   * @retval boolean - return true if xs:boolean is so
   */
  private function xs_boolean($str) {
    return (strtolower($str) == 'true' || $str == 1);
  }

  /** \brief - 
   * @param string $msg
   */
  private function trace($msg) {
    self::local_verbose(TRACE, $msg);
  }

  /** \brief - 
   * @param string $msg
   */
  private function fatal($msg) {
    self::local_verbose(FATAL, $msg);
  }

  /** \brief - 
   * @param string $level
   * @param string $msg
   */
  private function local_verbose($level, $msg) {
    if (method_exists('verbose','log')) {
      verbose::log($level, $msg);
    }
  }

}
?>
