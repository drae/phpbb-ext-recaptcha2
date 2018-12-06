<?php
/**
* @copyright (c) Numeric <http://www.starstreak.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace numeric\recaptchav2;

// All code below is under the given licence

/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * @copyright Copyright (c) 2015, Google Inc.
 * @link      http://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * reCAPTCHA client.
 */
class ReCaptcha
{
    /**
     * Version of this client library.
     * @const string
     */
    const VERSION = 'php_1.1.2';
    /**
     * Shared secret for the site.
     * @var type string
     */
    private $secret;
    /**
     * Method used to communicate with service. Defaults to POST request.
     * @var RequestMethod
     */
    private $requestMethod;
    /**
     * Create a configured instance to use the reCAPTCHA service.
     *
     * @param string $secret shared secret between site and reCAPTCHA server.
     * @param RequestMethod $requestMethod method used to send the request. Defaults to POST.
     */
    public function __construct($secret, RequestMethod $requestMethod = null)
    {
        if (empty($secret)) {
            throw new \RuntimeException('No secret provided');
        }
        if (!is_string($secret)) {
            throw new \RuntimeException('The provided secret must be a string');
        }
        $this->secret = $secret;
        if (!is_null($requestMethod)) {
            $this->requestMethod = $requestMethod;
        } else {
            $this->requestMethod = new RequestMethod\Post();
        }
    }
    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test.
     *
     * @param string $response The value of 'g-recaptcha-response' in the submitted form.
     * @param string $remoteIp The end user's IP address.
     * @return Response Response from the service.
     */
    public function verify($response, $remoteIp = null)
    {
        // Discard empty solution submissions
        if (empty($response)) {
            $recaptchaResponse = new Response(false, array('missing-input-response'));
            return $recaptchaResponse;
        }
        $params = new RequestParameters($this->secret, $response, $remoteIp, self::VERSION);
        $rawResponse = $this->requestMethod->submit($params);
        return Response::fromJson($rawResponse);
    }
}

/**
 * Method used to send the request to the service.
 */
interface RequestMethod
{
    /**
     * Submit the request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params);
}

/**
 * Stores and formats the parameters for the request to the reCAPTCHA service.
 */
class RequestParameters
{
    /**
     * Site secret.
     * @var string
     */
    private $secret;
    /**
     * Form response.
     * @var string
     */
    private $response;
    /**
     * Remote user's IP address.
     * @var string
     */
    private $remoteIp;
    /**
     * Client version.
     * @var string
     */
    private $version;
    /**
     * Initialise parameters.
     *
     * @param string $secret Site secret.
     * @param string $response Value from g-captcha-response form field.
     * @param string $remoteIp User's IP address.
     * @param string $version Version of this client library.
     */
    public function __construct($secret, $response, $remoteIp = null, $version = null)
    {
        $this->secret = $secret;
        $this->response = $response;
        $this->remoteIp = $remoteIp;
        $this->version = $version;
    }
    /**
     * Array representation.
     *
     * @return array Array formatted parameters.
     */
    public function toArray()
    {
        $params = array('secret' => $this->secret, 'response' => $this->response);
        if (!is_null($this->remoteIp)) {
            $params['remoteip'] = $this->remoteIp;
        }
        if (!is_null($this->version)) {
            $params['version'] = $this->version;
        }
        return $params;
    }
    /**
     * Query string representation for HTTP request.
     *
     * @return string Query string formatted parameters.
     */
    public function toQueryString()
    {
        return http_build_query($this->toArray(), '', '&');
    }
}

/**
 * The response returned from the service.
 */
class Response
{
    /**
     * Succes or failure.
     * @var boolean
     */
    private $success = false;
    /**
     * Error code strings.
     * @var array
     */
    private $errorCodes = array();
    /**
     * Build the response from the expected JSON returned by the service.
     *
     * @param string $json
     * @return \ReCaptcha\Response
     */
    public static function fromJson($json)
    {
        $responseData = json_decode($json, true);
        if (!$responseData) {
            return new Response(false, array('invalid-json'));
        }
        if (isset($responseData['success']) && $responseData['success'] == true) {
            return new Response(true);
        }
        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
            return new Response(false, $responseData['error-codes']);
        }
        return new Response(false);
    }
    /**
     * Constructor.
     *
     * @param boolean $success
     * @param array $errorCodes
     */
    public function __construct($success, array $errorCodes = array())
    {
        $this->success = $success;
        $this->errorCodes = $errorCodes;
    }
    /**
     * Is success?
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->success;
    }
    /**
     * Get error codes.
     *
     * @return array
     */
    public function getErrorCodes()
    {
        return $this->errorCodes;
    }
}

namespace numeric\recaptchav2\RequestMethod;

/**
 * Convenience wrapper around the cURL functions to allow mocking.
 */
class Curl
{
    /**
     * @see http://php.net/curl_init
     * @param string $url
     * @return resource cURL handle
     */
    public function init($url = null)
    {
        return curl_init($url);
    }
    /**
     * @see http://php.net/curl_setopt_array
     * @param resource $ch
     * @param array $options
     * @return bool
     */
    public function setoptArray($ch, array $options)
    {
        return curl_setopt_array($ch, $options);
    }
    /**
     * @see http://php.net/curl_exec
     * @param resource $ch
     * @return mixed
     */
    public function exec($ch)
    {
        return curl_exec($ch);
    }
    /**
     * @see http://php.net/curl_close
     * @param resource $ch
     */
    public function close($ch)
    {
        curl_close($ch);
    }
}

/**
 * Convenience wrapper around native socket and file functions to allow for
 * mocking.
 */
class Socket
{
    private $handle = null;
    /**
     * fsockopen
     * 
     * @see http://php.net/fsockopen
     * @param string $hostname
     * @param int $port
     * @param int $errno
     * @param string $errstr
     * @param float $timeout
     * @return resource
     */
    public function fsockopen($hostname, $port = -1, &$errno = 0, &$errstr = '', $timeout = null)
    {
        $this->handle = fsockopen($hostname, $port, $errno, $errstr, (is_null($timeout) ? ini_get("default_socket_timeout") : $timeout));
        if ($this->handle != false && $errno === 0 && $errstr === '') {
            return $this->handle;
        }
        return false;
    }
    /**
     * fwrite
     * 
     * @see http://php.net/fwrite
     * @param string $string
     * @param int $length
     * @return int | bool
     */
    public function fwrite($string, $length = null)
    {
        return fwrite($this->handle, $string, (is_null($length) ? strlen($string) : $length));
    }
    /**
     * fgets
     * 
     * @see http://php.net/fgets
     * @param int $length
     * @return string
     */
    public function fgets($length = null)
    {
        return fgets($this->handle, $length);
    }
    /**
     * feof
     * 
     * @see http://php.net/feof
     * @return bool
     */
    public function feof()
    {
        return feof($this->handle);
    }
    /**
     * fclose
     * 
     * @see http://php.net/fclose
     * @return bool
     */
    public function fclose()
    {
        return fclose($this->handle);
    }
}

use numeric\recaptchav2\RequestMethod;
use numeric\recaptchav2\RequestParameters;

/**
 * Sends cURL request to the reCAPTCHA service.
 * Note: this requires the cURL extension to be enabled in PHP
 * @see http://php.net/manual/en/book.curl.php
 */
class CurlPost implements RequestMethod
{
    /**
     * URL to which requests are sent via cURL.
     * @const string
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    /**
     * Curl connection to the reCAPTCHA service
     * @var Curl
     */
    private $curl;
    public function __construct(Curl $curl = null)
    {
        if (!is_null($curl)) {
            $this->curl = $curl;
        } else {
            $this->curl = new Curl();
        }
    }
    /**
     * Submit the cURL request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params)
    {
        $handle = $this->curl->init(self::SITE_VERIFY_URL);
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params->toQueryString(),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
            CURLINFO_HEADER_OUT => false,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true
        );
        $this->curl->setoptArray($handle, $options);
        $response = $this->curl->exec($handle);
        $this->curl->close($handle);
        return $response;
    }
}

/**
 * Sends POST requests to the reCAPTCHA service.
 */
class Post implements RequestMethod
{
    /**
     * URL to which requests are POSTed.
     * @const string
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params)
    {
        /**
         * PHP 5.6.0 changed the way you specify the peer name for SSL context options.
         * Using "CN_name" will still work, but it will raise deprecated errors.
         */
        $peer_key = version_compare(PHP_VERSION, '5.6.0', '<') ? 'CN_name' : 'peer_name';
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => $params->toQueryString(),
                // Force the peer to validate (not needed in 5.6.0+, but still works
                'verify_peer' => true,
                // Force the peer validation to use www.google.com
                $peer_key => 'www.google.com',
            ),
        );
        $context = stream_context_create($options);
        return file_get_contents(self::SITE_VERIFY_URL, false, $context);
    }
}

/**
 * Sends a POST request to the reCAPTCHA service, but makes use of fsockopen()
 * instead of get_file_contents(). This is to account for people who may be on
 * servers where allow_furl_open is disabled.
 */
class SocketPost implements RequestMethod
{
    /**
     * reCAPTCHA service host.
     * @const string
     */
    const RECAPTCHA_HOST = 'www.google.com';
    /**
     * @const string reCAPTCHA service path
     */
    const SITE_VERIFY_PATH = '/recaptcha/api/siteverify';
    /**
     * @const string Bad request error
     */
    const BAD_REQUEST = '{"success": false, "error-codes": ["invalid-request"]}';
    /**
     * @const string Bad response error
     */
    const BAD_RESPONSE = '{"success": false, "error-codes": ["invalid-response"]}';
    /**
     * Socket to the reCAPTCHA service
     * @var Socket
     */
    private $socket;
    /**
     * Constructor
     *
     * @param \ReCaptcha\RequestMethod\Socket $socket optional socket, injectable for testing
     */
    public function __construct(Socket $socket = null)
    {
        if (!is_null($socket)) {
            $this->socket = $socket;
        } else {
            $this->socket = new Socket();
        }
    }
    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params)
    {
        $errno = 0;
        $errstr = '';
        if (false === $this->socket->fsockopen('ssl://' . self::RECAPTCHA_HOST, 443, $errno, $errstr, 30)) {
            return self::BAD_REQUEST;
        }
        $content = $params->toQueryString();
        $request = "POST " . self::SITE_VERIFY_PATH . " HTTP/1.1\r\n";
        $request .= "Host: " . self::RECAPTCHA_HOST . "\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-length: " . strlen($content) . "\r\n";
        $request .= "Connection: close\r\n\r\n";
        $request .= $content . "\r\n\r\n";
        $this->socket->fwrite($request);
        $response = '';
        while (!$this->socket->feof()) {
            $response .= $this->socket->fgets(4096);
        }
        $this->socket->fclose();
        if (0 !== strpos($response, 'HTTP/1.1 200 OK')) {
            return self::BAD_RESPONSE;
        }
        $parts = preg_split("#\n\s*\n#Uis", $response);
        return $parts[1];
    }
}
