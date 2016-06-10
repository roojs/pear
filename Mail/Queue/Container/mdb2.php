<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * +----------------------------------------------------------------------+
 * | PEAR :: Mail :: Queue :: MDB2 Container                              |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2008 Lorenzo Alberton                             |
 * +----------------------------------------------------------------------+
 * | All rights reserved.                                                 |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * | * Redistributions of source code must retain the above copyright     |
 * |   notice, this list of conditions and the following disclaimer.      |
 * | * Redistributions in binary form must reproduce the above copyright  |
 * |   notice, this list of conditions and the following disclaimer in    |
 * |   the documentation and/or other materials provided with the         |
 * |   distribution.                                                      |
 * | * The names of its contributors may be used to endorse or promote    |
 * |   products derived from this software without specific prior written |
 * |   permission.                                                        |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 * | Author: Lorenzo Alberton <l.alberton at quipo.it>                    |
 * +----------------------------------------------------------------------+
 */

/**
 * Storage driver for fetching mail queue data from a PEAR::MDB2 database
 *
 * This storage driver can use all databases which are supported
 * by the PEAR MDB2 abstraction layer.
 *
 * PHP Version 4 and 5
 *
 * @category Mail
 * @package  Mail_Queue
 * @author   Lorenzo Alberton <l dot alberton at quipo dot it>
 * @version  CVS: $Id: mdb2.php 303870 2010-09-29 16:25:34Z till $
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link     http://pear.php.net/package/Mail_Queue
 */
require_once 'MDB2.php';
require_once 'Mail/Queue/Container.php';

/**
 * Mail_Queue_Container_mdb2
 * 
 * @category Mail
 * @package  Mail_Queue
 * @author   Lorenzo Alberton <l dot alberton at quipo dot it>
 * @version  Release: @package_version@
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link     http://pear.php.net/package/Mail_Queue
 */
class Mail_Queue_Container_mdb2 extends Mail_Queue_Container
{
    // {{{ class vars

    /**
     * Reference to the current database connection.
     * @var object PEAR::MDB2 instance
     */
    var $db = null;

    var $errorMsg = 'MDB2::query() failed: "%s", %s';

    /**
     * Table for sql database
     * @var  string
     */
    var $mail_table = 'mail_queue';

    /**
     * @var string  the name of the sequence for this table
     */
    var $sequence = null;

    // }}}
    // {{{ __construct()

    function __construct($options)
    {
        return $this->Mail_Queue_Container_mdb2($options);
    }

    // }}}
    // {{{ Mail_Queue_Container_mdb2()

    /**
     * Constructor
     *
     * Mail_Queue_Container_mdb2()
     *
     * @param mixed $options    An associative array of connection option.
     *
     * @access public
     */
    function Mail_Queue_Container_mdb2($options)
    {
        if (!is_array($options) || !isset($options['dsn'])) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_NO_OPTIONS,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'No dns specified!');
        }
        if (isset($options['mail_table'])) {
            $this->mail_table = $options['mail_table'];
        }
        $this->sequence = (isset($options['sequence']) ? $options['sequence'] : $this->mail_table);

        if (!empty($options['pearErrorMode'])) {
            $this->pearErrorMode = $options['pearErrorMode'];
        }
        $dsn = array_key_exists('dsn', $options) ? $options['dsn'] : $options;
        $res = $this->_connect($dsn);
        if (PEAR::isError($res)) {
            return $res;
        }
        $this->setOption();
    }

    // }}}
    // {{{ _connect()

    /**
     * Connect to database by using the given DSN string
     *
     * @param mixed &$db DSN string | array | MDB2 object
     *
     * @return boolean|PEAR_Error on error
     * @access private
     */
    function _connect(&$db)
    {
        if (is_object($db) && is_a($db, 'MDB2_Driver_Common')) {
            $this->db = &$db;
        } elseif (is_string($db) || is_array($db)) {
            include_once 'MDB2.php';
            $this->db =& MDB2::connect($db);
        } elseif (is_object($db) && MDB2::isError($db)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_CANNOT_CONNECT,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB2::connect failed: '. $this->_getErrorMessage($this->db));
        } else {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_CANNOT_CONNECT,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'The given dsn was not valid in file '. __FILE__ . ' at line ' . __LINE__);
        }
        if (PEAR::isError($this->db)) {
            return $this->db;
        }
        return true;
    }

    // }}}
    // {{{ _checkConnection()

    /**
     * Check if there's a valid db connection
     *
     * @return boolean|PEAR_Error on error
     */
    function _checkConnection() {
        if (!is_object($this->db) || !is_a($this->db, 'MDB2_Driver_Common')) {
            $msg = 'MDB2::connect failed';
            if (PEAR::isError($this->db)) {
                $msg .= $this->_getErrorMessage($this->db);
            }
            return new Mail_Queue_Error(MAILQUEUE_ERROR_CANNOT_CONNECT,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__, $msg);
        }
        return true;
    }

    /**
     * Create a more useful error message from DB related errors.
     *
     * @access private
     *
     * @param PEAR_Error $errorObj A PEAR_Error object.
     *
     * @return string
     */
    function _getErrorMessage($errorObj)
    {
        if (!Pear::isError($errorObj)) {
            return '';
        }
        $msg   = ': ' . $errorObj->getMessage();
        $debug = $errorObj->getDebugInfo();

        if (!empty($debug)) {
            $msg .= ", DEBUG: {$debug}";
        }
        return $msg;
    }

    // }}}
    // {{{ _preload()

    /**
     * Preload mail to queue.
     *
     * @return mixed  True on success else Mail_Queue_Error object.
     * @access private
     */
    function _preload()
    {
        $res = $this->_checkConnection();
        if (PEAR::isError($res)) {
            return $res;
        }
        $query = 'SELECT * FROM ' . $this->mail_table
                .' WHERE sent_time IS NULL AND try_sent < '. $this->try
                .' AND time_to_send <= '.$this->db->quote(date('Y-m-d H:i:s'), 'timestamp')
                .' ORDER BY time_to_send';
        $this->db->setLimit($this->limit, $this->offset);
        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, $this->_getErrorMessage($res)));
        }

        $this->_last_item = 0;
        $this->queue_data = array(); //reset buffer
        while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            //var_dump($row['headers']);
            if (!is_array($row)) {
                return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                    $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                    sprintf($this->errorMsg, $query, $this->_getErrorMessage($res)));
            }

            $delete_after_send = (bool) $row['delete_after_send'];

            $this->queue_data[$this->_last_item] = new Mail_Queue_Body(
                $row['id'],
                $row['create_time'],
                $row['time_to_send'],
                $row['sent_time'],
                $row['id_user'],
                $row['ip'],
                $row['sender'],
                $this->_isSerialized($row['recipient']) ? unserialize($row['recipient']) : $row['recipient'],
                unserialize($row['headers']),
                unserialize($row['body']),
                $delete_after_send,
                $row['try_sent']
            );
            $this->_last_item++;
        }

        return true;
    }

    // }}}
    // {{{ put()

    /**
     * Put new mail in queue and save in database.
     *
     * Mail_Queue_Container::put()
     *
     * @param string $time_to_send  When mail have to be send
     * @param integer $id_user  Sender id
     * @param string $ip  Sender ip
     * @param string $from  Sender e-mail
     * @param string $to  Reciepient e-mail
     * @param string $hdrs  Mail headers (in RFC)
     * @param string $body  Mail body (in RFC)
     * @param bool $delete_after_send  Delete or not mail from db after send
     *
     * @return mixed  ID of the record where this mail has been put
     *                or Mail_Queue_Error on error
     * @access public
     **/
    function put($time_to_send, $id_user, $ip, $sender,
                $recipient, $headers, $body, $delete_after_send=true)
    {
        $res = $this->_checkConnection();
        if (PEAR::isError($res)) {
            return $res;
        }
        $id = $this->db->nextID($this->sequence);
        if (empty($id) || PEAR::isError($id)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'Cannot create id in: '.$this->sequence);
        }
        $query = 'INSERT INTO '. $this->mail_table
                .' (id, create_time, time_to_send, id_user, ip'
                .', sender, recipient, headers, body, delete_after_send) VALUES ('
                .       $this->db->quote($id, 'integer')
                .', ' . $this->db->quote(date('Y-m-d H:i:s'), 'timestamp')
                .', ' . $this->db->quote($time_to_send, 'timestamp')
                .', ' . $this->db->quote($id_user, 'integer')
                .', ' . $this->db->quote($ip, 'text')
                .', ' . $this->db->quote($sender, 'text')
                .', ' . $this->db->quote($recipient, 'text')
                .', ' . $this->db->quote($headers, 'text')   //clob
                .', ' . $this->db->quote($body, 'text')      //clob
                .', ' . ($delete_after_send ? 1 : 0)
                .')';
        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, $this->_getErrorMessage($res)));
        }
        return $id;
    }

    // }}}
    // {{{ countSend()

    /**
     * Check how many times mail was sent.
     *
     * @param object   Mail_Queue_Body
     * @return mixed  Integer or Mail_Queue_Error class if error.
     * @access public
     */
    function countSend($mail)
    {
        $res = $this->_checkConnection();
        if (PEAR::isError($res)) {
            return $res;
        }
        if (!is_object($mail) || !is_a($mail, 'mail_queue_body')) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_UNEXPECTED, __FILE__, __LINE__);
        }
        $count = $mail->_try();
        $query = 'UPDATE ' . $this->mail_table
                .' SET try_sent = ' . $this->db->quote($count, 'integer')
                .' WHERE id = '     . $this->db->quote($mail->getId(), 'integer');
        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, $this->_getErrorMessage($res)));
        }
        return $count;
    }

    // }}}
    // {{{ setAsSent()

    /**
     * Set mail as already sent.
     *
     * @param object Mail_Queue_Body object
     * @return bool
     * @access public
     */
    function setAsSent($mail)
    {
        $res = $this->_checkConnection();
        if (PEAR::isError($res)) {
            return $res;
        }
        if (!is_object($mail) || !is_a($mail, 'mail_queue_body')) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_UNEXPECTED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
               'Expected: Mail_Queue_Body class');
        }
        $query = 'UPDATE ' . $this->mail_table
                .' SET sent_time = '.$this->db->quote(date('Y-m-d H:i:s'), 'timestamp')
                .' WHERE id = '. $this->db->quote($mail->getId(), 'integer');

        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, $this->_getErrorMessage($res)));
        }
        return true;
    }

    // }}}
    // {{{ getMailById()

    /**
     * Return mail by id $id (bypass mail_queue)
     *
     * @param integer $id  Mail ID
     * @return mixed  Mail object or false on error.
     * @access public
     */
    function getMailById($id)
    {
        $res = $this->_checkConnection();
        if (PEAR::isError($res)) {
            return $res;
        }
        $query = 'SELECT * FROM ' . $this->mail_table
                .' WHERE id = '   . (int)$id;
        $row = $this->db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($row)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, $this->_getErrorMessage($row)));
        }
        if (!is_array($row)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, 'no such message'));
        }

        $delete_after_send = (bool) $row['delete_after_send'];

        return new Mail_Queue_Body(
            $row['id'],
            $row['create_time'],
            $row['time_to_send'],
            $row['sent_time'],
            $row['id_user'],
            $row['ip'],
            $row['sender'],
            $this->_isSerialized($row['recipient']) ? unserialize($row['recipient']) : $row['recipient'],
            unserialize($row['headers']),
            unserialize($row['body']),
            $delete_after_send,
            $row['try_sent']
        );
    }

    /**
     * Return the number of emails currently in the queue.
     *
     * @return mixed An int, or Mail_Queue_Error on failure.
     */
    function getQueueCount()
    {
        $res = $this->_checkConnection();
        if (PEAR::isError($res)) {
            return $res;
        }
        $query = 'SELECT count(*) FROM ' . $this->mail_table;
        $count = $this->db->queryOne($query);
        if (PEAR::isError($count)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, $this->_getErrorMessage($count)));
        }
        return (int) $count;
    }

    // }}}
    // {{{ deleteMail()

    /**
     * Remove from queue mail with $id identifier.
     *
     * @param integer $id  Mail ID
     * @return bool  True on success else Mail_Queue_Error class
     *
     * @access public
     */
    function deleteMail($id)
    {
        $res = $this->_checkConnection();
        if (PEAR::isError($res)) {
            return $res;
        }
        $query = 'DELETE FROM ' . $this->mail_table
                .' WHERE id = ' . $this->db->quote($id, 'text');
        $res = $this->db->query($query);

        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                sprintf($this->errorMsg, $query, $this->_getErrorMessage($res)));
        }
        return true;
    }

    // }}}
}
?>
