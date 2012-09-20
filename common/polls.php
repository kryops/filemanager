<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


class Polls {

	public static $polls = array();
	public static $pollcount = 0;
	
	
	/**
	 * Die Antowortliste einer Umfrage laden
	 * @param int $id
	 */
	public static function getAnswerList($id) {
		
		$poll = MySQL::querySingle("
			SELECT
				".Config::mysql_prefix."poll.*
			FROM
				".Config::mysql_prefix."poll
			WHERE
				pollID = ".$id."
		", __FILE__, __LINE__);
		
		if($poll) return $poll->pollAnswerList;
		else return '';
	}
	
	
	/**
	 * Alle Umfragen laden
	 * @return array Umfrage-Datensätze
	 */
	public static function loadall() {
		
		General::loadClass('User');
		
		$conds = array();
		
		// zurücksetzen, wenn alle geladen werden
		self::$polls = array();
		
		$query = MySQL::query("
			SELECT
				".Config::mysql_prefix."poll.*
			FROM
				".Config::mysql_prefix."poll
		", __FILE__, __LINE__);
		
		while($row = MySQL::fetch($query)) {
			if($row->pollEndDate > time()) {
				$tmp = MySQL::query("
						SELECT
						".Config::mysql_prefix."pollstatus.pollstatusAnswer
						FROM
						".Config::mysql_prefix."pollstatus
						WHERE
						pollstatus_pollID = ".$row->pollID."
						AND
						pollstatus_userID = ".User::$id
						, __FILE__, __LINE__);
				$ans = MySQL::fetch($tmp);
				if($ans) $row->answer = $ans->pollstatusAnswer;
				else $row->answer = '';
				self::$polls[] = $row;
				self::$pollcount += 1;
			}
		}
		
	}
	
	/**
	 * pollcount getter
	 */
	public static function getCount() {
		return self::$pollcount;
	}
	
	/**
	 * Datum formatieren
	 * @param int $date Unix-Timestamp
	 * @return string formatiertes Datum
	 */
	public static function formatDate($date) {
		return strftime('%d.%m.%y', $date);
	}
	
}

?>