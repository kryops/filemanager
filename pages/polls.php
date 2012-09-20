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
				<a href="index.php?p=polls" id="pollhead'.$p->pollID.'" class="poll" data-id='.$p->pollID.' data-expanded=0 >
				';
				
				if($p->answer == '')
					$tmpl->content .= '<strong>'.$p->pollName.'</strong>';
				else
					$tmpl->content .= $p->pollName;
				
				
				$tmpl->content .= ' (bis '.Polls::formatDate($p->pollEndDate).')</a>';
				// $p->pollAnswerCount einbringen?
				
				// Aufklappbare Details
				$tmpl->content .= '
				<div class="polldetail" id="poll'.$p->pollID.'" style="display: none;">
				<form class="pollform" action="index.php?p=polls&amp;sp=';
				
				if($p->answer == '') $tmpl->content .= 'answer';
				else $tmpl->content .= 'update';
					
				$tmpl->content .= '" method="post" enctype="multipart/form-data">';
					
				// Liste mit möglichen Antworten erzeugen
				$answerlist = explode(",", Polls::getAnswerList($p->pollID));
					
				foreach($answerlist as $a) {
					$tmpl->content .= '
					<div class="pollopt">
					<input type="radio" name="answer" value="'.$a.'"';
						
					if($p->answer == $a) $tmpl->content .= ' checked="yes"';
						
					$tmpl->content .= '/>  '.$a.'
					</div>';
				}
				
				$tmpl->content .= '<input type="text" name="id" value='.$p->pollID.' style="display: none;">';
					
				if($p->answer == '')
					$tmpl->content .= '
					<input type="submit" class="button wide pbgreen" value="Antworten" />';
				else $tmpl->content .= '
				<input type="submit" class="button wide pbyellow" value="Ändern" />';
				
				$tmpl->content .= '
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
		
		if(isset($_GET['pollid'], $_POST['answer'])) {
			$id = $_POST['pollid'];
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
			
			$tmpl = new Template;
				
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
			
			$tmpl = new Template;
			
			$tmpl->content .= 'Antwort gespeichert.';
			
			$tmpl->output();

		}
		else
			Template::bakeError("Fehler beim Speichern der Daten. ".$_GET);
	
	}
	
	
// 	/**
// 	 * Suchfunktion
// 	 */
// 	public static function doSearch() {
		
// 		if(!isset($_POST['search'])) {
// 			Template::bakeError('Daten unvollständig');
// 		}
		
// 		General::loadClass('Folder');
// 		General::loadClass('Files');
		
// 		$tmpl = new Template;
		
// 		$topfolder = User::getTopFolder();
		
// 		// alle zu durchsuchenden ermitteln
// 		$folders = Folder::getchildren_ids($topfolder, true);
// 		$folders[] = $topfolder;
		
// 		$conds = array(
// 			"files_folderID IN(".implode(", ", $folders).")"
// 		);
		
// 		// Suchfilter: alle Wörter kommt im angezeigten Namen vor
// 		$search = explode(" ", $_POST['search']);
		
// 		$searchfilter = array();
		
// 		foreach($search as $s) {
// 			if($s != '') {
// 				$searchfilter[] = "filesName LIKE '%".MySQL::escape(MySQL::escape($s))."%'";
// 			}
// 		}
		
// 		if(count($searchfilter)) {
// 			$conds[] = "(".implode(" AND ", $searchfilter).")";
// 		}
		
		
// 		$query = MySQL::query("
// 			SELECT
// 				".Config::mysql_prefix."files.*,
// 				userName
// 			FROM
// 				".Config::mysql_prefix."files
// 				LEFT JOIN ".Config::mysql_prefix."user
// 					ON userID = files_userID
// 			WHERE
// 				".implode(" AND ", $conds)."
// 			ORDER BY
// 				filesDate DESC
// 			LIMIT 250
// 		", __FILE__, __LINE__);
		
// 		$treffer = MySQL::rows($query);
		
// 		if($treffer == 0) {
// 			$tmpl->content = '
// 				<br />
// 				<div class="center">Die Suche lieferte keine Treffer.</div>
// 			';
// 		}
// 		else {
// 			$tmpl->content = '
// 				<p>Die Suche lieferte '.$treffer.' Treffer:</p>
// 				<div class="whitebox">';
			
// 			while($f = mysql_fetch_object($query)) {
				
// 				$path = Folder::getFolderPath($f->files_folderID, false, true);
				
// 				$tmpl->content .= self::getFileView(
// 					$f,
// 					$path,
// 					Folder::getFolderPath($f->files_folderID, true).$f->filesName
// 				);
				
// 			}
			
// 			$tmpl->content .= '
// 				</div>
// 			';
// 		}
		
// 		$tmpl->output();
// 	}
	
	
	
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