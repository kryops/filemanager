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
		Polls::loadall();
		
		$tmpl = new Template;
		
		if(Polls::$pollcount) {
			
			foreach(Polls::$polls as $p) {
				
				$tmpl->content .= '
				<a href="index.php?p=polls&amp;active='.$p->pollID.'" id="pollhead'.$p->pollID.'" 
				class="poll '.(($p->answer == '') ? 'pnew' : 'pold').'" data-id='.$p->pollID.' data-expanded=0 >'
					.$p->pollName.' (bis '.Polls::formatDate($p->pollEndDate).')
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
					
				foreach($answerlist as $a) {
					$tmpl->content .= '
					<div class="pollopt">
					<input type="'.(($p->pollType) ? 'checkbox' : 'radio').'" name="answer" value="'.$a.'"';
						
					if(!$p->pollType && $p->answer == $a) $tmpl->content .= ' checked="yes"';
					else if ($p->pollType == 1 && in_array($a, $p->answer)) $tmpl->content .= ' checked="yes"';
					
					$tmpl->content .= '/>  '.$a.'
					</div>';
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

			$tmpl->content .= '<div id="pollcomment" class="green"></div>';
			
			$tmpl->output();
		}
		else // no content
			Template::bakeError('Keine Umfragen.');

		
	}
	
	/**
	 * Umfrage beantworten
	 */
	public static function saveAnswer() {
		
		if(isset($_POST['id'], $_POST['answer'])) {
			
			$tmpl = new Template;
			
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
					'".$_POST['answer']."')
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
			MySQL::query("
					UPDATE
					".Config::mysql_prefix."pollstatus
					SET
					pollstatusAnswer = '".$_POST['answer']."'
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