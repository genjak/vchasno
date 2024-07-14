<?php

namespace genjak;

use GuzzleHttp\Client;
use larapack\dd;

class VchasnoKasaApi
{
	const POST_URL = 'https://kasa.vchasno.ua/api/v3/';
	private $token;
	private $connectTimeout = 180;
	const ACTION_TYPE = 'fiscal/execute';

	/** @var Client $guzzleClient */
	private $guzzleClient;

	private $header = [];

	function __construct($connect_data)
	{
		# Save all variables
		$this->token = $connect_data['token'];

		$this->guzzleClient = new Client([
			'base_uri' => self::POST_URL,
			'timeout'  => $this->connectTimeout,
			'allow_redirects' => true,
		]);

		$this->header = [
			'Content-type' => 'application/json',
			'Authorization' => $this->token,
			'Accept' => 'application/json',
		];
	}

	public function request($params)
	{
		# Set header query values
		$params_put = json_encode($params, JSON_PRESERVE_ZERO_FRACTION);
		$res = $this->guzzleClient->post(self::ACTION_TYPE, ['headers' => $this->header, 'body' => $params_put]);
		$body = $res->getBody();
		return json_decode($body->getContents());
	}

	public function send_receipt($params, $action, $json = '')
	{
		# Encode query
		$params_put = ($json) ? $json : json_encode($params);
		# Get query url
		$url = self::POST_URL_V . $action;

		# Set header query values
		$header = array(
			'Content-type' => 'application/json',
			'Authorization' => $this->token,
		);

		# Generate setting Post query
		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => 60,
				'redirection' => 5,
				'blocking' => true,
				'headers'     => $header,
				'body'        => $params_put,
			)
		);

		# Return response
		return  $response['body'];
	}
}
