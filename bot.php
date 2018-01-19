<?php
	include(__DIR__.'/errors.php');
	include(__DIR__.'/config.php');
	include(__DIR__.'/lib.php');
	include(__DIR__.'/jokes.php');
	
	$client = new TelegramClient();
	$client->init($config['base_login'], $config['base_name'], $config['base_password']);
	$client->setToken($config['token']);
	$message = $client->getMessage();
	$adminID = 144481831;
	$underConstruction = false;
	
	if($underConstruction && $client->chatID != $adminID) {
		$client->postMessage("Проводятся технические работы. Бот временно недоступен.");
	}
	
	//проверим, не добавлен ли бот в группу
	$type = $client->type;
	if($type == 'group' || $type == 'supergroup') {
		if((strpos($message, $config['bot_tag']) !== false) && ($message[0] == '/')) {
			//обнаружена команда
			$message = str_replace($config['bot_tag'], '', $message);
			switch($message) {
				case '/create':
					$client->postMessage(getJokeInvalidLoc());
					die();
					break;
			}
		} else {
			//сообщение адресовано не данному боту
			die();
		}
	}
	
	if(strpos($message, '/send ') == 0) {
		if(substr_count($message, ' ') == 2) {
			//если передана готовая команда
			$parts = explode(" ", $message);
			$address = DataFilter($parts[1]);
			if(!adrressISValid($address)) {
				$client->postMessage("Неверный HLC-адрес");
				die();
			}
			$amount = DataFilter($parts[2]);
			$client->sendMoney($amount, $address);
			die();
		}
	}
	
	switch($message) {
		default:
			$client->postMessage(getJokeUnknown());
			break;
		case '/start':
			$client->postMessage('Приветствую тебя, '.$client->name."\n\nКоманды:\n/create - создать кошелек\n/balance - проверить свой баланс\n/send - Отправить HLC\n/course - текущий курс HLC\n/links - полезные ссылки\n/buy - купить HLC");
			break;
		case '/buy':
			$client->postMessage("Чтобы приобрести HLC по текущему курсу, свяжись с @Sagleft");
			break;
		case '/course':
			getCourse($client);
			break;
		case '/create':
			$client->createWallet();
			break;
		case '/balance':
			$client->getBalance();
			break;
		case '/send':
			$client->postMessage("Команда для отправки HLC на другой адрес кошелька.\nИспользование:\n/send <адрес> <количество HLC>");
			break;
		case '/links':
			$client->postMessage("Полезные ссылки:\n\nОфициальный сайт проекта: https://halykcoin.org\nОбменник: https://vk.com/hlc_trade\nВеб-кошелек: https://wallet.halykcoin.org");
			break;
		case '/help':
			$client->postMessage("Здесь будет информация о боте");
			break;
	}
?>