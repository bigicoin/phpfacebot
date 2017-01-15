<?php

class WitApi
{

	function __construct($options = array())
	{
		if(!function_exists('curl_setopt'))
			throw new Exception('WitApi requires the PHP Curl module.');

		# sensible defaults
		$this->config = array_merge(array(
			'base_url' => 'https://api.wit.ai/',
			'version' => date('Ymd'),
			'timeout' => 10, #seconds
			'headers' => array(
				'Accept' => 'application/json',
				),
			'data' => array(),
			), $options);

		$this->connector = new WitAPIConnector();
	}
	
	function request($data)
	{
		if(!is_array($data))
			throw new Exception('WitApi->request() expects an array argument.');
		$request = array_replace_recursive($this->config, $data);
		$request['headers']['Authorization'] = 'Bearer '.$request['access_token'];
		// print_r($request);
		return($this->connector->do_request($request));
	}
	
	function text_query($text, $options = array())
	{
		return( 
			$this->request(
				array_replace_recursive(
					array(
						'call' => 'message',
						'data' => array(
							'q' => $text,
							),
						'headers' => array(
							'Content-Type' => 'application/json',
							),
						), 
					$options
					)
				) 
			);
	}
	
	function voice_query()
	{
		
	}

}

// ****************************** Network Connector Class (cURL) ******************************

class WitAPIConnector
{

	function prepare_headers($kvList)
	{
		$headers = array();
		foreach($kvList as $k => $v)
			$headers[] = $k.': '.$v;
		return($headers);
	}
	
	// cut $cake at the first occurence of $segdiv, returns the slice
	function str_nibblef($segdiv, &$cake, &$found)
	{
		$p = strpos($cake, $segdiv);
		if ($p === false)
		{
			$result = $cake;
			$cake = '';
			$found = false;
		}
		else
		{
			$result = substr($cake, 0, $p);
			$cake = substr($cake, $p + strlen($segdiv));
			$found = true;
		}
		return $result;
	}
	
	function str_nibble($segdiv, &$cake)
	{
		return($this->str_nibblef($segdiv, $cake, $found));
	}

	function parse_httpresponse($res)
	{
		$mode = 'header';
		$result = array( 
			'code' => 0,
			'headers' => array(),
			'body' => '',
			'data' => array(),
			);
		
		foreach(explode(chr(10), $res) as $line)
		{
			$line = trim($line);
			if($mode == 'header')
			{
				if($line == '' && $result['code'] != '100') 
					$mode = 'body';
				else
				{
					if(substr($line, 0, 5) == 'HTTP/')
					{
						# this is a HTTP/ header line
						$result['HTTP-V'] = $this->str_nibble(' ', $line);
						$result['code'] = $this->str_nibble(' ', $line);
					}
					else
					{
						$hkey = $this->str_nibble(':', $line);
						if($hkey != '')
							$result['headers'][$hkey] = $line;
					}
				} 
				$httpContinue = false;
			}
			else
			{
				$result['body'] .= $line.chr(10);
			}
		} 
		
		# "autodetect" json bodies and parse them
		if(substr($result['body'], 0, 1) == '{' || substr($result['body'], 0, 1) == '[')
		{
			$result['data'] = json_decode($result['body'], true);
			unset($result['body']);
		}
		
		return($result);
	}
	
	function do_request($request)
	{
		$ch = curl_init();
		$resheaders = array();
		$resbody = array();
		curl_setopt($ch, CURLOPT_URL, $request['base_url'].$request['call'].'?'.http_build_query($request['data']));
		#$post = json_encode($request['data']);
		#curl_setopt($ch, CURLOPT_POST, 1); 
		#curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->prepare_headers($request['headers']));	
		curl_setopt($ch, CURLOPT_TIMEOUT, $request['timeout']); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // RETURN THE CONTENTS OF THE CALL
		$result = $this->parse_httpresponse(curl_exec($ch));
		curl_close($ch);
			
		return($result);		
	}

}
