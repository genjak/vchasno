<?php

namespace genjak;

use VchasnoKasaApi;
use larapack\dd;

class VchasnoKasa
{
	/** @var Config $config */
	private $config = [];

	private $api_vchasno_kasa;
	
	public function __construct(array $config, int $connectTimeoutSeconds = 180)
	{
		 $this->config = $config;		 		 	

		 $this->api_vchasno_kasa = new \genjak\VchasnoKasaApi($this->config);
	}

	public function openShift(){
		$params = [
			'fiscal' => [
				'task' => 0,
				'cashier' => $this->config['name']
			]
		];

		# Send query to Vchasno Kasa
		$result = $this->api_vchasno_kasa->request($params);				
		$s = '';
		# If response not exist error message
		if($result->errortxt == ''){
			# Show result in log file
			$s.= 'Статус зміни оновлено. Поточний статус зміни: Відкрита';

			# Get current datetime
			$date_now = date("Y-m-d h:i:sa");

			# Show added data
			$s.= ' ==Початок дня ' . $date_now . ' ==';
		}
		else{
			# Show Error
			$s.= 'Зміну не вдалося відкрити. Помилка: ' . $result->errortxt;
		}
		return $s;
	}

	public function closeShift(){
		$params = [
			'fiscal' => [
				'task' => 11,
				'cashier' => $this->config['name']
			]
		];

		# Send query to Vchasno Kasa
		$result = $this->api_vchasno_kasa->request($params);				
		$s = '';
		# If response not exist error message
		if($result->errortxt == ''){
			# Show result in log file
			$s.= 'Статус зміни оновлено. Поточний статус зміни: Закрита';

			# Get current datetime
			$date_now = date("Y-m-d h:i:sa");

			# Show added data
			$s.= ' ==Кінець дня ' . $date_now . ' ==';
		}
		else{
			# Show Error
			$s.= 'Зміну не вдалося закрити. Помилка: ' . $result->errortxt;
		}
		return $s;
	}	

	public function makeReceipt($goods_items, $total_sum, $oplata){
		if($oplata == 'card'){
			$oplata = "2";
		}elseif($oplata == 'pislaplata'){
			$oplata = "4";//"1" -  безготівка, 4 - післяплата.
		}elseif($oplata == 'nal'){
			$oplata = "0";
		}else{
			$oplata = "4";
		}
		# Create array with all params
		$params = [];
		# Get main order dat

		$params['userinfo'] = [
			'email' => 'genjak@gmail.com'//,
			//'phone' => $phone
		];
		unset($params['userinfo']);

		# Array of all products
		$goods = [];		
		# Loop all order items
		foreach ($goods_items as $k=>$item) {
			
			# Save item
			$goods[] = array(
				'code' => "" . ($k+1),
				'name' => $item['name'],
				'cnt' => $item['count'],
				'price' => $item['price'],
				'disc' => 0,//number_format(0.00, 2, '.', ''),
				'taxgrp' => 7
			);
			}
	
			# Set fiscal params
			$params['fiscal'] = array(
				'task' => 1,
				'receipt' => array(
					'sum' => $total_sum,
					'comment_up' => '',
					'comment_down' => '',
					'rows' => $goods,
					'pays' => array(
						array(
							'type' => $oplata,
							'sum' => $total_sum,
							'currency' => 'ГРН'
						)
					)
				)
			);
			//var_dump($params);
			# Create receipt (Send post query)
			$result = $this->api_vchasno_kasa->request($params);
			//var_dump($result);
			file_put_contents(__DIR__ . '/log.txt', PHP_EOL . date("d.m.y H:i") . PHP_EOL . var_export($params, true) . PHP_EOL . var_export($result, true), FILE_APPEND);
			# Show Error
			
			# Decode json response to StdClass
			//$result = json_decode($result);
			$s = '';
			# Check if result has error
			if($result->errortxt == ''){
				# Save qr link
				$receipt_url = $result->info->doccode;
				$s.= 'https://kasa.vchasno.ua/c/' . $receipt_url;				
			}
			else{
				# Show Error				
				$s.='Помилка при створенні чеку: ' . $result->errortxt;

			}
		return $s;
	}
}