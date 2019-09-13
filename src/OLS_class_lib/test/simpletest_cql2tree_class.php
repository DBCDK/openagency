<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 __DIR__ . '/../simpletest' . PATH_SEPARATOR .
                 __DIR__ . '/..');
require_once('simpletest/autorun.php');
require_once('cql2tree_class.php');


class TestOfCql2TreeClass extends UnitTestCase {
  private $c2t;
  private $cqlns;
  private $indexes;

  function __construct() {
    parent::__construct();
    $this->c2t = new CQL_parser();
    $this->c2t->set_prefix_namespaces(self::cqlns());
    $this->c2t->set_indexes(self::indexes());
  }

  function __destruct() { 
  }

  function test_instantiation() {
    $this->assertTrue(is_object($this->c2t));
  }

  function test_basal() {
    list($tree, $diags) = self::get_tree('test');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], 'test');
    $this->assertEqual($tree['field'], 'serverChoice');
    $this->assertEqual($tree['prefix'], 'cql');
    $this->assertEqual($tree['relation'], 'scr');
  }

  function test_bool() {
    list($tree, $diags) = self::get_tree('test and some');
    $this->assertEqual($tree['type'], 'boolean');
    $this->assertEqual($tree['op'], 'and');
    $this->assertEqual($tree['left']['term'], 'test');
    $this->assertEqual($tree['right']['term'], 'some');
    list($tree, $diags) = self::get_tree('test AND some');
    $this->assertEqual($tree['type'], 'boolean');
    $this->assertEqual($tree['op'], 'and');
    $this->assertEqual($tree['left']['term'], 'test');
    $this->assertEqual($tree['right']['term'], 'some');
  }

  function test_simple_field() {
    list($tree, $diags) = self::get_tree('dkcclphrase.cclphrase=test');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], 'test');
    $this->assertEqual($tree['field'], 'cclphrase');
    $this->assertEqual($tree['prefix'], 'dkcclphrase');
    $this->assertEqual($tree['fielduri'], $this->cqlns['dkcclphrase']);
    $this->assertEqual($tree['relation'], '=');
  }

  function test_adjacency() {
    list($tree, $diags) = self::get_tree('dkcclphrase.cclphrase ADJ "test some"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"test some"');
    $this->assertEqual($tree['field'], 'cclphrase');
    $this->assertEqual($tree['prefix'], 'dkcclphrase');
    $this->assertEqual($tree['fielduri'], $this->cqlns['dkcclphrase']);
    $this->assertEqual($tree['relation'], 'adj');
  }

  function test_interval() {
    list($tree, $diags) = self::get_tree('dkcclphrase.cclphrase<test');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], 'test');
    $this->assertEqual($tree['field'], 'cclphrase');
    $this->assertEqual($tree['prefix'], 'dkcclphrase');
    $this->assertEqual($tree['fielduri'], $this->cqlns['dkcclphrase']);
    $this->assertEqual($tree['relation'], '<');
  }

  function test_complex() {
    list($tree, $diags) = self::get_tree('facet.facet="karen blixen" AND term.term=bog');
    $this->assertEqual($tree['type'], 'boolean');
    $this->assertEqual($tree['op'], 'and');
    $this->assertEqual($tree['left']['type'], 'searchClause');
    $this->assertEqual($tree['left']['term'], '"karen blixen"');
    $this->assertEqual($tree['left']['field'], 'facet');
    $this->assertEqual($tree['left']['prefix'], 'facet');
    $this->assertEqual($tree['left']['fielduri'], $this->cqlns['facet']);
    $this->assertEqual($tree['left']['relation'], '=');
    $this->assertEqual($tree['right']['type'], 'searchClause');
    $this->assertEqual($tree['right']['term'], 'bog');
    $this->assertEqual($tree['right']['field'], 'term');
    $this->assertEqual($tree['right']['prefix'], 'term');
    $this->assertEqual($tree['right']['fielduri'], $this->cqlns['term']);
    $this->assertEqual($tree['right']['relation'], '=');
  }

  function test_quote() {
    list($tree, $diags) = self::get_tree('term.term="karen \"blixen\" 1885"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"karen \"blixen\" 1885"');
    $this->assertEqual($tree['slop'], 9999);
    list($tree, $diags) = self::get_tree('term.term="karen \"blixen 1885"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"karen \"blixen 1885"');
    $this->assertEqual($tree['slop'], 9999);
    list($tree, $diags) = self::get_tree('dkcclphrase.cclphrase="karen \"blixen\" 1885"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"karen \"blixen\" 1885"');
    $this->assertEqual($tree['slop'], 9999);
  }

  function test_slop() {
    list($tree, $diags) = self::get_tree('term.slop="karen blixen"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"karen blixen"');
    $this->assertEqual($tree['slop'], 10);
    list($tree, $diags) = self::get_tree('term.term="karen blixen"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"karen blixen"');
    $this->assertEqual($tree['slop'], 9999);
  }

  function test_alias() {
    list($tree, $diags) = self::get_tree('slop="karen blixen"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"karen blixen"');
    $this->assertEqual($tree['slop'], 5);
    $this->assertEqual($tree['field'], 'slop');
    $this->assertEqual($tree['prefix'], 'term');
    $this->assertEqual($tree['fielduri'], $this->cqlns['term']);
  }

  function test_errors() {
    list($tree, $diags) = self::get_tree('en prox/unit=word/distance=2 to');
    $this->assertEqual($diags[0]['no'], 37);
    $this->assertEqual($diags[0]['details'], 'prox');
    $this->assertEqual($diags[0]['pos'], '7');
    list($tree, $diags) = self::get_tree('ttt.xxx=test');
    $this->assertEqual($diags[0]['no'], 16);
    $this->assertEqual($diags[0]['details'], 'ttt.xxx');
    $this->assertEqual($diags[0]['pos'], '12');
  }

  function test_any_relation() {
    list($tree, $diags) = self::get_tree('cql.keywords any "code computer calculator programming"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"code computer calculator programming"');
    $this->assertEqual($tree['relation'], 'any');
    $this->assertEqual($tree['field'], 'keywords');
    $this->assertEqual($tree['prefix'], 'cql');
    $this->assertEqual($tree['fielduri'], $this->cqlns['cql']);
    $this->assertEqual($tree['modifiers'], array());
  }

  function test_all_relation() {
    list($tree, $diags) = self::get_tree('cql.keywords all "code computer calculator programming"');
    $this->assertEqual($tree['type'], 'searchClause');
    $this->assertEqual($tree['term'], '"code computer calculator programming"');
    $this->assertEqual($tree['relation'], 'all');
    $this->assertEqual($tree['field'], 'keywords');
    $this->assertEqual($tree['prefix'], 'cql');
    $this->assertEqual($tree['fielduri'], $this->cqlns['cql']);
    $this->assertEqual($tree['modifiers'], array());
  }

  function test_relation_modifier_relevant() {
    list($tree, $diags) = self::get_tree('cql.keywords =/relevant "code computer calculator programming"');
    $this->assertEqual($tree['relation'], '=');
    $this->assertEqual($tree['modifiers'], array('relevant' => TRUE));
    list($tree, $diags) = self::get_tree('cql.keywords any/relevant "code computer calculator programming"');
    $this->assertEqual($tree['relation'], 'any');
    $this->assertEqual($tree['modifiers'], array('relevant' => TRUE));
    list($tree, $diags) = self::get_tree('cql.keywords all/relevant "code computer calculator programming"');
    $this->assertEqual($tree['relation'], 'all');
    $this->assertEqual($tree['modifiers'], array('relevant' => TRUE));
  }

  function test_relation_modifier_word() {
    list($tree, $diags) = self::get_tree('cql.keywords =/word "code computer calculator programming"');
    $this->assertEqual($tree['relation'], '=');
    $this->assertEqual($tree['modifiers'], array('word' => TRUE));
  }

  function test_relation_modifier_string() {
    list($tree, $diags) = self::get_tree('cql.keywords =/string "code computer calculator programming"');
    $this->assertEqual($tree['relation'], '=');
    $this->assertEqual($tree['modifiers'], array('string' => TRUE));
  }

  function get_tree($query) {
    $this->c2t->parse($query);
    return array($this->c2t->result(), $this->c2t->get_diagnostics());
  }

  function cqlns() {
    $this->cqlns = 
      array('cql' => 'info:srw/cql-context-set/1/cql-v1.1',
            'dkcclphrase' => 'http://oss.dbc.dk/ns/dkcclphrase',
            'phrase' => 'http://oss.dbc.dk/ns/phrase',
            'dkcclterm' => 'http://oss.dbc.dk/ns/dkcclterm',
            'term' => 'http://oss.dbc.dk/ns/term',
            'facet' => 'http://oss.dbc.dk/ns/facet');
    return $this->cqlns;
  }
  
  function indexes() {
    $this->indexes = 
      array('serverChoice' => array('cql' => array('filter' => false, 'slop' => 9999, 'handler' => '')),
            'keywords' => array('cql' => array('filter' => false, 'slop' => 9999, 'handler' => '')),
            'cclphrase' => array('dkcclphrase' => array('filter' => false, 'slop' => 9999, 'handler' => '')),
            'phrase' => array('phrase' => array('filter' => false, 'slop' => 9999, 'handler' => '')),
            'cclterm' => array('dkcclterm' => array('filter' => false, 'slop' => 9999, 'handler' => '')),
            'filter' => array('term' => array('filter' => true, 'slop' => 9999, 'handler' => '')),
            'term' => array('term' => array('filter' => false, 'slop' => 9999, 'handler' => '')),
            'slop' => array('term' => array('filter' => false, 'slop' => 10, 'handler' => ''),
                            '' => array('alias' => array('slop' => 5, 'handler' => '', 'prefix' => 'term', 'field' => 'slop'))),
            'facet' => array('facet' => array('filter' => false, 'slop' => 9999, 'handler' => '')));
    return $this->indexes;
  }

}
?>
