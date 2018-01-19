<?php
	function strtolower_ru($text) {
		$alfavitlover = array('ё','й','ц','у','к','е','н','г', 'ш','щ','з','х','ъ','ф','ы','в', 'а','п','р','о','л','д','ж','э', 'я','ч','с','м','и','т','ь','б','ю');
		$alfavitupper = array('Ё','Й','Ц','У','К','Е','Н','Г', 'Ш','Щ','З','Х','Ъ','Ф','Ы','В', 'А','П','Р','О','Л','Д','Ж','Э', 'Я','Ч','С','М','И','Т','Ь','Б','Ю');
		return str_replace($alfavitupper,$alfavitlover,strtolower($text));
	}
	
	function DataFilter($string) {
		$string = strip_tags($string);
		$string = stripslashes($string);
		$string = htmlspecialchars($string);
		$string = mysql_escape_string($string);
		$string = trim($string);
		return $string;
	}
	
	function generateCode($length=6) {
		$chars = "_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
		$code = "";
		$clen = strlen($chars) - 1;
		while (strlen($code) < $length) {
			$code .= $chars[mt_rand(0,$clen)];
		}
		return $code;
	}
	
	function getQuery($query, $dataBase){
		$res = mysql_query($query, $dataBase);
		if($res != false) {
			$row = mysql_fetch_row($res);
			return $row[0];
		} else {
			return false;
		}
	}
	
	function setQuery($query, $dataBase, $wait=true){
		if($wait) {
			return mysql_query($query, $dataBase) or die(mysql_error());
		} else {
			mysql_unbuffered_query($query, $dataBase);
		}
	}
	
	function adrressISValid($address) {
		if(strlen($address) == 95 && $address[0] == '4') {
			return true;
		} else {
			return false;
		}
	}
	
	function getCourse($client) {
		$hlc_btc_price = 0.00002;
		$kzt_rub_price = 5.8;
		$client->postMessage("Загружаю курс. Подождите...");
		$response = file_get_contents("https://yobit.net/api/2/btc_rur/ticker");
		if(!isJSON($response)) {
			$client->postMessage("API временно недоступно. Повторите запрос позже");
		} else {
			$arr = json_decode($response, true);
			$rub_price = number_format($hlc_btc_price*$arr['ticker']['sell'], 2);
			$kzt_price = number_format($rub_price * $kzt_rub_price, 2);
			
			$response = file_get_contents("https://yobit.net/api/2/btc_usd/ticker");
			if(isJSON($response)) {
				$arr = json_decode($response, true);
				$usd_price = number_format($hlc_btc_price*$arr["ticker"]["sell"], 2);
				$usd_line = "1 HLC = ".$usd_price." USD\n";
			} else {
				$usd_line = '';
			}
			
			$message = "Текущий курс HLC:\n\n1 HLC = ".number_format($hlc_btc_price, 8)." BTC\n".$usd_line."1 HLC = ".$rub_price." RUB\n1 HLC = ".$kzt_price." KZT";
		}
		$client->postMessage($message);
	}
	
	function apiQuery($method, $query) {
		$apiURL = "https://api.wallet.halykcoin.org:2023/api/";
		return GetUrlData($apiURL.$method, $query);
	}
	
	function GetUrlData($url, $fields = "") {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		curl_setopt($ch, CURLOPT_USERAGENT, "Wilogio Api (compatible; MSIE 6.0; Windows NT 5.0)");
		
		if ($fields != "") {
		$fields_string = "";
		foreach ($fields as $key => $value) {
		$fields_string .= $key . '=' . $value . '&';
		}
		rtrim($fields_string, '&');
		
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
	class TelegramClient {
		var $token = '';
		var $api = 'https://api.telegram.org/bot';
		var $db = null;
		
		public $chatID = null;
		public $name = null;
		public $message = null;
		public $data = null;
		public $debug = null;
		public $type = null;
		
		function init($login, $base, $password) {
			$db = mysql_connect("localhost",$login,$password);
			mysql_select_db($base, $db);
			$this->db = $db;
		}
		
		function setToken($value) {
			$this->token = $value;
		}
		
		function getMessage() {
			$output = json_decode(file_get_contents('php://input'), TRUE);
			$chatID = DataFilter($output['message']['chat']['id']);
			if($this->chatID) {
				die(ERR_INVALID_CID);
			} else {
				$this->chatID = $chatID;
			}
			
			$is_bot = DataFilter($output['message']['from']['is_bot']);
			if($is_bot == True) {
				//не общаемся с ботами
				die();
			}
			$this->name = DataFilter($output['message']['chat']['first_name']);
			$message = DataFilter($output['message']['text']);
			$this->message = $message;
			$this->type = DataFilter($output['message']['chat']['type']);
			//$this->debug = DataFilter($output['message']['entities']);
			
			//найдем в базе данный chatID, чтобы идентифицировать пользователя
			$querySTR = "SELECT uid,IFNULL(aid,'none') AS authID FROM hlc_bot_users WHERE tid=".$chatID;
			$query = mysql_query($querySTR, $this->db);
			
			$result = mysql_fetch_assoc($query);
			//$this->postMessage($result['authID']);
			if($result == false) {
				//пользователь еще не добавлен в базу
				mysql_query("INSERT INTO hlc_bot_users (tid) VALUES ($chatID)", $this->db);
				$query = mysql_query($querySTR, $this->db);
				$this->data = mysql_fetch_assoc($query);
			} else {
				$this->data = $result;
			}
			
			//возвращаем введенную пользователем команду или сообщение
			return $message;
		}
		
		function postImage($info, $path) {
			$chatID = $this->chatID;
			$url  = $this->api . $this->token . "/sendPhoto?chat_id=".$chatID;
			$post_fields = array('chat_id' => $chatID,
				'caption' => $info,
				'photo' => new CURLFile(realpath($path))
			);
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
			$output = curl_exec($ch);
		}
		
		function postMessage($message, $buttons=null) {
			$queryURL = $this->api . $this->token . '/sendMessage?chat_id=' . $this->chatID . '&text=' . urlencode($message);
			
			if($buttons) {
				$queryURL .= '&reply_markup='.$buttons;
			}
			file_get_contents($queryURL);
		}
		
		function sendMoney($amount, $address) {
			$data = $this->data;
			if($data['authID'] == 'none') {
				$this->postMessage("Кошелек пока не создан. Чтобы создать кошелек, введи команду /create");
			} else {
				$tx_amount = round($amount*100000000000);
				$response = apiQuery("transfer", array(
					'id' => $data['authID'],
					'address' => $address,
					'amount' => $tx_amount,
					'paymentId' => '',
					'mixIn' => 1
				));
				if(!isJSON($response)) {
					$this->postMessage("Произошла ошибка при обращении к API: запрос на отправку hlc");
				} else {
					$result = json_decode($response, true);
					if($result['success'] != true) {
						$this->postMessage("Ошибка при отправке средств. Проверьте правильность ввода данных\nОшибка: ".$result['message']);
					} else {
						$this->postMessage("Средства успешно отправлены!");
					}
				}
			}
		}
		
		function getBalance() {
			$decimals = 2;
			$data = $this->data;
			if($data['authID'] == 'none') {
				$this->postMessage("Кошелек пока не создан. Чтобы создать кошелек, введи команду /create");
			} else {
				$this->postMessage("Проверяю баланс...");
				$response = apiQuery("getBalance", array('id' => $data['authID']));
				if(!isJSON($response)) {
					$this->postMessage("Произошла ошибка при обращении к API: запрос на проверку баланса");
					die();
				} else {
					$result = json_decode($response, true);
					if($result['success'] != true) {
						//запрос выполнен неудачно
						$this->postMessage($result['data']);
						die();
					} else {
						//удачный запрос
						$balance = number_format($result['data']['balance']/100000000000, $decimals);
						$unlocked = number_format($result['data']['unlocked_balance']/100000000000, $decimals);
						$this->postMessage("Суммарно: \n".$balance." HLC\nДоступно: ".$unlocked.' HLC');
					}
				}
			}
		}
		
		function createWallet() {
			$data = $this->data;
			if($data['authID'] != 'none') {
				//если кошелек уже был создан
				$this->postMessage("Кошелек был создан ранее");
			} else {
				$this->postMessage("Создаю кошелек...");
				//кошелек еще не был создан, создадим
				$response = apiQuery("createWallet", array());
				if(!isJSON($response)) {
					$this->postMessage("Произошла ошибка при обращении к API: запрос на создание кошелька");
					die();
				} else {
					$result = json_decode($response, true);
					if($result['success'] != true) {
						//запрос выполнен неудачно
						$this->postMessage($result['data']);
						die();
					}
				}
				$authID = $result['data'];
				//записываем авторизационный id в базу
				mysql_query("UPDATE hlc_bot_users SET aid='$authID' WHERE uid=".$data['uid'], $this->db);
				//получим seed кошелька
				$response = apiQuery("showMnemonicSeed", array('id' => $authID));
				$result = json_decode($response, true);
				$seed = $result['data'];
				
				$response = apiQuery("getAddress", array('id' => $authID));
				$result = json_decode($response, true);
				$address = $result['data'];
				
				$this->postMessage("Кошелек успешно создан! Запиши и никому не сообщай auth_id и seed: ");
				$this->postMessage("auth_id: ".$authID);
				$this->postMessage("seed: ".$seed);
				$this->postMessage("Ваш адрес для приема HLC: ".$address);
			}
		}
	}
	
	function isJSON($string) {
		return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
	}
?>