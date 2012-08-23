<?php
/*
dl('mysql.so');
ini_set('display_errors' , true);
//$x = new Text_SearchParser("(age:acme) AND (NOT test:fund) OR \"investment in testing\") FRED BLOGS");
$x = new Text_SearchParser("(age:acme%) AND (test:\"% fund\") OR \"investment in testing\") FRED BLOGS");
echo "<PRE>\n";
echo $x->toSQL(array(
    'default' => array('name', 'description'),
    'map' => array(
        'age' => 'agefield',
        'test' => 'testfield',
    ),
    'escape' => 'mysql_escape_string', //array($db->getDatabaseConnection(), 'escapeSimple'), /// pear db or mdb object..

));

FIXME:

xxx: -- if xxx is not part of the 'map', then xxx: is dropped..
.. it should really be converted into a string to search..


*/

class Text_SearchParser
{
    function __construct($str)
    {
        $x = new Text_SearchParser_Tokenizer ($str);
        $ar = $x->parse();
        // catch eq
        $l = count($ar) -1;
        for($i =1; $i < $l; $i++) { // not to end!!
            $x=  $ar[$i];
            if ($x->type == ':') {
                // str : ...
                if ($ar[$i-1] && $ar[$i+1] && $ar[$i-1]->type == 's' && $ar[$i+1]->type == 's') {
                    $ar[$i-1] = new Text_SearchParser_Token_Eq($ar[$i-1]->str, $ar[$i+1]->str);
                    $ar[$i]  = false;
                    $ar[$i+1]  = false;
                    $i++;
                    continue;
                }
                // delete it...
                $ar[$i]  = false;
            }
        }
       // echo '<PRE>';print_r($ar);
        $ar = $this->cleanAr($ar);
        // group stuff!!!
        $s = 0;
        $ar = $this->fixGrp($ar, $s, true);
        $ar->fixOp();
       
        //echo '<PRE>';print_r($ar);
        $this->ar = $ar;
        
    }
    
    
    function toSQL($conf)
    {
        return $this->ar->toSQL($conf);
    }
    
    
    function fixGrp($ar, &$s, $top= false) {
        //echo "FIXGRP $s<BR>";flush();
        $l = count($ar);
        while ($s < $l) {
            $x=  $ar[$s];
            if ($x->type == '(') {
                $s++;
                $ret[] = $this->fixGrp($ar, $s);
                continue;
            }
            if ($x->type == ')') {
                if ($top) {
                    $s++;
                    continue; // ignore overclosing..
                }
                
                $s++;
                return new   Text_SearchParser_Token_Grp($ret);
            }
            $ret[] = $x;
            $s++;
        }
        $s++;
        return new Text_SearchParser_Token_Grp($ret);
    }
    
    function cleanAr($ar) {
        $ret = array();
        for($i =0; $i < count($ar); $i++) { // not to end!!
            if ($ar[$i]) {
                $ret[] = $ar[$i];
            }
        }
        return $ret;
    }
    
}
 
 
class Text_SearchParser_Tokenizer {
    var $i = 0;
    var $str = '';
    var $strlen = 0;
    var $tokens = array();
    function __construct($s)
    {
        $this->str = $s;
        $this->strlen = strlen($s);
        $this->i = 0;
       //print_r($this);
    }
    
    function parse()
    {
        while(true) {
            //echo $this->i . "\n";;
            //var_dump($this->tokens);
            if (false === ($c = $this->getChar())) {
                return $this->tokens;
            }
            
            switch($c) {
                case ' ': continue;
                case ':': $this->tokens[] = new Text_SearchParser_Token_Eq(); break;
                case '(': $this->tokens[] = new Text_SearchParser_Token_GrpS(); break;
                case ')': $this->tokens[] = new Text_SearchParser_Token_GrpE(); break;
                default:
                    
                    $this->ungetChar();
                    $this->strParse();
                    break;
                
               
            }
        }
        return $this->tokens;
    }
    var $utf =array(
        252 => 6,
        248 => 5,
        240 => 4,
        224 => 3,
        192 => 2,
    );
    
    function strParse()
    {
        
        $str = '';
        while(true) {
            if (false === ($c = $this->getChar())) {
                $this->addStr($str);
                return;
            }
            
            $o = ord($c);
            if (isset($utf[$o]) && false !== ( $rest = $this->getChar($utf[$o]-1))) {
                if (strlen($str)) {
                    $this->addStr($str); 
                    $str = '';
                }
                // adds a unique character..
                $this->addStr( $c . $rest);
                
            }
            
            
            switch($c) {
                // end chars.
                case ' ': 
                case ':': 
                case '(': 
                case ')': $this->addStr($str); $this->ungetChar(); return;
                case '"': 
                    if (strlen($str)) {
                        $this->addStr($str); 
                        $str = '';
                    }
                    $this->strParseQuoted($c);
                    break;
                    
                default : 
                    $str .= $c;
                    continue;
            }
            
        }
    }
    function strParseQuoted($end) 
    {
        $str = '';   /// ignore \" slashed ???
        while(true) {
            if (false === ($c = $this->getChar())) {
                $this->addStr($str,true);
                return;
            }
            if ($c == $end) {
                $this->addStr($str,true);
                return;
            }
            $str .= $c;
        }
            
    }
    /**
     * add a string to the tokens list..
     * @param string $str The string to add
     * @param boolean $q  is the string quoted.
     */

    function addStr($s, $q=false)
    { 
        //$s = $q ? $s : trim($s);
        $s = trim($s);
        if (!strlen($s)) {
            return;
        }
        if (!$q) {
            
            if ((strtoupper($s) == 'AND') || (strtoupper($s) == 'OR')) {
                $this->tokens[] = new Text_SearchParser_Token_Op(strtoupper($s));
                return;
            }
        }
        $this->tokens[] = new Text_SearchParser_Token_String($s);
    }
    
    function getChar($n=1)
    {
        if ($this->i + ($n-1) >= $this->strlen) {
            return false;
        }
        $c = ($n === 1) ? $this->str[$this->i] : substr($this->str, $this->i, $n);
        $this->i += $n;
    }
    function ungetChar()
    {
        $this->i--;
    }
    
    
    
    
}
class Text_SearchParser_Token { 
    function escape($conf, $v)
    {
        $ret = call_user_func($conf['escape'], $v);
        if (strpos($ret,'%') !== false) {
            return $ret;
        }
        return '%' . $ret . '%';
        
    }

}
   
class Text_SearchParser_Token_String extends Text_SearchParser_Token {
    var $type = 's';
    function __construct($s) {
        $this->str = $s;
    }
    function toSQL($conf)
    {
        // should use mapping in conf..
        $ar = array();
        $v= $this->escape($conf,$this->str);
        foreach($conf['default'] as $k) {
            $ar[] = "$k LIKE '".$v. "'";
        }
        
        return '( ' . implode(' OR ', $ar) . ' )';
        
    }
}

class Text_SearchParser_Token_Keyword extends Text_SearchParser_Token  {
    var $type = 'k';
}

class Text_SearchParser_Token_Op extends Text_SearchParser_Token  { // AND || OR
    var $type = '&&';
    function __construct($s) {
        $this->op = $s;
    }
    function toSQL($conf)
    {
        // should use mapping in conf..
        return $this->op;
        
    }
}

class Text_SearchParser_Token_Grp extends Text_SearchParser_Token { // (
    var  $type = '()';
    var $ar = array();
    function __construct($ar) {
        $this->ar = $ar;
    }
    
    function fixOp()
    {
        // each string or group must be seperated by a AND || OR..
        // if not, then we and and 'AND'
        $ar = array();
        $expOp = false;
       
        
        for($i = 0; $i < count($this->ar); $i++) {
            if ($this->ar[$i]->type == '()') {
                $this->ar[$i]->fixOp();
               
            }
            if ($this->ar[$i]->type == '&&') {
                // opp..
                if ($expOp) {
                    $ar[]= $this->ar[$i];
                    $expOp = false;
                    continue;
                }
                // remove this operation..
                continue;
            }
            if ($expOp) { // add an extra op!!!
                $ar[] = new Text_SearchParser_Token_Op('AND');
            }
            $ar[] = $this->ar[$i];
            $expOp = true;
            
        }
        $this->ar = $ar;
        
        
        
    }
    
    
    function toSQL($conf)
    {
        $ret = '(';
        foreach($this->ar as $o) {
            
            $ret .= ' ' .$o->toSQL($conf);
            
            
        }
        
        return $ret . ')' .  "\n";
        
        
        
    }
    
}

class Text_SearchParser_Token_GrpS extends Text_SearchParser_Token
{ 
    var $type = '(';
}
class Text_SearchParser_Token_GrpE extends Text_SearchParser_Token
{  
    var $type = ')';
}

class Text_SearchParser_Token_Eq extends Text_SearchParser_Token
{ // :  or = ?
    var $type = ':';
    var $k;
    var $v;
    function __construct($k=false, $v=false) {
        if (!$k) {
            return;
        }
        $this->k = $k;
        $this->v = $v;
    }
    
    function toSQL($conf)
    {
        // should use mapping in conf..
        if (empty($this->k) || !isset($conf['map'][$this->k])) {
            
            if (empty($this->k) && empty($this->v)) {
                return '1=1'; // not sure if this is valid.
            }
            if (empty($this->v)) {
                $s = new Text_SearchParser_Token_String($this->k);
                return $s->toSQL($conf);
            }
            
            
            $g = new Text_SearchParser_Token_Grp(array(
                new Text_SearchParser_Token_String($this->k),
                new Text_SearchParser_Token_Op('OR'),
                new Text_SearchParser_Token_String($this->v)
            ));
            
            //print_R($g);exit;
            
            return $g->toSQL($conf);
        }
        return $conf['map'][$this->k] ." LIKE '". $this->escape($conf,$this->v). "'";
        
    }
}