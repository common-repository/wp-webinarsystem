<?php
/**
 * The base REST class for Enormail API
 *
 * This class provides all the tools to communicate
 * with a REST API.
 * 
 * @package Enormail API
 * @version 1.0
 * @author Enormail
 */
class Em_Rest {
    
    protected $host = 'https://api.enormail.eu/api/1.0/';
    
    protected $key = '';
    
    protected $version = '1.0';
    
    public function __construct($key)
    {
        $this->key = $key;
    }

    public function get($uri, $params = array())
    {
        return $this->_exec('GET', $uri, $params);
    }
    
    public function post($uri, $params = array())
    {
        return $this->_exec('POST', $uri, $params);
    }
    
    public function put($uri, $params = array())
    {
        return $this->_exec('PUT', $uri, $params);
    }
    
    public function delete($uri, $params = array())
    {
        return $this->_exec('DELETE', $uri, $params);
    }
    
    private function _exec($method, $uri, $params = array())
    {
        // Init
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
        $ch  = curl_init();
        $uri = ltrim($uri, '/');

        // Options
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_HEADER, false);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_USERPWD, $this->key.':password');
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_USERAGENT, 'EM REST API WRAPPER '.$this->version);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Set request
        switch(strtoupper($method))
        {
            case 'GET' :
            
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_URL, $this->host . $uri  . '?' . http_build_query($params));
            
            break;
            
            case 'POST' :
            
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_URL, $this->host . $uri);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_POST, true);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_prep_post_vars($params));
            
            break;
            
            case 'PUT' :
            
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_URL, $this->host . $uri);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_prep_post_vars($params));
            
            break;
            
            case 'DELETE' :
            
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_URL, $this->host . $uri  . '?' . http_build_query($params));
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            
            break;            
        }
        
        // Fetch output
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
        $output = curl_exec($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close connection
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
        curl_close($ch);
        
        // Set response
        $return = new Em_Rest_Response(array(
            'code' => $code,
            'response' => $output
        ));
        
        // Return
        return (string) $return;
    }
    
    private function _prep_post_vars($vars, $sep = '&')
    {
        $str = '';
        
        foreach ($vars as $k => $v)
        {
            if (is_array($v))
            {
                foreach($v as $vk => $vi)
                {
                    $str .= urlencode($k).'['.$vk.']'.'='.urlencode($vi).$sep;
                }
            } 
            else 
            {
                $str .= urlencode($k).'='.urlencode($v).$sep;
            }
        }
        
        return substr($str, 0, -1);
    }
    
}

class Em_Rest_Response {
    
    public function __construct($response)
    {
        // Set response
        $this->http_code = $response['code'];
        $this->http_response = $response['response'];
    }
    
    public function __toString()
    {
        return $this->http_response;
    }

    
}

class Em_Rest_Exception extends Exception {}