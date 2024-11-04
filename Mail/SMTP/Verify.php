<?PHP

require_once 'Mail/smtpmx.php';
require_once 'Net/SMTP.php';

class Mail_SMTP_Verify extends Mail_smtpmx {
    /**
     * Implements Mail::verify() function using SMTP direct delivery
     *
     * @access public
     * @param mixed $recipients in RFC822 style or array
     * @param array $headers The array of headers to send with the mail.
     * @param string $body The full text of the message body,
     * @return mixed Returns true on success, or a PEAR_Error
     */
    function verify($recipients, $headers, $body)
    {
        if (!is_array($headers)) {
            return PEAR::raiseError('$headers must be an array');
        }

        $result = $this->_sanitizeHeaders($headers);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Prepare headers
        $headerElements = $this->prepareHeaders($headers);
        if (is_a($headerElements, 'PEAR_Error')) {
            return $headerElements;
        }
        list($from, $textHeaders) = $headerElements;

        // use 'Return-Path' if possible
        if (!empty($headers['Return-Path'])) {
            $from = $headers['Return-Path'];
        }
        if (!isset($from)) {
            return $this->_raiseError('no_from');
        }

        // Prepare recipients
        $recipients = $this->parseRecipients($recipients);
        if (is_a($recipients, 'PEAR_Error')) {
            return $recipients;
        }

        foreach ($recipients as $rcpt) {
            list($user, $host) = explode('@', $rcpt);

            $mx = $this->_getMx($host);
            if (is_a($mx, 'PEAR_Error')) {
                return $mx;
            }

            if (empty($mx)) {
                $info = array('rcpt' => $rcpt);
                return $this->_raiseError('no_mx', $info);
            }

            $connected = false;
            foreach ($mx as $mserver => $mpriority) {
                $this->_smtp = new Net_SMTP($mserver, $this->port, $this->mailname);

                // configure the SMTP connection.
                if ($this->debug) {
                    $this->_smtp->setDebug(true);
                }

                // attempt to connect to the configured SMTP server.
                $res = $this->_smtp->connect($this->timeout);
                if (is_a($res, 'PEAR_Error')) {
                    $this->_smtp = null;
                    continue;
                }

                // connection established
                if ($res) {
                    $connected = true;
                    break;
                }
            }

            if (!$connected) {
                $info = array(
                    'host' => implode(', ', array_keys($mx)),
                    'port' => $this->port,
                    'rcpt' => $rcpt,
                );
                return $this->_raiseError('not_connected', $info);
            }

            // Verify recipient
            if ($this->vrfy) {
                $res = $this->_smtp->vrfy($rcpt);
                if (is_a($res, 'PEAR_Error')) {
                    $info = array('rcpt' => $rcpt);
                    return $this->_raiseError('failed_vrfy_rcpt', $info);
                }
            }

            // mail from:
            $args['verp'] = $this->verp;
            $res = $this->_smtp->mailFrom($from, $args);
            if (is_a($res, 'PEAR_Error')) {
                $info = array('from' => $from);
                return $this->_raiseError('failed_set_from', $info);
            }

            // rcpt to:
            $res = $this->_smtp->rcptTo($rcpt);
            if (is_a($res, 'PEAR_Error')) {
                $info = array('rcpt' => $rcpt);
                return $this->_raiseError('failed_set_rcpt', $info);
            }

            // Don't send anything in test mode
            if ($this->test) {
                $result = $this->_smtp->rset();
                $res = $this->_smtp->rset();
                if (is_a($res, 'PEAR_Error')) {
                    return $this->_raiseError('failed_rset');
                }

                $this->_smtp->disconnect();
                $this->_smtp = null;
                return true;
            }

            // Send data
            $res = $this->_smtp->data("$textHeaders\r\n$body");
            if (is_a($res, 'PEAR_Error')) {
                $info = array('rcpt' => $rcpt);
                return $this->_raiseError('failed_send_data', $info);
            }

            $this->_smtp->disconnect();
            $this->_smtp = null;
        }

        return true;
    }
}