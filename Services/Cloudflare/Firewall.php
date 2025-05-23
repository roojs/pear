<?php

class Services_Cloudflare_Firewall {
    
    
    var $baseURL;
    var $apiToken;
    var $account;
    
    /**
     *  apiToken
     *
     *
     */
    
    
    
    function __construct($cfg)
    {
        // no error checking - should result in warnings if done wrong...
        foreach($cfg as $k=>$v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
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
        $target = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ip' : 'ip6';
        
        if ($ip !== false) {
             return $this->request("GET", "?configuration.target={$target}&configuration.value={$ip}");
        }
        $ret = array();
        $page = 1;

        
        //"result_info": {
            //"count": 1,
            //"page": 1,
            //"per_page": 20,
            //"total_count": 2000
        //}
 
        
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
        // should not get here...
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

        $rules = isset($rules->result) ? $rules->result : $rules;
        
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
    
    function request($method, $param, $data = array()) 
    {
         // Headers for API requests
        $headers = array(
            "Authorization: Bearer {$this->apiToken}",
            "Content-Type: application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        switch($method) {
            
            case 'GET':
                break;
           
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            
            case 'POST' :
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $param .= " / " . json_encode($data);
                break;
            
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $param .= " / " . json_encode($data);
                break;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 ) {
            $ret = json_decode($response);
            
            
            
            if (!$ret->success) {
                return $this->raiseError("Failed : $method : $params returned {$httpCode} - ". json_encode($ret['errors']));
            }
            if (isset($ret->result_info)) {
                return $ret;
            }
            return $ret->result;
        }
        return $this->raiseError("Failed : $method : $param returned {$httpCode} - {$response}");
        
    }
    
    
    

    // Function to add a firewall rule
    function create($mode, $ip,  $notes) 
    {
        return $this->request("POST", "",    array(
            'mode' => $mode,
            'configuration' => array(
                'target' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ip' : 'ip6',
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
 
    function raiseError($message = null,
                         $code = null,
                         $mode = null,
                         $options = null,
                         $userinfo = null,
                         $error_class = null,
                         $skipmsg = false)
    {
        //$this->debug("ERROR: $message");
        require_once 'PEAR.php';
        $p = new PEAR();
        return $p->raiseError($message,$code,$mode,$options,$userinfo,$error_class,$skipmsg);
    }
}