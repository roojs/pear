<?php

class Net_RDAP {
    
    static $servers = array(
        // gTLDs - use bootstrap service
        'com' => 'https://rdap.org/domain/',
        'net' => 'https://rdap.org/domain/',
        
        // ccTLDs from gist
        'ar' => 'https://rdap.nic.ar/domain/',
        'ch' => 'https://rdap.nic.ch/domain/',
        'li' => 'https://rdap.nic.ch/domain/',
        'cr' => 'https://rdap.nic.cr/domain/',
        'cv' => 'https://rdap.nic.cv/domain/',
        'cx' => 'https://rdap.nic.cx/domain/',
        'gs' => 'https://rdap.nic.gs/domain/',
        'cz' => 'https://rdap.nic.cz/domain/',
        'ms' => 'https://rdap.nic.ms/domain/',
        'nf' => 'https://rdap.nic.nf/domain/',
        'sd' => 'https://rdap.nic.sd/domain/',
        'ss' => 'https://rdap.nic.ss/domain/',
        'vi' => 'https://rdap.nic.vi/domain/',
        'tv' => 'https://rdap.nic.tv/domain/',
        'ky' => 'https://whois.kyregistry.ky/rdap/domain',
        'fr' => 'https://rdap.nic.fr/domain/',
        'pm' => 'https://rdap.nic.pm/domain/',
        're' => 'https://rdap.nic.re/domain/',
        'tf' => 'https://rdap.nic.tf/domain/',
        'wf' => 'https://rdap.nic.wf/domain/',
        'yt' => 'https://rdap.nic.yt/domain/',
        'zm' => 'https://rdap.nic.zm/domain/',
        'ca' => 'https://rdap.ca.fury.ca/domain/',
        'fm' => 'https://rdap.centralnic.com/fm/domain/',
        'fo' => 'https://rdap.centralnic.com/fo/domain/',
        'gd' => 'https://rdap.centralnic.com/gd/domain/',
        'pw' => 'https://rdap.centralnic.com/pw/domain/',
        'vg' => 'https://rdap.centralnic.com/vg/domain/',
        'fi' => 'https://rdap.fi/rdap/rdap/domain',
        'kg' => 'http://rdap.cctld.kg/domain/',
        'ua' => 'https://rdap.hostmaster.ua/domain',
        'uz' => 'https://rdap.cctld.uz/domain/',
        'si' => 'https://rdap.register.si/domain/',
        'br' => 'https://rdap.registro.br/domain/',
        'ec' => 'https://rdap.registry.ec/domain/',
        'ai' => 'https://rdap.whois.ai/domain/',
        'cc' => 'https://tld-rdap.verisign.com/cc/v1/domain/',
        'tz' => 'https://whois.tznic.or.tz/rdap/domain/',
        'id' => 'https://rdap.pandi.id/rdap/domain/',
        'no' => 'https://rdap.norid.no/domain/',
        'uk' => 'https://rdap.nominet.uk/uk/domain/',
        'pn' => 'https://rdap.nominet.uk/pn/domain/',
        'is' => 'https://rdap.isnic.is/domain/',
        'au' => 'https://rdap.identitydigital.services/rdap/domain/',
        'ag' => 'https://rdap.identitydigital.services/rdap/domain/',
        'bz' => 'https://rdap.identitydigital.services/rdap/domain/',
        'gi' => 'https://rdap.identitydigital.services/rdap/domain/',
        'pr' => 'https://rdap.identitydigital.services/rdap/domain/',
        'sc' => 'https://rdap.identitydigital.services/rdap/domain/',
        'vc' => 'https://rdap.identitydigital.services/rdap/domain/',
        'gy' => 'https://rdap.coccaregistry.org/domain/',
        'ki' => 'https://rdap.coccaregistry.org/domain/',
        'ht' => 'https://rdap.coccaregistry.org/domain/',
        'hn' => 'https://rdap.coccaregistry.org/domain/',
        'tl' => 'https://rdap.coccaregistry.org/domain/',
        'sb' => 'https://rdap.coccaregistry.org/domain/',
        'af' => 'https://rdap.coccaregistry.org/domain/',
        'de' => 'https://rdap.denic.de/domain/',
        'nl' => 'https://rdap.sidn.nl/domain/',
        'sn' => 'http://rdap.nic.sn/domain/',
        'ga' => 'https://rdap.nic.ga/domain/',
        'mr' => 'https://rdap.nic.mr/domain/',
        'ad' => 'https://rdap.nic.ad/domain/',
        'td' => 'https://rdap.nic.td/domain',
        'ke' => 'https://rdap.kenic.or.ke/domain',
        'so' => 'https://rdap.nic.so/domain/',
        'lb' => 'https://rdap.lbdr.org.lb/domain/',
    );
    var $domain = '';
    var $result = false;

    function __construct($domain = '')
    {
        $this->domain = preg_replace('/^www\./i', '', trim(strtolower((string) $domain)));
        $this->result = false;
    }

    function isRdapSupported()
    {
        if (empty($this->domain)) {
            return false;
        }
        
        $parts = explode('.', $this->domain);
        if (count($parts) < 1) {
            return false;
        }
        
        $tld = array_pop($parts);
        return isset(self::$servers[$tld]);
    }

    function query()
    {
        if (empty($this->domain)) {
            return false;
        }
        
        // Extract TLD
        $parts = explode('.', $this->domain);
        if (count($parts) < 1) {
            return false;
        }
        
        $tld = array_pop($parts);
        
        // Check if TLD is supported
        if (!isset(self::$servers[$tld])) {
            return false;
        }
        
        // Build URL
        $baseUrl = self::$servers[$tld];
        $url = $baseUrl . $this->domain;
        
        // Create HTTP context
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0',
                'method' => 'GET',
                'header' => 'Accept: application/rdap+json'
            )
        ));
        
        // Fetch RDAP data
        $json_data = @file_get_contents($url, false, $context);
        if (empty($json_data)) {
            return false;
        }
        
        // Decode JSON
        $data = json_decode($json_data, true);
        if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        // Check for error responses
        if (isset($data['errorCode']) || isset($data['error'])) {
            return false;
        }
        
        $this->result = $data;
        return true;
    }

    function expirationDate()
    {
        if ($this->result === false) {
            return false;
        }
        
        if (!isset($this->result['events']) || !is_array($this->result['events'])) {
            return false;
        }
        
        foreach ($this->result['events'] as $event) {
            if (!isset($event['eventAction']) || $event['eventAction'] != 'expiration' || !isset($event['eventDate'])) {
                continue;
            }
            $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $event['eventDate']);
            if ($date === false) {
                $date = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $event['eventDate']);
            }
            if ($date === false) {
                $date = new DateTime($event['eventDate']);
            }
            return $date;
        }
        
        return false;
    }
     
}
