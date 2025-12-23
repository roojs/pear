<?php

require_once 'Services/Cloudflare.php';

class Services_Cloudflare_Dns extends Services_Cloudflare {
    
    
    var $zoneId;
    
    /**
     *  apiToken
     *
     *
     */
    
    
    
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $this->baseURL = "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records";
    }
    /**
     * get a single record, or all of them.
     * @param string $recordId (optional) - the record ID to fetch (leave empty to fetch all)
     * @param string $name (optional) - filter by DNS name
     * @param string $type (optional) - filter by record type (A, AAAA, CNAME, MX, TXT, etc.)
     * @return array records: eg..
     * {
     *   "id": "372e67954025e0ba6aaa6b9869",
     *   "type": "A",
     *   "name": "example.com",
     *   "content": "198.51.100.4",
     *   "proxied": false,
     *   "ttl": 3600
     * }
     * 
     */
    
    function get($recordId = false, $name = false, $type = false)
    {
        if ($recordId !== false) {
             return $this->request("GET", "/{$recordId}");
        }
        
        $params = array();
        if ($name !== false) {
            $params[] = "name=" . urlencode($name);
        }
        if ($type !== false) {
            $params[] = "type=" . urlencode($type);
        }
        
        $queryString = !empty($params) ? "?" . implode("&", $params) : "";
        
        $ret = array();
        $page = 1;

        
        //"result_info": {
            //"count": 1,
            //"page": 1,
            //"per_page": 20,
            //"total_count": 2000
        //}
 
        
        while(true) {
            $sep = empty($params) ? "?" : "&";
            $add = $this->request("GET", $queryString . $sep . 'per_page=1000&page=' . $page++);
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
     * update a DNS record
     * @param string $recordId - the record ID to update
     * @param array $data - array with keys: type, name, content, ttl (optional), proxied (optional), priority (optional)
     * @return object updated record
     * 
     */
    
    function update($recordId, $data)
    {
        return $this->request("PATCH", "/{$recordId}", $data);
    }
    
    /**
     * update a DNS record by name and type (finds the record first)
     * @param string $name - the DNS name
     * @param string $type - the record type
     * @param string $content - the new content value
     * @param int $ttl - time to live (optional, default 3600)
     * @param bool $proxied - whether to proxy through Cloudflare (optional, default false)
     * @param int $priority - priority for MX records (optional)
     * @return object updated record
     */
    
    function updateByName($name, $type, $content, $ttl = 3600, $proxied = false, $priority = null)
    {
        $records = $this->get(false, $name, $type);
        
        if (is_a($records, 'PEAR_Error')) {
            return $records;
        }

        // get() returns an array when fetching multiple records
        $records = is_array($records) ? $records : (isset($records->result) ? $records->result : array());
        
        // no such record -> create
        if(empty($records)) {
            return $this->create($type, $name, $content, $ttl, $proxied, $priority);
        }

        $record = $records[0];
        
        $data = array(
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
            'proxied' => $proxied
        );
        
        if ($priority !== null) {
            $data['priority'] = $priority;
        }
        
        return $this->update($record->id, $data);
    }
    
    // Function to create a DNS record
    function create($type, $name, $content, $ttl = 3600, $proxied = false, $priority = null) 
    {
        $data = array(
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
            'proxied' => $proxied
        );
        
        if ($priority !== null) {
            $data['priority'] = $priority;
        }
        
        return $this->request("POST", "", $data);
    }
    
    /**
     * set/create or update a DNS record
     * @param string $type - record type (A, AAAA, CNAME, MX, TXT, etc.)
     * @param string $name - DNS name
     * @param string $content - record content
     * @param int $ttl - time to live (optional, default 3600)
     * @param bool $proxied - whether to proxy through Cloudflare (optional, default false)
     * @param int $priority - priority for MX records (optional)
     * @return object created or updated record
     */
    
    function set($type, $name, $content, $ttl = 3600, $proxied = false, $priority = null)
    {
        return $this->updateByName($name, $type, $content, $ttl, $proxied, $priority);
    }
 

    // Function to delete a DNS record
    function delete($recordId) 
    {
        return $this->request("DELETE", "/"  . $recordId);
    }
    
    /**
     * delete a DNS record by name and type
     * @param string $name - the DNS name
     * @param string $type - the record type
     * @return object deletion result
     */
    
    function deleteByName($name, $type)
    {
        $records = $this->get(false, $name, $type);
        
        if (is_a($records, 'PEAR_Error')) {
            return $records;
        }

        // get() returns an array when fetching multiple records
        $records = is_array($records) ? $records : (isset($records->result) ? $records->result : array());
        
        if(empty($records)) {
            return $this->raiseError("No DNS record found with name '{$name}' and type '{$type}'");
        }

        $record = $records[0];
        return $this->delete($record->id);
    }
}

