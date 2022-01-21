<?php


/**
 * This works by creating '_generate** methods, which are called by start()
 * 
 */

require_once 'PDO/DataObject/Generator.php';


class HTML_FlexyFramework2_Generator extends PDO_DataObject_Generator 
{
    // block class generation.
    static $generateClasses = false;
    
    function generateClasses()
    {
//        echo "GENERATE CLASSES?";
        if (!HTML_FlexyFramework2_Generator::$generateClasses ) {
            return;
        }
       
        //echo "GENERATE CLASSES?";
        parent::generateClasses();
    }
    
    
    function generateReaders()
    {
        $options = PDO_DataObject::config();
        
        $out = array();
        foreach($this->tables as $table) {
              
            $out = array_merge($out, $this->_generateReader($table));
            
            
        }
           
        file_put_contents($options["schema_location"] . '.reader', serialize($out));
         
    }
    
    function generatePhp ()
    {
        // NOOP 
    }
    
    /**
     * Generate the cached readers used for meta data in the queries.
     * 
     */
    function _generateReader($table)
    {
        $DB = $this->PDO();
        $dbtype = $DB->getAttribute(PDO::ATTR_DRIVER_NAME);;
         
        $ret = array();
        foreach($table->columns as $t) {
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
                
                    $ret[$table->table.'.'.$t->name] = array('type' => 'int');
                    continue 2;
               
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
                    $ret[$table->table.'.'.$t->name] = array('type' => 'float'); //???
                    break;
                    
                case 'BIT':
                case 'BOOL':   
                case 'BOOLEAN':   
                    $ret[$table->table.'.'.$t->name] = array('type' => 'boolean'); //???
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
                    $ret[$table->table.'.'.$t->name] =  $t->name; // strings are not sent as arrays..
                   // $type = DB_DATAOBJECT_STR;
                    break;
                
                
                
                
                case 'DATE':    
                    $ret[$table->table.'.'.$t->name] = array('type' => 'date', 'dateFormat' => 'Y-m-d'); //???
                    break;
                    
                case 'TIME':    
                    $ret[$table->table.'.'.$t->name] = $t->name; // technically not...
                    break;    
                    
                
                case 'DATETIME': 
                    $ret[$table->table.'.'.$t->name] = array('type' => 'date', 'dateFormat' => 'Y-m-d H:i:s'); //???
                    break;    
                    
                case 'TIMESTAMP': // do other databases use this???
                    
                    $ret[$table->table.'.'.$t->name] =   ($dbtype == 'mysql') ?
                         array('type' => 'float') : 
                        array('type' => 'date', 'dateFormat' => 'Y-m-d H:i:s');
                    break;    
                    
                
                
                    
                    
                default:     
                    $ret[$table->table.'.'.$t->name] = $t->name;
                    break;
            }
        }
        
        return $ret;
        
        
    }
    /**
     * Generate the cached *.ini and links.ini files (merged for all components)
     * 
     */
    static function writeCache($iniCacheTmp, $iniCache, $replace = array())
    {
        
        $fp = fopen($iniCache.".lock", "a+");
        flock($fp,LOCK_EX);

        $ff = HTML_FlexyFramework2::get();
        $ff->debug('Framework Generator:writeCache ' . $iniCacheTmp .  ' ' . $iniCache);
          
        //var_dump($iniCacheTmp);
       // echo '<PRE>';echo file_get_contents($iniCacheTmp);exit;
        // only unpdate if nothing went wrong.
        if (file_exists($iniCacheTmp) && filesize($iniCacheTmp)) {
            // is the replace file exist?
            if (!isset($replace[$iniCache]) || $replace[$iniCache] != md5_file($iniCacheTmp)) {
            
            
                if (file_exists($iniCache)) {
                    unlink($iniCache);
                }
                $ff->debug("Writing merged ini file : $iniCache\n");
                rename($iniCacheTmp, $iniCache);
            } else {
                touch($iniCache);
            }
        }
         // readers..??? not needed??? (historical)
        if (file_exists($iniCacheTmp.'.reader') &&  filesize($iniCacheTmp.'.reader')) {
            
            if (file_exists($iniCache.'.reader') ) {
                if (!isset($replace[$iniCache] ) || $replace[$iniCache] != md5_file($iniCacheTmp.'.reader')) {
                    unlink($iniCache.'.reader');
                    rename($iniCacheTmp.'.reader', $iniCache.'.reader');
                } else {
                    // do not need to touch..
                    unlink($iniCacheTmp.'.reader');
                }
            } else {
                rename($iniCacheTmp.'.reader', $iniCache.'.reader');
            }
             
        }
        
        // merge and set links.. test for generated links file..
        
        $linksCacheTmp = preg_replace('/\.ini/', '.links.ini', $iniCacheTmp );
        $links = array();
        if (file_exists($linksCacheTmp )) {
            $links = self::mergeIni( parse_ini_file($linksCacheTmp, true), $links);
            unlink($linksCacheTmp);
        }
        // we are going to use the DataObject directories..
        
        $inis = explode(PATH_SEPARATOR,$ff->PDO_DataObject['class_location']);
        //print_r($inis);exit;
        $ff->debug("class_location = ". $ff->PDO_DataObject['class_location']);
        
        
        $lproject = strtolower(explode('/', $ff->project)[0]);
        
        foreach($inis as $path) {
            $ini = $path . '/'. strtolower( $lproject ) . '.links.ini';
             //var_dump($ini);
            if (!file_exists($ini)) {
                $ff->debug("Framework Generator:writeCache PROJECT.links.ini does not exist in $path - trying glob");
       
                // try scanning the directory for another ini file..
                $ar = glob(dirname($ini).'/*.links.ini');
                
                
                if (empty($ar)) {
                    continue;
                }
                
                
                sort($ar);
                $ff->debug("Framework Generator:writeCache using {$ar[0]}");
                
                // first file.. = with links removed..
                $ini = preg_replace('/\.links\./' , '.', $ar[0]);
                $ini = preg_replace('/\.ini$/', '.links.ini', $ini);
            }
            
            // why do this twice???
            if (!file_exists($ini)) {
                continue;
            }
            $ff->debug("Adding in $ini");
            // prefer first ?
            $links = self::mergeIni( parse_ini_file($ini, true), $links);   
        }
        $iniLinksCache = preg_replace('/\.ini$/', '.links.ini', $iniCache);
        $out = array();
        foreach($links as $tbl=>$ar) {
            $out[] = '['. $tbl  .']';
            foreach ($ar as $k=>$v) {
                $out[] = $k . '=' .$v;
            }
            $out[] = '';
        }
        if (count($out)) {
            $ff->debug("Writing merged Links file : $iniLinksCache \n");
            $out_str = implode("\n", $out);
            // is target file different?
            if (!isset($replace[$iniLinksCache]) || $replace[$iniLinksCache] != md5($out)) {
          
                 file_put_contents($iniCacheTmp. '.links.ini', $out_str);
                 if (file_exists($iniLinksCache)) {                
                     unlink($iniLinksCache);
                 }
                 rename($iniCacheTmp. '.links.ini', $iniLinksCache);
            } else {
                touch($iniLinksCache);
                
            }
        } // we ignore that we might need to delete old links.ini
        
        flock($fp, LOCK_UN);
        fclose($fp);
        
        clearstatcache();
        
        if(file_exists($iniCache.".lock")){
            unlink($iniCache.".lock");
        }
        
    }
    /* bit like merge recursive, but it avoids doing stuff with arrays.. */
    static function mergeIni($new, $old) 
    {
        foreach($new as $g => $ar) {
            if (!isset($old[$g])) {
                $old[$g] = $ar;
                continue;
            }
            foreach($ar as $k=>$v) {
                if (isset($old[$g][$k])) {
                    continue;
                }
                $old[$g][$k] = $v;
            }
        }
        return $old;
        
        
    }
    
}
