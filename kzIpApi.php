<?php
if(!defined('PLX_ROOT')) { exit('Make our planet great !'); }

/**
 * Retrouve les informations de géo-location d'un lot d'adresses IP à partir du site http://ip-api.com.
 * Utiliser la methode post pour la requête.
 * http://ip-api.com/docs/api:batch
 * Nécessite les librairies PHP pour json et curl.
 * */
class kzIpApi extends plxPlugin {

	const PROVIDER_URL = 'http://ip-api.com/batch';
	const COMMENTS_SELECTOR = '#comments-table:not(.flag) td.ip-address[data-ip]';
	const MAX_IPS = 8;
	const SPRITES = true;

	# const JSON_OPTIONS = JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT;
	const JSON_OPTIONS = JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE;

	private $__IPs = false;
	private $__timestamp = 0;

	public function __construct($default_lang) {
		parent::__construct($default_lang);

		if(
			!function_exists('geoip_country_code_by_name') and
			function_exists('json_decode') and
			function_exists('curl_init')
		) {
			# Pour sauvegarde des paramètres
			parent::setConfigProfil(PROFIL_EDITOR);

			$this->lang = $default_lang;
			parent::addHook('AdminCommentsFoot', 'AdminCommentsFoot');

			if(!empty(self::SPRITES) and is_file(__DIR__.'/flags.css')) {
				parent::addHook('AdminTopEndHead', 'AdminTopEndHead');
			}
		}
	}

	private function print_license() {
		// $title = 'From http://ip-api.com';
		$src = PLX_PLUGINS.__CLASS__.'/icon.jpg';
		echo <<< EOT
		const container = document.querySelector('#form_comments div.action-bar');
		if(container != null) {
			const div = document.createElement('DIV');
			div.className = 'ipApi-logo';
			div.innerHTML = '<a href="http://ip-api.com/" rel="noreferrer" target="_blank"><img src="$src" alt="http://ip-api.com"></a>';
			container.appendChild(div);
		}
EOT;
	}

	/**
	 * Enregistre chaque adresse IP présente dans le tableaud de commentaires.
	 * */
	public function add_IP($ip) {
		if(empty($this->IP_list)) {
			$this->IP_list = array(
				$ip	=> false
			);
		} elseif(!array_key_exists($ip, $this->IP_list)) {
			$this->IP_list[$ip] = false;
		}
	}

	private function __load_IPs() {
		$buffer = parent::getParam('IPs');
		if(!empty($buffer)) {
			$this->__IPs = json_decode($buffer, true);
			$this->__timestamp = parent::getParam('timestamp');
		} else {
			$this->__IPs = array();
		}
	}

	/**
	 * Sauvegarde toutes les infos de géo-localisations connues dans le fichier
	 * de paramètres au format JSON.
	 * */
	private function __save_IPs() {
		if(count($this->__IPs) > self::MAX_IPS) {
			uasort($this->__IPS, function ($a, $b) {
				return $b['timestamp'] - $a['timestamp'];
			});
			for($i=self::MAX_IPS, $iMax = count($this->__IPS); $i<$iMax; $i++) {
				// unset($this->__IPs[$i]);
			}
		}
		parent::setParam('timestamp', $this->__timestamp, 'numeric');
		parent::setParam('IPs', json_encode($this->__IPs, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT),'cdata');
		parent::saveParams();
	}

	/**
	 * Charge les infos de géo-localisation soit à partir du fichier de paramètres,
	 * soit depuis le site http://ip-api.com.
	 * */
	private function __getGeoIP() {
		self::__load_IPs();
		// Rechercher les adresses IP sans info
		$missingIPs = (!empty($this->__IPs)) ? array_keys(array_diff_key($this->IP_list, $this->__IPs)) : array_keys($this->IP_list);
		$lang = $this->lang;
		$batch = '['.implode(', ',
			array_map(
				function($ipAddr) use($lang) {
					return  <<< EOT
{"query": "$ipAddr", "lang": "$lang"}
EOT;
				},
				$missingIPs
			)
		).']';

		if(!empty($missingIPs)) {
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL				=> self::PROVIDER_URL,
				CURLOPT_POST			=> true,
				CURLOPT_HEADER			=> false,
				CURLOPT_RETURNTRANSFER	=> true,
				CURLOPT_POSTFIELDS		=> $batch
			));
			$resp = curl_exec($ch);
			if($resp !== false) {
				$locations = json_decode($resp, true);
				foreach($locations as $loc) {
					$ip = $loc['query'];
					// $this->IP_list[$ip] = $loc;
					$loc['timestamp'] = $this->__timestamp;
					$this->__IPs[$ip] = $loc;
					$this->__timestamp++;
				}
			}

			// Stocker les nouvelles infos de géo-localisation
			self::__save_IPs();
		}
	}

	/**
	 * Modifie le DOM avec Javascript pour afficher le drapeau et les élements de géo-localisation
	 * dans les éléments sélectionnés par la requête self::COMMENTS_SELECTOR.
	 * */
	public function set_flags($cssSelector) {
		if(empty($this->IP_list)) { return; }

		self::__getGeoIP();
		$geoLocs = array_intersect_key($this->__IPs, $this->IP_list);
?>
<script type="text/javascript">
	(function() {
		const cells = document.body.querySelectorAll('<?php echo $cssSelector; ?>');

		if(cells.length > 0) {
			const ipList = JSON.parse('<?php echo json_encode($geoLocs, SELF::JSON_OPTIONS); ?>');

			function printFlags() {
				if(ipList != null) {
					for(var i=0, iMax=cells.length; i<iMax; i++) {
						const cell = cells[i];
						const ip = cell.getAttribute('data-ip');

						if(typeof ipList[ip] != 'undefined') {
							// Interface avec les enregistrements de http://ip-api.com
							const iso_code = ipList[ip].countryCode;
							const caption = (typeof ipList[ip].city !== 'undefined') ? [ipList[ip].city, ipList[ip].country].join('<br />') : ipList[ip].country;
<?php
		if(!empty(self::SPRITES)) {
?>
							const flag = document.createElement('SPAN');
							flag.className = 'kz-flag ' + iso_code;
<?php
		} else {
?>
							const flag = document.createElement('IMG');
							flag.src = '<?php echo PLX_FLAGS_32_PATH; ?>' + iso_code + '.png';
							flag.className = 'flag';
							flag.alt = iso_code;
							flag.title = caption;
<?php
		}
?>
							cell.appendChild(flag);
							const span = document.createElement('SPAN');
							span.innerHTML = caption;
							cell.appendChild(span);
						}
					}
				}
			}

			printFlags();
<?php self::print_license(); ?>
		}
	})();
</script>
<?php
	}

	/* --------- Hooks ------------- */

	/**
	 * Récolte les adresses IP présentes dans le tableau des commentaires.
	 * */
	public function AdminCommentsFoot() {
		$code = <<< 'CODE'
<?php
foreach($plxAdmin->plxRecord_coms->result as $com) {
	$plxAdmin->plxPlugins->aPlugins['##CLASS##']->add_IP($com['ip']);
}
$plxAdmin->plxPlugins->aPlugins['##CLASS##']->set_flags('##CSS_SELECTOR##');
?>

CODE;
		$replaces = array(
			'##CLASS##'	=> __CLASS__,
			'##CSS_SELECTOR##'	=> self::COMMENTS_SELECTOR
		);
		echo str_replace(array_keys($replaces), array_values($replaces), $code);
	}

	public function AdminTopEndHead() {
		$href = PLX_PLUGINS.__CLASS__.'/flags.css';
		echo <<< LINK
		<link rel="stylesheet" href="$href" />
LINK;
	}

}
?>