<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


class Polls {

	/**
	 * Den Datensatz einer Umfrage laden
	 * @param int $id
	 * @return object Umfrage-Datensatz
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
	 * @return array Zweidimensionales Array dessen Schlüssel die Antworten der Umfrage sind
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
		
		$userMap = User::getMap();
		
		foreach(explode(",",$answerlist) as $a) {
			$results[$a][0] = 0;
			$results[$a][1] = '';
		}
		
		while($row = MySQL::fetch($query)) {
			$ans = $row->pollstatusAnswer;
			
			if(strpos($ans, ","))
			{
				$answerline = explode(",",$ans);
				foreach($answerline as $a) {
					$results[$a][0]++;
					
					$results[$a][1] .= ($results[$a][1] == '' ? '' : ', ').$userMap[(int)$row->pollstatus_userID];
				}
			}
			else {
				$results[$ans][0]++;
					
				$results[$ans][1] .= ($results[$ans][1] == '' ? '' : ', ').$userMap[(int)$row->pollstatus_userID];
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
	 * Prüfen ob eine Antwort möglich ist (gegen böswillige HTTP-Pakete)
	 * @param int $id
	 * @param String $answer
	 * @return boolean Gültigkeit
	 */
	public static function checkAnswer($id,$answer)
	{
		$query = MySQL::querySingle("SELECT
							  ".Config::mysql_prefix."poll.*
							  FROM
							  ".Config::mysql_prefix."poll
					 		  WHERE
							  pollID = ".$id."
							  ", __FILE__, __LINE__);
		
		if($query)
		{
			$validanswers = explode(",", $query->pollAnswerList);
			
			if(is_array($answer))
			{
				if($query->pollType != 1) return false;
				
				foreach($answer as $a)
				{
					if(in_array($a, $validanswers))
						continue;
					else
						return false;
				}
				
				return true;
			}
			else
			{				
				if(in_array($answer, $validanswers)) 
					return true;
				else 
					return false;
			}
			
			
		}	
		else return false;
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
		
		return MySQL::queryArray("
			SELECT
				".Config::mysql_prefix."poll.*,
				pollstatusAnswer AS answer
			FROM
				".Config::mysql_prefix."poll
				LEFT JOIN ".Config::mysql_prefix."pollstatus
				ON pollstatus_pollID = pollID AND pollstatus_userID = ".User::$id."
		", __FILE__, __LINE__);
		
	}
	
}

?>