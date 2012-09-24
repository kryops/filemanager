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
		
		$active = (isset($_GET['active']) ? $_GET['active'] : 0);
		
		$tmpl = new Template;
		
		if(Polls::$pollcount) {
			
			foreach($polls as $p) {
				
				$tmpl->content .= '
				<a href="index.php?p=polls'.($active != $p->pollID ? '&amp;active='.$p->pollID : '').'" id="pollhead'.$p->pollID.'" 
				class="poll '.(($p->answer == '') ? 'bold' : 'grey').'" data-id='.$p->pollID.' data-expanded=0 >'
					.$p->pollTitle.' (bis '.General::formatDate($p->pollEndDate).')
				</a>
				';

				// Details
				$tmpl->content .= '
				<div class="polldetail" id="poll'.$p->pollID.'"'
				.((isset($_GET['active']) AND $p->pollID == $_GET['active']) ? '' : 'style="display: none;"').'>
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
					<input type="'.(($p->pollType) ? 'checkbox" name="answer['.$i.']"' : 'radio" name="answer"').' value="'.$a.'"';

					if($p->pollType == 0 && $p->answer == $a) $tmpl->content .= ' checked="yes"';
					
					if($p->pollType == 1 && $p->answer != '' && in_array($a, explode(",", $p->answer)))
							$tmpl->content .= ' checked="yes"';
					
					$tmpl->content .= '/>  '.$a.'
					</div>';
					
					$i++;
				}
				
				$tmpl->content .= '<input type="text" name="id" value='.$p->pollID.' style="display: none;">
				<input type="submit" id="pb'.$p->pollID.'" class="button wide ';
				
				if($p->answer == '')
					$tmpl->content .= 'pbanswer" value="Abstimmen"';
				else 
					$tmpl->content .= 'pbupdate" value="Ändern"';
				
				$tmpl->content .= '/>
				</form>
				</div>
				';
				
			}

			$tmpl->content .= '<div id="poll_status" class="green"></div>';
			
		}
		else // no content
			$tmpl->content .= '<div class="center">Keine Umfragen</div>';

		$tmpl->output();
		
	}
	
	/**
	 * Umfrage beantworten
	 */
	public static function saveAnswer() {
		
		if(isset($_POST['id'], $_POST['answer'])) {
			
			$tmpl = new Template;
			
			$answer = $_POST['answer'];
			
			if(is_array($answer)) $answer = implode(",", $answer);
			
			$id = $_POST['id'];
			MySQL::query("
					INSERT INTO
					".Config::mysql_prefix."pollstatus
					(pollstatus_pollID,
					pollstatus_userID,
					pollstatusAnswer)
					VALUES
					(".$id.",
					".User::$id.",
					'".$answer."')
					", __FILE__, __LINE__);
				
				
			MySQL::query("
					UPDATE
					".Config::mysql_prefix."poll
					SET
					pollAnswerCount = pollAnswerCount + 1
					WHERE
					pollID = ".$id."
					", __FILE__, __LINE__);

			$tmpl->content .= 'Antwort gespeichert.';
				
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
			
			$id = $_POST['id'];
			
			$answer = $_POST['answer'];
				
			if(is_array($answer)) $answer = implode(",", $answer);
			
			MySQL::query("
					UPDATE
					".Config::mysql_prefix."pollstatus
					SET
					pollstatusAnswer = '".$answer."'
					WHERE
					pollstatus_pollID = ".$id."
					AND
					pollstatus_userID = ".User::$id."
					", __FILE__, __LINE__);
			
			$tmpl->content .= 'Antwort gespeichert.';
			
			$tmpl->output();

		}
		else
			Template::bakeError("Fehler beim Speichern der Daten. ".$_GET);
	
	}
	
	
	/**
	 * Seite auswählen, die geladen werden soll
	 */
	public static function dispatch() {
		
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