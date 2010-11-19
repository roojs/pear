<?php


require_once 'DB/DataObject/Generator.php';


class HTML_FlexyFramework_Generator extends DB_DataObject_Generator 
{
    // block class generation.
    var $generateClasses = false;
    
    function generateClasses()
    {
        if (!$this->generateClasses) {
            return;
        }
        parent::generateClasses();
    }
    
    
    function generateReaders()
    {
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        
        $out = array();
        foreach($this->tables as $this->table) {
            $this->table        = trim($this->table);
            
            $out = array_merge($out, $this->_generateReader($this->table));
            
            
        }
        //echo '<PRE>';print_r($out);exit;
         
        file_put_contents($options["ini_{$this->_database}"] . '.reader', serialize($out));
         
    }
    function _generateReader($table)
    {
        $DB = $this->getDatabaseConnection();
        $dbtype = $DB->phptype;
        $def = $this->_definitions[$table] ;
        $ret = array();
        foreach($def as $t) {
             switch (strtoupper($t->type)) {

                case 'INT':
                case 'INT2':    // postgres
                case 'INT4':    // postgres
                case 'INT8':    // postgres
                case 'SERIAL4': // postgres
                case 'SERIAL8': // postgres
                case 'INTEGER':
                case 'TINYINT':
                case 'SMALLINT':
                case 'MEDIUMINT':
                case 'BIGINT':
                // wierd ones..
                case 'YEAR':
                
                    $ret[$table.'.'.$t->name] = array('type' => 'int');
                    continue;
               
                case 'REAL':
                case 'DOUBLE':
                case 'DOUBLE PRECISION': // double precision (firebird)
                case 'FLOAT':
                case 'FLOAT4': // real (postgres)
                case 'FLOAT8': // double precision (postgres)
                case 'DECIMAL':
                case 'MONEY':  // mssql and maybe others
                case 'NUMERIC':
                case 'NUMBER': // oci8 
                    $ret[$table.'.'.$t->name] = array('type' => 'float'); //???
                    break;
                    
                case 'BIT':
                case 'BOOL':   
                case 'BOOLEAN':   
                    $ret[$table.'.'.$t->name] = array('type' => 'boolean'); //???
                    // postgres needs to quote '0'
                    if ($dbtype == 'pgsql') {
                        ///$type +=  DB_DATAOBJECT_STR;
                    }
                    break;
                    
                case 'STRING':
                case 'CHAR':
                case 'VARCHAR':
                case 'VARCHAR2':
                case 'TINYTEXT':
                
                case 'ENUM':
                case 'SET':         // not really but oh well
                
                case 'POINT':       // mysql geometry stuff - not really string - but will do..
                
                case 'TIMESTAMPTZ': // postgres
                case 'BPCHAR':      // postgres
                case 'INTERVAL':    // postgres (eg. '12 days')
                
                case 'CIDR':        // postgres IP net spec
                case 'INET':        // postgres IP
                case 'MACADDR':     // postgress network Mac address.
                
                case 'INTEGER[]':   // postgres type
                case 'BOOLEAN[]':   // postgres type
                
                
                case 'TEXT':
                case 'MEDIUMTEXT':
                case 'LONGTEXT':
                case 'BLOB':       /// these should really be ignored!!!???
                case 'TINYBLOB':
                case 'MEDIUMBLOB':
                case 'LONGBLOB':
                
                case 'CLOB': // oracle character lob support
                
                case 'BYTEA':   // postgres blob support..
                    $ret[$table.'.'.$t->name] =  $t->name; // strings are not sent as arrays..
                   // $type = DB_DATAOBJECT_STR;
                    break;
                
                
                
                
                case 'DATE':    
                    $ret[$table.'.'.$t->name] = array('type' => 'date', 'dateFormat' => 'Y-m-d'); //???
                    break;
                    
                case 'TIME':    
                    $ret[$table.'.'.$t->name] = $t->name; // technically not...
                    break;    
                    
                
                case 'DATETIME': 
                    $ret[$table.'.'.$t->name] = array('type' => 'date', 'dateFormat' => 'Y-m-d H:i:s'); //???
                    break;    
                    
                case 'TIMESTAMP': // do other databases use this???
                    
                    $ret[$table.'.'.$t->name] =   ($dbtype == 'mysql') ?
                         array('type' => 'float') : 
                        array('type' => 'date', 'dateFormat' => 'Y-m-d H:i:s');
                    break;    
                    
                
                
                    
                    
                default:     
                    $ret[$table.'.'.$t->name] = $t->name;
                    break;
            }
        }
        
        return $ret;
        
        
    }
}
