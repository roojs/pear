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
    $args = func_get_args()
    if (func_get_args()) {
        $val = 0;
        if (is_object($args[0])) {
            $val = $args[0]->{array_unshift($args[0]->keys())};
        }
        $this->company_id = $val;
    }
    $r = new DB_DataObject_Links(array(
            'load' =>array('company'), 
            'scanf' => '%s_id',
            'cached' => true,
            'do' => $this
    ));
    return $r->links['company'];
    
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
    var $cached = true;
    /**
     * @property {Boolean}      apply   apply the result to this object, (default true)
     */
    var $apply = true;
    /**
     * @property {DB_DataObject}      do   DataObject to apply this to.
     */
    var $do = false;
    
    
    //------------------------- RETURN ------------------------------------
    /**
     * @property {Array}      links    key value associative array of links.
     */
    var $links;
    
    
    /**
     * Constructor
     *   -- good ole style..
     *  @param {Array}           cfg  Configuration (basically properties of this object)
     */
    
    function DB_DataObject_Links($cfg= array())
    {
        // check if do is set!!!?
        
        foreach($cfg as $k=>$v) {
            $this->$k = $v;
        }
        $this->parseConfig();
        $this->createLinks();
        if ($this->apply) { 
            $this->applyLinks(); // if apply is set..
        } 
        
    }
    
    function parseConfig()
    {
         
        if ($cfg['load'] == 'all') {
            // $cfg['load'] = ... links...
        }
          
        
        
    }
    
    /**
     * load the single linked object..
     *
     */
    function loadSingle($field, $table= false, $link=false)
    {
        
        // GUESS THE LINKED TABLE.. (if found - recursevly call self)
        
        if ($table == false) {
            $links = $this->do->links();
            
            if (!is_array($links)) {
                return false;
            }
            
            if ($links[$field]) {
                list($table,$rlink) = explode(':', $links[$field]);
                if ($p = strpos($field,".")) {
                    $field = substr($field,0,$p);
                }
                
                return $this->loadSingle($field, $table, $link === false ? $rlink : $link );
                    
             } 
                
            $this->do->raiseError(
                 "getLink: $field is not defined as a link (normally this is ok)", 
                    DB_DATAOBJECT_ERROR_NODATA);
                    
            return false;
            

        }
        
        
        
        
            // use the old _ method - this shouldnt happen if called via getLinks()
        //if (!($p = strpos($field, '_'))) {
        //        $r = null;
        //        return $r; 
        //    }
         
            //return $this->getLink($row, $table);
            

        
        
        
        if (!isset($this->$field)) {
            $this->raiseError("getLink: row not set $field", DB_DATAOBJECT_ERROR_NODATA);
            return false;
        }
        
        // check to see if we know anything about this table..
        
        $obj = $this->factory($table);
        
        if (!is_object($obj) || !is_a($obj,'DB_DataObject')) {
            $this->raiseError(
                "getLink:Could not find class for row $row, table $table", 
                DB_DATAOBJECT_ERROR_INVALIDCONFIG);
            return false;
        }
        if ($link) {
            if ($obj->get($link, $this->$row)) {
                return $obj;
            } 
            return  false;
        }
        
        if ($obj->get($this->$row)) {
            return $obj;
        }
        return false;
        
    }
        
        
        
        
    }
    
    
    
    
}