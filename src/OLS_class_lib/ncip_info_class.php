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

//==============================================================================

require_once('verbose_class.php');
require_once('oci_class.php');
require_once('memcache_class.php');

/** \brief ncip_info class
*
* Henter NCIP info for et hovedbibliotek fra VIP 
*   
* @author Finn Stausgaard - DBC
*/

class NcipInfo {

  private $oci;
  private $error;
  private static $memcache;

  /** \brief __construct
  *
  * Constructor - sætter oci credentials op
  * 
  */
  public function __construct($oci_credentials, $cache_settings = NULL) {
    $this->oci = new Oci($oci_credentials);
    $this->oci->set_charset('UTF8');
    try {
      $this->oci->connect();
    }
    catch (ociException $e) {
      $this->error = $this->oci->get_error_string();
      verbose::log(FATAL, __CLASS__ . '(' . __LINE__.'):: OCI connect error: ' . $this->error);
      return FALSE;
    }

    if (isset($cache_settings) && !is_object($this->memcache)) {
      $this->memcache = new cache($cache_settings['host'], $cache_settings['port'], $cache_settings['expire']);
      if (!$this->memcache->check()) {
        $this->memcache = NULL;    
      }
    }
  }


 /** \brief get_ncip_info
  *
  * Henter NCIP info for et givet bibliotek
  *
  * @param bib (integer) - Bibliotekskode
  * 
  * @retval array - NCIP data hentet fra VIP basen
  * 
  */
  public function get_ncip_info($bibno) {
    if ($this->error || empty($bibno)) { return NULL; }
    if (isset($this->memcache)) {
      $cachekey = 'ncip_info_' . $bibno;
      if ($ret = $this->memcache->get($cachekey))
        return $ret;
    }

    try {
      $this->oci->bind('bind_bib_dk', 'bibliotek.dk');
      $this->oci->bind('bind_ncip', 'ncip');
      $this->oci->bind('bind_ncipk', 'ncipk');
      $this->oci->bind('bind_bibno', $bibno);
      $this->oci->set_query(
          'SELECT laanertjek.address, laanertjek.password,
                  vip_kat.ncip_renew, vip_kat.ncip_cancel, vip_kat.ncip_update_request, vip_kat.ncip_lookup_user,
                  vip_kat.ncip_lookup_user_address, vip_kat.ncip_lookup_user_password
             FROM fjernadgang_andre, fjernadgang, laanertjek, vip_kat
            WHERE fjernadgang_andre.navn = :bind_bib_dk 
              AND fjernadgang_andre.faust = fjernadgang.faust 
              AND fjernadgang.bib_nr = :bind_bibno
              AND (laanertjek.type = :bind_ncip OR laanertjek.type = :bind_ncipk)
              AND vip_kat.bib_nr = :bind_bibno
              AND fjernadgang.laanertjekmetode_id = laanertjek.id_nr');
      $buf = $this->oci->fetch_into_assoc();
      if (empty($buf)) {
        $this->oci->bind('bind_bibno', $bibno);
        $this->oci->set_query(
            'SELECT vip_kat.ncip_renew, vip_kat.ncip_cancel, vip_kat.ncip_update_request, vip_kat.ncip_lookup_user,
                    vip_kat.ncip_lookup_user_address, vip_kat.ncip_lookup_user_password
               FROM vip_kat
              WHERE vip_kat.bib_nr = :bind_bibno');
       $buf = $this->oci->fetch_into_assoc();
      }
      if (is_array($buf)) {
        $ret = array_change_key_case($buf, CASE_LOWER);
      }
      if (isset($this->memcache)) {
        $this->memcache->set($cachekey, $ret);
      }
    }
    catch (ociException $e) {
      $this->error = $this->oci->get_error_string();
      verbose::log(FATAL, __CLASS__ . '(' . __LINE__.'):: OCI select error: ' . $this->error);
      return NULL;
    }

    return $ret;
  }

}



?>
