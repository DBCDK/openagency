<?php
/**
 *
 * This file is part of Open Library System.
 * Copyright © 2014, Dansk Bibliotekscenter a/s,
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
 * 
 * 
 * 
 *
**/

require_once('OLS_class_lib/memcache_class.php');

class UniversalSearch {

  private $settings;
  private $marcx_ns;
  private $hits;

  /** \brief -
   */
  public function __construct($settings, $marcx_ns) {
    $this->settings = $settings;
    $this->marcx_ns = $marcx_ns;
    if ($mnp = $this->settings['marcxchange_namespace_prefix']) {
      $mnp .= ':';
    }
    define('NS_PREFIX', $mnp);
  }

  /**
  * \brief sets a list of ressources and the right atributes of each
  *
  * @param $query
  * @param $start
  * @param $step
  *
  * @returns 
  **/
  public function search($query, $start, $step) {
    return self::zsearch($query, $start, $step);
  }

  /** \brief - return number of hits
   */
  public function get_hits() {
    return $this->hits;
  }

  /** \brief Use out ztarget for searching worldCat - not elegant or correct, but it somehow works
    */
  private function zsearch($query, $start, $step) {
    require_once 'OLS_class_lib/z3950_class.php';
    $z = new z3950();
    $z->set_target($this->settings['target']);
    $z->set_database($this->settings['database']);
    $z->set_authentication($this->settings['authentication']);
    if ($this->settings['proxy']) {
      $z->set_proxy($this->settings['proxy']);
    }
    $z->set_syntax($this->settings['syntax']);
    $z->set_element($this->settings['element']);
    $z->set_rpn(sprintf($this->settings['rpn'], $query));
    $this->hits = $z->z3950_search($this->settings['timeout']);
    if ($z->get_errno()) {
      $ret = $z->get_error_string();
    }
    else {
      $pos = 0;
      for ($n = $start; $n <  min($this->hits + 1, $start + $step); $n++, $pos++) {
        $ret[$pos]->_value->collection->_value->resultPosition->_value = $n;
        $ret[$pos]->_value->collection->_value->numberOfObjects->_value = '1';
        $ret[$pos]->_value->collection->_value->object[$n]->_value = self::to_marcxchange($z->z3950_record($n));
      }
    }
    return $ret;
  }

  /** \brief Convert xml-marc-like format to something close to marc21 marcxchange
    */
  private function to_marcxchange($xml) {
    static $dom;
    if (empty($dom)) {
      $dom = new DomDocument();
    }
    if ($dom->loadXML('<?xml version="1.0" encoding="ISO-8859-1"?>'.$xml)) {
      $marc21 = $dom->getElementsByTagName('marc21')->item(0)->nodeValue;
    }
    else {
      $marc21 = '<eti></eti>' . PHP_EOL . '<001>001 missing data</001>';
    }
    $lines = explode(PHP_EOL, $marc21);
    $ret->collection->_value->record->_value = &$rec_obj;
    self::add_to_object($ret->collection);
    self::add_to_object($ret->collection->_value->record, '', array('format' => 'MARC21', 
                                                                    'type' => 'Bibliographic'));
    foreach ($lines as $line) {
      if (empty($line)) {
        continue;
      }
      $tag = substr($line, 1, 3);
      if ($tag == 'eti') {
        self::add_to_object($rec_obj->leader, '00000' . substr($line, 5, -6));
      }
      else {
        if (intval($tag) < 10) {
          self::add_to_object($obj, substr($line, 9, -6), array('tag' => $tag));
          $rec_obj->controlfield[] = $obj;
        }
        else {
          $data = utf8_encode(substr($line, 13, -6));
          $sfs = explode('*', $data);
          self::add_to_object($obj, '', array('tag' => $tag, 
                                              'ind1' => substr($line, 9, 1), 
                                              'ind2' => substr($line, 10, 1)));
          foreach ($sfs as $sf) {
            self::add_to_object($obj_sf, substr($sf, 1), array('code' => substr($sf, 0, 1)));
            $obj->_value->subfield[] = $obj_sf;
            unset($obj_sf);
          }
          $rec_obj->datafield[] = $obj;
        }
        unset($obj);
      }
    }

    return $ret;
  }

  private function add_to_object(&$obj, $value = '', $attr = array()) {
    if ($value) { $obj->_value = $value; }
    $obj->_namespace = $this->marcx_ns;
    foreach ($attr as $a => $v) {
      $obj->_attributes->$a->_value = $v;
    }
  }

}
