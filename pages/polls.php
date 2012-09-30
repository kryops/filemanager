<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class PollsPage {
	
	/**
	 * Verfügbare Unterseiten
	 * Schlüssel: $_GET['sp']
	 * Wert: Name der Funktion, die aufgerufen werden soll
	 */
	public static $actions = array(
		'' => 'displayOverview',
		'answer' => 'saveAnswer',
		'update' => 'updateAnswer'
	);
	
	/*
	 * Seiten und Aktionen
	 */
	
	/**
	 * Umfragenübersicht anzeigen
	 */
	public static function displayOverview() {
		
		// Umfragen laden
		General::loadClass('Polls');
		
		$polls= Polls::getall();
		
		$active = (isset($_GET['active']) ? (int)$_GET['active'] : 0);
		
		$tmpl = new Template;

		foreach($polls as $p) {
			
			if($p->pollEndDate < time()) continue;
			
			$tmpl->content .= '
			<a href="index.php?p=polls'.($active != $p->pollID ? '&amp;active='.$p->pollID : '').'" id="pollhead'.$p->pollID.'" 
			class="poll '.(($p->answer == '') ? 'bold' : 'grey').'" data-id='.$p->pollID.' data-expanded=0 >'
				.h($p->pollTitle).' (bis '.General::formatDate($p->pollEndDate).')
			</a>
			';

			// Details
			$tmpl->content .= '
			<div class="polldetail" id="poll'.$p->pollID.'"'
			.(($active == $p->pollID) ? '' : 'style="display: none;"').'>
			<form class="pollform '.(($p->answer == '') ? '' : 'pupdate').'" action="index.php?p=polls&amp;sp=';
			
			if($p->answer == '') $tmpl->content .= 'answer';
			else $tmpl->content .= 'update';
				
			$tmpl->content .= '" method="post" enctype="multipart/form-data">';
				
			// Liste mit möglichen Antworten erzeugen
			$answerlist = explode(",", $p->pollAnswerList);
			$i = 0;
				
			foreach($answerlist as $a) {
				$tmpl->content .= '
				<div class="pollopt">
				<input type="'.(($p->pollType) ? 'checkbox" name="answer['.$i.']"' : 'radio" name="answer"').' value="'.h($a).'"';

				if($p->pollType == 0 && $p->answer == $a) $tmpl->content .= ' checked="yes"';
				
				if($p->pollType == 1 && $p->answer != '' && in_array($a, explode(",", $p->answer)))
						$tmpl->content .= ' checked="yes"';
				
				$tmpl->content .= '/>  '.h($a).'
				</div>';
				
				$i++;
			}
			
			$tmpl->content .= '<input type="text" name="id" value='.$p->pollID.' style="display: none;">
			<input type="submit" id="pb'.$p->pollID.'" class="button wide ';
			
			if($p->answer == '')
				$tmpl->content .= 'pbanswer" value="Abstimmen"';
			else 
				$tmpl->content .= 'pbupdate" value="&Auml;ndern"';
			
			$tmpl->content .= '/>
			</form>
			</div>
			';
			
		}	

		if($tmpl->content == '') 
			$tmpl->content = '<div class="center grey">Keine Umfragen</div>';
		else 
			$tmpl->content .= '<br/>
		<div id="poll_status" class="center grey"></div>';

		$tmpl->output();
		
	}
	
	/**
	 * Umfrage beantworten
	 */
	public static function saveAnswer() {
		
		if(isset($_POST['id'], $_POST['answer'])) {
			
			$tmpl = new Template;
			
			$id = (int)$_POST['id'];
			$answer = $_POST['answer'];
			
			General::loadClass("Polls");
			
			if(!Polls::checkAnswer($id, $answer))
				Template::bakeError("Daten ungültig.");
			
			if(is_array($answer)) $answer = implode(",", $answer);
			
			
			// doppelt abgestimmt?
			$doppelt = MySQL::querySingle("
				SELECT
					pollstatus_userID
				FROM
					".Config::mysql_prefix."pollstatus
				WHERE
					pollstatus_pollID = ".$id."
					AND pollstatus_userID = ".User::$id."
			", __FILE__, __LINE__);
			
			if($doppelt) {
				Template::bakeError('Du hast bereits abgestimmt!');
			}
			
			
			MySQL::query("
					INSERT INTO
					".Config::mysql_prefix."pollstatus
					(pollstatus_pollID,
					pollstatus_userID,
					pollstatusAnswer)
					VALUES
					(".$id.",
					".User::$id.",
					'".MySQL::escape($answer)."')
					", __FILE__, __LINE__);
				
				
			MySQL::query("
					UPDATE
					".Config::mysql_prefix."poll
					SET
					pollAnswerCount = pollAnswerCount + 1
					WHERE
					pollID = ".$id."
					", __FILE__, __LINE__);

			$tmpl->content = 'Antwort gespeichert.';
				
			$tmpl->output();
		}
		else
			Template::bakeError("Fehler beim Speichern der Daten.");
		
	}
	
	/**
	 * Antwort ändern
	 */
	public static function updateAnswer() {
	
		if(isset($_POST['id'], $_POST['answer'])) {
					
			$tmpl = new Template;
			
			$id = (int)$_POST['id'];
			$answer = $_POST['answer'];
			
			General::loadClass("Polls");
			
			if(!Polls::checkAnswer($id, $answer))
				Template::bakeError("Daten ungültig.");
				
			if(is_array($answer)) $answer = implode(",", $answer);
			
			MySQL::query("
					UPDATE
					".Config::mysql_prefix."pollstatus
					SET
					pollstatusAnswer = '".MySQL::escape($answer)."'
					WHERE
					pollstatus_pollID = ".$id."
					AND
					pollstatus_userID = ".User::$id."
					", __FILE__, __LINE__);
			
			$tmpl->content = 'Antwort gespeichert.';
			
			$tmpl->output();

		}
		else
			Template::bakeError("Fehler beim Speichern der Daten.");
	
	}
	
	
	/**
	 * Seite auswählen, die geladen werden soll
	 */
	public static function dispatch() {
		
		if(!User::$login) {
			Template::bakeError('Du bist nicht eingeloggt!');
		}
		
		if(isset(self::$actions[$_GET['sp']])) {
			$action = self::$actions[$_GET['sp']];
			self::$action();
		}
		
		// Seite nicht gefunden
		else {
			Template::bakeError('Die Seite wurde nicht gefunden!');
		}
		
	}
	
}


?>