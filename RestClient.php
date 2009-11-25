<?php
// Restful Rails
//
// Author:  Sean Huber (shuber@huberry.com)
// Date:    January 2008
//
// Author:  Kevin van Zonneveld (kvz@php.net)
// Date:    November 2009
//
// View the README.markdown for documentation/examples

require_once dirname(__FILE__). '/Curl.php'; // http://github.com/shuber/curl
require_once dirname(__FILE__). '/CurlResponse.php';
require_once dirname(__FILE__). '/RestClientResponse.php';

class RestClient {
    public $request_prefix = 'http://';
    public $request_suffix = '';
    
    protected $url;
    
    protected $Curl;
    protected $error = '';
    protected $response_type;
    protected $response_types = array();

    protected $failCodes  = array(403);
    protected $crashCodes = array(404, 500);
    
    public function __construct($request_prefix = false, $request_suffix = false, $restOpts = array()) {
        $this->Curl = new Curl();
        if (array_key_exists('userAgent', $restOpts)) {
            $this->Curl->user_agent = $restOpts['userAgent'];
        }
        if (array_key_exists('cookieFile', $restOpts)) {
            $this->Curl->cookie_file = $restOpts['cookieFile'];
        }
        
        $this->add_response_type('json', array('RestClientResponse', 'json'), '.json');
        $this->add_response_type('text', array('RestClientResponse', 'text'), '');
        $this->add_response_type('xml', array('RestClientResponse', 'xml'), '.xml');
        
        $this->set_response_type('text');
        
        if ($request_prefix) $this->request_prefix = $request_prefix;
        if ($request_suffix) $this->request_suffix = $request_suffix;
    }

    public function error() {
        return $this->error;
    }

    public function headers($key, $val = null) {
        if (is_array($key)) {
            foreach($key as $k => $v) {
                $this->headers($k, $v);
            }
            return $this->_options;
        }
        if (func_num_args() === 2) {
            $this->Curl->headers[$key] = $val;
        }
        return $this->Curl->headers[$key];
    }


    public function delete($url, $vars = array()) {
        return $this->request('delete', $url, $vars);
    }
    
    public function get($url, $vars = array()) {
        return $this->request('get', $url, $vars);
    }
    
    public function post($url, $vars = array()) {
        return $this->request('post', $url, $vars);
    }
    
    public function put($url, $vars = array()) {
        return $this->request('put', $url, $vars);
    }
    
    public function add_response_type($type, $callback, $request_suffix = '') {
        if (is_array($callback)) {
            $object = $callback[0];
            $method = $callback[1];
        } else {
            $object = $this;
            $method = $callback;
        }
        if (method_exists($object, $method)) {
            $this->response_types[strtolower($type)] = array('callback' => array($object, $method), 'request_suffix' => $request_suffix);
            return true;
        } else {
            throw new Exception('Callback method "'.get_class($object).'::'.$method.'" does not exist');
            return false;
        }
    }
    
    public function set_response_type($type, $set_request_suffix = true) {
        $type = strtolower($type);
        if (in_array($type, array_keys($this->response_types))) {
            $this->response_type = $type;
            if ($set_request_suffix) $this->request_suffix = $this->response_types[$type]['request_suffix'];
            return true;
        } else {
            throw new Exception('Invalid response type. Must be one of these: "'.join('", "', array_keys($this->response_types)).'"');
            return false;
        }
    }

    public function lastUrl() {
        return $this->url;
    }
    
    protected function request($method, $url, $vars = array()) {
        if ($method != 'get') $vars['_method'] = $method;
        $this->url = $this->request_prefix.$url.$this->request_suffix;
        $response  = ($method == 'get') ? $this->Curl->get($this->url, $vars) : $this->Curl->post($this->url, $vars);
        $crash     = $fail = false;
        if ($response) {
            if (($fail = in_array($response->headers['Status-Code'], $this->failCodes))
                || ($crash = in_array($response->headers['Status-Code'], $this->crashCodes))) {
                $this->error = 'Request to "'.$this->url.'" responded with a ' . $response->headers['Status'];
            }
            
            if ($crash) {
                return false;
            }

            try {
                $type_casted_response = call_user_func($this->response_types[$this->response_type]['callback'], $response);
            } catch (Exception $e) {
                $type_casted_response = false;
                $this->error = $e->getMessage();
            }
            return $type_casted_response;
        } else {
            $this->error = $this->Curl->error();
            return false;
        }
    }
}
?>