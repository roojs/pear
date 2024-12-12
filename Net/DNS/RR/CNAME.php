<?php
/**
*  License Information:
*
*  Net_DNS:  A resolver library for PHP
*  Copyright (c) 2002-2003 Eric Kilfoil eric@ypass.net
*  Maintainers:
*  Marco Kaiser <bate@php.net>
*  Florian Anderiasch <fa@php.net>
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.01 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_01.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*/

/* Net_DNS_RR_CNAME definition {{{ */
/**
 * A representation of a resource record of type <b>CNAME</b>
 *
 * @package Net_DNS
 */
class Net_DNS_RR_CNAME extends Net_DNS_RR
{
    /* class variable definitions {{{ */
    var $name;
    var $type;
    var $class;
    var $ttl;
    var $rdlength;
    var $rdata;
    var $cname;

    /* }}} */
    /* class constructor - RR(&$rro, $data, $offset = '') {{{ */
    function __construct(&$rro, $data, $offset = '')
    {
        $this->name = $rro->name;
        $this->type = $rro->type;
        $this->class = $rro->class;
        $this->ttl = $rro->ttl;
        $this->rdlength = $rro->rdlength;
        $this->rdata = $rro->rdata;

        if ($offset) {
            if ($this->rdlength > 0) {
                $packet = new Net_DNS_Packet();
                list($cname, $offset) = $packet->dn_expand($data, $offset);
                $this->cname = $cname;
            }
        } elseif (is_array($data)) {
            $this->cname = $data['cname'];
        } else {
            $this->cname = preg_replace("/[ \t]+(.+)[\. \t]*$/", '\\1', $data);
        }
    }

    /* }}} */
    /* Net_DNS_RR_CNAME::rdatastr() {{{ */
    function rdatastr()
    {
        if (strlen($this->cname)) {
            return $this->cname . '.';
        }
        return '; no data';
    }

    /* }}} */
    /* Net_DNS_RR_CNAME::rr_rdata($packet, $offset) {{{ */
    function rr_rdata($packet, $offset)
    {
        if (strlen($this->cname)) {
            return $packet->dn_comp($this->cname, $offset);
        }
        return null;
    }

    /* }}} */
}
/* }}} */
/* VIM settings {{{
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * soft-stop-width: 4
 * c indent on
 * End:
 * vim600: sw=4 ts=4 sts=4 cindent fdm=marker et
 * vim<600: sw=4 ts=4
 * }}} */
?>
