RestfulRails
============

An extendable PHP library to communicate with RESTful rails applications


Installation
------------

	git clone git://github.com/shuber/restful_rails.git


Note
----

This library relies on the libcurl extension so make sure that you have it installed and enabled.
See [http://php.net/curl](http://php.net/curl) for more information.

This library also relies on my basic PHP Curl wrapper which can be found at [http://github.com/shuber/curl](http://github.com/shuber/curl).

This library parses xml with the standard SimpleXML library by default.
See [http://php.net/simplexml](http://php.net/simplexml) for more information.


Usage
-----

Initialize the RestfulRails class

	$api = new RestfulRails;

You may optionally set a response type. The default response type is set to "text". 
This method also accepts a second optional argument *$set\_request_suffix* which defaults 
to true. This argument will automatically add the correct suffix to all	of your requests 
if enabled. For example, setting a response type of "xml" will automatically append ".xml" 
to the end of all your requests. Response types included by default are "json", "text", and "xml".
See the method *$api->add\_response_type()* below for overwriting or adding additional response types.

	$api->set_response_type($type [, $set_request_suffix = true]);
	
	// Request suffixes
	$api->set_response_type('json'); // sets $api->request_suffix to '.js'
	$api->set_response_type('text'); // sets $api->request_suffix to ''
	$api->set_response_type('xml');  // sets $api->request_suffix to '.xml'

To overwrite or add additional response types, you must specify a type and callback method. The callback method
can be passed as a string: *'method\_name'*, or an array: *array('class\_name\_or\_object', 'method_name')*.
If a string is passed, the method assumes that the object *$this* contains the method that is to be called. 
If an array is passed, the callback method must be declared as public in order to be called. An optional third
argument *$request_suffix*, which defaults to an empty string, may be passed as well.

	class MyApi extends RestfulRails {
	    public function response_from_csv($curl_csv_response) {
	        // Logic to convert this csv response into an array or object or something
	        return $converted_response;
	    }
	}
	
	$api = new MyApi;
	$api->add_response_type('csv', 'response_from_csv', '.csv');
	$api->set_response_type('csv');
	$posts = $api->get('localhost.com/blog/posts'); // Returns whatever MyApi::response_from_csv() returns
	
	
	
	class CsvParser {
	    // This method must be "public" in order to be called
	    public function parse($curl_csv_response) {
	        // Logic to convert $curl_response into an array or object or something
	        return $converted_csv;
	    }
	}
	
	$api = new RestfulRails;
	$api->add_response_type('csv', array('CsvParser','parse'), '.csv');
	$api->set_response_type('csv');
	$posts = $api->get('localhost.com/blog/posts'); // Returns whatever CsvParser::parse() returns

You also have access to the request_prefix/suffix properties:

	$api->request_prefix = 'http://localhost.com/'; // defaults to 'http://'
	$api->request_suffix = '.xml'; // defaults to ''

You may optionally set the request_prefix/suffix properties when initializing the RestfulRails class:

	$api = new RestfulRails('http://localhost.com/', '.xml');

With the class initialized, you now have access to the four request methods below.
Each of them returns a type casted response formatted to the response type that you set
with the *$api->set_response_type()* method shown above. These methods will 
return false if an error/exception occurs.

	$api->delete($url [, $vars = array()]);
	$api->get($url [, $vars = array()]); // Builds a query string if passed an array of $vars
	$api->post($url [, $vars = array()]);
	$api->put($url [, $vars = array()]);

If an error/exception occurred, you can check what it was with:

	$api->error(); // Returns a string with the last error message


Example
-------

	$api = new RestfulRails;
	$api->request_prefix = 'http://localhost.com/blog/';
	
	
	$api->set_response_type('xml');
	$response = $api->get('posts/1');
	if ($response) {
	    echo '<pre>';
	    print_r($response);
	    echo '</pre>';
	} else {
	    echo $api->error();
	}
	
	
	$api->set_response_type('text');
	$api->put('posts/1', array('post[body]' => 'Test from the API'));
	
	
	$api->set_response_type('json');
	$response = $api->get('posts/1');
	if ($response) {
	    echo '<pre>';
	    print_r($response);
	    echo '</pre>';
	} else {
	    echo $api->error();
	}


Contact
-------

Problems, comments, and suggestions all welcome: [shuber@huberry.com](mailto:shuber@huberry.com)