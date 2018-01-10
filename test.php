<?php

const RAW_TEXT = false;

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
		$geo_locs = json_decode($resp, true);
		if(!empty(RAW_TEXT)) {
			header('Content-Type: text/plain;charset=utf-8');
			// echo "$batch\n\n";
			print_r($geo_locs);
		} else {
			$fields = explode(' ', 'as city country countryCode isp lat lon org region regionName status timezone zip');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>Ip-api.com demo</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<style type="text/css">
		body > div { overflow-x: auto; }
		table { border-collapse: collapse; }
		thead tr { background-color: #4CAF50; color: #fff; }
		tbody tr:nth-child(even) { background-color: #f2f2f2; }
		tbody tr:hover { background-color: #444; color: #fff; }
		td { white-space: nowrap; padding: 0 0.5rem; }
		td:not(:last-of-type) { border-right: 1px solid #444; }
		p { text-align: center; }
	</style>
</head>
<body>
	<div>
		<table>
			<thead>
				<tr>
<?php
	foreach($fields as $f) {
		echo <<< ROW
					<th>$f</th>\n
ROW;
	}
?>
				</tr>
			</thead>
			<tbody>
<?php
	foreach($geo_locs as $infos) {
		echo <<< ROW_STARTS
				<tr>\n
ROW_STARTS;
		foreach($fields as $field) {
			echo <<< CELL
					<td>{$infos[$field]}</td>\n
CELL;
		}
		echo <<< ROW_ENDS
				</tr>\n
ROW_ENDS;
	}
?>
			</tbody>
		</table>
	</div>
	<p>More help at <a href="http://ip-api.com" rel="noreferrer nofollow" target="_blank">http://ip-api.com</a></p>
</body>
</html>
<?php
		}
	} else {
		die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
	}
?>