<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2003-2004 John Griffin                                 |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Authors: John Griffin <jgriffin316@netscape.net>                     |
// +----------------------------------------------------------------------+
//
// $Id: Compiler.php 338539 2016-01-26 04:02:46Z alan_k $
//

require_once 'PEAR.php';

/**
 * A SQL parse tree compiler.
 * 
 * This is really a skeleton, you should really extend this 
 * and implement it for different databases - eg. SQL_Parser_Compiler_SQLite.. etc..
 *
 * @author  John Griffin <jgriffin316@netscape.net>
 * @version 0.1
 * @access  public
 * @package SQL_Parser
 */
class SQL_Parser_Compiler
{
    var $tree;
    var $quote_identifier = '`';
// {{{ function __construct($array = null)
    function __construct($array = null)
    {
        $this->tree = $array;
    }
// }}}

//    {{{ function getWhereValue ($arg)
    function getWhereValue ($arg)
    {
        switch ($arg['type']) {
            case 'ident':
            case 'real_val':
            case 'int_val':
            case 'null':
                $value = $arg['value'];
                break;
            case 'text_val':
                $value = '\''.$arg['value'].'\'';
                break;
            case 'subclause':
                $value = '('.$this->compileSearchClause($arg['value']).')';
                break;
            default:
                return PEAR::raiseError('Unknown type: '.$arg['type']);
        }
        return $value;
    }
//    }}}

//    {{{ function getParams($arg)
    function getParams($arg)
    {
        $types = count($arg['type']);
        for ($i = 0; $i < $types; $i++) {
            switch ($arg['type'][$i]) {
                case 'ident':
                case 'real_val':
                case 'int_val':
                case 'null':
                    $value[] = $arg['value'][$i];
                    break;
                case 'text_val':
                    $value[] = '\''.$arg['value'][$i].'\'';
                    break;
                default:
                    return PEAR::raiseError('Unknown type: '.$arg['type'][$i]);
            }
        }
        $value ='('.implode(', ', $value).')';
        return $value;
    }
//    }}}

//    {{{ function compileFunctionOpts($arg)
    function compileFunctionOpts($arg)
    {
        $types = count($arg['type']);
        for ($i = 0; $i < $types; $i++) {
            switch ($arg['type'][$i]) {
                case 'ident':
                case 'real_val':
                case 'int_val':
                case 'null':
                    $value[] = $arg['arg'][$i];
                    break;
                case 'text_val':
                    $value[] = '\''.$arg['arg'][$i].'\'';
                    break;
                default:
                    return PEAR::raiseError('Unknown type: '.$arg['type'][$i]);
            }
        }
        $value = implode(', ', $value);
        return $value;
    }
//    }}}

//    {{{ function compileSearchClause
    function compileSearchClause($where_clause)
    {
        // FIXME = nested - and a better interpretter
        
        // args + ops == nested...
        $sql = '';
        for($i = 0; $i < count($where_clause['args']); $i+=2) {
            $sql = ' ' . $where_clause['args'][$i]['column'] . 
                ' ' .
                $where_clause['ops'][floor($i/2)] .
                ' ' .
                $this->getWhereValue ($where_clause['args'][$i+1]);
            
        }
        return $sql;
        
        /*
        if (isset ($where_clause['arg_1']['value'])) {
            $value = $this->getWhereValue ($where_clause['arg_1']);
            if (PEAR::isError($value)) {
                return $value;
            }
            $sql = $value;
        } else {
            $value = $this->compileSearchClause($where_clause['arg_1']);
            if (PEAR::isError($value)) {
                return $value;
            }
            $sql = $value;
        }
        if (isset ($where_clause['op'])) {
            if ($where_clause['op'] == 'in') {
                $value = $this->getParams($where_clause['arg_2']);
                if (PEAR::isError($value)) {
                    return $value;
                }
                if (isset($where_clause['neg'])) {
                    $sql .= ' not';
                }
                $sql .= ' '.$where_clause['op'].' '.$value;
            } elseif ($where_clause['op'] == 'is') {
                $value = isset ($where_clause['neg']) ? 'not null' : 'null';
                $sql .= ' is '.$value;
            } else {
                $sql .= ' '.$where_clause['op'].' ';
                if (isset ($where_clause['arg_2']['value'])) {
                    $value = $this->getWhereValue ($where_clause['arg_2']);
                    if (PEAR::isError($value)) {
                        return $value;
                    }
                    $sql .= $value;
                } else {
                    $value = $this->compileSearchClause($where_clause['arg_2']);
                    if (PEAR::isError($value)) {
                        return $value;
                    }
                    $sql .= $value;
                }
            }
        }
        return $sql;
        */
    }
//    }}}

//    {{{ function compileSelect()
    function compileSelect()
    {
        // save the command and set quantifiers
        $sql = 'select ';
        if (isset($this->tree['set_quantifier'])) {
            $sql .= $this->tree['set_quantifier'].' ';
        }

        // save the column names and set functions
        $cols = count($this->tree['column_names']);
        for ($i = 0; $i < $cols; $i++) {
            $column = $this->tree['column_names'][$i];
            if ($this->tree['column_aliases'][$i] != '') {
                $column .= ' as '.$this->tree['column_aliases'][$i];
            }
            $column_names[] = $column;
        }

        $funcs = count($this->tree['set_function']);
        for ($i = 0; $i < $funcs; $i++) {
            $column = $this->tree['set_function'][$i]['name'].'(';
            if (isset ($this->tree['set_function'][$i]['distinct'])) {
                $column .= 'distinct ';
            }
            if (isset ($this->tree['set_function'][$i]['arg'])) {
                $column .= $this->compileFunctionOpts($this->tree['set_function'][$i]);
            }
            $column .= ')';
            if ($this->tree['set_function'][$i]['alias'] != '') {
                $column .= ' as '.$this->tree['set_function'][$i]['alias'];
            }
            $column_names[] = $column;
        }
        if (isset($column_names)) {
            $sql .= implode (", ", $column_names);
        }

        // save the tables
        $sql .= ' from ';
        $c_tables = count($this->tree['table_names']);
        for ($i = 0; $i < $c_tables; $i++) {
            $sql .= $this->tree['table_names'][$i];
            if ($this->tree['table_aliases'][$i] != '') {
                $sql .= ' as '.$this->tree['table_aliases'][$i];
            }
            if ($this->tree['table_join_clause'][$i] != '') {
                $search_string = $this->compileSearchClause ($this->tree['table_join_clause'][$i]);
                if (PEAR::isError($search_string)) {
                    return $search_string;
                }
                $sql .= ' on '.$search_string;
            }
            if (isset($this->tree['table_join'][$i])) {
                $sql .= ' '.$this->tree['table_join'][$i].' ';
            }
        }

        // save the where clause
        if (isset($this->tree['where_clause'])) {
            $search_string = $this->compileSearchClause ($this->tree['where_clause']);
            if (PEAR::isError($search_string)) {
                return $search_string;
            }
            $sql .= ' where '.$search_string;
        }

        // save the group by clause
        if (isset ($this->tree['group_by'])) {
            $sql .= ' group by '.implode(', ', $this->tree['group_by']);
        }

        // save the order by clause
        if (isset ($this->tree['sort_order'])) {
            foreach ($this->tree['sort_order'] as $key => $value) {
                $sort_order[] = $key.' '.$value;
            }
            $sql .= ' order by '.implode(', ', $sort_order);
        }

        // save the limit clause
        if (isset ($this->tree['limit_clause'])) {
            $sql .= ' limit '.$this->tree['limit_clause']['start'].','.$this->tree['limit_clause']['length'];
        }

        return $sql;
    }
//    }}}

//    {{{ function compileUpdate()
    function compileUpdate()
    {
        
        $sql = 'update ';
        foreach($this->tree['tables'] as $t) {
            $sql .= ' ' . $t['table']; //database & alias missing..
        }

        // save the set clause
        /*
        $cols = count($this->tree['sets']);
        for ($i = 0; $i < $cols; $i++) {
            $set_columns[] = $this->tree['column_names'][$i].' = '.$this->getWhereValue($this->tree['values'][$i]);
        }
        */
        
        print_r($this->tree['sets']);
        foreach($this->tree['sets'] as $s) {
            // FIXME - parser handles more complex than this does...
            $set_columns[] = $s['name']['column'].' = '.$this->getWhereValue($s['value']['args'][0]);
        }
        $sql .= ' set '.implode (', ', $set_columns);

        // save the where clause
        if (isset($this->tree['where_clause'])) {
            $search_string = $this->compileSearchClause ($this->tree['where_clause']);
            if (PEAR::isError($search_string)) {
                return $search_string;
            }
            $sql .= ' where '.$search_string;
        } 
        return $sql . ';';
    }
//    }}}

//    {{{ function compileDelete()
    function compileDelete()
    {
        $sql = 'delete from '.implode(', ', $this->tree['table_names']);

        // save the where clause
        if (isset($this->tree['where_clause'])) {
            $search_string = $this->compileSearchClause ($this->tree['where_clause']);
            if (PEAR::isError($search_string)) {
                return $search_string;
            }
            $sql .= ' where '.$search_string;
        }
        return $sql;
    }
//    }}}

//    {{{ function compileInsert()
    function compileInsert()
    {
        $sql = 'insert into ' . $this->tree['table_names'][0] .
                (isset($this->tree['column_names']) ? 
                    ' (' . implode(', ', $this->tree['column_names']) . ')' : 
                    ''
                ) . ' values (';

        $c_vals = count($this->tree['values']);
        for ($i = 0; $i < $c_vals; $i++) {
            $value = $this->getWhereValue ($this->tree['values'][$i]);
            if (PEAR::isError($value)) {
                return $value;
            }
            $value_array[] = $value;
        }
        $sql .= implode(', ', $value_array).')';
        return $sql;
    }
//    }}}

    function compileCreateTable()
    {
        $qi = $this->quote_identifier;
        $sql = "CREATE TABLE {$qi}" . $this->tree['table_names'][0]. "{$qi} (\n";
        $pk =  array();
        foreach($this->tree['column_defs'] as $k=>$type) {
            if (empty($type['constraints'])) {
                continue ;
            }
            foreach($type['constraints'] as $c) {
                if ($c['type'] == 'primary_key') {
                    $pk[] = $k ;
                    break;
                }
            }
        }
        // if we have more than one primary key... - then we have to use a line at the end..
        $body = array();
        foreach($this->tree['column_defs'] as $name=>$type) {
            $body[] = "    {$qi}$name{$qi} " . 
                $this->typeToSQL(
                    $type, 
                    count($pk) > 1 ? false : true
                );
        }
        if (count($pk) >  1) {
            $body[] = "    PRIMARY KEY ({$qi}" . 
                implode("{$qi},{$qi}", $pk) .
                "{$qi})";
        }
        
        
        
        $sql .= implode(",\n", $body) . "\n);";
        return $sql;
    }
    
    function typeToSQL($data, $add_primary_key) 
    {
        $ret = $data['type'];
        if (!empty($data['length'])) {
            $ret.='('.$data['length'].')';
        }
        if (empty($data['constraints'])) {
            return $ret;
        }
        foreach($data['constraints'] as $c) {
            switch($c['type']) {
                case 'not_null': 
                    $ret .= " NOT NULL"; 
                    break;
                    
                case 'auto_increment': 
                    $ret .= " AUTO_INCREMENT"; 
                    break;
                    
                case 'default_value': 
                    $ret .= " DEFAULT " . $c['value']; 
                    break;
                
                case 'primary_key': 
                    if (!$add_primary_key) {
                        continue;
                    }
                    $ret .= " PRIMARY KEY"; 
                    break;
                
                default: 
                    throw new Exception("FIXME - need to handle type" . $c['type']);
            }
        }
        return $ret;
    }
    
    function compileDropTable()
    {
        
    }
    function compileAlterTable()
    {
        $qi = $this->quote_identifier;
        $sql = "ALTER TABLE {$qi}". $this->tree['table_names'][0] . "{$qi} ";
        $sa = array();
        foreach($this->tree['table_actions'] as $a) {
            $line = $a['action'] . ' '. $a['what'];
            
            switch($a['what']) {
                case 'column': 
                    switch($a['action']) {
                        case 'drop':
                            $line .= ' ' . $qi . $a['name'] . $qi;
                            break;
                        
                        case 'change':
                            $line .= ' ' . $qi . $a['from'] . $qi;
                            // no break.. continue thru to add..
                        
                        case 'add':
                            $line .= ' ' . $qi . $a['name'] . $qi;
                            $line .= ' ' . $this->typeToSQL(
                                $a['field'], 
                                true ///
                            );
                            //var_dump($line);exit;
                            break;
                        
                        default:
                            throw new Exception("FIXME - need to handle column action" . print_r($a, true));
                    }
                    break;
                
                case 'index': 
                    $line .= " {$qi}{$a['name']}{$qi} ({$qi}" . 
                        implode("{$qi},{$qi}", $a['indexes']) .
                        "{$qi})";
                    break;
                    
                default:
                    throw new Exception("FIXME - need to handle alter table" . print_r($this->tree, true));
            }
            
            $sa[] = $line;
        }
        return $sql . implode(",\n    ", $sa). ";";
    }
//    {{{ function compile($array = null)
    function compile($array = null)
    {
        $this->tree = $array;

        switch ($this->tree['command']) {
            case 'select':
                return $this->compileSelect();
                
            case 'update':
                return $this->compileUpdate();
                
            case 'delete':
                return $this->compileDelete();
                
            case 'insert':
                return $this->compileInsert();
                
            case 'create_table':
                return $this->compileCreateTable();
                
            case 'alter_table':
                return $this->compileAlterTable();
                
            case 'drop':
            case 'alter':
            default:
                throw new Exception("FIXME - need to handle type" . print_r($this->tree, true));
                return PEAR::raiseError('Unknown action: '.$this->tree['command']);
        }    // switch ($this->_tree["command"])

    }
//    }}}
}