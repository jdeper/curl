# curl

A basic CURL wrapper for PHP (see [http://php.net/curl](http://php.net/curl) for more information about the libcurl extension for PHP)

The reason for this fork is to add support for getting the original request object. This allows us to check for http headers.

The library is in active use for the crawler on [https://foderpriser.dk](Foderpriser) and [https://fyringsolie-online.dk](Fyringsolie online)


## Installation

Click the `download` link above or `git clone git@github.com:veqryn/curl.git`

To install into your project, add these lines to your composer.json:

	"require": {
	    "veqryn/curl": "*"
	}


## Usage

### Initialization

Simply require and initialize the `Curl` class like so:

	require_once('vendor/autoload.php');

	use veqryn\Curl\Curl;
	use veqryn\Curl\CurlResponse;
	use veqryn\Curl\CurlException;

	// ...

	$curl = new Curl();


### Performing a Request

The Curl object supports 5 types of requests: HEAD, GET, POST, PUT, and DELETE. You must specify a url to request and optionally specify an associative array or string of variables to send along with it.

	$response = $curl->head($url, $vars = array());
	$response = $curl->get($url, $vars = array()); # The Curl object will append the array of $vars to the $url as a query string
	$response = $curl->post($url, $vars = array());
	$response = $curl->put($url, $vars = array());
	$response = $curl->delete($url, $vars = array());

To use a custom request methods, you can call the `request` method:

	$response = $curl->request('YOUR_CUSTOM_REQUEST_TYPE', $url, $vars = array());

All of the built in request methods like `put` and `get` simply wrap the `request` method. For example, the `post` method is implemented like:

	public function post($url, $vars = array(), $enctype = null) {
            return $this->request('POST', $url, $vars, $enctype);
        }

Examples:

	$response = $curl->get('google.com?q=test');

	# The Curl object will append '&some_variable=some_value' to the url
	$response = $curl->get('google.com?q=test', array('some_variable' => 'some_value'));
	
	$response = $curl->post('test.com/posts', array('title' => 'Test', 'body' => 'This is a test'));

All requests return a CurlResponse object (see below) or throw a CurlException if an error occurred. You can access the error string with the `$curl->error()` method.


### The CurlResponse Object

A normal CURL request will return the headers and the body in one response string. This class parses the two and places them into separate properties.

For example

	$response = $curl->get('google.com');
	echo $response->body; # A string containing everything in the response except for the headers
	print_r($response->headers); # An associative array containing the response headers

Which would display something like

	<html>
	<head>
	<title>Google.com</title>
	</head>
	<body>
	Some more html...
	</body>
	</html>

	Array
	(
	    [Http-Version] => 1.0
	    [Status-Code] => 200
	    [Status] => 200 OK
	    [Cache-Control] => private
	    [Content-Type] => text/html; charset=ISO-8859-1
	    [Date] => Wed, 07 May 2008 21:43:48 GMT
	    [Server] => gws
	    [Connection] => close
	)
	
The CurlResponse class defines the magic [__toString()](http://php.net/__toString) method which will return the response body, so `echo $response` is the same as `echo $response->body`


### Cookie Sessions

By default, cookies will be stored in a file called `curl_cookie.txt`. You can change this file's name by setting it like this

	$curl->cookie_file = 'some_other_filename';

This allows you to maintain a session across requests


### Basic Configuration Options

You can easily set the referer or user-agent

	$curl->referer = 'http://google.com';
	$curl->user_agent = 'some user agent string';

You may even set these headers manually if you wish (see below)


### Setting Custom Headers

You can set custom headers to send with the request

	$curl->headers['Host'] = 12.345.678.90;
	$curl->headers['Some-Custom-Header'] = 'Some Custom Value';


### Setting Custom CURL request options

By default, the `Curl` object will follow redirects. You can disable this by setting:

	$curl->follow_redirects = false;

You can set/override many different options for CURL requests (see the [curl_setopt documentation](http://php.net/curl_setopt) for a list of them)

	# any of these will work
	$curl->options['AUTOREFERER'] = true;
	$curl->options['autoreferer'] = true;
	$curl->options['CURLOPT_AUTOREFERER'] = true;
	$curl->options['curlopt_autoreferer'] = true;


## Testing

Uses [phpunit](https://phpunit.de/).  Simply run phpunit in the 'test' directory.
Example on linux (assuming php is on your path):

    cd <project_root_dir>/test
    ../vendor/bin/phpunit



## Contact

Problems, comments, and suggestions all welcome: [shuber@huberry.com](mailto:shuber@huberry.com) and/or VEQRYN [at] hotmail dot com
