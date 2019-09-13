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
 * @file LibV3API_class.php
 * @brief 1. version can only fetch a record from a libV3 database
 * by "lokalid" and "bibliotek"
 *
 * @author Hans-Henrik Lund
 *
 * @date 11-07-2012
 */
//$startdir = dirname(__FILE__);
//$inclnk = $startdir . "/../inc";

require_once 'marc_class.php';
require_once "verbose_class.php";
require_once "oci_class.php";

class LibV3API {

//  private $oci;
    private $withAuthor;
    private $hsb;
    private $return;
    private $oci;
    private $PhusOci;
    private $BasisOci;

    function __construct($ociuser, $ocipasswd, $ocidatabase) {
        $this->oci = new oci($ociuser, $ocipasswd, $ocidatabase);
        $this->oci->set_charset('WE8ISO8859P1');
        $this->oci->connect();
        $this->BasisOci = $this->oci;
    }

    function setPhusLogin($ociuser, $ocipasswd, $ocidatabase) {
        $this->PhusOci = new oci($ociuser, $ocipasswd, $ocidatabase);
        $this->PhusOci->set_charset('WE8ISO8859P1');
        $this->PhusOci->connect();
    }

    function close() {
        $this->BasisOci->disconnect();
        $this->PhusOci->disconnect();
    }

    /**
     *
     * @param type $name
     * @param type $value
     *
     * set various parameters
     * withAuthor: the 100/770 field in the record will be data from the authority database.
     * hsb:  head bind section. all records (from this and up) will be fetched
     *
     */
    function set($name, $value = true) {
        switch ($name) {
        case 'withAuthor' :
            $this->withAuthor = $value;
            break;
        case 'hsb':
            $this->hsb = $value;
            break;
        case 'Basis' :
            $this->oci = $this->BasisOci;
            break;
        case 'Phus' :
            $this->oci = $this->PhusOci;
        }
    }

    function getDBtime() {
        $sql = "select to_char(current_timestamp,'DDMMYYYY HH24MISS') tid "
            . "from dual";
        $rows = $this->oci->fetch_all_into_assoc($sql);
        $tid = $rows[0]['TID'];
        return $tid;
    }

    function getLekUpdates($lastupdated) {
//        $f001d = 'd' . substr($lastupdated, 0, 8);
//        $sql = "select lokalid, to_char(ajourdato,'DDMMYYYY HH24MISS') as dato from poster
//               where data like '%$f001d%'
//               and bibliotek = '870976'
//               and lokalid like '3%'
//          UNION
        $sql = "select lokalid, to_char(ajourdato,'DDMMYYYY HH24MISS') as dato from poster
           where ajourdato > to_timestamp('$lastupdated','DDMMYYYY HH24MISS')
           and bibliotek = '870976'
           and lokalid like '3%' 
           order by ajourdato desc
           ";
        $updates = $this->oci->fetch_all_into_assoc($sql);
        return $updates;
    }

    /**
     *
     * @param type $lastupdate
     *
     * en forbedret udgave:
     */
    function getUpdatedRecs($lastupdated) {
//        $sql = "select lokalid, to_char(ajourdato,'DDMMYYYY HH24MISS') as dato from poster "
//                . "where ajourdato > to_date('$lastupdated','DDMMYYYY HH24MISS') "
//                . "and bibliotek = '870970'  "
//                . "and (lokalid like '2%' or lokalid like '5%')"
//                . "order by ajourdato ";

        $sql = " select lokalid, to_char(ajourdato,'DDMMYYYY HH24MISS') as dato from poster
      where ajourdato > to_timestamp('$lastupdated','DDMMYYYY HH24MISS')
      and bibliotek = '870970'
      and ( lokalid like '2 %' or lokalid like '5 %') ";
//      and ( data like '%xERL%')";
//         and (data like '%xNLY%' or data like '%xERE%' or data like '%xERL%')"
//        echo "sql:$sql\n";
        $updates = $this->oci->fetch_all_into_assoc($sql);
        return $updates;
    }

    /**
     *
     * @param string with iso2709 record $data
     * @return iso2709 type
     *
     * test wether there is a subfield '5' and/or '6' in field
     * 100/700.  If so replace data with the data fra the
     * authority record.
     */
    function insertAuthors($data) {
        $marc = new marc();
        $marc->fromIso($data);
        $ln = $marc->toLineFormat();
        $fields = array('100', '700');
        foreach ($fields as $field) {
            $bib = $lokalid = $fcode = "";
            while ($marc->thisField($field)) {

                while ($marc->thisSubfield('5')) {
                    $bib = $marc->subfield();
                }
                while ($marc->thisSubfield('6')) {
                    $lokalid = $marc->subfield();
                }
                if ($bib && $lokalid) {
                    $autMarcs = $this->getMarcByLB($lokalid, $bib);
                    $autmarc = new marc();
                    $autmarc->fromIso($autMarcs[0]['DATA']);
                    $lns = $autmarc->toLineFormat();
                    $afields = array('100', '400');
                    $res = null;

                    foreach ($afields as $afield) {
                        if ($res != null) {
                            break;
                        }
                        $res = $autmarc->findFields($afield);
                        if ($res) {
//                            $ln = $marc->toLineFormat();
                            foreach ($res as $rec) {
                                $rec['field'] = $field;
                                while ($marc->thisSubfield('4')) {
                                    $rec['subfield'][] = '4' . $marc->subfield();
                                }
                                $marc->updateField($rec);
                            }
//                            $ln = $marc->toLineFormat();
                        }
                    }
                }
            }
        }
        $data = $marc->toIso();
        return $data;
    }

    function fetchHSB($data) {
        $marc = new marc();
        $marc->fromIso($data);
        $lokalid = false;
        $result = array();
        while ($marc->thisField('014')) {
            while ($marc->thisSubfield('a')) {
                $lokalid = $marc->subfield();
            }
        }
        if ($lokalid) {
            $bib = $marc->findSubFields('001', 'b');
            $result['bibliotek'] = $bib[0];
            $result['lokalid'] = $lokalid;
        }
        return $result;
    }

    function getMarcByDanbibid($danbibid, $bibliotek) {
        $lokalid = '';
        $where = "where danbibid = $danbibid and bibliotek = '$bibliotek' ";
        return $this->getMarcByLokalidBibliotek($lokalid, $bibliotek, $where);
    }

    function getMarcById($id) {
        $where = "where id = $id";
        return $this->getMarcByLokalidBibliotek($lokalid, $bibliotek, $where);
    }

    function getIdsByLokalidBibliotek($lokalid, $bibliotek) {
        $ids = array();
        $select = "select id from poster where lokalid = '$lokalid' and "
            . "bibliotek = '$bibliotek' ";
        $res = $this->oci->fetch_all_into_assoc($select);
        foreach ($res as $id) {
            $ids[] = $id['ID'];
        }
        return $ids;
    }

    function getMarcByLokalidBibliotek($lokalid, $bibliotek, $wh = "") {
        $this->return = array();
//        $pointer017 = true;
//        while ($pointer017) {
        $result = $this->getMarcByLB($lokalid, $bibliotek, $wh);
        if (!$result) {
            return $result;
        }
//            $m = new marc();
//            $m->fromIso($result[0]['DATA']);
//            $f017s = $m->findSubFields('017', 'a');
//            if (!$f017s) {
//                $pointer017 = false;
//            } else {
//                $lokalid = $f017s[0];
//            }
//        }
        $this->return[] = $result[0];
        if ($this->hsb) {
            $m = new marc();
            $n = new marc();
            $m->fromIso($result[0]['DATA']);
//            $strng = $m->toLineFormat();
            $res = $this->fetchHSB($result[0]['DATA']);
            while ($res) {
                $result = $this->getMarcByLB($res['lokalid'], $res['bibliotek']);
                $this->return[] = $result[0];
                $res = $this->fetchHSB($result[0]['DATA']);
            }
        }

        if ($this->withAuthor) {
            for ($i = 0; $i < count($this->return); $i++) {
                $this->return[$i]['DATA'] = $this->insertAuthors($this->return[$i]['DATA']);
            }
        }

        return $this->return;
    }


    function getMarcByLB($lokalid, $bibliotek, $wh = "", $base = 'Basis') {

        if (strlen($lokalid) == 8 and $bibliotek == '870970') {
            $lokalid = substr($lokalid, 0, 1) . ' ' . substr($lokalid, 1, 3) . ' ' . substr($lokalid, 4, 3) . ' ' . substr($lokalid, 7, 1);
        }
        $this->set($base);
        if ($wh) {
            $where = $wh;
        } else {
            $where = "where lokalid   = '$lokalid' and bibliotek = '$bibliotek'";
        }

        $sql = "select to_char(ajourdato, 'YYYYMMDD HH24MISS')ajour, to_char(opretdato, 'YYYYMMDD HH24MISS')opret,
                id,
                danbibid,
                lokalid,
                bibliotek,
                data
                from poster
                $where ";

        $result = $this->oci->fetch_all_into_assoc($sql);
        if (count($result) == 0)
            return $result;
        $data = $result[0]['DATA'];
        $id = $result[0]['ID'];
        $marclngth = substr($data, 0, 5);
        if ($marclngth > 4000) {
            $sql = "select data from poster_overflow "
                . "where id = $id order by lbnr";
            $overflow = $this->oci->fetch_all_into_assoc($sql);
            foreach ($overflow as $record) {
                $data .= $record['DATA'];
            }
            $result[0]['DATA'] = $data;
        }

        return $result;
    }

    function getLekNoViaRel($lokalid, $bibliotek) {
        $lokalid = str_replace(' ', '', $lokalid);
        $sql = "select * from poster where id in ( "
            . "select fromid from postrelationer where toid in ( "
            . "select id from poster "
            . "where lokalid = '$lokalid' and bibliotek = '$bibliotek')) "
            . "order by lokalid";
        $sql = "select fromid from postrelationer r, poster p
                  where id = toid
                  and lokalid = '$lokalid' and bibliotek = '$bibliotek'";
        $rows = $this->oci->fetch_all_into_assoc($sql);
        $result = array();
        if ($rows) {
            foreach ($rows as $row) {
                $res = $this->getMarcByDanbibid($row['FROMID'], $bibliotek);
                if ($res) {
                    $result[] = $res[0];
                }

            }
        }

        return $result;
    }

    /**
     * @param $ts ts will be a string on formated 'YYYY-MM-DD HH24:MI:SS'
     * Return an array of database items
     */
    function getUpdatedSince($ts) {
        $f = 'YYYY-MM-DD HH24:MI:SS';
        $sql = "select lokalid, bibliotek, 
                to_char(opretdato,'$f') as opretdato,
                to_char(ajourdato,'$f') as ajourdato
                from poster
               where ajourdato >  to_date('$ts','$f') ";
        $arr = $this->BasisOci->fetch_all_into_assoc($sql);
        return $arr;
    }
}
