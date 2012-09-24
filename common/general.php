<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


/**
 * 
 * Klasse, die generelle Funktionen übernimmt
 * 
 */

class General {
	
	/**
	 * Vorhandene Seiten im Projekt
	 * Schlüssel: $_GET['p'], Name der PHP-Datei im Ordner pages
	 * Wert: Name der Klasse, von welcher die dispatch()-Funktion aufgerufen wird
	 */
	public static $pages = array(
		'polls' => 'PollsPage',
		'files' => 'FilesPage',
		'login' => 'LoginPage',
		'register' => 'RegisterPage',
		'upload' => 'UploadPage',
		'settings' => 'SettingsPage',
		'admin' => 'AdminPage',
		'impressum' => 'ImpressumPage'
	);
	
	
	/**
	 * Zeitpunkt, zu welchem der Ladevorgang der Seite begonnen wurde
	 * @var float
	 */
	public static $loadtime;
	
	
	/**
	 * Lädt eine Klassendatei
	 * @param string $classname Klassenname
	 * 		Dazugehörige Datei muss im Ordner common liegen
	 * 		Dateiname kleingeschrieben!
	 * @return boolean Erfolg
	 */
	public static function loadClass($classname) {
		
		// Klasse bereits geladen
		if(class_exists($classname)) {
			return false;
		}
		
		// Klasse einbinden
		include './common/'.strtolower($classname).'.php';
		
		return true;
	}
	
	
	/**
	 * Setzt wichtige globale PHP-Einstellungen
	 * und startet eine Session
	 */
	public static function globalPHPSettings() {
		
		ignore_user_abort(true);
		date_default_timezone_set('Europe/Berlin');
		
		@session_start();
		
		// Magic Quotes entfernen
		if(get_magic_quotes_gpc()) {
			function strsl(&$item, $key) {
				$item = stripslashes($item);
			}
			array_walk_recursive($_GET, 'strsl');
			array_walk_recursive($_POST, 'strsl');
		}
		
	}
	
	
	/**
	 * Setzt HTTP-Header, die verhindern, dass die Seite gecached wird
	 */
	public static function setCacheHeaders() {
		
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
	
	}
	
	
	/**
	 * Berechnet die bisher vergangene Seitenladezeit
	 * @param bool $format
	 * 		Gibt an, ob die Ausgabe formatiert erfolgen soll @default false
	 * 		Bei false wird die Ladezeit als float zurückgegeben
	 * 		Bei true wird die Ladezeit als String mit Mikrosekunden zurückgegeben
	 * @return number|string
	 */
	public static function getBenchmark($format=false) {
		
		if(self::$loadtime == null) {
			return 0;
		}
		
		if($format) {
			return number_format(microtime(true)-self::$loadtime, 6);
		}
		else {
			return (microtime(true)-self::$loadtime);
		}
		
	}
	
	/**
	 * Datum formatieren
	 * @param int $date Unix-Timestamp
	 * @return string formatiertes Datum
	 */
	public static function formatDate($date) {
		return strftime('%d.%m.%Y', $date);
	}
	
}

/*
 * Shortcut-Funktionen
 */


/**
 * Shortcut für htmlspecialchars (mit UTF-8);
 * @param unknown_type $str
 */
function h($str) {
	return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
}


?>