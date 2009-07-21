<?php

// Restful Rails
//
// Author:  Sean Huber (shuber@huberry.com)
// Date:    January 2008
//
// View the README for documentation/examples

require_once 'curl.php'; // http://github.com/shuber/curl
require_once 'restful_rails_response.php';

class RestfulRails {
    
    public $request_prefix = 'http://';
    public $request_suffix = '';
    
    protected $curl;
    protected $error = '';
    protected $response_type;
    protected $response_types = array();
    
    public function __construct($request_prefix = false, $request_suffix = false) {
        $this->curl = new Curl;
        
        $this->add_response_type('json', array('RestfulRailsResponse', 'json'), '.js');
        $this->add_response_type('text', array('RestfulRailsResponse', 'text'), '');
        $this->add_response_type('xml', array('RestfulRailsResponse', 'xml'), '.xml');
        
        $this->set_response_type('text');
        
        if ($request_prefix) $this->request_prefix = $request_prefix;
        if ($request_suffix) $this->request_suffix = $request_suffix;
    }
    
    public function error() {
        return $this->error;
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
    
    protected function request($method, $url, $vars = array()) {
        if ($method != 'get') $vars['_method'] = $method;
        $url = $this->request_prefix.$url.$this->request_suffix;
        $response = ($method == 'get') ? $this->curl->get($url, $vars) : $this->curl->post($url, $vars);
        if ($response) {
            if ($response->headers['Status-Code'] == '404') {
                $this->error = 'Request to "'.$url.'" responded with a 404 - Not Found';
                return false;
            } else {
                try {
                    $type_casted_response = call_user_func($this->response_types[$this->response_type]['callback'], $response);
                } catch (Exception $e) {
                    $type_casted_response = false;
                    $this->error = $e->getMessage();
                }
            }
            return $type_casted_response;
        } else {
            $this->error = $this->curl->error();
            return false;
        }
    }
    
}

?>