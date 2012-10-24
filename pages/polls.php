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
		'update' => 'updateAnswer',
		'results' => 'displayPollResults'
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
			
			<p>'.$p->pollDescription.'</p>
			
			<form class="pollform" action="index.php?p=polls&amp;sp=';
			
			if($p->answer == '') $tmpl->content .= 'answer';
			else $tmpl->content .= 'update';
				
			$tmpl->content .= '" method="post" enctype="multipart/form-data">';
				
			// Liste mit möglichen Antworten erzeugen
			$optionlist = explode(",", $p->pollOptionList);
			$desclist = explode(",", $p->pollDescList);
			
			for($i = 0; $i < $p->pollOptionCount; $i++) {
				$tmpl->content .= '
				<div class="pollopt">
				<input type="'.(($p->pollType) ? 'checkbox" name="option['.$i.']"' : 'radio" name="option"').' value="'.h($optionlist[$i]).'"';
				
				if($p->pollType == 0 && $p->answer == $optionlist[$i]) 
					$tmpl->content .= ' checked="yes"';
				
				if($p->pollType == 1 && $p->answer != '' && in_array($optionlist[$i], explode(",", $p->answer)))
						$tmpl->content .= ' checked="yes"';
				
				$tmpl->content .= '/>  '.h($optionlist[$i]);
				if($desclist[$i] != '')
				{
					$tmpl->content .= ' ('.h($desclist[$i]).')';
				}
				$tmpl->content .= '
				</div>';
			}
			
			$tmpl->content .= '<input type="text" name="id" value='.$p->pollID.' style="display: none;">';
			
			if($p->answer == '')
				$tmpl->content .= '
				<input type="submit" id="pb'.$p->pollID.'" class="button wide pbanswer" value="Abstimmen" />';
			else 
				$tmpl->content .= '
				<input type="submit" id="pb'.$p->pollID.'" class="button wide pbupdate" value="Ändern" />
				<a href="index.php?p=polls&amp;sp=results&amp;id='.$p->pollID.'" class="button wide">Ergebnisse</a>'; // link in form ok?

			$tmpl->content .= '
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
		
		if(isset($_POST['id'], $_POST['option'])) {
			
			$tmpl = new Template;
			
			$id = (int)$_POST['id'];
			$answer = $_POST['option'];
			
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
	
		if(isset($_POST['id'], $_POST['option'])) {
					
			$tmpl = new Template;
			
			$id = (int)$_POST['id'];
			$answer = $_POST['option'];
			
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
	 * Ergebnisse einer Umfrage anzeigen
	 */
	public static function displayPollResults() {
	
		if(!isset($_GET['id'])) {
			Template::bakeError('Daten unvollständig!');
		}
	
		$id = (int)$_GET['id'];
	
		General::loadClass('Polls');
	
		$p = Polls::get($id);
	
		if(!$p) {
			Template::bakeError('Die Umfrage existiert nicht!');
		}
		
		if(!Polls::hasAnswered($id)) {
			Template::bakeError('Bitte stimme zuerst selbst ab!');
		}
		
		$results = Polls::getResults($id,$p->pollOptionList);
		
		$tmpl = new Template;
		$tmpl->title = 'Ergebnisse';
	
		$tmpl->content = '
	
		<div class="center">
	
		<h1>'.$p->pollTitle.'</h1>
		';
	
		switch($p->pollAnswerCount) {
			case 0: $tmpl->content .= '<p>Es hat noch niemand abgestimmt.</p>'; break;
			case 1: $tmpl->content .= '<p>Es hat bereits eine Person abgestimmt.</p>'; break;
			default: $tmpl->content .= '<p>Es haben bereits '.$p->pollAnswerCount.' Personen abgestimmt.</p>';
		}
			
		if($p->pollAnswerCount > 0)
		{
			$optionlist = explode(",", $p->pollOptionList);
			$desclist = explode(",", $p->pollDescList);
			
			$tmpl->content .= '
			<table class="polltable center">
			<tbody>';
			
			for($i = 0; $i < $p->pollOptionCount; $i++)
			{
				$tmpl->content .= '
				<tr>
				<td class="pt_title" colspan=2><span class="bold">'.h($optionlist[$i]).'</span> ('.$desclist[$i].')</td>
				</tr>
				<tr>
				<td class="pt_bar">
					<div class="thebar" style="width: '.(($results[$optionlist[$i]][0]*100) / $p->pollAnswerCount).'%">
					&nbsp;'.$results[$optionlist[$i]][0].'&nbsp;
					</div>
				</td>
				</tr>';
			}
			
			$tmpl->content .= '
			</tbody>
			</table>';
		}
	
		$tmpl->content .= '
		<br/>
		<p><a class="button wide" href="index.php?p=polls">Zurück</a></p>
	
		</div>
		';
	
		$tmpl->output();
	
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