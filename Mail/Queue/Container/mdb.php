<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * +----------------------------------------------------------------------+
 * | PEAR :: Mail :: Queue :: MDB Container                               |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2004 The PHP Group                                |
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
 */

/**
 * Storage driver for fetching mail queue data from a PEAR::MDB database
 *
 * This storage driver can use all databases which are supported
 * by the PEAR MDB abstraction layer.
 *
 * PHP Version 4 and 5
 *
 * @category   Mail
 * @package    Mail_Queue
 * @author     Lorenzo Alberton <l dot alberton at quipo dot it>
 * @version    CVS: $Id: mdb.php 303870 2010-09-29 16:25:34Z till $
 * @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link       http://pear.php.net/package/Mail_Queue
 * @deprecated
 */
require_once 'MDB.php';
require_once 'Mail/Queue/Container.php';

/**
* PEAR/MDB Mail_Queue Container.
*
* NB: The field 'changed' has no meaning for the Cache itself. It's just there
* because it's a good idea to have an automatically updated timestamp
* field for debugging in all of your tables.
*
* A XML MDB-compliant schema example for the table needed is provided.
* Look at the file "mdb_mail_queue_schema.xml" for that.
*
* -------------------------------------------------------------------------
* A basic usage example:
* -------------------------------------------------------------------------
*
* $container_options = array(
*   'type'        => 'mdb',
*   'database'    => 'dbname',
*   'phptype'     => 'mysql',
*   'username'    => 'root',
*   'password'    => '',
*   'mail_table'  => 'mail_queue'
* );
*   //optionally, a 'dns' string can be provided instead of db parameters.
*   //look at MDB::connect() method or at MDB docs for details.
*
* $mail_options = array(
*   'driver'   => 'smtp',
*   'host'     => 'your_smtp_server.com',
*   'port'     => 25,
*   'auth'     => false,
*   'username' => '',
*   'password' => ''
* );
*
* $mail_queue =& new Mail_Queue($container_options, $mail_options);
* *****************************************************************
* // Here the code differentiates wrt you want to add an email to the queue
* // or you want to send the emails that already are in the queue.
* *****************************************************************
* // TO ADD AN EMAIL TO THE QUEUE
* *****************************************************************
* $from             = 'user@server.com';
* $from_name        = 'admin';
* $recipient        = 'recipient@other_server.com';
* $recipient_name   = 'recipient';
* $message          = 'Test message';
* $from_params      = empty($from_name) ? '"'.$from_name.'" <'.$from.'>' : '<'.$from.'>';
* $recipient_params = empty($recipient_name) ? '"'.$recipient_name.'" <'.$recipient.'>' : '<'.$recipient.'>';
* $hdrs = array( 'From'    => $from_params,
*                'To'      => $recipient_params,
*                'Subject' => "test message body"  );
* $mime =& new Mail_mime();
* $mime->setTXTBody($message);
* $body = $mime->get();
* $hdrs = $mime->headers($hdrs);
*
* // Put message to queue
* $mail_queue->put( $from, $recipient, $hdrs, $body );
* //Also you could put this msg in more advanced mode [look at Mail_Queue docs for details]
* $seconds_to_send = 3600;
* $delete_after_send = false;
* $id_user = 7;
* $mail_queue->put( $from, $recipient, $hdrs, $body, $seconds_to_send, $delete_after_send, $id_user );
*
* *****************************************************************
* // TO SEND EMAILS IN THE QUEUE
* *****************************************************************
* // How many mails could we send each time
* $max_ammount_mails = 50;
* $mail_queue =& new Mail_Queue($container_options, $mail_options);
* $mail_queue->sendMailsInQueue($max_ammount_mails);
* *****************************************************************
* // end usage example
* -------------------------------------------------------------------------
*
* //You can also send the emails one by one:
*
* //set the internal buffer size according your
* //memory resources (the number indicates how
* //many emails can stay in the buffer at any
* //given time
* $mail_queue->setBufferSize(20);
*
* //set the queue size (i.e. the number of mails to send)
* $limit = 50;
* $mail_queue->setOption($limit);
*
* //loop through the stored emails and send them
* while ($mail = $mail_queue->get()) {
*     $result = $mail_queue->sendMail($mail);
* }
*/

/**
 * Mail_Queue_Container_mdb
 *
 * @deprecated
 *
 * @category   Mail
 * @package    Mail_Queue
 * @author     Lorenzo Alberton <l dot alberton at quipo dot it>
 * @version    Release: @package_version@
 * @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link       http://pear.php.net/package/Mail_Queue
 * @deprecated
 */
class Mail_Queue_Container_mdb extends Mail_Queue_Container
{
    // {{{ class vars

    /**
     * Reference to the current database connection.
     * @var object PEAR::MDB instance
     */
    var $db;

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
        return $this->Mail_Queue_Container_mdb($options);
    }

    // }}}
    // {{{ Mail_Queue_Container_mdb()

    /**
     * Contructor
     *
     * Mail_Queue_Container_mdb:: Mail_Queue_Container_mdb()
     *
     * @param mixed $options    An associative array of option names and
     *                          their values. See MDB_common::setOption
     *                          for more information about connection options.
     *
     * @access public
     */
    function Mail_Queue_Container_mdb($options)
    {
        if (!is_array($options)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_NO_OPTIONS,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'No options specified!');
        }
        if (isset($options['mail_table'])) {
            $this->mail_table = $options['mail_table'];
            unset($options['mail_table']);
        }
        if (isset($options['sequence'])) {
            $this->sequence = $options['sequence'];
            unset($options['sequence']);
        } else {
            $this->sequence = $this->mail_table;
        }
        if (!empty($options['pearErrorMode'])) {
            $this->pearErrorMode = $options['pearErrorMode'];
        }
        $dsn = array_key_exists('dsn', $options) ? $options['dsn'] : $options;
        $this->db = &MDB::Connect($dsn);
        if (PEAR::isError($this->db)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_CANNOT_CONNECT,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB::connect failed: '. $this->db->getMessage());
        } else {
            $this->db->setFetchMode(MDB_FETCHMODE_ASSOC);
        }
        $this->setOption();
    }

    // }}}
    // {{{ _preload()

    /**
     * Preload mail to queue.
     *
     * @return mixed   True on success else Mail_Queue_Error object.
     * @access private
     */
    function _preload()
    {
        if (!is_object($this->db) || !is_a($this->db, 'MDB_Common')) {
            $msg = 'MDB::connect failed';
            if (PEAR::isError($this->db)) {
                $msg .= ': '.$this->db->getMessage();
            }
            return new Mail_Queue_Error(MAILQUEUE_ERROR_CANNOT_CONNECT,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__, $msg);
        }
        $query = 'SELECT id FROM ' . $this->mail_table
                .' WHERE sent_time IS NULL AND try_sent < '. $this->try
                .' AND time_to_send <= '.$this->db->getTimestampValue(date("Y-m-d H:i:s"))
                .' ORDER BY time_to_send';
        $res = $this->db->limitQuery($query, null, $this->offset, $this->limit);

        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB: query failed - "'.$query.'" - '.$res->getMessage());
        }

        $this->_last_item = 0;
        $this->queue_data = array(); //reset buffer
        while ($row = $this->db->fetchInto($res, MDB_FETCHMODE_ASSOC)) {
            $this->queue_data[$this->_last_item] = $this->getMailById($row['id']);
            if (PEAR::isError($this->queue_data[$this->_last_item])) {
                return $this->queue_data[$this->_last_item];
            }
            $this->_last_item++;
        }
        @$this->db->freeResult($res);
        return true;
    }

    // }}}
    // {{{ put()

    /**
     * Put new mail in queue and save in database.
     *
     * Mail_Queue_Container::put()
     *
     * @param string  $time_to_send  When mail have to be send
     * @param integer $id_user  Sender id
     * @param string  $ip  Sender ip
     * @param string  $from  Sender e-mail
     * @param string  $to  Recipient e-mail
     * @param string  $hdrs  Mail headers (in RFC)
     * @param string  $body  Mail body (in RFC)
     * @param bool    $delete_after_send  Delete or not mail from db after send
     * @return mixed  ID of the record where this mail has been put
     *                or Mail_Queue_Error on error
     * @access public
     */
    function put($time_to_send, $id_user, $ip, $sender,
                $recipient, $headers, $body, $delete_after_send=true)
    {
        if (!is_object($this->db) || !is_a($this->db, 'MDB_Common')) {
            $msg = 'MDB::connect failed';
            if (PEAR::isError($this->db)) {
                $msg .= ': '.$this->db->getMessage();
            }
            return new Mail_Queue_Error(MAILQUEUE_ERROR_CANNOT_CONNECT,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__, $msg);
        }
        $id = $this->db->nextId($this->sequence);
        if (empty($id)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'Cannot create id in: '.$this->sequence);
        }
        $query = 'INSERT INTO '. $this->mail_table
                .' (id, create_time, time_to_send, id_user, ip'
                .', sender, recipient, delete_after_send) VALUES ('
                .       $this->db->getIntegerValue($id)
                .', ' . $this->db->getTimestampValue(date("Y-m-d H:i:s"))
                .', ' . $this->db->getTimestampValue($time_to_send)
                .', ' . $this->db->getIntegerValue($id_user)
                .', ' . $this->db->getTextValue($ip)
                .', ' . $this->db->getTextValue($sender)
                .', ' . $this->db->getTextValue($recipient)
                .', ' . ($delete_after_send ? 1 : 0)
                .')';
        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB: query failed - "'.$query.'" - '.$res->getMessage());
        }
        foreach (array('headers', 'body') as $field) {
            $query = 'UPDATE ' . $this->mail_table .' SET ' .$field. '=?'
                    .' WHERE id=' . $this->db->getIntegerValue($id);
            if ($prepared_query = $this->db->prepareQuery($query)) {
                $char_lob = array('Error' => '',
                                  'Type'  => 'data',
                                  'Data'  => $$field);
                if (PEAR::isError($clob = $this->db->createLob($char_lob))) {
                    return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                        $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                        'MDB: query failed - "'.$query.'" - '.$clob->getMessage());
                }
                $this->db->setParamClob($prepared_query,1,$clob,$field);
                if (PEAR::isError($error = $this->db->executeQuery($prepared_query))) {
                    return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                        $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                        'MDB: query failed - "'.$query.'" - '.$res->getMessage());
                }
                $this->db->destroyLob($clob);
                $this->db->freePreparedQuery($prepared_query);
            } else {
                //prepared query failed
                return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                        $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                        'MDB: query failed - "'.$query.'" - '.$clob->getMessage());
            }

        }
        return $id;
    }

    // }}}
    // {{{ countSend()

    /**
     * Check how many times mail was sent.
     *
     * @param object  Mail_Queue_Body
     * @return mixed  Integer or Mail_Queue_Error class if error.
     * @access public
     */
    function countSend($mail)
    {
        if (!is_object($mail) || !is_a($mail, 'mail_queue_body')) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_UNEXPECTED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'Expected: Mail_Queue_Body class');
        }
        $count = $mail->_try();
        $query = 'UPDATE ' . $this->mail_table
                .' SET try_sent = ' . $this->db->getIntegerValue($count)
                .' WHERE id = '     . $this->db->getIntegerValue($mail->getId());
        $res = $this->db->query($query);

        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB: query failed - "'.$query.'" - '.$res->getMessage());
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
        if (!is_object($mail) || !is_a($mail, 'mail_queue_body')) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_UNEXPECTED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'Expected: Mail_Queue_Body class');
        }
        $query = 'UPDATE ' . $this->mail_table
                .' SET sent_time = '.$this->db->getTimestampValue(date("Y-m-d H:i:s"))
                .' WHERE id = '. $this->db->getIntegerValue($mail->getId());

        $res = $this->db->query($query);

        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB: query failed - "'.$query.'" - '.$res->getMessage());
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
        $query = 'SELECT * FROM ' . $this->mail_table
                .' WHERE id = '   . (int)$id;
        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB: query failed - "'.$query.'" - '.$res->getMessage());
        }
        $row = $this->db->fetchRow($res, MDB_FETCHMODE_ASSOC);
        if (!is_array($row)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB: query failed - "'.$query.'" - '.$res->getMessage());
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

    // }}}
    // {{{ deleteMail()

    /**
     * Remove from queue mail with $id identifier.
     *
     * @param integer $id  Mail ID
     * @return bool  True on success else Mail_Queue_Error class
     * @access public
     */
    function deleteMail($id) {
        $query = 'DELETE FROM ' . $this->mail_table
                .' WHERE id = ' . $this->db->getTextValue($id);
        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'MDB: query failed - "'.$query.'" - '.$res->getMessage());
        }
        return true;
    }

    // }}}
}
?>
