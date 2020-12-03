<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's mysqli extension (fallback version) -
 * for interacting with MySQL databases
 *
 * PHP versions 7
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Database
 * @package    DB
 * @author     Alan Knowles <alan@roojs.com>
 * @copyright  1997-2007 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 */
 
require_once 'DB/mysqli.php';
 
class DB_mysqlfb extends DB_mysqli
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'mysqlfb';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'mysqlfb';

    /**
     * The capabilities of this DB implementation
     *
     * The 'new_link' element contains the PHP version that first provided
     * new_link support for this DBMS.  Contains false if it's unsupported.
     *
     * Meaning of the 'limit' element:
     *   + 'emulate' = emulate with fetch row by number
     *   + 'alter'   = alter the query
     *   + false     = skip rows
     *
     * @var array
     */
    var $features = array(
        'limit'         => 'alter',
        'new_link'      => false,
        'numrows'       => true,
        'pconnect'      => false,
        'prepare'       => false,
        //'ssl'           => true, // removed...
        'transactions'  => true,
        'abort'         => false
    );

     
    function connect($dsn, $persistent = false)
    {
        if (!PEAR::loadExtension('mysqli')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }
        
        if (!empty($dsn['abort'])) {
            $this->features['abort'] = true;
        }

        $ini = ini_get('track_errors');
        @ini_set('track_errors', 1);
        $php_errormsg = '';
 
        $this->connection = @mysqli_connect(
            $dsn['hostspec'],
            $dsn['username'],
            $dsn['password'],
            $dsn['database'],
            $dsn['port'],
            $dsn['socket']
         );
        
        @ini_set('track_errors', $ini);

        if (!$this->connection) {
            if (($err = @mysqli_connect_error()) != '') {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         $err);
            } else {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         $php_errormsg);
            }
        }

        if ($dsn['database']) {
            $this->_db = $dsn['database'];
        }
        // appears that these are not needed 
        //@mysqli_set_charset($this->connection, "utf8");
        //@mysqli_query($this->connection, 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

     
    // }}}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
 
