<?php

// Restful Rails - Response
//
// Author: 	Sean Huber (shuber@huberry.com)
// Date: 	January 2008
//
// Type casts CurlResponse objects

class RestfulRailsResponse {
	
	public function json($curl_response) {
		return array('response' => $curl_response, 'json' => json_decode($curl_response->body));
	}
	
	public function text($curl_response) {
		return array('response' => $curl_response, 'text' => $curl_response->body);
	}
	
	public function xml($curl_response) {
		return array('response' => $curl_response, 'xml' => new SimpleXMLElement($curl_response->body));
	}
	
}

?>