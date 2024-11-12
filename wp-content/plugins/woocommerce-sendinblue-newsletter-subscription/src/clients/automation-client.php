<?php

namespace SendinblueWoocommerce\Clients;

/**
 * Class AutomationClient
 *
 * @package SendinblueWoocommerce\Clients
 */
class AutomationClient
{
	private const AUTOMATION_URL   = 'https://in-automate.brevo.com/api/v2/trackEvent';
	private const HTTP_METHOD_POST = 'POST';
	private const USER_AGENT       = 'sendinblue_plugins/woocommerce_common';

	private function makeHttpRequest($body, $ma_key)
	{
		$post_data = json_encode($body ,JSON_UNESCAPED_UNICODE);
		$url = self::AUTOMATION_URL;
		$headers = array(
			'Content-Type: application/json',
			'ma-key: ' . $ma_key,
			'ma-key: ' . $ma_key,
			'User-Agent: ' . self::USER_AGENT,
		);

		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => self::HTTP_METHOD_POST,
				CURLOPT_POSTFIELDS => $post_data
			)
		);

		curl_exec($curl);
		curl_close($curl);
	}

	public function send($data, $ma_key)
	{
		return $this->makeHttpRequest($data, $ma_key);
	}
}
