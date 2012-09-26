<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


class Polls {

	public static $pollcount = 0;
	
	/**
	 * Den Datensatz einer Umfrage laden
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
	 * Die Ergebnisse einer Umfrage laden
	 * @param int $id
	 */
	public static function getResults($id, $answerlist) {
	
		$results = array();
		
		$query = MySQL::query("
				SELECT
				".Config::mysql_prefix."pollstatus.*
				FROM
				".Config::mysql_prefix."pollstatus
				WHERE
				pollstatus_pollID = ".(int)$id."
				", __FILE__, __LINE__);
		
		foreach(explode(",",$answerlist) as $a) {
			$results[$a][0] = 0;
			$results[$a][1] = '';
		}
		
		while($row = MySQL::fetch($query)) {
			if(strpos($row->pollstatusAnswer, ","))
			{
				$answerline = explode(",",$row->pollstatusAnswer);
				foreach($answerline as $a) {
					$results[$a][0]++;
					
					$results[$a][1] .= ($results[$a][1] == '' ? '' : ', ').User::getData($row->pollstatus_userID)->userName;
				}
			}
			else {
				$results[$row->pollstatusAnswer][0]++;
					
				$results[$row->pollstatusAnswer][1] .= ($results[$row->pollstatusAnswer][1] == '' ? '' : ', ').User::getData($row->pollstatus_userID)->userName;
			}

		}
		
		return $results;
	
	}
	
	/**
	 * Den Datensatz einer Datei löschen
	 * @param int $id
	 */
	public static function delete($id) {
	
		MySQL::query("DELETE FROM
					 ".Config::mysql_prefix."pollstatus
					 WHERE
					 pollstatus_pollID = ".$id."
					 ", __FILE__, __LINE__);
		
		MySQL::query("DELETE FROM
					 ".Config::mysql_prefix."poll
					 WHERE
					 pollID = ".$id."
					 ", __FILE__, __LINE__);
	
	}
	
	/**
	 * Antworten auf eine Umfrage löschen
	 * @param int $id
	 */
	public static function removeAnswers($id) {
	
		MySQL::query("
				DELETE FROM
				".Config::mysql_prefix."pollstatus
				WHERE
				pollstatus_pollID = ".$id."
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