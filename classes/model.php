<?php

class Model
{

	protected $config = null;

	protected $conn = null;


	public function __construct()
	{
		$config = $GLOBALS['config'];
		$this->config = $config;
	}


	public function fromBlockchain($method,$params=array())
	{
		$data['id']      = rand(0,42);
		$data['jsonrpc'] = '2.0';
		$data['method']  = $method;
		if(!empty($params))
		{
			$data['params']  = $params;
		}

		$data_string = json_encode($data);
		$ch = curl_init($this->config['server']);
		curl_setopt($ch, CURLOPT_USERAGENT, 'SHIFTexplorer');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string))
		);
		$result = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if(!empty($error))
		{
			return $error;
		}

		return json_decode($result,true)['result'];
	}


	public function getSupply()
	{
		$blockNumber = $this->fromBlockchain($this->config['prefix'].'_blockNumber');
		$supply      = 5000000;
		if($blockNumber <= 28799)
		{
			$supply += $blockNumber * 3;
		}
		elseif($blockNumber > 28799 && $blockNumber <= 57599)
		{
			$supply += 86397+(($blockNumber-28799)*2.9);
		}
		elseif($blockNumber > 57599 && $blockNumber <= 86399)
		{
			$supply += 169914+(($blockNumber-57599)*2.8); 
		}
		elseif($blockNumber > 86399 && $blockNumber <= 115199)
		{
			$supply += 250551+(($blockNumber-86399)*2.6);
		}
		elseif($blockNumber > 115199 && $blockNumber <= 143999)
		{
			$supply += 325428+(($blockNumber-115199)*2.4);
		}
		elseif($blockNumber > 143999 && $blockNumber <= 172799)
		{
			$supply += 394546+(($blockNumber-143999)*2.0);
		}
		elseif($blockNumber > 172799 && $blockNumber <= 230399)
		{
			$supply += 452144+(($blockNumber-172799)*1.5);
		}
		elseif($blockNumber > 230399)
		{
			$supply += 495342+(($blockNumber-230399)*1.0);
		}
		//when node staking is up: //xxx is block number where V1 switches to V2
		//if($blockNumber > xxx) {
		// 	$supply += ($blockNumber-xxx)*0.5;
		// 	}
		
		return $supply;
	}


	public function hex2str($hex)
	{
		$str = '';
    	for($i=0;$i<strlen($hex);$i+=2)
    	{
    		$str .= chr(hexdec(substr($hex,$i,2)));
    	}
    	
    	return $str;
	}


}