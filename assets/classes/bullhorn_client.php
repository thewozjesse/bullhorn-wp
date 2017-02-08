<?php

class BullhornClient {

	// connection credentials
	private $client_id;
	private $client_secret;
	private $bh_user;
	private $bh_password;

	private $auth_code;
	private $oauth_token;
	private $oauth_refresh;

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
	}

	public function connect()
	{
		//check connection and refresh if made, get authorization if not
		if ($this->checkConnection())
		{
			$refresh = $this->getRefreshTokens();	
		}
		else
		{
			$auth_code = $this->getAuthCode();
			$oauth = $this->getOauthTokens();			
		}

		$login = $this->doLogin();
		$store = $this->storeTokensToSession();
	}

	private function getAuthCode()
	{
		$auth_url = 'https://auth.bullhornstaffing.com/oauth/authorize?client_id='.$this->client_id.'&response_type=code';
		$data = "action=Login&username=".$this->bh_user."&password=".$this->bh_password;

		$options = array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $data,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,   
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_AUTOREFERER    => true,   
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120,     
		);

		$ch  = curl_init( $auth_url );
		curl_setopt_array( $ch, $options );
		$content = curl_exec( $ch );

		curl_close( $ch );

		if(preg_match('#Location: (.*)#', $content, $r)) {
			$l = trim($r[1]);
			$temp = preg_split("/code=/", $l);
			$authcode_string = $temp[1];
			$auth_code_array = preg_split("/&/", $authcode_string);
			$authcode = $auth_code_array[0];

			$this->auth_code = $authcode;
		}
	}

	private function getOauthTokens()
	{
		$auth_url = 'https://auth.bullhornstaffing.com/oauth/token?grant_type=authorization_code&code='.$this->auth_code.'&client_id='.$this->client_id.'&client_secret='.$this->client_secret;
		$data = '';

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

	private function getRefreshTokens()
	{
		$auth_url = 'https://auth.bullhornstaffing.com/oauth/token?grant_type=refresh_token&refresh_token='.$_SESSION['refreshToken'].'&client_id='.$this->client_id.'&client_secret='.$this->client_secret;

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
		$login_url = 'https://rest.bullhornstaffing.com/rest-services/login?version=*&access_token='.$this->oauth_token;
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_AUTOREFERER    => true,   
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120,     
		);

		$ch  = curl_init( $login_url );
		curl_setopt_array( $ch, $options );
		$content = curl_exec( $ch );

		curl_close( $ch );

		$json = json_decode($content);

		$this->BhRestToken = $json->BhRestToken;
		$this->restUrl = $json->restUrl;
	}

	private function storeTokensToSession()
	{
		$_SESSION['BhRestToken'] = $this->BhRestToken;
		$_SESSION['restUrl'] = $this->restUrl;
		$_SESSION['refreshToken'] = $this->oauth_refresh;
	}

	private function checkConnection()
	{
		if (!empty($_SESSION['refreshToken']) && strlen($_SESSION['refreshToken']))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
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