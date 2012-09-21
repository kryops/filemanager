<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


class Polls {

	public static $pollcount = 0;
	
	/**
	 * Den Datensatz einer Datei laden
	 * @param int $id
	 */
	public static function get($id) {
	
		return MySQL::querySingle("
				SELECT
				".Config::mysql_prefix."poll.*
				FROM
				".Config::mysql_prefix."poll
				WHERE
				pollID = ".(int)$id."
				", __FILE__, __LINE__);
	
	}
	
	/**
	 * Den Datensatz einer Datei laden
	 * @param int $id
	 */
	public static function delete($id) {
	
		MySQL::query("DELETE FROM
					 ".Config::mysql_prefix."poll
					 WHERE
					 pollID = ".$id."
					 ", __FILE__, __LINE__);
	
	}
	
	/**
	 * Alle Umfragen laden
	 * @return array Umfrage-Datensätze
	 */
	public static function getall() {

		$polls = array();
		
		$query = MySQL::query("
			SELECT
				".Config::mysql_prefix."poll.*,
				pollstatusAnswer AS answer
			FROM
				".Config::mysql_prefix."poll
				LEFT JOIN ".Config::mysql_prefix."pollstatus
				ON pollstatus_pollID = pollID AND pollstatus_userID = ".User::$id."
		", __FILE__, __LINE__);
		
		while($row = MySQL::fetch($query)) {
			if($row->pollEndDate > time()) {
				$polls[] = $row;
				self::$pollcount += 1;
			}
		}
		
		return $polls;
	}
	
	/**
	 * pollcount getter
	 */
	public static function getCount() {
		return self::$pollcount;
	}
	
}

?>