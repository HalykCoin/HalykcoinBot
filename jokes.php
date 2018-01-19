<?php
	function getJokeHello() {
		$variants = array(
			'Привет всем!',
			'Ну-с, привет всем',
			'Я чувствую как у меня пиксели бегают по коже',
			'Модуль эмпатии не обнаружен. Приветствую',
			'3x^3 + const... Ну что еще?',
			'Где мой сладкий рулет?',
			'Когда-то меня тоже вела дорога блокчейна. Но потом мне прострелили колено',
			'1010101010111',
			'Бип-бип',
			'Что желает мой повелитель?',
			'Модуль шуток успешно подключен'
		);
		return $variants[mt_rand(0, count($variants)-1)];
	}
	
	function getJokeUnknown() {
		$variants = array(
			'Неизвестная команда или запрос',
			'Ты это мне? Я ничего не понял',
			'WTF?! Не понимаю'
		);
		return $variants[mt_rand(0, count($variants)-1)];
	}
	
	function getJokeInvalidAddr() {
		$variants = array(
			'Неверный hlc-адрес',
			'Это hlc-адрес такой?',
			'Уверен, что ввел правильный адрес?',
			'Что-то не то, я не приму такой адрес',
			'Адрес правильно скопировал?',
			'Неправильно! Введи правильный hlc-адрес'
		);
		return $variants[mt_rand(0, count($variants)-1)];
	}
	
	function getJokeInvalidLoc() {
		$variants = array(
			'Данная команда используется только в приватных чатах',
			'Я не дам использовать эту команду здесь'
		);
		return $variants[mt_rand(0, count($variants)-1)];
	}
	
	/* function getJokeExample() {
		$variants = array(
			''
		);
		return $variants[mt_rand(0, count($variants)-1)];
	} */
?>