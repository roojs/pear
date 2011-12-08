<?php
/**
 * Link tool for DB_DataObject
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Database
 * @package    DB_DataObject
 * @author     Alan Knowles <alan@akbkhome.com>
 * @copyright  1997-2006 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    : FIXME
 * @link       http://pear.php.net/package/DB_DataObject
 */


/**
 *
 * Example of how this could be used..
 *
 * 


$person->loadLinks( array( 
        'load' => array(
                'company_id',
                'office_id',
                'image' =>  array('Images', array( 'on_id' => $do->id , 'on_table' => $do->tableName()))
        ),
        'scanf' => '%s_id', // or 'printf' => '%s_obj'
        'cached' => true
));


// THE DEFAULT BEHAVOIUR... - eg. $person->loadLinks() 
$person->loadLinks( array( 
        'load' => 'all'
        'scanf' => false,        
        'printf' => '%s_link'
        'cached' => false,
        'apply' => true
        
));


getLinks would then be:
function getLinks($format =  '_%s')
        $r = new DB_DataObject_Links(array(
                'load' => all',
                'scanf' => false,
                'printf' => $format
                'cached' => false,
                'do' => $this
        ));
        return $r->links;
}

May also be used by the generator to generate hook methods that look like this:

function company()
{
    $r = new DB_DataObject_Links(array( 
            'do' => $this
    ));
    return $r->apply('company_id', func_get_args());
    
}
 *
 *
 */
  
  
/**
 * Links class
 *
 * @package DB_DataObject
 */
class DB_DataObject_Links 
{
     /**
     * @property {DB_DataObject}      do   DataObject to apply this to.
     */
    var $do = false;
    
    
    /**
     * @property {Array|String} load    What to load, 'all' or an array of properties. (default all)
     */
    var $load = 'all';
    /**
     * @property {String|Boolean}       scanf   use part of column name as resulting
     *                                          property name. (default false)
     */
    var $scanf = false;
    /**
     * @property {String|Boolean}       printf  use column name as sprintf for resulting property name..
     *                                     (default %s_link if apply is true, otherwise it is %s)
     */
    var $printf = false;
    /**
     * @property {Boolean}      cached  cache the result, so future queries will use cache rather
     *                                  than running the expensive sql query.
     */
    var $cached = false;
    /**
     * @property {Boolean}      apply   apply the result to this object, (default true)
     */
    var $apply = true;
   
    
    //------------------------- RETURN ------------------------------------
    /**
     * @property {Array}      links    key value associative array of links.
     */
    var $links;
    
    
    /**
     * Constructor
     *   -- good ole style..
     *  @param {DB_DataObject}           do  DataObject to apply to.
     *  @param {Array}           cfg  Configuration (basically properties of this object)
     */
    
    function DB_DataObject_Links($do,$cfg= array())
    {
        // check if do is set!!!?
        $this->do = $do;
        
        foreach($cfg as $k=>$v) {
            $this->$k = $v;
        }
       
        
    }
     
    /**
     * return name from related object
     *
     * The relies on  a <dbname>.links.ini file, unless you specify the arguments.
     * 
     * you can also use $this->getLink('thisColumnName','otherTable','otherTableColumnName')
     *
     *
     * @param string $row    either row or row.xxxxx
     * @param string $table  (optional) name of table to look up value in
     * @param string $link   (optional)  name of column in other table to match
     * @author Tim White <tim@cyface.com>
     * @access public
     * @return mixed object on success false on failure or '0' when not linked
     */
    function getLink($field, $table= false, $link='')
    {
        
        static $cache = array();
        
        // GUESS THE LINKED TABLE.. (if found - recursevly call self)
        
        if ($table == false) {
            $links = $this->do->links();
            
            if (!empty($links) && is_array($links)) {
                
                
                
                if (isset($links[$field])) {
                    list($table,$rlink) = explode(':', $links[$field]);
                    if ($p = strpos($field,".")) {
                        $field = substr($field,0,$p);
                    }
                    
                    return $this->getLink($field, $table, $link === false ? $rlink : $link );
                        
                }
                
                    
                $this->do->raiseError(
                     "getLink: $field is not defined as a link (normally this is ok)", 
                        DB_DATAOBJECT_ERROR_NODATA);
                        
                return false;
            }
            // no links defined.. - use borked BC method...
                  // use the old _ method - this shouldnt happen if called via getLinks()
            if (!($p = strpos($field, '_'))) {
                return false;
            }
            $table = substr($row, 0, $p);
            return $this->getLink($row, $table);
            
            

        }
         
      
         
            //return $this->getLink($row, $table);
            
 
        if (!isset($this->$field)) {
            $this->raiseError("getLink: row not set $field", DB_DATAOBJECT_ERROR_NODATA);
            return false;
        }
        
        // check to see if we know anything about this table..
        
      
        if (empty($this->$field) || $this->$field < 0) {
            return 0; // no record. 
        }
        
        if ($this->cached && isset($cache[$table.':'. $link .':'. $this->$field])) {
            return $cache[$table.':'. $link .':'. $this->$field];    
        }
        
        $obj = $this->do->factory($table);
        
        if (!is_a($obj,'DB_DataObject')) {
            $this->raiseError(
                "getLink:Could not find class for row $field, table $table", 
                DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return false;
        }
        // -1 or 0 -- no referenced record..
       
        $ret = false;
        if ($link) {
            
            if ($obj->get($link, $this->$field)) {
                $ret = $obj;
            }
            
            
        // this really only happens when no link config is set (old BC stuff)    
        } else if ($obj->get($this->$row)) {
            $ret= $obj;
             
        }
        if ($this->cached) {
            $cache[$table.':'. $link .':'. $this->$field] = $ret;
        }
        return $ret;
        
    }
    
    
    
    
        
    /**
     *  a generic geter/setter provider..
     *  
     *
     */
    function apply($field,  $args)
    {
        if (empty($args)) {
            return $this->getLink($field);
        }
        // otherwise it's a set call..
        if (!is_a($args[0], 'DB_DataObject')) {
            if (is_integer($args[0])) {
                $this->do->$field = $args[0];
            }
            return false;
        }
        $assign = $args[0];
        // otherwise we are assigning it ...
        $links = $this->do->links();
            
        if (empty($links) || !is_array($links) || !isset($links[$field])) {
            $this->do->raiseError(
                "getLink:Could not find link for row $field", 
                DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return false;
        }
        
        $use = $links[$field];
        $this->do->$field = $assign->$use;
        return true;
        
        
    }
    /**
     * load related objects
     *
     * Generally not recommended to use this.
     * The generator should support creating getter_setter methods which are better suited.
     *
     * Relies on  <dbname>.links.ini
     *
     * Sets properties on the calling dataobject  you can change what
     * object vars the links are stored in by  changeing the format parameter
     *
     *
     * @param  string format (default _%s) where %s is the table name.
     * @author Tim White <tim@cyface.com>
     * @access public
     * @return boolean , true on success
     */
    
    function getLinks($format = '_%s')
    {
         
        // get table will load the options.
        if (isset($this->do->_link_loaded)) {
            return true;
        }
        
        $this->_do->link_loaded = false;
        $cols  = $this->do->table();
        $links = $this->do->links();
         
        $loaded = array();
        
        if ($links) {   
            foreach($links as $key => $match) {
                list($table,$link) = explode(':', $match);
                $k = sprintf($format, str_replace('.', '_', $key));
                // makes sure that '.' is the end of the key;
                if ($p = strpos($key,'.')) {
                      $key = substr($key, 0, $p);
                }
                
                $this->$k = $this->getLink($key, $table, $link);
                
                if (is_object($this->$k)) {
                    $loaded[] = $k; 
                }
            }
            $this->_link_loaded = $loaded;
            return true;
        }
        // this is the autonaming stuff..
        // it sends the column name down to getLink and lets that sort it out..
        // if there is a links file then it is not used!
        // IT IS DEPRECITED!!!! - USE 
        if (!is_null($links)) {    
            return false;
        }
        
        
        foreach (array_keys($cols) as $key) {
            if (!($p = strpos($key, '_'))) {
                continue;
            }
            // does the table exist.
            $k =sprintf($format, $key);
            $this->$k = $this->getLink($key);
            if (is_object($this->$k)) {
                $loaded[] = $k; 
            }
        }
        $this->_link_loaded = $loaded;
        return true;
    }

}