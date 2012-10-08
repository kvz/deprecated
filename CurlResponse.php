<?php

/**
 * Parses the response from a Curl request into an object containing
 * the response body and an associative array of headers
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
**/
class CurlResponse {

    /**
     * The body of the response without the headers block
     *
     * @var string
    **/
    public $body = '';

    /**
     * An associative array containing the response's headers
     *
     * @var array
    **/
    public $headers = array();

    /**
     * Accepts the result of a curl request as a string
     *
     * <code>
     * $response = new CurlResponse(curl_exec($curl_handle));
     * echo $response->body;
     * echo $response->headers['Status'];
     * </code>
     *
     * @param string $response
    **/
    function __construct($response) {
        # Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        # Start the body
        $this->body = $response;

        # Extract headers from response
        preg_match_all($pattern, $response, $matches);

        # Go though all the headers (maybe a redirect prior the 200) but only get the last headers
        foreach ($matches[0] as $match) {
            $headers = explode("\r\n", str_replace("\r\n\r\n", '', $match));

            # Remove headers from the response body
            $this->body = str_replace($match, '', $this->body);
        }

        # Extract the headers
        $this->headers = $this->_extract($headers);
    }

    /**
     * Extract the headers information from an string
     *
     * @param string $headers
     */
    function _extract($headers) {
        # Extract the version and status from the first header
        $version_and_status = array_shift($headers);
        preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches);

        $_headers = array(
            'Http-Version' => $matches[1],
            'Status-Code'  => $matches[2],
            'Status'       => $matches[2].' '.$matches[3]
        );

        # Convert headers into an associative array
        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $_headers[$matches[1]] = $matches[2];
        }

        return $_headers;
    }

    /**
     * Returns the response body
     *
     * <code>
     * $curl = new Curl;
     * $response = $curl->get('google.com');
     * echo $response;  # => echo $response->body;
     * </code>
     *
     * @return string
    **/
    function __toString() {
        return $this->body;
    }

}
?>