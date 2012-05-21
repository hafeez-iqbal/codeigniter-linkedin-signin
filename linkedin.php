<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Linked Sign In Library
 * 
 * @author Ulugbek
 * 
 */

require_once("OAuth.php");

class Linkedin
{
	//authorization toke
	public $token = NULL;
	//authorization toke
	public $signature_method;
	//contains the last HTTP status code returned
 	public $http_code;
 	//Contains the last HTTP headers returned
 	public $http_info;
	//oauth object
	protected $consumer;
	//call back url
	protected $callback;
	//api host
	protected $host = "https://api.linkedin.com";
	//request token url
	protected $request_token_url = '/uas/oauth/requestToken';
	//request token url
	protected $access_token_url = '/uas/oauth/accessToken';
	//request token url
	protected $authorize_url = '/uas/oauth/authorize';
	//request token url
	protected $authenticate_url = '/uas/oauth/authenticate';
	
	/**
	 * Construct 
	 * 
	 * @access public
	 * @param string linkedin consumer key 
	 * @param string linkedin consumer secret
	 * @return mixed
	 */
	public function __construct($consumer_key, $consumer_secret, $callback = NULL)
	{
		$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret, $callback);
		
		$this->callback = $callback;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set request token 
	 * 
	 * @access public
	 * @param string
	 * @param string
	 * @return mixed
	 */
	public function set_request_token($oauth_token, $oauth_token_secret)
	{
		$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get request token 
	 * 
	 * @access public
	 * @param string callback url
	 * @return string
	 */
	public function get_request_token($callback = NULL)
	{
		$parameters = array();
		
		if ($callback) 
		{
      		$this->callback = $callback;
		}
		 
		$parameters['oauth_callback'] = $this->callback;
		
		//request oauth server (url, method, parameters) 
    	$request = $this->oauth_request($this->request_token_url, 'GET', $parameters);

    	$token = OAuthUtil::parse_parameters($request);
    
    	$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    	
    	return $token;  
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get authorization url 
	 * 
	 * @access public
	 * @param string
	 * @param bool
	 * @return string
	 */
 	public function get_authorize_url($token, $sign_in_with_twitter = TRUE) 
 	{
    	if (is_array($token)) 
    	{
      		$token = $token['oauth_token'];
    	}
    	
    	if (empty($sign_in_with_twitter)) 
    	{
      		return "{$this->host}{$this->authorize_url}?oauth_token={$token}";
    	} 
    	else 
    	{
       		return "{$this->host}{$this->authenticate_url}?oauth_token={$token}";
    	}
  }
	
	// --------------------------------------------------------------------
	
	/**
	 * Request outh server, API call
	 * 
	 * @access private
	 * @param string request url
	 * @param string method get /post
	 * @param string
	 * @return string
	 */	
	private function oauth_request($url, $method, $parameters) 
	{
		$url = "{$this->host}{$url}";
    
    	$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    	
    	$request->sign_request($this->signature_method, $this->consumer, $this->token);
    	
    	switch ($method) 
    	{
    		case 'GET':
      			return $this->http_request($request->to_url(), 'GET');
    		default:
      			return $this->http_request($request->get_normalized_http_url(), $method, $request->to_postdata());
    	}
  	}	
  	
  	// --------------------------------------------------------------------
	
	/**
	 * Make http request
	 * 
	 * @access private
	 * @param string request url
	 * @param string method get /post
	 * @param string post fields
	 * @return string
	 */
  	private function http_request($url, $method, $postfields = NULL)
  	{
  		$this->http_info = array();
  		
	    $ch = curl_init();
	
	    curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_HEADER, FALSE);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
    	curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'get_header'));
	
	    switch ($method) 
	    {
	    	case 'POST':
	        	curl_setopt($ch, CURLOPT_POST, TRUE);
	        	if (!empty($postfields)) 
	        	{
	        		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	        	}
	        	break;
	      	case 'DELETE':
	        	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	        	if (!empty($postfields)) 
	        	{
	          		$url = "{$url}?{$postfields}";
	        	}
	        	break;
	    }

	    $response = curl_exec($ch);

	    $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $this->http_info = array_merge($this->http_info, curl_getinfo($ch));
	    
	    curl_close ($ch);
	    
	    return $response;
  	}
  	
  	// --------------------------------------------------------------------
	
	/**
	 * Get http header info
	 * 
	 * @access private
	 * @param string
	 * @param string
	 * @return int
	 */
	private function get_header($ch, $header) 
	{
    	$i = strpos($header, ':');
    	if (!empty($i)) 
    	{
      		$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));      		
      		$value = trim(substr($header, $i + 2));
      		$this->http_header[$key] = $value;
    	}
    	
    	return strlen($header);
  }
  	
  	
}
 
 
/* End of file  linkedin.php */