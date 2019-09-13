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

class view {

    private $templateFileName = "";
    private $parameters = array();
    private $parsed = false;

  /** \brief -
   */
    function __construct($template = "") {
        if (empty($template))
            $template = basename($_SERVER['SCRIPT_FILENAME'], 'php') . 'thtml';
        if (!file_exists($template))
            die('Unknown template/html file');
        else {
            $template = getcwd() . "/" . $template;
        }
        $this->templateFileName = $template;
    }

  /** \brief -
   */
    function __destruct() {
        if (!$this->parsed)
            $this->parse();
    }

  /** \brief -
   */
    public function set($var, $value) {
        $this->parameters[$var] = $value;
    }

  /** \brief -
   */
    public function parse() {
        foreach ($this->parameters as $parameter_variable_name => $parameter_value)
            $$parameter_variable_name = $parameter_value;
        unset($parameter_variable_name, $parameter_value);  // Do not expose these variables to the template
        require($this->templateFileName);
        $this->parsed = true;
    }

}

?>
