<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('solr_query_class.php');

class TestOfSolrQueryClass extends UnitTestCase {
  private $c2s;

  function __construct() {
    parent::__construct();
    $this->c2s = new SolrQuery(array('cql_settings' => $this->cql_def()));
    $this->c2s->phrase_index = array('dkcclphrase', 'phrase', 'facet');
    //$this->c2s->best_match = TRUE;
  }

  function __destruct() { 
  }

  function test_instantiation() {
    $this->assertTrue(is_object($this->c2s));
  }

  function test_basal() {
    $tests = array('et' => 'et');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_bool() {
    $tests = array('et and to' => 'et AND to',
                   'et AND to' => 'et AND to',
                   'and AND and' => 'and AND and',
                   'et or to' => '(et OR to)',
                   'et OR to' => '(et OR to)',
                   'et AND to OR tre' => '((et AND to) OR tre)',
                   'et AND to OR tre AND fire' => '((et AND to) OR tre) AND fire',
                   'et to OR tre fire' => array(array('no' => 10, 'description' => 'Query syntax error', 'details' => '', 'pos' => 5)),
                   '(et AND to) OR tre' => '((et AND to) OR tre)',
                   'et AND (to OR tre)' => 'et AND (to OR tre)',
                   '(et AND to' => array(array('no' => 13, 'description' => 'Invalid or unsupported use of parentheses', 'details' => '', 'pos' => 10)),
                   'et AND to)' => array(array('no' => 10, 'description' => 'Query syntax error', 'details' => '', 'pos' => 10)));
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_string() {
    $tests = array('"karen blixen"' => '"karen blixen"~9999',
                   '"karen AND blixen"' => '"karen AND blixen"~9999',
                   '"karen and blixen"' => '"karen and blixen"~9999');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_simple_field() {
    $tests = array('dkcclphrase.cclphrase=en' => 'dkcclphrase.cclphrase:en',
                   'dkcclphrase.cclphrase="en to"' => 'dkcclphrase.cclphrase:en\ to',
                   'dkcclphrase.cclphrase=en AND to' => 'dkcclphrase.cclphrase:en AND to',
                   'phrase.phrase=en' => 'phrase.phrase:en',
                   'phrase.phrase=en to' => array(array('no' => 10, 'description' => 'Query syntax error', 'details' => '', 'pos' => 19)),
                   'phrase.phrase=en AND to' => 'phrase.phrase:en AND to',
                   'dkcclterm.cclterm=en' => 'dkcclterm.cclterm:en',
                   'dkcclterm.cclterm="en to"' => 'dkcclterm.cclterm:"en to"~9999',
                   'dkcclterm.cclterm=en AND to' => 'dkcclterm.cclterm:en AND to',
                   'dkcclterm.cclterm=en OR to' => '(dkcclterm.cclterm:en OR to)',
                   'dkcclterm.cclterm=(en OR to)' => '(dkcclterm.cclterm:en OR dkcclterm.cclterm:to)',
                   'facet.facet=en' => 'facet.facet:en',
                   'facet.facet=en to' => array(array('no' => 10, 'description' => 'Query syntax error', 'details' => '', 'pos' => 17)),
                   'term.term=en' => 'term.term:en',
                   'term.term=en to' => array(array('no' => 10, 'description' => 'Query syntax error', 'details' => '', 'pos' => 15)),
                   'term.term=en AND to' => 'term.term:en AND to',
                   'term.term=en OR to' => '(term.term:en OR to)',
                   'term.term=(en OR to)' => '(term.term:en OR term.term:to)',
                   'phrase.xxx=to' => array(array('no' => 16, 'description' => 'Unsupported index', 'details' => 'phrase.xxx', 'pos' => 13)),
                   'xxx.term=to' => array(array('no' => 16, 'description' => 'Unsupported index', 'details' => 'xxx.term', 'pos' => 11)),
                   'facet.xxx=to' => array(array('no' => 16, 'description' => 'Unsupported index', 'details' => 'facet.xxx', 'pos' => 12)),
                   'term.xxx=to' => array(array('no' => 16, 'description' => 'Unsupported index', 'details' => 'term.xxx', 'pos' => 11)));
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_relation_adjacency() {
    $tests = array('dkcclphrase.cclphrase adj "en to"' => 'dkcclphrase.cclphrase:en\ to',
                   'dkcclphrase.cclphrase adj "en to tre"' => 'dkcclphrase.cclphrase:en\ to\ tre',
                   'term.term adj "en to"' => 'term.term:"en to"~0',
                   'term.term adj "en to tre"' => 'term.term:"en to tre"~0');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_relation_any() {
    $tests = array('term.term any en' => 'term.term:en',
                   'term.term any "en"' => 'term.term:en',
                   'term.term any "   en  "' => 'term.term:en',
                   'term.term any "en to tre"' => 'term.term:(en OR to OR tre)',
                   'term.term any "  en   to   tre  "' => 'term.term:(en OR to OR tre)');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_relation_all() {
    $tests = array('term.term all en' => 'term.term:en',
                   'term.term all "en"' => 'term.term:en',
                   'term.term all "   en  "' => 'term.term:en',
                   'term.term all "en to tre"' => 'term.term:(en AND to AND tre)',
                   'term.term all "  en   to   tre  "' => 'term.term:(en AND to AND tre)');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_relation_modifier_relevant() {
    $tests = array('slop=/relevant karen' => 'term.slop:karen',
                   'slop=/relevant "karen"' => 'term.slop:karen',
                   'slop=/relevant "karen blixen"' => 'term.slop:"karen blixen"~5');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_relation_modifier_word() {
    $tests = array('slop=/word karen' => 'term.slop:karen',
                   'slop=/word "karen"' => 'term.slop:karen',
                   'slop=/word "karen blixen"' => 'term.slop:"karen blixen"~9999');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_relation_modifier_string() {
    $tests = array('slop=/string karen' => 'term.slop:karen',
                   'slop=/string "karen"' => 'term.slop:karen',
                   'slop=/string "karen blixen"' => 'term.slop:"karen blixen"~0');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_quoted_parenthesis() {
    $tests = array('dkcclphrase.cclphrase = "karen blixen (f. 1885)"' => 'dkcclphrase.cclphrase:karen\ blixen\ \(f.\ 1885\)');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_quote() {
    $tests = array('dkcclphrase.cclphrase = "karen \"blixen\" 1885"' => 'dkcclphrase.cclphrase:karen\ \"blixen\"\ 1885',
                   'dkcclterm.cclterm = "karen \"blixen\" 1885"' => 'dkcclterm.cclterm:"karen \"blixen\" 1885"~9999');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_interval() {
    $tests = array('dkcclphrase.cclphrase < en' => 'dkcclphrase.cclphrase:[* TO en}',
                   'dkcclphrase.cclphrase > en' => 'dkcclphrase.cclphrase:{en TO *]',
                   'dkcclphrase.cclphrase <= en' => 'dkcclphrase.cclphrase:[* TO en]',
                   'dkcclphrase.cclphrase >= en' => 'dkcclphrase.cclphrase:[en TO *]',
                   'dkcclterm.cclterm < en ' => 'dkcclterm.cclterm:[* TO en}',
                   'dkcclterm.cclterm > en' => 'dkcclterm.cclterm:{en TO *]',
                   'dkcclterm.cclterm <= en' => 'dkcclterm.cclterm:[* TO en]',
                   'dkcclterm.cclterm >= en' => 'dkcclterm.cclterm:[en TO *]',
                   'dkcclterm.cclterm >= "en"' => 'dkcclterm.cclterm:["en" TO *]');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_complex() {
    $tests = array('facet.facet="karen blixen" AND term.term=bog' => 'facet.facet:karen\ blixen AND term.term:bog');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_slop() {
    $tests = array('term.slop=karen' => 'term.slop:karen',
                   'term.slop="karen"' => 'term.slop:karen',
                   'term.slop="karen blixen"' => 'term.slop:"karen blixen"~10');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_alias() {
    $tests = array('slop=karen' => 'term.slop:karen',
                   'slop="karen"' => 'term.slop:karen',
                   'slop="karen blixen"' => 'term.slop:"karen blixen"~5');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_filter() {
    $tests = array('term.filter=filter' => '*',
                   'term.filter=filter and term.term="no filter"' => 'term.term:"no filter"~9999');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_trunkation() {
    $tests = array('term.term=karen*' => 'term.term:karen*',
                   'term.term="karen*"' => 'term.term:karen*', 
                   'term.term=(karen* AND wulf)' => 'term.term:karen* AND term.term:wulf',
                   'term.term="karen* wulf"' => 'term.term:(karen* wulf)',
                   'term.term="karen\\* wulf"' => 'term.term:"karen\\* wulf"~9999');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_masking() {
    $tests = array('term.term=kar?n' => 'term.term:kar?n',
                   'term.term="kar?n"' => 'term.term:kar?n',
                   'term.term="kar?n wulf"' => 'term.term:(kar?n wulf)');
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_bestmatch() {
    $tests = array('term.term="karen blixen"' => array('t0' => 'term.term:karen', 
                                                       't1' => 'term.term:blixen', 
                                                       'sort' => 'sum(query($t0,50),query($t1,50)) asc'),
                   'term.term="  karen  amalie  blixen  "' => array('t0' => 'term.term:karen', 
                                                                    't1' => 'term.term:amalie', 
                                                                    't2' => 'term.term:blixen', 
                                                                    'sort' => 'sum(query($t0,33),query($t1,33),query($t2,33)) asc'));
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send, TRUE), $recieve);
    }
  }

  function test_empty_string() {
    $tests = array('""'                        => array(array('no' => 27, 'description' => 'Empty term unsupported', 'details' => '', 'pos' => 1)),
                   'term.filter=filter and ""' => array(array('no' => 27, 'description' => 'Empty term unsupported', 'details' => '', 'pos' => 24)));
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_errors() {
    $tests = array('en prox/unit=word/distance=2 to' => array(array('no' => 37, 'description' => 'Unsupported boolean operator', 'details' => 'prox', 'pos' => 7)),
                   'en prox/unit=woord/distance=2 to' => array(array('no' => 37, 'description' => 'Unsupported boolean operator', 'details' => 'prox', 'pos' => 7), 
                                                               array('no' => 42, 'description' => 'Unsupported proximity unit', 'details' => 'woord', 'pos' => 18))
                  ); 
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function test_many_operands() {
    for ($i = 0; $i < 101; $i++) {
      if ($i) {
        $prn .= '(';
        $opr[] = 'opr' . $i;
        $res[] = 'opr' . $i . ')';
      }
      else {
        $opr[] = 'opr' . $i;
        $res[] = 'opr' . $i;
      }
    }
    $tests = array(implode(' or ', $opr) => $prn . implode(' OR ', $res),
                  implode(' and ', $opr) => implode(' AND ', $opr));
    foreach ($tests as $send => $recieve) {
      $this->assertEqual($this->get_edismax($send), $recieve);
    }
  }

  function get_edismax($cql, $bestmatch = FALSE) {
    $help = $this->c2s->parse($cql);
    if (isset($help['error'])) {
      return $help['error'];
    }
    if ($bestmatch) {
      return $help['best_match']['sort'];
    }
    elseif (isset($help['edismax']['q'])) {
      return implode(' AND ', $help['edismax']['q']);
    }
    return 'no reply';
  }

  function cql_def() {
    return
'<explain>
  <indexInfo>
   <set identifier="info:srw/cql-context-set/1/cql-v1.1" name="cql"/>
   <set identifier="http://oss.dbc.dk/ns/dkcclphrase" name="dkcclphrase"/>
   <set identifier="http://oss.dbc.dk/ns/phrase" name="phrase"/>
   <set identifier="http://oss.dbc.dk/ns/dkcclterm" name="dkcclterm"/>
   <set identifier="http://oss.dbc.dk/ns/term" name="term"/>
   <set identifier="http://oss.dbc.dk/ns/facet" name="facet"/>
   <index><map><name set="cql">serverChoice</name></map></index>
   <index><map><name set="dkcclphrase">cclphrase</name></map></index>
   <index><map><name set="phrase">phrase</name></map></index>
   <index><map><name set="dkcclterm">cclterm</name></map></index>
   <index><map><name set="term" filter="1">filter</name></map></index>
   <index><map><name set="term">term</name></map></index>
   <index><map><name set="term" slop="10">slop</name>
               <alias set="" slop="5">slop</alias></map></index>
   <index><map><name set="facet">facet</name></map></index>
  </indexInfo>
  <configInfo>
   <supports type="danBooleanModifier">og</supports>
   <supports type="danBooleanModifier">eller</supports>
   <supports type="danBooleanModifier">ikke</supports>
   <supports type="engBooleanModifier">and</supports>
   <supports type="engBooleanModifier">or</supports>
   <supports type="engBooleanModifier">not</supports>
   <supports type="relation">&gt;=</supports>
   <supports type="relation">&gt;</supports>
   <supports type="relation">&lt;=</supports>
   <supports type="relation">&lt;</supports>
   <supports type="relation">=</supports>
   <supports type="relation">adj</supports>
   <supports type="maskingCharacter">?</supports>
   <supports type="maskingCharacter">*</supports>
   <supports type="booleanChar">(</supports>
   <supports type="booleanChar">)</supports>
  </configInfo>
</explain>';
  }
}
?>
