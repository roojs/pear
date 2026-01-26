<?php

require_once 'Services/Cloudflare.php';

class Services_Cloudflare_Firewall extends Services_Cloudflare {
    
    
    var $account;
    
    /**
     *  apiToken
     *
     *
     */
    
    
    
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $this->baseURL = "https://api.cloudflare.com/client/v4/accounts/{$this->account}/firewall/access_rules/rules";
    }
    /**
     * get a single record, or all of them.
     * @param string $ip (optional) - the ip to fetch (leave empty to fetch all)
     * @return array records: eg..
     * {
      "configuration": {
        "target": "ip",
        "value": "198.51.100.4"
      },
      "mode": "challenge",
      "notes": "This rule is enabled because of an event that occurred on date X."
    }
     * 
     */
    
    function get($ip = false)
    {
        if ($ip !== false) {
            // No '/' - regular IP address
            if (strpos($ip, '/') === false) {
                $valid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
                $target = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ip' : 'ip6';
                return $valid ? $this->request("GET", "?configuration.target={$target}&configuration.value=" . urlencode($ip)) : false;
            }
            
            // Has '/' - CIDR range
            $bits = explode('/', $ip);
            $valid = filter_var($bits[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($bits[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
            $valid = $valid && in_array($bits[1], array('16')); // we don't support any other ranges yet
            $target = filter_var($bits[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ip' : 'ip6';
            return $valid ? $this->request("GET", "?configuration.target={$target}&configuration.value=" . urlencode($ip)) : false;
        }
        
        // Fetch all rules
        $ret = array();
        $page = 1;
        while(true) {
            $add = $this->request("GET", '?per_page=1000&page=' . $page++);
            if (is_a($add, 'PEAR_Error')) {
                return $add;
            }
            if (empty($add->result)) {
                return $ret;
            }
            foreach($add->result as $r) {
                if (isset($dupes[$r->id])) {
                    continue;
                }
                $dupes[$r->id] = true;
                $ret[] = $r;
            }
             
            if ($page > $add->result_info->total_pages) {
                return $ret;
            }
        }
    }
    /**
     * update a 
     * 
     */
    
    function update($ip, $notes, $mode = 'whitelist')
    {
        $rules = $this->get($ip);
        
        if (is_a($rules , 'PEAR_Error')) {
            return $rules;
        }

        // get() returns an array when fetching multiple records
        $rules = is_array($rules) ? $rules : (isset($rules->result) ? $rules->result : array());
        
        // no such rule -> add
        if(empty($rules)) {
            $this->create($mode, $ip, $notes);
            return;
        }

        $rule = $rules[0];

        // matching rule's mode is not 'whitelist' -> update
        if($rule->mode != $mode) {
            return $this->updateID(
                $rule->id,
                $rule->configuration->target,
                $rule->configuration->value,
                $notes,
                $mode
            );
            
        }
    }
    
    function updateID($id, $target, $ip, $notes, $mode = 'whitelist')
    {
        
        return $this->request("PATCH", "/{$id}",    array(
            'mode' => $mode,    
            'configuration' => array(
                'target' => $target,
                'value' => $ip
            ),
            'notes' => $notes
        ));
         
    }
    
    // Function to add a firewall rule
    function create($mode, $ip,  $notes, $target = false) 
    {
        return $this->request("POST", "",    array(
            'mode' => $mode,
            'configuration' => array(
                'target' => $target === false ? (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ip' : 'ip6' ) : $target,
                'value' => $ip
            ),
            'notes' => $notes
        ));
         
    }
 
 
 
    // Function to add a firewall rule
    function delete($id) 
    {
        return $this->request("DELETE", "/"  . $id);
    }
}