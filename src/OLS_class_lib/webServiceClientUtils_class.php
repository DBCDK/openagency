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

require_once("xmlconvert_class.php");
require_once("objconvert_class.php");
require_once("curl_class.php");

class webServiceClientUtils {

	private $xml_request_path; ///< -
	public $request_objects; ///< -
	public $xmlconvert; ///< -
	public $objconvert; ///< -
  public $request_action; ///< -

  /** \brief -
   */
	function __construct ($xml_request_path) {
		$this->xml_request_path=$xml_request_path;
		$this->xmlconvert = new xmlconvert();
		$this->objconvert = new objconvert();
	}

  /** \brief -
   */
  public function set_request_action($request_action) {
    $this->request_action=$request_action;
  }

  /** \brief -
   */
	public function insert_tag(&$request_object, $parent_tag, $tag_name, $tag_value, $tag_namespace) {
   foreach ($request_object as $k=>$v) {
      if($k==$parent_tag) {
				if(!isset($v->_value->$tag_name)) {
					$target_array=&$v->_value->$tag_name;
					$target_array[0]->_namespace=$tag_namespace;
          $target_array[0]->_value=$tag_value;	
				} else if(is_array($v->_value->$tag_name)) {
					$target_array=&$v->_value->$tag_name;
					if(isset($target_array[0])) {
						$i=count($target_array)+1;
						$target_array[$i]->_namespace=$tag_namespace;	
						$target_array[$i]->_value=$tag_value;	
					}
				}

        break;
      }
      if(is_object($v)) {
        $this->insert_tag($v, $parent_tag, $tag_name, $tag_value, $tag_namespace);
      }
    }
	}

  /** \brief -
   */
	public function check_error($obj, &$error=FALSE) {
		 foreach ($obj as $k=>$v) {
     	if($k=="error" || $k=="faultstring") {
				$error=TRUE;
      }
		 	if(is_object($v)) {
      	$this->check_error($v, $error);
    	}
    }
		return $error;
	}

  /** \brief -
   */
	public function load_request($request_name) {
		$request=file_get_contents($this->xml_request_path.$request_name.'.xml');
		$obj=$this->xmlconvert->soap2obj($request);
		$this->request_objects[$request_name]=$obj;
	}

  /** \brief -
   */
	public function change_tag_value(&$request_object, $target_tag_name, $target_tag_value) {
		foreach ($request_object as $k=>$v) {
			if($k==$target_tag_name) {
				$v->_value=$target_tag_value;
				break;
			} 
			if(is_object($v)) {
				$this->change_tag_value($v, $target_tag_name, $target_tag_value);
			} 
		}
	}

  /** \brief -
   */
  public function delete_tag(&$request_object, $target_tag_name) {
    foreach ($request_object as $k=>$v) {
      if($k==$target_tag_name) {
				unset($request_object->$k);
        break;
      }
      if(is_object($v)) {
        $this->delete_tag($v, $target_tag_name);
      }
    }
  }


  /** \brief -
   */
	public function get_request_object($request_name) {
		return $this->request_objects[$request_name];
	}

  /** \brief -
   */
	public function  show_request_objects() {
		print_r($this->request_objects);
	}

  /** \brief -
   */
	public function send_request($request_name, $request_action) {
		$curl = new curl(); 
		$curl->set_timeout(30);
		$xml=$this->objconvert->obj2xml($this->request_objects[$request_name]);
		$curl->set_post_xml($xml);  
		return $res = $curl->get($request_action);
	}
}

?>
