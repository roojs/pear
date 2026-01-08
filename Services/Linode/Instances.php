<?php

require_once 'Services/Linode/Linode.php';

class Services_Linode_Instances extends Services_Linode_Linode {
    
    /**
     * Find an instance by IP address
     * 
     * @param string $ip IP address to search for
     * @return array|false Instance data array (including id) or false if not found
     */
    
    function findByIp($ip) {
        // List all instances
        $data = $this->request('GET', '/linode/instances');
        
        if (!$data || !isset($data['data'])) {
            error_log("Failed to list Linode instances");
            return false;
        }
        require_once 'Services/Linode/InstanceIps.php';

        // Iterate through instances to find the one with matching IP
        foreach ($data['data'] as $instance) {
            $instanceIps = new Services_Linode_InstanceIps($this->apiToken, $instance['id']);
            $ipData = $instanceIps->get();
            
            if ($ipData && $this->ipMatches($ipData, $ip)) {
                return $instance;
            }
        }
        
        return false;
    }
    
    /**
     * Check if an IP address matches any IP in the IP data
     * 
     * @param array $ipData IP data from InstanceIps->get()
     * @param string $ip IP address to match
     * @return bool True if IP matches
     */
    
    private function ipMatches($ipData, $ip) {
        if (!$ipData) {
            return false;
        }
        
        // Check IPv4 addresses
        if (isset($ipData['ipv4']['public']) && is_array($ipData['ipv4']['public'])) {
            foreach ($ipData['ipv4']['public'] as $ipv4) {
                if ($ipv4['address'] === $ip) {
                    return true;
                }
            }
        }
        // Check IPv4 private addresses
        if (isset($ipData['ipv4']['private']) && is_array($ipData['ipv4']['private'])) {
            foreach ($ipData['ipv4']['private'] as $ipv4) {
                if ($ipv4['address'] === $ip) {
                    return true;
                }
            }
        }
        // Check IPv6 addresses
        if (isset($ipData['ipv6']['link_local']) && $ipData['ipv6']['link_local']['address'] === $ip) {
            return true;
        }
        if (isset($ipData['ipv6']['slaac']) && $ipData['ipv6']['slaac']['address'] === $ip) {
            return true;
        }
        if (isset($ipData['ipv6']['global']) && is_array($ipData['ipv6']['global'])) {
            foreach ($ipData['ipv6']['global'] as $ipv6) {
                if ($ipv6['address'] === $ip || $ipv6['range'] === $ip) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

