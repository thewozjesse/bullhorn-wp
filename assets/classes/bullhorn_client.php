<?php

// require bullhorn request class
require_once 'bullhorn_request.php';

class BullhornClient {

	private $bullhorn_auth_url = 'https://auth.bullhornstaffing.com';
	private $bullhorn_rest_url = 'https://rest.bullhornstaffing.com';
	
	// connection credentials
	private $client_id;
	private $client_secret;
	private $bh_user;
	private $bh_password;

	private $auth_code;
	private $oauth_token;
	private $oauth_refresh;

	private $bullhorn_request;
	
	private $BhRestToken;
	private $restUrl;

	private $limit;

	private $job_title_sort;
	private $division_sort;
	private $city_sort;
	private $state_sort;

	public function __construct($get = NULL)
	{
		$this->set_sorts($get);
		
		// set credentials
		try {
			$this->_setBullhornCredentials();
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		
		// set request class
		$this->bullhorn_request = new BullhornRequest();

		// connect to bullhorn
		$this->_connect();
	}

	private function _setBullhornCredentials()
	{
		$this->client_id = get_option( 'sbwp_bullhorn_client_id', false );
	    $this->client_secret = get_option( 'sbwp_bullhorn_client_secret', false );
	    $this->bh_user = get_option( 'sbwp_bullhorn_username', false );
	    $this->bh_password = get_option( 'sbwp_bullhorn_password', false );

		if (isset($this->client_id) && strlen($this->client_id) > 0 &&
			isset($this->client_secret) && strlen($this->client_secret) > 0 &&
			isset($this->bh_user) && strlen($this->bh_user) > 0 &&
			isset($this->bh_password) && strlen($this->bh_password) > 0) {
			return true;
		} else {
			throw new Exception('Cannot set Bullhorn API credentials.');
		}
	}

	private function _connect()
	{
		//check connection and refresh if made, get authorization if not
		if ($this->_hasConnection())
		{
			$refresh = $this->getRefreshTokens();
		}
		else
		{
			$this->_getAuthCode();
			$this->_getOauthTokens();
		}

		$this->doLogin();
		// $this->storeTokensToSession();
	}
	
	private function _hasConnection()
	{
		$refresh_token = get_option( 'sbwp_bullhorn_refresh_token', false );
		
		if ($refresh_token && strlen($refresh_token) > 0)
		{
			$this->oauth_refresh = $refresh_token;
			
			return true;
		}
		else
		{
			return false;
		}
	}

	private function _getAuthCode()
	{
		$auth_url = $this->bullhorn_auth_url.'/oauth/authorize?client_id='.$this->client_id.'&response_type=code';

		$data = array(
			'action' => 'Login',
			'username' => $this->bh_user,
			'password' => $this->bh_password
		);

		$content = $this->bullhorn_request->post($auth_url, $data, true);

		// get the authcode from bullhorn response
		if(preg_match('#Location: (.*)#', $content, $r)) {
			$temp = preg_split("/code=/", trim($r[1]));
			$authcode_array = preg_split("/&/", $temp[1]);

			$this->auth_code = $authcode_array[0];
		}
	}

	private function _getOauthTokens()
	{
		$auth_url = $this->bullhorn_auth_url.'/oauth/token?grant_type=authorization_code&code='.$this->auth_code.'&client_id='.$this->client_id.'&client_secret='.$this->client_secret;
		$data = '';

		$json = json_decode($this->bullhorn_request->post($auth_url, $data));

		$this->oauth_token = $json->access_token;
		$this->oauth_refresh = $json->refresh_token;
	}

	private function getRefreshTokens()
	{
		$auth_url = $this->bullhorn_auth_url.'/oauth/token?grant_type=refresh_token&refresh_token='.$_SESSION['refreshToken'].'&client_id='.$this->client_id.'&client_secret='.$this->client_secret;

		$options = array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $data,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120,
		);

		$ch  = curl_init( $auth_url );
		curl_setopt_array( $ch, $options );
		$content = curl_exec( $ch );

		curl_close( $ch );
		
		$json = json_decode($content);

		$this->oauth_token = $json->access_token;
		$this->oauth_refresh = $json->refresh_token;
	}

	private function doLogin()
	{
		try {
			if (!isset($this->oauth_token)) {
				throw new Exception('Oauth token is not set.');
			}
			
			$login_url = $this->bullhorn_rest_url.'/rest-services/login?version=*&access_token='.$this->oauth_token;
			
			$json = json_decode($this->bullhorn_request->get($login_url));

			$this->BhRestToken = $json->BhRestToken;
			$this->restUrl = $json->restUrl;
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	private function storeTokensToSession()
	{
		$_SESSION['BhRestToken'] = $this->BhRestToken;
		$_SESSION['restUrl'] = $this->restUrl;
		$_SESSION['refreshToken'] = $this->oauth_refresh;
	}

	private function set_sorts($get)
	{
		if (isset($get['limit']))
		{
			$this->limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_STRING);
		}
		else
		{
			$this->limit = 10;
		}

		$this->job_title_sort = isset($get['job']) ? filter_input(INPUT_GET, 'job', FILTER_SANITIZE_STRING) : 'all';
		$this->division_sort = isset($get['division']) ? filter_input(INPUT_GET, 'division', FILTER_SANITIZE_STRING) : 'all';
		$this->city_sort = isset($get['city']) ? filter_input(INPUT_GET, 'city', FILTER_SANITIZE_STRING) : 'all';
		$this->state_sort = isset($get['state']) ? filter_input(INPUT_GET, 'state', FILTER_SANITIZE_STRING) : 'all';
	}

	public function get_sorts()
	{
		$sorts = array(
			'limit' => $this->limit,
			'job' => $this->job_title_sort,
			'division' => $this->division_sort,
			'city' => $this->city_sort,
			'state' => $this->state_sort
			);

		return $sorts;
	}
 
	public function getAllJobs()
	{
		if (strlen($this->job_title_sort) || strlen($this->division_sort) || strlen($this->city_sort) || strlen($this->state_sort))
		{
			$job_title = ($this->job_title_sort != 'all') ? ' AND title = \''.$this->job_title_sort.'\'' : '';
			$division = ($this->division_sort != 'all') ? ' AND clientCorporation.name = \''.$this->division_sort.'\'' : '';
			$city = ($this->city_sort != 'all') ? ' AND address.city = \''.$this->city_sort.'\'' : '';
			$state = ($this->state_sort != 'all') ? ' AND address.state = \''.$this->state_sort.'\'' : '';

			$where = urlencode($job_title . $division . $city . $state);
		}
		else
		{
			$where = '';
		}

		$url = $_SESSION['restUrl'].'query/JobOrder?where=isOpen=TRUE&fields=id,title,address,businessSectors(name)&BhRestToken='.$_SESSION['BhRestToken'];

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120,
		);

		$ch  = curl_init( $url );
		curl_setopt_array( $ch, $options );
		$content = curl_exec( $ch );

		curl_close( $ch );

		$data = json_decode($content);

		return $data;
	}

	public function getJob()
	{
		$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

		$url = $_SESSION['restUrl'].'entity/JobOrder/'.$id.'?fields=id,title,address,businessSectors(name),startDate,employmentType,educationDegree,salaryUnit,salary,publicDescription&BhRestToken='.$_SESSION['BhRestToken'];

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120,
		);

		$ch  = curl_init( $url );
		curl_setopt_array( $ch, $options );
		$content = curl_exec( $ch );

		curl_close( $ch );

		$data = json_decode($content);

		return $data;
	}
}
