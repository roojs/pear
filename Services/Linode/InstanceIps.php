<?php

require_once 'Services/Linode/Linode.php';

class Services_Linode_InstanceIps extends Services_Linode_Linode {
    
    private $instanceId;
    
    /**
     * Constructor
     * 
     * @param string $apiToken Linode API token
     * @param int|string $instanceId Instance ID or name
     */
    
    public function __construct($apiToken, $instanceId) {
        parent::__construct($apiToken);
        $this->instanceId = $instanceId;
    }
    
    /**
     * Get IP addresses for this instance
     * 
     * @return array|false IP data array or false on failure
     */
    
    public function get() {
        $data = $this->request('GET', "/linode/instances/{$this->instanceId}/ips");
        return $data;
    }
    
    /**
     * Update reverse pointer for an IP address
     * 
     * @param string $ip IP address
     * @param string $hostname Reverse DNS hostname
     * @return bool True on success, false on failure
     */
    
    public function updateReversePointer($ip, $hostname) {
        $data = [
            'ipv6' => $ip,
            'reverse_pointer' => $hostname
        ];
        
        $response = $this->request('PUT', "/linode/instances/{$this->instanceId}/ips", $data);
        
        return $response !== false;
    }
}

