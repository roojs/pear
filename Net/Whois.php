<?php
/**
 * Whois.php
 *
 * PHP Version 4 and 5
 *
 * Copyright (c) 1997-2003 The PHP Group
 * Portions Copyright (c) 1980, 1993 The Regents of the University of
 *   California.  All rights reserved.
 *
 * This source file is subject to version 3.01 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available at through the world-wide-web at
 * http://www.php.net/license/3_01.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  Net
 * @package   Net_Whois
 * @author    Seamus Venasse <seamus.venasse@polaris.ca>
 * @copyright 1997-2003 The PHP Group
 * @copyright 1980-1993 The Regents of the University of California (Portions)
 * @license   http://www.php.net/license/3_01.txt PHP 3.01
 * @version   CVS: $Id: Whois.php 314693 2011-08-10 00:31:45Z kguest $
 * @link      http://pear.php.net/package/Net_Whois
 */

require_once 'PEAR.php';

/**
 * Looks up records in the databases maintained by several Network Information
 * Centres (NICs).  This class uses PEAR's Net_Socket:: class.
 *
 * @category Net
 * @package  Net_Whois
 * @author   Seamus Venasse <seamus.venasse@polaris.ca>
 * @license  http://www.php.net/license/3_01.txt PHP 3.01
 * @link     http://pear.php.net/package/Net_Whois
 */
class Net_Whois extends PEAR
{

    // {{{ properties

    /**
     * Retrieve authoritative definition only
     *
     * @var boolean
     * @access public
     */
    var $authoritative = false;

    /**
     * Port for whois servers
     *
     * @var int
     * @access public
     */
    var $port = 43;

    /**
     * See options for stream_context_create.
     *
     * @param array
     * @access public
     */
    var $options = null;

    /**
     * List of NICs to query
     *
     * @var array
     * @access private
     */
    var $_nicServers = array (
        'NICHOST'           => 'whois.crsnic.net.',
        'INICHOST'          => 'whois.networksolutions.com.',
        'GNICHOST'          => 'whois.nic.gov.',
        'ANICHOST'          => 'whois.arin.net.',
        'RNICHOST'          => 'whois.ripe.net.',
        'PNICHOST'          => 'whois.apnic.net.',
        'RUNICHOST'         => 'whois.ripn.net.',
        'MNICHOST'          => 'whois.ra.net.',
        'QNICHOST_TAIL'     => '.whois-servers.net.',
        'SNICHOST'          => 'whois.6bone.net.',
        'BNICHOST'          => 'whois.registro.br.'
    );

    /**
     * Search string of server to search on
     *
     * @var string
     * @access private
     */
    var $_whoisServerID = 'Whois Server: ';

    /**
     * Server to search for IP address lookups
     *
     * @var array
     * @access private
     */
    var $_ipNicServers = array ('RNICHOST', 'PNICHOST', 'BNICHOST');

    /**
     * List of error codes and text
     *
     * @var array
     * @access private
     */
    var $_errorCodes = array (
        010 => 'Unable to create a socket object',
        011 => 'Unable to open socket',
        012 => 'Write to socket failed',
        013 => 'Read from socket failed',
        014 => 'Specified server is null or empty',
    );

    /**
     * Number of seconds to wait on socket connections before assuming
     * there's no more data from the Whois server. Defaults to no timeout.
     * @var integer $timeout
     * @access private
     */
    var $_timeout = false;
    // }}}

    // {{{ constructor
    /**
     * Constructs a new Net_Whois object
     *
     * @access public
     */
    function Net_Whois()
    {
        $this->PEAR();

        $this->setPort();
        $this->setAuthoritative();
        $this->setTimeout();
    }
    // }}}

    // {{{ setTimeout()
    /**
     * Set timeout value - number of seconds afterwhich an attempt to connect
     * to a whois server should be aborted.
     *
     * @param integer $timeout false is also an acceptable value
     *
     * @access public
     * @return void
     */
    function setTimeout($timeout = false)
    {
        $this->_timeout = $timeout;
    }
    // }}}

    // {{{ getTimeout()
    /**
     * Retrieve timeout value
     *
     * @access public
     *
     * @return mixed either false or an integer value
     */
    function getTimeout()
    {
        return $this->_timeout;
    }
    // }}}

    // {{{ setTimeout()
    /**
     * setAuthoritative
     *
     * @param bool $authoritative defaults to false
     *
     * @access public
     * @return void
     */
    function setAuthoritative($authoritative = false)
    {
        $this->authoritative = $authoritative;
    }
    // }}}

    // {{{ getAuthoritative()
    /**
     * getAuthoritative
     *
     * @return bool Query for authoritative result?
     */
    function getAuthoritative()
    {
        return (bool) $this->authoritative;
    }
    // }}}


    /**
     * set which port should be used
     *
     * @param integer $port Port to use
     *
     * @access public
     * @return void
     */
    function setPort($port = false)
    {
        $port = is_numeric($port) ? $port : getservbyname('whois', 'tcp');
        $this->port = $port ? $port : 43;
    }
    // }}}

    // {{{ getPort()
    /**
     * Retrieve which port to connect to.
     *
     * @return integer port to connect to
     */
    function getPort()
    {
        return $this->port;
    }
    // }}}

    /**
     * setOptions
     *
     * @param mixed $options
     * @return void
     */
    function setOptions($options)
    {
        if ((!is_null($options)) && (!is_array($options))) {
            return;
        }
        $this->options = $options;
    }

    // {{{ getOptions()
    /**
     * Retrieve which port to connect to.
     *
     * @return array
     */
    function getOptions()
    {
        return $this->options;
    }
    // }}}

    // {{{ query()
    /**
     * Connect to the necessary servers to perform a domain whois query.  Prefix
     * queries with a "!" to lookup information in InterNIC handle database.
     * Add a "-arin" suffix to queries to lookup information in ARIN handle
     * database.
     *
     * @param string $domain          IP address or host name
     * @param string $userWhoisServer server to query (optional)
     *
     * @access public
     * @return mixed returns a PEAR_Error on failure, or a string on success
     */
    function query($domain, $userWhoisServer = null)
    {
        $domain = trim($domain);

        if (isset($userWhoisServer)) {
            $whoisServer = $userWhoisServer;
        } elseif (preg_match('/^!.*/', $domain)) {
            $whoisServer = $this->_nicServers['INICHOST'];
        } elseif (preg_match('/.*?-arin/i', $domain)) {
            $whoisServer = $this->_nicServers['ANICHOST'];
        } else {
            $whoisServer = $this->_chooseServer($domain);
        }

        $_domain = $this->authoritative ? 'domain ' . $domain : $domain;
        $whoisData = $this->_connect($whoisServer, $_domain);

        if (PEAR::isError($whoisData)) {
            return $whoisData;
        }

        if ($this->authoritative) {
            $pattern = '/\s+' . preg_quote($this->_whoisServerID) . '(.+?)\n/i';

            if (preg_match($pattern, $whoisData, $matches)) {
                $whoisData = $this->_connect(trim(array_pop($matches)), $domain);
            }
        }
        return $whoisData;
    }
    // }}}

    // {{{ queryAPNIC()
    /**
     * Use the Asia/Pacific Network Information Center (APNIC) database.
     * It contains network numbers used in East Asia, Australia, New
     * Zealand, and the Pacific islands.
     *
     * @param string $domain IP address or host name
     *
     * @access public
     * @return mixed returns a PEAR_Error on failure, or a string on success
     */
    function queryAPNIC($domain)
    {
        return $this->query($domain, $this->_nicServers['PNICHOST']);
    }
    // }}}

    // {{{ queryIPv6()
    /**
     * Use the IPv6 Resource Center (6bone) database.  It contains network
     * names and addresses for the IPv6 network.
     *
     * @param string $domain IP address or host name
     *
     * @access public
     * @return mixed returns a PEAR_Error on failure, or a string on success
     */
    function queryIPv6($domain)
    {
        return $this->query($domain, $this->_nicServers['SNICHOST']);
    }
    // }}}

    // {{{ queryRADB()
    /**
     * Use the Route Arbiter Database (RADB) database.  It contains
     * route policy specifications for a large number of operators'
     * networks.
     *
     * @param string $ipAddress IP address
     *
     * @access public
     * @return mixed returns a PEAR_Error on failure, or a string on success
     */
    function queryRADB($ipAddress)
    {
        return $this->query($ipAddress, $this->_nicServers['MNICHOST']);
    }
    // }}}

    // {{{ _chooseServer()
    /**
     * Determines the correct server to connect to based upon the domain
     *
     * @param string $query IP address or host name
     *
     * @access private
     * @return string whois server host name
     */
    function _chooseServer($query)
    {
        if (!strpos($query, '.')) {
            return $this->_nicServers['NICHOST'];
        }

        $TLD = substr($query, strrpos($query, '.') + 1);

        if (is_numeric($TLD)) {
            $whoisServer = $this->_nicServers['ANICHOST'];
        } else {
            $whoisServer = $this->getDomainServer($query);
        }

        return $whoisServer;
    }
    // }}}

    // {{{ getDomainServer()
    /**
     * Determines the correct whois server to connect to based upon the domain
     *
     * @param string $q domain name
     *
     * @access public
     * @return string whois server ip address
     */
    function getDomainServer($q)
    {
        $tail = $this->_nicServers['QNICHOST_TAIL'];
        if (strchr($q, '.')) {
            //get the last 2 parts
            $q = array_reverse(explode('.', $q));
            $a = array($q[1] . '.' . $q[0], $q[0]);
        } else {
            $a = array($q);
        }
        foreach ($a as $q) {
            //check host has real ip
            $q = gethostbyname($q . $tail);
            if (filter_var($q, FILTER_VALIDATE_IP)) {
                return $q;
            }
        }
    }
    // }}}

    // {{{ _connect()
    /**
     * Connects to the whois server and retrieves domain information
     *
     * @param string $nicServer FQDN of whois server to query
     * @param string $domain    Domain name to query
     *
     * @access private
     * @return mixed returns a PEAR_Error on failure, string of whois data on success
     */
    function _connect($nicServer, $domain)
    {
        include_once 'Net/Socket.php';

        if (is_null($nicServer) || (empty($nicServer))) {
            return new PEAR_Error($this->_errorCodes[014], 14);
        }

        if (PEAR::isError($socket = new Net_Socket())) {
            return new PEAR_Error($this->_errorCodes[010], 10);
        }

        $result = $socket->connect(
            $nicServer,
            $this->getPort(),
            null,
            $this->getTimeout(),
            $this->getOptions()
        );
        if (PEAR::isError($result)) {
            return new PEAR_Error($this->_errorCodes[011], 11);
        }
        $socket->setBlocking(false);
        // Querying denic.de requires some special coaxing for a domain query.
        // http://www.denic.de/en/faq-single/2978/1115.html
        if (substr($domain, -3) == '.de') {
            if (PEAR::isError($socket->writeLine("-T dn,ace " . $domain))) {
                return new PEAR_Error($this->_errorCodes[012], 12);
            }
        } else {

            if (PEAR::isError($socket->writeLine($domain))) {
                return new PEAR_Error($this->_errorCodes[012], 12);
            }
        }

        $nHost = null;

        $whoisData = $socket->readAll();
        if (PEAR::isError($whoisData)) {
            return new PEAR_Error($this->_errorCodes[013], 13);
        }

        $data = explode("\n", $whoisData);
        foreach ($data as $line) {
            $line = rtrim($line);

            // check for whois server redirection
            if (!isset($nHost)) {
                $pattern='/'.$this->_whoisServerID.'([a-z0-9.]+)\n/i';
                if (preg_match($pattern, $line, $matches)) {
                    $nHost = $matches[1];
                } elseif ($nicServer == $this->_nicServers['ANICHOST']) {
                    foreach ($this->_ipNicServers as $ipNicServer) {
                        $server = trim($this->_nicServers[$ipNicServer], '.');
                        if (strstr($line, $server)) {
                            $nHost = $this->_nicServers[$ipNicServer];
                        }
                    }
                }
            }
        }

        // this should fail, but we'll call it anyway and ignore the error
        $socket->disconnect();

        if ($nHost && $nHost != $nicServer) {
            $tmpBuffer = $this->_connect($nHost, $domain);
            if (PEAR::isError($tmpBuffer)) {
                return $tmpBuffer;
            }
            $whoisData .= $tmpBuffer;
        }

        return $whoisData;
    }
    // }}}
}
?>
