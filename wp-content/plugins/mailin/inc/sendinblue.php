<?php
class Sendinblue
{
	public $api_key;
	public $base_url;
	public $curl_opts = array();

	public function __construct($api_key)
	{
		if (!function_exists('curl_init')) {
			throw new Exception('Sendinblue requires CURL module');
		}
		$this->base_url = 'https://in-automate.brevo.com/p';
		$this->api_key = $api_key;
		//create a session cookie
		if (!array_key_exists('session_id', $_COOKIE)) {
			$domain = self::get_app_domain();
			//store session_id cookie
			$session_id = md5(uniqid(time()));
			$expiry_time = self::get_default_cookie_expiry();
			setcookie("session_id", $session_id, $expiry_time, "/", $domain, is_ssl());
		}
	}

	/**
	 * @param $input
	 * @return mixed
	 */
	private function do_request($input)
	{
		$input['key'] = $this->api_key;
		$url = $this->base_url . "?" . http_build_query($input);
		$data = wp_remote_retrieve_body(wp_remote_request($url, ['method' => 'GET']));

		return json_decode($data, true);
	}

	/**
	 * @return string
	 */
	private static function get_app_domain()
	{
		$url = 	get_site_url();
		$parsed_url = parse_url($url);
		return !empty($parsed_url['host']) ? $parsed_url['host'] : 'localhost';
	}

	/**
	 * @return int
	 */
	private static function get_default_cookie_expiry()
	{
		return time() + 8640;
	}


	/**
	 * @param string $email
	 * @param int $expiry_time
	 * @return void
	 */
	private function set_email_cookie($email)
	{
		if (is_string($email)) {
			$expiry_time = self::get_default_cookie_expiry();
			$domain = self::get_app_domain();
			setcookie("email_id", sanitize_email($email), $expiry_time, "/", $domain, is_ssl());
		}
	}

	public function identify($data)
	{
		$data['sib_type'] = 'identify';

		if (!array_key_exists('name', $data)) {
			$data['name'] = "Contact Created";
		}
		$url = esc_url_raw((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		if (!array_key_exists('url', $data)) {
			$data['url'] = $url;
		}
		if (isset($_COOKIE['session_id']) && $_COOKIE['session_id'] != '') {
			$data['session_id'] = sanitize_text_field($_COOKIE['session_id']);
		}
		//store email_id cookie
		$this->set_email_cookie($data['email_id']);
		return $this->do_request($data);
	}

	public function track($data)
	{
		$data['sib_type'] = 'track';
		$url = esc_url_raw((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		if (!array_key_exists('url', $data)) {
			$data['url'] = $url;
		}

		if (!array_key_exists('sib_name', $data)) {
			if (array_key_exists('name', $data)) {
				$data['sib_name'] = $data['name'];
			}
		}

		//get email cookie

		if (isset($_COOKIE['email_id']) && $_COOKIE['email_id'] != '') {
			$data['email_id'] = sanitize_email($_COOKIE['email_id']);
		}
		if (isset($_COOKIE['session_id']) && $_COOKIE['session_id'] != '') {
			$data['session_id'] = sanitize_text_field($_COOKIE['session_id']);
		}

		//store email cookie
		$obj = $this->do_request($data);
		if (isset($obj['email_id']) && $obj['email_id'] != '') {
			$this->set_email_cookie($obj['email_id']);
		}
	}

	public function page($data)
	{
		$data['sib_type'] = 'page';
		$url = esc_url_raw((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		if (!array_key_exists('url', $data)) {
			$data['url'] = $url;
		}
		//get email cookie
		if (isset($_COOKIE['email_id']) && $_COOKIE['email_id'] != '') {
			$data['email_id'] = sanitize_email($_COOKIE['email_id']);
		}
		if (isset($_COOKIE['session_id']) && $_COOKIE['session_id'] != '') {
			$data['session_id'] = sanitize_text_field($_COOKIE['session_id']);
		}
		//referrer
		if (!array_key_exists('referrer', $data) && array_key_exists('HTTP_REFERER', $_SERVER)) {
			$data['referrer'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}
		//pathname
		if (!array_key_exists('pathname', $data)) {
			$data['pathname'] = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		//name
		if (!array_key_exists('name', $data)) {
			$data['name'] = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		//store email cookie
		$obj = $this->do_request($data);
		if (isset($obj['email_id']) && $obj['email_id'] != '') {
			$this->set_email_cookie($obj['email_id']);
		}
	}

	public function trackLink($data)
	{
		$data['sib_type'] = 'trackLink';
		//get email cookie
		if (isset($_COOKIE['email_id']) && $_COOKIE['email_id'] != '') {
			$data['email_id'] = sanitize_email($_COOKIE['email_id']);
		}
		if (isset($_COOKIE['session_id']) && $_COOKIE['session_id'] != '') {
			$data['session_id'] = sanitize_text_field($_COOKIE['session_id']);
		}
		$url = esc_url_raw((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		if (!array_key_exists('url', $data)) {
			$data['url'] = $url;
		}
		//store email cookie
		$obj = $this->do_request($data);
		if (isset($obj['email_id']) && $obj['email_id'] != '') {
			$this->set_email_cookie($obj['email_id']);
		}
	}
}
