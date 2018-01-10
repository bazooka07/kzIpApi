<?php
	header('Content-Type: text/plain;charset=utf-8');

	$IpAddrs = <<< IP_ADDR
37.115.205.45
146.185.223.154
146.185.223.166
146.185.223.177
146.185.223.147
146.185.223.132
185.38.250.76
146.185.223.163
146.185.223.123
8.8.8.8
146.185.223.132
198.11.132.250
41.202.129.195
5.194.254.153
103.26.42.16
158.69.197.22
202.36.253.12
200.68.105.160
177.223.193.203
37.115.205.45
185.38.250.76
185.194.141.58
82.112.93.25
217.70.184.38
213.186.33.4
185.98.131.142
IP_ADDR;

	$batch = '['.implode(
		', ',
		array_map(
			function($ipAddr) {
				return <<< EOT
{"query": "$ipAddr", "lang": "fr"}
EOT;
			},
			explode(PHP_EOL, $IpAddrs)
		)
	).']';
	echo "$batch\n\n";

	// Post the batch request
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL				=> 'http://ip-api.com/batch',
		CURLOPT_POST			=> true,
		CURLOPT_HEADER			=> false,
		CURLOPT_RETURNTRANSFER	=> true,
		CURLOPT_POSTFIELDS		=> $batch
	));
	$resp = curl_exec($ch);

	// If that's right, then we receive the response in JSON format
	if($resp !== false) {
		print_r(json_decode($resp, true));
	} else {
		die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
	}
?>