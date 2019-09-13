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
 * \brief Class for handling Marc (iso and ln) records
 *
 * Example usage:
 *
 */
class marcException extends Exception {
//  public function __toString() {
// return "marcException -->".$this-
}

class marc implements Iterator {

//class marc {
    private $marc_array = array();
    private $position = 0;
    private $field;
    private $marc_arrayIndex;
    private $subfield;
    private $subfieldIndex;
    private $subfieldText;
    var $fp;                   ///< -
    var $marcLength;           ///< -

    /** \brief constructor
     */

    public function __construct() {
        $this->position = 0;
        $this->substitute = chr(26);
        $this->endOfRecord = chr(29);
        $this->fieldTerminator = chr(30);
        $this->delimiter = chr(31);

        return;
    }

    /** \brief -
     */
    function rewind() {
        $this->position = 0;
    }

    /** \brief -
     */
    function current() {
        return $this->marc_array[$this->position];
    }

    /** \brief -
     */
    function key() {
        return $this->position;
    }

    /** \brief -
     */
    function next() {
        ++$this->position;
    }

    /** \brief -
     */
    function valid() {
        return isset($this->marc_array[$this->position]);
    }

    /** \brief
     * -
     * @param $txt string
     * @retval string
     */
    private function striptxt($txt) {
        $txt = str_replace('&', 'og', $txt);
        $newtxt = "";
        for ($i = 0; $i < strlen($txt); $i++) {
            if (ctype_alnum($txt[$i])) {
                $newtxt .= $txt[$i];
            }
        }
        return strtoupper($newtxt);
    }

    /** \brief
     * -
     * @param $txt1 string
     * @param $txt2 string
     * @param $loose boolean
     * @retval boolean
     */
    function FuzzyCompare($txt1, $txt2, $loose = false) {
        $txt1 = $this->striptxt($txt1);
        $txt2 = $this->striptxt($txt2);
        if (!$txt1 && $txt2) {
            return false;
        }
        if ($txt1 && !$txt2) {
            return false;
        }

        if ($txt1 == $txt2) {
            return true;
        } else {
            if ($loose) {
                $pos = strpos($txt1, $txt2);
                if ($pos !== false) {
                    return true;
                }
                $pos = strpos($txt2, $txt1);
                if ($pos !== false) {
                    return true;
                }
            } else {
                return false;
            }
        }
    }

    /** \brief
     * Returns all fields matching $fieldName
     * $fieldName can be masked from right
     * ex. 5xx means all fields starting with 5
     * @param $fieldName string
     * @retval array - of fields
     */
    function findFields($fieldName) {
        $lng = 3;
        if ($pos = strpos($fieldName, 'x')) {
            $lng = $pos;
        }
        $fields = array();
        foreach ($this->marc_array as $value) {
            if (substr($value['field'], 0, $lng) == substr($fieldName, 0, $lng)) {
                $fields[] = $value;
            }
        }
        return $fields;
    }

    /** \brief
     * Returnin all fields $fieldName convertet into strings
     * ex.  260 00*aGyldendel*bKbh 1984
     * @param $fieldName string
     * @retval array of strings
     */
    function getLineFields($fieldName) {
        $fields = $this->findFields($fieldName);
        $strings = array();
        foreach ($fields as $field) {
            $string = $field['field'] . ' ' . $field['indicator'];
            foreach ($field['subfield'] as $subfield) {
                $string .= '*' . $subfield;
            }
            $strings[] = $string;
        }
        return $strings;
    }

    /** \brief
     * If a field is already present nothing happens (return false)
     * otherwise the field is inserted ( return true)
     * @param $newField array
     * @retval boolean
     */
    function insertWithoutDublets($newField) {
        $string = $newField['field'] . ' ' . $newField['indicator'];
        foreach ($newField['subfield'] as $subfield) {
            $string .= '*' . $subfield;
        }
        $found = false;
        $oldFields = $this->getLineFields($newField['field']);
        foreach ($oldFields as $oldField) {
            if ($oldField == $string) {
                $found = true;
            }
        }
        if ($found) {
            return false;
        } else {
            $this->insert($newField);
            return true;
        }
    }

    /** \brief -
     * @param $string string
     * @retval array
     */
    function stringToField($string) {
        if (strlen($string) < 10) {
            return false;
        }
        $field = array();
        $field['field'] = substr($string, 0, 3);
        $field['indicator'] = substr($string, 4, 2);
        $field['subfield'][] = substr($string, 7);
        return $field;
    }

    /** \brief
     * Jeg har fejlagtigt lagt poster i phuset med et tomt delfelt.  Disse skal cleares for at kunne blive
     * fjernet.
     */
    function CleanMarcRecord() {
        $newmarc = $this->marc_array;
        $this->marc_array = array();
//    $prnt = false;
        foreach ($newmarc as $field) {
//      print_r($field);
            if ($field['field'] == 'e01') {
                continue;
            }

            $newfield = array();
            $newfield['field'] = $field['field'];
            $newfield['indicator'] = $field['indicator'];
            $newsubfield = array();
            foreach ($field['subfield'] as $subfield) {
                if (strlen($subfield)) {
                    $newsubfield[] = $subfield;
                }
//        else {
//          $prnt = true;
//        }
            }
            $newfield['subfield'] = $newsubfield;
            $this->marc_array[] = $newfield;
        }
//    if ($prnt) {
//      print_r($this->marc_array);
//      print_r($newmarc);
//      exit;
//    }
    }

    /** \brief
     * -
     * @param $fieldName string The name of the field (marc field ex. '245')
     * @param $subFields string The subfield(s) code (marc 'a' or 'ea')
     * @param $maxres integer- Maxresult, if you for instance only one the first subfield. If more
     * an exception is thrown
     * @retval array The subfields. The first character is the subfield code: 'aDet lille hus på...'
     * @throws marcException to many results
     */
    function findSubFields($fieldName, $subFields, $maxres = 99999) {
        $subreturn = array();
        foreach ($this->marc_array as $value) {
            if ($value['field'] != $fieldName) {
                continue;
            }
            foreach ($value['subfield'] as $subcode) {
                for ($cnt = 0; $cnt < strlen($subFields); $cnt++) {
                    if (strlen($subcode) < 1) {
                        echo "FEJL i posten -- find\n";
                        print_r($this->marc_array);

                        exit;
                    }
                    if ($subFields[$cnt] == $subcode[0]) {
                        $subreturn[] = substr($subcode, 1);
                    }
                }
            }
        }
        if (count($subreturn) > $maxres) {
            throw new marcException("to many result in \"findSubFields\"");
        }
        if ($maxres == 1) {
            if (array_key_exists(0, $subreturn)) {
                return ($subreturn[0]);
            } else {
                return "";
            }
        } else {
            return ($subreturn);
        }
    }

    /**
     * @param $field
     * @param $subfield
     * @param $txt
     */
    function substitute($field, $subfield, $txt) {
        while ($this->thisField($field)) {
            while ($this->thisSubfield($subfield)) {
                $this->updateSubfield($txt);
            }
        }

    }

    function clearThisField() {
        $this->field = '---';
    }

    /** \brief
     * -
     * @param $field string
     * @retval boolean
     */
    function thisField($field) {
        if (!$field) {
            return false;
        }
        if ($this->field != $field) {
            $this->field = $field;
            $this->marc_arrayIndex = -1;
            $this->subfield = '';
        }
        for ($i = $this->marc_arrayIndex + 1; $i < count($this->marc_array); $i++) {
            if ($this->marc_array[$i]['field'] == $this->field) {
                $this->marc_arrayIndex = $i;
                $this->subfield = '';
                return true;
            }
        }
        $this->field = '';
        return false;
    }

    /** \brief
     * -
     * @retval integer
     */
    function getMarc_arryIndex() {
        return $this->marc_arrayIndex;
    }

    /** \brief
     * -
     * @param $subfield string
     * @retval boolean
     */
    function thisSubfield($subfield) {
        if (!$subfield) {
            return false;
        }
        if ($this->marc_arrayIndex < 0) {
            return false;
        }
        if ($this->subfield != $subfield) {
            $this->subfield = $subfield;
            $this->subfieldIndex = -1;
        }
        if (!array_key_exists($this->marc_arrayIndex, $this->marc_array)) {
            return false;
        }
        $sub = $this->marc_array[$this->marc_arrayIndex];
        for ($i = $this->subfieldIndex + 1; $i < count($sub['subfield']); $i++) {
            if (substr($sub['subfield'][$i], 0, 1) == $this->subfield) {
                $this->subfieldText = substr($sub['subfield'][$i], 1);
                $this->subfieldIndex = $i;
                return true;
            }
        }
        $this->subfield = '';
        return false;
    }

    /** \brief
     *
     * @param $field string
     * @return boolean
     */
    function updateField($field) {
        if ($this->marc_arrayIndex < 0) {
            return false;
        }
        $this->marc_array[$this->marc_arrayIndex] = $field;
    }

    /**
     * function update subfield
     * @param $txt string
     */
    function updateSubfield($txt) {
        if ($this->subfieldIndex < 0) {
            return false;
        }
        if ($this->marc_arrayIndex < 0) {
            return false;
        }
        $txt = $this->marc_array[$this->marc_arrayIndex]['subfield'][$this->subfieldIndex][0] . $txt;
        $this->marc_array[$this->marc_arrayIndex]['subfield'][$this->subfieldIndex] = $txt;
    }

    /**
     *
     * @return type returning the field (array) from the latest
     * call to thisField
     *
     */
    function field() {
        return $this->marc_array[$this->marc_arrayIndex];
    }

    /** \brief
     * -
     * @retval string
     */
    function subfield() {
        return $this->subfieldText;
    }

    /** \brief
     * -
     * @retval boolean
     */
    function remSubfield() {
//        if (!$this->marc_arrayIndex)
//            return false;
        if ($this->subfieldIndex < 0) {
            return false;
        }
        $newSubfield = array();
        $arr = $this->marc_array[$this->marc_arrayIndex]['subfield'];
        for ($i = 0; $i < count($arr); $i++) {
            if ($i == $this->subfieldIndex) {
                continue;
            }
            $newSubfield[] = $arr[$i];
        }
        $this->marc_array[$this->marc_arrayIndex]['subfield'] = $newSubfield;
        if (count($this->marc_array[$this->marc_arrayIndex]['subfield']) == 0) {
            $this->remField();
        }
        return true;
    }

    /** \brief
     * -
     * @param $field string  Field
     * @param $index integer Index of field
     */
    function remField($field = '', $index = 0) {
        if ($field) {
            $indx = 0;
            $this->marc_arrayIndex = 0;
            foreach ($this->marc_array as $key => $m) {
                if ($m['field'] == $field) {
                    if ($indx == $index) {
                        $this->marc_arrayIndex = $key;
                        break;
                    }
                    $indx++;
                }
            }
        }
        if (!$this->marc_arrayIndex) {
            return false;
        }
        $newMarcArray = array();
        for ($i = 0; $i < count($this->marc_array); $i++) {
            if ($i == $this->marc_arrayIndex) {
                continue;
            }
            $newMarcArray[] = $this->marc_array[$i];
        }
        $this->marc_array = $newMarcArray;
        return true;
    }

    function remSubfieldText($searchfield, $subfieldcode, $txt) {
        $remove = true;
        $ret = false;
        while ($remove) {
            $remove = false;
            while ($this->thisField($searchfield)) {
                while ($this->thisSubfield($subfieldcode)) {
                    $t = $this->subfield();
                    if (substr($t, 0, strlen($txt)) == $txt) {
                        $this->remSubfield();
                        $remove = $ret = true;
                        break;
                    }
                }
                if ($remove) {
                    break;
                }
            }
        }
        return $ret;
    }

    /** \brief
     * - Removes the entire field if a subfield starts with $txt
     * @param $searchfield string
     * @param $subfieldcode char
     * @param $txt string
     * @retval boolean
     */
    function remFieldText($searchfield, $subfieldcode, $txt) {
        $remove = true;
        $ret = false;
        $txt = $subfieldcode . $txt;
        while ($remove) {
            $remove = false;
            $fields = $this->findFields($searchfield);
            foreach ($fields as $key => $currenctfield) {
                foreach ($currenctfield['subfield'] as $field) {
                    if (substr($field, 0, strlen($txt)) == $txt) {
                        $this->remField($searchfield, $key);
                        $remove = true;
                        $ret = true;
                        break;
                    }
                }
                if ($remove) {
                    break;
                }
            }
        }
        return $ret;
    }

    /** \brief
     * -
     * @param $fieldName string
     * @param $subfieldArray array
     * @param $indexToSubfieldArray array
     *
     * @retval boolean true: subfield removed, false: no such subfield
     */
    function removeSubfield($fieldName, $subfieldArray, $indexToSubfieldArray) {
        $returnValue = false;
        foreach ($this->marc_array as $value) {
            if ($value['field'] != $fieldName) {
                continue;
            }
//            print_r($value);
            $newSubfields = array();
            foreach ($value['subfield'] as $key => $subF) {
                if ($key == $indexToSubfieldArray) {
                    $returnValue = true;
                    continue;
                }
                $newSubfields[] = $subF;
            }
            $value['subfield'] = $newSubfields;
        }
        return $returnValue;
    }

    /** \brief
     * -
     * @retval boolean
     * @throws marcException to many results
     */
    function readNextMarc() {
// read next 5 chars:
        if (!$marcLength = @fread($this->fp, 5)) {
            if (!feof($this->fp)) {
                throw new marcException("reading error");
            } else {
                return false;

//                throw new marcException("reading beyond end of medium");
            }
        }

        if ($marcLength[0] == $this->substitute || $marcLength[0] == "\n") {
            return false;
        }
        if ($marcLength < 40) {
            throw new marcException("wrong marcLength:$marcLength\n");
        }

//        echo "marcLength:$marcLength \n";
        $xx = $marcLength - 5;
//        echo "marcLength:" . $xx . "\n";
        // af en eller anden grund vil php læse ligeså meget som jeg beder den om
        // derfor denne lidt mærkelige konstruktion!
        $yy = $xx;
        $rest = "";
        while ($yy) {
            if (!$rest .= @fread($this->fp, $yy)) {
                throw new marcException("reading error - something is missing?");
            }
            $yy = $xx - strlen($rest);
            if ($yy < 0) {
                throw new marcException("reading error - Shit");
            }
//            echo "xx:$xx, yy:$yy\n";
        }
//        echo "strlen(rest):" . strlen($rest) . "\n";
        $this->fromIso($marcLength . $rest);

        return true;
    }

    /** \brief
     * -
     * @param $isofile resource
     * @retval boolean
     * @throws marcException to many results
     */
    function openMarcFile($isofile) {
        if (is_resource($isofile)) {
            if (get_resource_type($isofile) == 'stream') {
                $this->fp = $isofile;
                return true;
            }
            return false;
        }
        if (!$this->fp = fopen($isofile, "r")) {
            throw new marcException("Error while opening file:$isofile");
        }
//$this->readNextMarc();
    }

    /** \brief insert a subfield.
     * If no field exist, make one.
     * If one exist add the subfield to the field.
     * Default '00' to indicators.
     *
     * @param $data string - (the data going into the marc-field)
     * @param $field string - (the field. ex. '032')
     * @param $subfield char - ( the subfield ex. 'a')
     * @param $indicators string - (the indicators ex. '01' )
     */
    function insert_subfield($data, $field, $subfield, $indicators = '00') {
        $found = false;
        foreach ($this->marc_array as $key => $value) {
            if ($value['field'] == $field) {
                $found = true;
                break;
            }
        }
        if (!$found) {
// make an empty field
            $newfield = array();
            $newfield['field'] = $field;
            $newfield['indicator'] = $indicators;
            $subfields = array();
            $subfields[] = $subfield . $data;
            $newfield['subfield'] = $subfields;
            $this->insert($newfield);
        } else {
            $this->marc_array[$key]['subfield'][] = $subfield . $data;
        }
    }

    function insert_field($data, $field, $subfield, $indicators = '00') {
        $newfield = array();
        $newfield['field'] = $field;
        $newfield['indicator'] = $indicators;
        $subfields = array();
        $subfields[] = $subfield . $data;
        $newfield['subfield'] = $subfields;
        $this->insert($newfield);
    }

    /** \brief
     * -
     * @param $field_array array
     */
    function insert($field_arrays) {
// find where to insert
        if (!$field_arrays) {
            return true;
        }
        if (array_key_exists('field', $field_arrays)) {
            $x[] = $field_arrays;
            $field_arrays = $x;
        }
        foreach ($field_arrays as $field_array) {
            $this->position = count($this->marc_array);
            if (!empty($this->marc_array)) {
                foreach ($this->marc_array as $key => $value) {
                    if ($value['field'] > $field_array['field']) {
                        $this->position = $key;
                        break;
                    }
                }

                $this->marc_array[] = array();
                for ($cnt = count($this->marc_array) - 1; $cnt && ($cnt >= $this->position); $cnt--) {
                    $this->marc_array[$cnt] = $this->marc_array[$cnt - 1];
                }
            }
            $this->marc_array[$this->position] = $field_array;
            $this->position++;
        }
    }

    /** \brief
     * -
     * @param $marcln string
     */
    function fromString($marcln) {

        if (is_string($marcln)) {
            $marcln = explode("\n", $marcln);
        }
        $this->marc_array = array();
        foreach ($marcln as $ln) {
            if (strlen($ln) < 2) {
                continue;
            }
            $this->field = array();
            $this->field['field'] = substr($ln, 0, 3);
            $this->field['indicator'] = substr($ln, 4, 2);
            $sln = str_replace('@*', chr(1), substr($ln, 7));
//            $sln = substr($ln, 7);
            $this->field['subfield'] = explode("*", $sln);
            foreach ($this->field['subfield'] as $key => $value) {
                $this->field['subfield'][$key] = str_replace(chr(1), '@*', $value);
            }
            $this->marc_array[] = $this->field;
        }
    }

    /** \brief
     * -
     * @param $isomarc string
     * @retval array
     */
    function fromIso($isomarc) {
        $this->marc_array = array();

        $fld = explode($this->fieldTerminator, $isomarc);
        $dummy = array_pop($fld);
//print_r($fld);

        $indx = 0;
        $fldno = '000';
        foreach ($fld as $field) {
            $subfield = explode($this->delimiter, $field);
            $marcar1 = array();
            $marcar1['field'] = $fldno;
            if ($fldno == '000') {
                $marcar1['indicator'] = "";
                $marcar1['subfield'][] = substr(array_shift($subfield), 0, 24);
            } else {
                $marcar1['indicator'] = array_shift($subfield);
                $marcar1['subfield'] = $subfield;
            }
            $this->marc_array[] = $marcar1;
            $fldno = substr($isomarc, 24 + ($indx++ * 12), 3);
        }
//print_r($this->marc_array);
        return ($this->marc_array);
    }

    /** \brief
     * -
     * @retval array - with all the fields
     */
    function getArray() {
        return $this->marc_array;
    }

    /** \brief
     * -
     * @param $marcar array
     */
    function fromArray($marcar) {
        $this->marc_array = $marcar;
        return;
    }

    /** \brief
     * -
     * @retval string The marc record as lineformat
     */
    function toLineFormat($linelength = 0) {
        $strng = "";
        if ($linelength != 0) {
            $spc = ' ';
        } else {
            $spc = '';
        }
//        $spc = '';
        foreach ($this->marc_array as $field) {
            if ($field['field'] == '000') {
                continue;
            }
            $ln = '';
            $ln .= $field['field'] . " " . $field['indicator'] . $spc;
            foreach ($field['subfield'] as $subfield) {
                $ln .= "*" . $subfield;
            }
            if ($linelength) {
                $first = true;
                while (strlen($ln) > $linelength) {
                    if ($first) {
                        $strng .= substr($ln, 0, $linelength) . "\n";
                        $ln = substr($ln, $linelength);
                        $first = false;
                    } else {
                        $strng .= '    ' . substr($ln, 0, $linelength - 4) . "\n";
                        $ln = substr($ln, $linelength - 4);
                    }
                }
                if (strlen($ln)) {
                    if ($first) {
                        $strng .= $ln . "\n";
                    } else {
                        $strng .= '    ' . $ln . "\n";
                    }
                }
            } else {
                $strng .= $ln . "\n";
            }
        }
        if ($linelength) {
            return $strng . "$\n";
        } else {
            return $strng;
        }
    }

    /** \brief
     * -
     * @retval integer
     */
    function isoSize() {
        $total = 0;
        foreach ($this->marc_array as $field) {
            if ($field['field'] == '000') {
                continue;
            }
            $lngth = strlen($field['indicator']);
            if ($lngth > 9998) {
                echo "subfield for stor\n";
            }
            foreach ($field['subfield'] as $subfield) {
                $lngth += strlen($subfield) + 1;
            }
            $total += $lngth + 13;
        }
        $total += 26;
        return $total;
    }

    /** \brief
     * -
     */
    function to88591() {
//        print_r($this->marc_array);
        foreach ($this->marc_array as $fieldkey => $field) {
            foreach ($field['subfield'] as $subfieldkey => $subfield) {
//                echo $this->marc_array[$fieldkey]['subfield'][$subfieldkey] . "\n";
                $this->marc_array[$fieldkey]['subfield'][$subfieldkey] = utf8_decode($this->marc_array[$fieldkey]['subfield'][$subfieldkey]);
            }
        }
    }

    /** \brief
     * -
     * @retval string
     */
    function toIso() {
        $headinfo = "name 22";

        foreach ($this->marc_array as $field) {
            if ($field['field'] == '004') {
                foreach ($field['subfield'] as $subfield) {
                    if ($subfield[0] == 'r') {
                        if (strlen($subfield) > 1) {
                            $headinfo[0] = $subfield[1];
                        }
                    }
                    if ($subfield[0] == 'a') {
                        if (strlen($subfield) > 1) {
                            $headinfo[3] = $subfield[1];
                        }
                    }
                }
            }
            if ($field['field'] == '008') {
                foreach ($field['subfield'] as $subfield) {
                    if ($subfield[0] == 't') {
                        if (strlen($subfield) > 1) {
                            $headinfo[2] = $subfield[1];
                        }
                    }
                }
            }
            if ($field['field'] == '009') {
                foreach ($field['subfield'] as $subfield) {
                    if ($subfield[0] == 'a') {
                        if (strlen($subfield) > 1) {
                            $headinfo[1] = $subfield[1];
                        }
                    }
                }
            }
        }
        $total = 0;
        $cntfields = 0;
        $adrss = "";
        $data = "";
        if (count($this->marc_array) == 0) {
            throw new marcException("Empty array");
        }
        foreach ($this->marc_array as $field) {
//echo "field:" . $field['field'] . "\n";
            if ($field['field'] == '000') {
//        $headinfo = substr($field['subfield'][0], 5, 7);
                continue;
            }
            $cntfields++;
            $data .= $field['indicator'];
            $lngth = strlen($field['indicator']);
            if (count($field['subfield']) == 0) {
                throw new marcException("500 empty subfileds");
            }
            foreach ($field['subfield'] as $subfield) {
                $lngth += strlen($subfield) + 1;
                $data .= $this->delimiter . $subfield;
            }
            $lngth++;
            $data .= $this->fieldTerminator;
            if ($lngth > 9999) {
                throw new marcException("Subfield greater than 9999 characters (\$lngth:$lngth)");
            }
            $adrss .= $field['field'] . substr($lngth + 10000, 1, 4) . substr($total + 100000, 1, 5);
            $total += $lngth;
        }
        $adrss .= $this->fieldTerminator;
        $total += 25 + strlen($adrss);
        $adrslngth = 25 + $cntfields * 12;
        $head = substr($total + 100000, 1, 5) . $headinfo .
            substr($adrslngth + 100000, 1, 5) . "   45  ";
        return $head . $adrss . $data . $this->endOfRecord;
    }

    /**
     * @return bool
     *
     * TEST WHETER A MARC RECORD HAS A POINTER TO A "LEKTØR UDTALELSE"
     */
    function HasLektoer($incl_choice = false) {
        $choice = '';
        $ret = false;
        $f990s = $this->findSubFields('990', 'b');
        foreach ($f990s as $f990) {
            $choice .= strtoupper($f990) . ' ';
            if (strtolower($f990) == 'l') {
                $ret = true;
            }
        }
        $fd90s = $this->findSubFields('d90', 'b');
        foreach ($fd90s as $fd90) {
            $choice .= strtoupper($fd90) . ' ';
            if (strtolower($fd90) == 'l') {
                $ret = true;
            }
        }
        $f06s = $this->findSubFields('f06', 'b');
        foreach ($f06s as $f06) {
            $choice .= strtoupper($f06) . ' ';
            if (strtolower($f06) == 'l') {
                $ret = true;
            }
        }
        if ($incl_choice) {
            return array('status' => $ret, 'choice' => $choice);
        } else {
            return $ret;
        }
    }

    function concat(marc $hsb) {
        foreach ($hsb->marc_array as $newf) {
            if ($newf['field'] != '000') {
                $this->marc_array[] = $newf;
            }
        }
    }


    function changeOrderSubfields($fieldName, $order) {
        foreach ($this->marc_array as $key => $value) {
            if ($value['field'] == $fieldName) {
                $oldSub = $value['subfield'];
                $newSub = array();
                for ($i = 0; $i < strlen($order); $i++) {
                    $s = substr($order, $i, 1);
                    foreach ($oldSub as $k => $o) {
                        if ($s == substr($o, 0, 1)) {
                            $newSub[] = $o;
                            $oldSub[$k] = '@'; // a subfield code I hope dosen't exsist
                        }
                    }
                }
                foreach ($oldSub as $o) {
                    if (substr($o, 0, 1) != '@') {
                        $newSub[] = $o;
                    }
                }
                $this->marc_array[$key]['subfield'] = $newSub;
            }
        }
    }

    function fromMarcExchange($xml) {

        $strng = '';
        $dom = new DOMDocument($xml);
        $dom->loadXML($xml);
//        foreach ($dom->getElementsByTagNameNS('info:lc/xmlns/marcxchange-v1', '*') as $element) {
//            echo 'local name: ', $element->localName, ', prefix: ', $element->prefix, "\n";
//        }
        foreach ($dom->getElementsByTagName('*') as $element) {
//            echo 'local name: ', $element->localName, ', prefix: ', $element->prefix, "\n";
            if ($element->localName == 'datafield') {
                $field = $element->getAttribute('tag');
                $indk1 = $element->getAttribute('ind1');
                $indk2 = $element->getAttribute('ind2');
                $strng .= "\n" . $field . ' ' . $indk1 . $indk2;
            }
            if ($element->localName == 'subfield') {
                $subfield = $element->getAttribute('code');
                $data = $element->nodeValue;
                $strng .= '*' . $subfield . $data;
            }
        }
        $strng = utf8_decode(ltrim($strng, "\n"));
//        echo "\n----$strng\n----\n";
        $this->fromString($strng);
    }

    function merge191919(marc $marc191919) {

        // this method merge a 870970 record and a 191919 record from rawrepo.
        // The data is altered in order to sent it back to Basis without syntax errors.
        $arr = $marc191919->getArray();
        foreach ($arr as $f) {
            if ($f['field'] == '001' or $f['field'] == '004') {
                continue;
            }
            $this->insert($f);
        }
        $f = $this->findSubFields('001', 'a', 1);
        $f = substr($f, 0, 1) . ' ' . substr($f, 1, 3) . ' ' . substr($f, 4, 3) . ' ' . substr($f, 7, 1);
        $this->substitute('001', 'a', $f);
        $f = $this->findSubFields('f07', 'a', 1);
        if ($f) {
            $f = substr($f, 0, 1) . ' ' . substr($f, 1, 3) . ' ' . substr($f, 4, 3) . ' ' . substr($f, 7, 1);
            $this->substitute('f07', 'a', $f);
        }
        $f996 = $this->findSubFields('996', 'a', 1);
        $this->remField('996');
        $this->insert_subfield($f996, 's10', 'a');
    }
}

?>