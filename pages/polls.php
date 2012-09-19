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
		'answer' => 'saveAnswer'
	);
	
	public static $answered = false;
	
	/*
	 * Seiten und Aktionen
	 */
	
	/**
	 * Dateiübersicht anzeigen
	 */
	public static function displayOverview() {
		
		// Umfragen laden
		General::loadClass('Polls');
		Polls::loadall();
		
		if(isset($_GET['active'])) $active = $_GET['active'];
		else $active = -1;
		
		$tmpl = new Template;
		
		if(self::$answered) {
			$tmpl->content .= '<div class="green">Stimme abgegeben.</div>';
			self::$answered = false;
		}
		
		if(Polls::$pollcount) {
// 			$tmpl->content = '
// 				<div id="pollcontent">';
			
			foreach(Polls::$polls as $p) {
				
				$tmpl->content .= '
				<a href="index.php?p=polls';
				
				if($active != $p->pollID) $tmpl->content .= '&amp;active='.$p->pollID;
				
				$tmpl->content .= '" class="poll">
				';
				
				if($p->answer == '')
					$tmpl->content .= '<strong>'.$p->pollName.'</strong>';
				else
					$tmpl->content .= $p->pollName;
				
				
				$tmpl->content .= ' (bis '.Polls::formatDate($p->pollEndDate).')';
				// '.$p->pollAnswerCount.' Antworten - '
				
				$tmpl->content .= '
				</a>';
				
				if($p->pollID == $active) {
					$tmpl->content .= '
					<div class="polldetail">
					
					<form action="index.php?p=polls&amp;active='.$active
					.'&amp;sp=answer" method="post" id="pollform" enctype="multipart/form-data">';
						
					$i = 0;
					$ans = Polls::check($active);
						
					foreach(explode(",", $p->pollAnswerList) as $a) {
						$tmpl->content .= '
						<div class="pollopt">
						<input type="checkbox" name="answer" value="'.$a.'"';

						$ans = Polls::check($active);
						if($ans == $a) $tmpl->content .= ' checked="checked"';
						
						$tmpl->content .= '/>  '.$a.'
						</div>';
					}

					if($ans == '')
					$tmpl->content .= '
					<input type="submit" class="button wide pbgreen" value="Antworten" />';
					else $tmpl->content .= '
					<input type="submit" class="button wide pbyellow" value="Ändern" />';
					
					$tmpl->content .= '
					</form>
					</div>
					';
				}
				
			}
			
// 			$tmpl->content .= '
// 				</div>
// 			';
			
			$tmpl->output();
		}
		else // no content
			Template::bakeError('Keine Umfragen.');

		
	}
	
	/**
	 * Umfrage beantworten
	 */
	public static function saveAnswer() {
		
		General::loadClass('Polls');
		General::loadClass('User');
		
		if(isset($_GET['active'], $_POST['answer'])) {
			$active = $_GET['active'];
			if(Polls::check($active) != '')
			{
				MySQL::query("
						UPDATE
						".Config::mysql_prefix."pollstatus
						SET
						pollstatusAnswer = '".$_POST['answer']."'
						WHERE
						pollstatus_pollID = ".$active."
						AND
						pollstatus_userID = ".User::$id."
						", __FILE__, __LINE__);
			}
			else
			{
				MySQL::query("
						INSERT INTO
						".Config::mysql_prefix."pollstatus
						(pollstatus_pollID,
						pollstatus_userID,
						pollstatusAnswer)
						VALUES
						(".$active.",
						".User::$id.",
						'".$_POST['answer']."')
						", __FILE__, __LINE__);
				
				
				MySQL::query("
						UPDATE
						".Config::mysql_prefix."poll
						SET
						pollAnswerCount = pollAnswerCount + 1
						WHERE
						pollID = ".$active."
						", __FILE__, __LINE__);
			}

			
			self::$answered = true;
			self::displayOverview();
		}
		else
			Template::bakeError("Fehler beim Speichern der Daten.");
		

		
// 		$tmpl = new Template;
// 		$tmpl->title = 'Umfrage beantworten';
		
		//$tmpl->content .= '<div class="green">Stimme abgegeben.</div>';

		
		
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