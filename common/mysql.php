<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


/**
 * 
 * Klasse für die MySQL-Abstraktion von Projekten mit einer einzelnen MySQL-Verbindung
 * Unterstützt Lazy Connecting, Tracken und Benchmarken von abgesetzten Queries
 * 
 */

class MySQL {

	/**
	 * Verbindungs-Link
	 */
	private static $connection = false;
	
	/**
	 * Verbindungs-Konfiguration, @see setConfig()
	 */
	private static $config = false;
	
	/**
	 * Bei einem Fehler den kompletten Ladevorgang abbrechen
	 * @default true
	 */
	public static $die_on_error = true;
	
	/**
	 * Array, in dem alle abgesetzten Queries gespeichert werden
	 * @var array
	 */
	private static $queries = array();
	
	
	
	/**
	 * Verbindungs-Einstellungen setzen
	 * @param unknown_type $host
	 * @param unknown_type $user
	 * @param unknown_type $password
	 * @param unknown_type $database
	 */
	public static function setConfig($host, $user, $password, $database) {
		
		self::$config = array(
			'host' => $host,
			'user' => $user,
			'password' => $password,
			'database' => $database
		);
		
	}
	
	
	/**
	 * Verbindung aufbauen und Datenbank auswählen
	 */
	public static function connect() {
		
		if(self::$connection OR !self::$config) {
			return false;
		}
		
		// Verbindung aufbauen
		self::$connection = mysql_connect(
			self::$config['host'],
			self::$config['user'],
			self::$config['password']
		);
		
		if(!self::$connection) {
			if(self::$die_on_error) {
				die('Konnte keine Verbindung zur MySQL-Datenbank aufbauen: '.mysql_error());
			}
			else {
				return false;
			}
		}
		
		// Datenbank auswählen
		if(!mysql_select_db(self::$config['database'])) {
			if(self::$die_on_error) {
				die('Kein Zugriff auf die MySQL-Datenbank: '.mysql_error());
			}
			else {
				return false;
			}
		}
		
		// Zeichenkodierung auf UTF-8 stellen
		if(function_exists('mysql_set_charset')) {
			mysql_set_charset('utf8');
		}
		else {
			mysql_query("
				SET NAMES 'UTF8'
			");
		}
		
		return true;
	}
	
	
	/**
	 * MySQL-Query absetzen
	 * @param string $sql MySQL-Query
	 * @param string $file Ursprungs-Datei, im Regelfall __FILE__
	 * @param int $line Ursprungs-Zeile, im Regelfall __LINE__
	 * 
	 * @return MySQL-Ressource
	 */
	public static function query($sql, $file, $line) {
		
		// Lazy connecting
		if(!self::$connection) {
			self::connect();
		}
		
		$benchmark = microtime(true);
		
		// Query absetzen
		$query = mysql_query($sql);
		
		$benchmark = number_format(microtime(true)-$benchmark, 6);
		
		// tracken und benchmarken
		self::$queries[] = array(
			$sql,
			$file,
			$line,
			$benchmark
		);
		
		// fehlgeschlagen
		if(!$query) {
			if(self::$die_on_error) {
				die('Fehler in '.$file.' Zeile '.$line.': '.mysql_error());
			}
			else {
				return false;
			}
		}
		
		// Query zurückgeben
		return $query;
		
	}
	
	
	/**
	 * MySQL-Query absetzen und einzelnen Datensatz abfragen
	 * @param string $sql MySQL-Query
	 * @param string $file Ursprungs-Datei, im Regelfall __FILE__
	 * @param int $line Ursprungs-Zeile, im Regelfall __LINE__
	 * 
	 * @return object|false Datensatz
	 */
	public static function querySingle($sql, $file, $line) {
		
		$query = self::query($sql, $file, $line);
		
		if($query AND mysql_num_rows($query)) {
			return mysql_fetch_object($query);
		}
		
		return false;
		
	}
	
	
	/**
	 * MySQL-Query absetzen und Ergebnis als Array abfragen
	 * @param string $sql MySQL-Query
	 * @param string $file Ursprungs-Datei, im Regelfall __FILE__
	 * @param int $line Ursprungs-Zeile, im Regelfall __LINE__
	 * 
	 * @return array Datensatz-Array
	 */
	public static function queryArray($sql, $file, $line) {
		
		$query = self::query($sql, $file, $line);
		
		$arr = array();
		
		if($query) {
			while($row = mysql_fetch_object($query)) {
				$arr[] = $row;
			}
		}
		
		return $arr;
		
	}
	
	
	/**
	 * String escapen, um SQL-Injections zu verhindern
	 * @param string $str
	 */
	public static function escape($str) {
		
		// Lazy connecting
		if(!self::$connection) {
			self::connect();
		}
		
		return mysql_real_escape_string($str);
	}
	
	/**
	 * Zeile auslesen
	 *
	 * @param MySQLresult $query
	 * @return object Zeile
	 */
	public static function fetch($query) {
		
		return mysql_fetch_object($query);
		
	}
	
	/**
	 * Anzahl der Zeilen auslesen
	 *
	 * @param MySQLresult $query
	 * @return int Zeilenanzahl
	 */
	public static function rows($query) {
		
		return mysql_num_rows($query);
		
	}
	
	/**
	 * Speicher freigeben
	 *
	 * @param MySQLresult $query
	 */
	public static function free($query) {
		
		mysql_free_result($query);
		
	}
	
	/**
	 * Betroffene Zeilen auslesen
	 *
	 * @return int betroffene Zeilen
	 */
	public static function affected() {
		
		return mysql_affected_rows();
		
	}
	
	/**
	 * Eingefügte ID auslesen
	 *
	 * @return int ID
	 */
	public static function id() {
		
		return mysql_insert_id();
		
	}
	
	
	/**
	 * MySQL-Fehler zurückgeben
	 * @returns mysql_error();
	 */
	public static function getError() {
		
		if(!self::$connection) {
			return false;
		}
		
		return mysql_error();
	}
	
	
	/**
	 * Array aus abgesetzten Queries zurückgeben
	 */
	public static function getQueries() {
		return self::$queries;
	}
	
	
}