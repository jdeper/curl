<?php

namespace veqryn\Curl;

/**
 * Class Curl
 * A basic CURL wrapper
 *
 * See the README for documentation/examples or http://php.net/curl for more information about the libcurl extension for PHP
 *
 * @package Curl
 * @author Sean Huber <shuber@huberry.com>
 **/
class Curl {

    /**
     * The file to read and write cookies to for requests
     *
     * @var string
     **/
    public $cookie_file;

    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
     **/
    public $follow_redirects = true;

    /**
     * An associative array of headers to send along with requests
     *
     * @var array
     **/
    public $headers = array();

    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
     **/
    public $options = array();

    /**
     * The referer header to send along with requests
     *
     * @var string
     **/
    public $referer;

    /**
     * When set to something greater than zero allows for retrys on exceptions
     *
     * @var string
     **/
    public $exception_retry_attempts = 0;

    /**
     * The user agent to send along with requests
     *
     * @var string
     **/
    public $user_agent;

    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
     **/
    protected $request;

    /**
     * Stores the HTTP auth credentials
     *
     * @var $userpwd
     * @access protected
     **/
    protected $userpwd;

    /**
     * Initializes a Curl object
     *
     * Sets the $cookie_file to "curl_cookie.txt" in the current directory
     * Also sets the $user_agent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION.' (http://github.com/veqryn/curl)' otherwise
     **/
    public function __construct() {
        $this->cookie_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'curl_cookie.txt';
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP ' . PHP_VERSION . ' (http://github.com/veqryn/curl)';
    }

    /**
     * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful.
     * If unsuccessful, throws CurlException
     *
     * @param string $url
     * @param mixed|string|array $vars
     * @return CurlResponse object
     * @throws CurlException
     */
    public function delete($url, $vars = array()) {
        return $this->request('DELETE', $url, $vars);
    }

    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful.
     * If unsuccessful, throws CurlException
     *
     * @param string $url
     * @param mixed|array|string $vars
     * @return CurlResponse object
     * @throws CurlException
     **/
    public function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }

    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful.
     * If unsuccessful, throws CurlException
     *
     * @param string $url
     * @param mixed|array|string $vars
     * @return CurlResponse object
     * @throws CurlException
     **/
    public function head($url, $vars = array()) {
        return $this->request('HEAD', $url, $vars);
    }

    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful.
     * If unsuccessful, throws CurlException
     *
     * @param string $url
     * @param mixed|array|string $vars
     * @param mixed|string|null $enctype
     * @return CurlResponse object
     * @throws CurlException
     */
    public function post($url, $vars = array(), $enctype = null) {
        return $this->request('POST', $url, $vars, $enctype);
    }

    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful.
     * If unsuccessful, throws CurlException
     *
     * @param string $url
     * @param mixed|array|string $vars
     * @return CurlResponse
     * @throws CurlException
     **/
    public function put($url, $vars = array()) {
        return $this->request('PUT', $url, $vars);
    }

    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful.
     * If unsuccessful, throws CurlException
     *
     * @param string $method
     * @param string $url
     * @param mixed|array|string $vars
     * @param mixed|string|null $enctype
     * @return CurlResponse object
     * @throws CurlException
     */
    public function request($method, $url, $vars = array(), $enctype = null) {
        $this->request = curl_init();
        if (is_array($vars) && $enctype != 'multipart/form-data') {
            $vars = http_build_query($vars, '', '&');
        }

        $this->set_request_method($method);
        $this->set_request_options($url, $vars);
        $this->set_request_headers();

        $response = curl_exec($this->request);
        if (!$response) {

            if ($this->exception_retry_attempts == 0) {
                throw new CurlException(curl_error($this->request), curl_errno($this->request));
            } else {
                // used when retry on exception option is set
                $retry = 0;
                while(curl_errno($this->request) == CURLE_OPERATION_TIMEOUTED && $retry < $this->exception_retry_attempts){
                    $response = curl_exec($this->request);
                    $retry = $retry + 1;
                }

            }

        }

        $response = new CurlResponse($response,$this->request);
        curl_close($this->request);
	    if (isset($response->headers['Status-Code']) && $response->headers['Status-Code'] == 404) {
		    $response->body = null;
	    }

        return $response;
    }

    /**
     * Sets the user and password for HTTP auth basic authentication method.
     *
     * @param mixed|string|null $username
     * @param mixed|string|null $password
     * @return Curl this for chaining
     */
    public function setAuth($username, $password = null) {
        if (null === $username) {
            $this->userpwd = null;
            return $this;
        }

        $this->userpwd = $username . ':' . $password;
        return $this;
    }

	public function setHeader($name,$value) {
		$this->headers[$name] = $value;
		return $this;
	}

    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
     **/
    protected function set_request_headers() {
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
     **/
    protected function set_request_method($method) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @return void
     * @access protected
     **/
    protected function set_request_options($url, $vars) {
        curl_setopt($this->request, CURLOPT_URL, $url);
        if (!empty($vars)) {
            curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
        }

        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_HEADER, true);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);
        if ($this->cookie_file) {
            curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        if ($this->follow_redirects) {
            curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->request, CURLOPT_MAXREDIRS, 10);
        }
        if ($this->referer) {
            curl_setopt($this->request, CURLOPT_REFERER, $this->referer);
        }
        if ($this->userpwd) {
            curl_setopt($this->request, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->request, CURLOPT_USERPWD, $this->userpwd);
        } else {
            curl_setopt($this->request, CURLOPT_HTTPAUTH, false);
        }

        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }

    /**
     * Returns an associative array of curl options currently configured.
     * Includes everything in "curl_getinfo"
     *
     * @return array Associative array of curl options
     */
    public function get_request_options() {
        return curl_getinfo($this->request);
    }

}
