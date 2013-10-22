<?php
/**
 * SMTP implementation of the PEAR Mail interface. Requires the Net_SMTP class.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2010, Chuck Hagenbuch
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Jon Parise <jon@php.net> 
 * @author      Chuck Hagenbuch <chuck@horde.org>
 * @copyright   2010 Chuck Hagenbuch
 * @license     http://opensource.org/licenses/bsd-license.php New BSD License
 * @version     CVS: $Id: smtp.php 294747 2010-02-08 08:18:33Z clockwerx $
 * @link        http://pear.php.net/package/Mail/
 */
 
/**
 * SMTP implementation of the PEAR Mail interface. Requires the Net_SMTP class.
 * @access public
 * @package Mail
 * @version $Revision: 294747 $
 */
class Mail_smtpraw extends Mail_smtp {
 
    function Mail_smtpraw($params)
    {
       parent::Mail_smtp($params);
    }

     

    /**
     * Implements Mail::send() function using SMTP.
     *
     * @param mixed $recipients Either a comma-seperated list of recipients
     *              (RFC822 compliant), or an array of recipients,
     *              each RFC822 valid. This may contain recipients not
     *              specified in the headers, for Bcc:, resending
     *              messages, etc.
     *
     * @param array $headers IGNORED
     *
     * @param string $body The full text of the message body, including any
     *               MIME parts, etc.
     * @param string $from The sender email
    *
     * @return mixed Returns true on success, or a PEAR_Error
     *               containing a descriptive error message on
     *               failure.
     * @access public
     */
    function send($recipients, $headers, $body, $from)
    {
        /* If we don't already have an SMTP object, create one. */
        $result = &$this->getSMTPObject();
        if (PEAR::isError($result)) {
            return $result;
        }

        if (!is_array($headers)) {
            return PEAR::raiseError('$headers must be an array');
        }
  
        if (!isset($from)) {
            $this->_smtp->rset();
            return PEAR::raiseError('No From: address has been provided',
                                    PEAR_MAIL_SMTP_ERROR_FROM);
        }

        $params = null;
        if (!empty($this->_extparams)) {
            foreach ($this->_extparams as $key => $val) {
                $params .= ' ' . $key . (is_null($val) ? '' : '=' . $val);
            }
        }
        if (PEAR::isError($res = $this->_smtp->mailFrom($from, ltrim($params)))) {
            list($code, $error) = $this->_error(
                    "Failed to set sender: $from", $res, PEAR_MAIL_SMTP_ERROR_SENDER);
            $this->_smtp->rset();
            return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_SENDER,
                    null,null,array('smtpcode' => $code)
            );
        }

        $recipients = $this->parseRecipients($recipients);
        if (is_a($recipients, 'PEAR_Error')) {
            $this->_smtp->rset();
            return $recipients;
        }

        foreach ($recipients as $recipient) {
            $res = $this->_smtp->rcptTo($recipient);
            if (is_a($res, 'PEAR_Error')) {
                list($code, $error) = $this->_error("Failed to add recipient: $recipient", $res);
                $this->_smtp->rset();
                return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_RECIPIENT,
                    null,null,array('smtpcode' => $code)
                );
            }
        }

        /* Send the message's headers and the body as SMTP data. */
        $res = $this->_smtp->data( $body);
        list(,$args) = $this->_smtp->getResponse();

        if (preg_match("/Ok: queued as (.*)/", $args, $queued)) {
                $this->queued_as = $queued[1];
        }

        /* we need the greeting; from it we can extract the authorative name of the mail server we've really connected to.
         * ideal if we're connecting to a round-robin of relay servers and need to track which exact one took the email */
        $this->greeting = $this->_smtp->getGreeting();

        if (is_a($res, 'PEAR_Error')) {
            list($code,$error) = $this->_error('Failed to send data', $res);
            $this->_smtp->rset();
            return PEAR::raiseError($error, PEAR_MAIL_SMTP_ERROR_DATA,
                null,null,array('smtpcode' => $code)
            );
        }

        /* If persistent connections are disabled, destroy our SMTP object. */
        if ($this->persist === false) {
            $this->disconnect();
        }

        return true;
    }
 
}
