<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


/**
 * 
 * Klasse, die Funktionen für die Benutzerauthentifizierung enthält
 *
 */

class User {
	
	public static $login = false;
	
	public static $id;
	
	public static $name;
	
	public static $admin = false;
	
	public static $data;
	
	public static $error;
	
	
	/**
	 * Liest den Datensatz eines Benutzers aus der Datenbank aus
	 * @param int $id Benutzer-ID
	 * @return object|false
	 * 		Benutzer-Datensatz; false, wenn der Benutzer nicht existiert
	 */
	public static function getData($id) {
		
		return MySQL::querySingle("
			SELECT
				*
			FROM
				".Config::mysql_prefix."user
			WHERE
				userID = ".(int)$id."
		", __FILE__, __LINE__);
		
	}
	
	
	/**
	 * Überprüft anhand von Session und Cookie, ob der Benutzer eingeloggt ist
	 * @return boolean Erfolg
	 */
	public static function checkLogin() {
		
		// bereits als eingeloggt erkannt
		if(self::$login) {
			return true;
		}
		
		
		// über die Session
		if(isset($_SESSION[Config::session_prefix.'uid'])) {
			
			$data = self::getData($_SESSION[Config::session_prefix.'uid']);
			
			if($data !== false) {
				self::$login = true;
			}
			else {
				self::$error = 'Der Account wurde gelöscht!';
			}
			
		}
		
		// über das Autologin-Cookie
		else if(isset($_COOKIE[Config::cookie_name])) {
			
			$cookie = explode('+', $_COOKIE[Config::cookie_name]);
			
			if(count($cookie) == 2) {
				// Benutzerdaten abfragen
				$data = self::getData($cookie[0]);
				
				if($data !== false) {
					// Passwort überprüfen
					if($data->userPassword == $cookie[1]) {
						
						self::$login = true;
						
						// Session setzen
						$_SESSION[Config::session_prefix.'uid'] = $data->userID;
						$_SESSION[Config::session_prefix.'lastvisit'] = $data->userOnline;
						
					}
					else {
						self::$error = 'Dein Passwort wurde geändert!';
					}
				}
				else {
					self::$error = 'Der Account wurde gelöscht!';
				}
			}		
		}
		
		// Daten übernehmen
		if(self::$login) {
			self::$data = $data;
			self::$id = $data->userID;
			self::$name = $data->userName;
			self::$admin = (boolean)$data->userAdmin;
			
			self::updateOnline();
		}
		
		return self::$login;
	}
	
	
	/**
	 * Überprüft eingegebene Benutzerdaten und loggt den Benutzer ein
	 * @param string $username
	 * @param string $password
	 * 		Unverschlüsseltes Passwort
	 * @param boolean $auto
	 * 		Soll der Benutzer zukünftig automatisch eingeloggt werden?
	 * @return boolean Erfolg
	 */
	public static function login($username, $password, $auto=false) {
		
		// bereits eingeloggt
		if(self::$login) {
			return true;
		}
		
		
		// Benutzerdaten abfragen
		$data = MySQL::querySingle("
			SELECT
				*
			FROM
				".Config::mysql_prefix."user
			WHERE
				userName = '".MySQL::escape($username)."'
				AND userPassword = '".MySQL::escape(self::encryptPassword($password))."'
		", __FILE__, __LINE__);
		
		if($data !== false) {
			self::$login = true;
			self::$data = $data;
			self::$id = $data->userID;
			self::$name = $data->userName;
			self::$admin = (boolean)$data->userAdmin;
			
			// Session setzen
			$_SESSION[Config::session_prefix.'uid'] = $data->userID;
			$_SESSION[Config::session_prefix.'lastvisit'] = $data->userOnline;
			
			// Cookie setzen
			if($auto) {
				self::cookie();
			}
			
			self::updateOnline();
		}
		else {
			self::$error = 'Die eingegebenen Daten sind ungültig!';
		}
		
		return self::$login;
	}
	
	
	/**
	 * Setzt das Benutzer-Cookie
	 */
	public static function cookie() {
		setcookie(
			Config::cookie_name,
			$data->userID.'+'.$data->userPassword,
			time()+Config::cookie_lifetime
		);
	}
	
	
	/**
	 * loggt den Benutzer aus
	 */
	public static function logout() {
		
		unset($_SESSION[Config::session_prefix.'uid']);
		
		setcookie(Config::cookie_name, '', time()-3600);
		
		if(isset($_COOKIE[Config::cookie_name])) {
			unset($_COOKIE[Config::cookie_name]);
		}
		
	}
	
	
	/**
	 * letzte Aktivität aktualisieren
	 */
	public static function updateOnline() {
		
		if(!self::$login) {
			return false;
		}
		
		if(self::$data->userOnline < time()-60) {
			MySQL::query("
				UPDATE
					".Config::mysql_prefix."user
				SET
					userOnline = ".time().",
					userPassForgotten = 0
				WHERE
					userID = ".self::$id."
			", __FILE__, __LINE__);
		}
		
	}
	
	
	/**
	 * Passwort verschlüsseln
	 * @param string $pass Passwort
	 * @return string Passwort-Hash
	 */
	public static function encryptPassword($pass) {
		
		$hash = crypt($pass, '$2a$10$'.Config::key.'s61n5j$');
		
		return substr($hash, -32, 32);
		
	}
	
}


?>