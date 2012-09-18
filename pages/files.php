<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class FilesPage {
	
	/**
	 * Verfügbare Unterseiten
	 * Schlüssel: $_GET['sp']
	 * Wert: Name der Funktion, die aufgerufen werden soll
	 */
	public static $actions = array(
		'' => 'displayOverview',
		'toggle' => 'saveToggle',
		'edit' => 'editFile',
		'delete' => 'deleteFile',
		'move' => 'moveFile',
		'download' => 'downloadFile',
		'search' => 'doSearch',
		'resetnew' => 'resetNewFiles'
	);
	
	
	
	public static $toggle = false;
	
	
	/*
	 * Seiten und Aktionen
	 */
	
	/**
	 * Dateiübersicht anzeigen
	 */
	public static function displayOverview() {
		
		// Ordner laden
		General::loadClass('Folder');
		Folder::getall();
		
		// Dateien laden
		General::loadClass('Files');
		Files::load(self::getToggle());
		
		
		$tmpl = new Template;
		
		// neue Dateien seit dem letzten Besuch
		if(isset($_SESSION[Config::session_prefix.'lastvisit']) AND $_SESSION[Config::session_prefix.'lastvisit']) {
			
			$lastvisit = (int)$_SESSION[Config::session_prefix.'lastvisit'];
			
			$conds = array(
				"filesDate > ".$lastvisit
			);
			
			if(User::$login) {
				$conds[] = "files_userID != ".User::$id;
			}
			
			$query = MySQL::query("
				SELECT
					".Config::mysql_prefix."files.*,
					userName
				FROM
					".Config::mysql_prefix."files
					LEFT JOIN ".Config::mysql_prefix."user
						ON userID = files_userID
				WHERE
					".implode(" AND ", $conds)."
				ORDER BY
					filesDate DESC
				LIMIT 250
			", __FILE__, __LINE__);
			
			$treffer = MySQL::rows($query);
			
			if($treffer) {
				$tmpl->content = '
					<div class="whitebox" id="newfiles">
						<div class="whitebox_icon">
							<a href="index.php?p=files&amp;sp=resetnew" class="ajax" title="ausblenden">
								<img src="img/loeschen.png" alt="ausblenden" class="icon hover" />
							</a>
						</div>
						<p>'.$treffer.' neue Datei'.($treffer == 1 ? '' : 'en').' seit deinem letzten Besuch:</p>';
				
				while($f = mysql_fetch_object($query)) {
					
					$path = Folder::getFolderPath($f->files_folderID, false, true);
					
					$tmpl->content .= self::getFileView(
						$f,
						$path,
						Folder::getFolderPath($f->files_folderID, true).$f->filesName
					);
					
				}
				
				$tmpl->content .= '
					</div>
				';
			}
			
		}
		
		
		
		$tmpl->content .= '
	<div id="contenttop">';
		
		if(User::$login) {
			$tmpl->content .= '
		<div id="contenttop_upload">
			<a class="button" href="index.php?p=upload">
				<img src="img/hochladen.png" alt="" />
				Hochladen
			</a>
		</div>';
		}
		
		$tmpl->content .= '
		<div id="contenttop_search">
			<form action="index.php?p=files&amp;sp=search" method="post" class="ajaxform" data-target="#filecontent">
			
				<input type="text" class="text iconinput" name="search" placeholder="Dateien suchen" /><input type="image" src="img/suchen.png" class="input_icon" />
				
			</form>
		</div>
		
		<div id="contenttop_select">
			<form action="index.php" method="get">
				<input type="hidden" name="p" value="files" />
				Anzeigen:
				
				<select id="select_topfolder" name="top" size="1">
					<option value="0">- alle -</option>';
		
		// Wurzel-Ordner anzeigen
		$topfolder = User::getTopFolder();
		
		if(isset($_GET['top'])) {
			$topfolder = (int)$_GET['top'];
			$_SESSION[Config::session_prefix.'topfolder'] = $topfolder;
		}
		
		
		
		$rootfolders = Folder::getchildren(0);
		
		foreach($rootfolders as $folder) {
			$tmpl->content .= '<option value="'.$folder->folderID.'"'.($folder->folderID == $topfolder ? ' selected' : '').'>'.h($folder->folderName).'</option>';
		}
		
		$tmpl->content .= '
			</select>
			
			<noscript>
				<input type="submit" class="button" value="los" />
			</noscript>
			
			</form>
		</div>
	</div>
	
	<div id="filecontent">
		
		'.self::getFolderView($topfolder).'
		
	</div>';
		
		$tmpl->output();
		
	}
	
	
	/**
	 * Beim Auf- und Zuklappen eines Ordners Status speichern
	 * Beim erstmaligen Aufklappen eines Ordners wird dieser geladen
	 */
	public static function saveToggle() {
		
		General::loadClass('Folder');
		
		// Daten vollständig
		if(!isset($_POST['id'], $_POST['toggle'])) {
			Template::bakeError('Daten unvollständig');
		}
		
		$id = (int)$_POST['id'];
		
		$tmpl = new Template;
		
		
		$toggle = array();
		
		if(isset($_SESSION[Config::session_prefix.'toggle'])) {
			$toggle = $_SESSION[Config::session_prefix.'toggle'];
		}
		
		if(self::$toggle === false) {
			self::$toggle = $toggle;
		}
		
		
		if($_POST['toggle'] != -1) {
			// aufgeklappte Ordner speichern
			if(!$_POST['toggle']) {
				foreach($toggle as $key=>$val) {
					if($val == $id) {
						unset($toggle[$key]);
					}
				}
			}
			else {
				$toggle[] = $id;
				$toggle = array_unique(array_merge(
					$toggle,
					Folder::getparents($id)
				));
			}
			
			$_SESSION[Config::session_prefix.'toggle'] = $toggle;
			self::$toggle = $toggle;
		}
		
		// Ordner erstmalig aufklappen
		if(isset($_GET['load'])) {
			General::loadClass('Files');
			Files::load(self::$toggle);
			
			$tmpl->content = self::getFolderView($id);
		}
		
		// Drag & Drop-Funktionalität erhalten
		$tmpl->script = '
		ajaxController.addDragDrop("#folder'.$id.'");
		';
		
		$tmpl->output();
	}
	
	/**
	 * Datei bearbeiten
	 */
	public static function editFile() {
		
		if(!isset($_GET['id'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		$id = (int)$_GET['id'];
		
		if(!User::$login) {
			Template::bakeError('Du bist nicht eingeloggt!');
		}
		
		General::loadClass('Folder');
		General::loadClass('Files');
		
		$file = Files::get($id);
		
		if(!$file) {
			Template::bakeError('Die Datei wurde nicht gefunden!');
		}
		
		if(!User::$admin AND $file->files_userID != User::$id) {
			Template::bakeError('Du hast keine Berechtigung!');
		}
		
		$folder = $file->files_folderID;
		
		
		
		$tmpl = new Template;
		$tmpl->title = 'Datei bearbeiten';
		
		$tmpl->content = '
		
		<div class="center">
		
			<h1>Datei bearbeiten</h1>';
		
		if(isset($_FILES['file'])) {
			
			$path = Folder::getFolderPath($folder);
			$destination = './files/'.$path.utf8_decode($file->filesPath);
			
			// Fehlerbehandlung
			if($_FILES['file']['error'] > 0) {
				if($_FILES['file']['error'] != 4) {
					$tmpl->content .= '
						<div class="error">'.h($_FILES['file']['name']).': Fehler aufgetreten!</div>
						<br />';
				}
			}
			else {
				// speichern und eintragen
				move_uploaded_file($_FILES['file']['tmp_name'], $destination);
				
				Files::createThumbnail($id, $file->filesPath, $destination);
				
				MySQL::query("
					UPDATE
						".Config::mysql_prefix."files
					SET
						filesDate = ".time().",
						filesSize = ".(int)$_FILES['file']['size']."
					WHERE
						filesID = ".$id."
				", __FILE__, __LINE__);
				
				
				$tmpl->content .= '
					<div class="green">'.h($_FILES['file']['name']).' erfolgreich hochgeladen</div>
					<br />';
				
			}
		}
		
		// Name und Ordner ändern
		if(isset($_POST['name'], $_POST['folder'])) {
			
			$folder = (int)$_POST['folder'];
			
			$file->filesName = $_POST['name'];
			
			$path = Folder::getFolderPath($folder);
			$name_new = $file->filesPath;
			$name_new2 = $name_new;
			
			$destination = './files/'.$path.utf8_decode($name_new);
			
			$i = 1;
			
			if($folder != $file->files_folderID) {
				
				// Datei schon vorhanden
				while(file_exists($destination)) {
					
					if(strlen($name_new > 96)) {
						$name_new = substr($name_new, -96, 96);
						$i++;
					}
					
					$name_new2 = $i.$name_new;
					$i++;
					
					$destination = './files/'.$path.utf8_decode($name_new2);
				}
				
			}
			
			// Datei verschieben
			@rename(
				'./files/'.Folder::getFolderPath($file->files_folderID).utf8_decode($file->filesPath),
				$destination
			);
			
			// aktualisieren
			MySQL::query("
				UPDATE
					".Config::mysql_prefix."files
				SET
					filesName = '".MySQL::escape($_POST['name'])."',
					files_folderID = ".$folder.",
					filesPath = '".MySQL::escape($name_new2)."'
				WHERE
					filesID = ".$id."
			", __FILE__, __LINE__);
			
		}
		
		$tmpl->content .= '
			
			<form action="index.php?p=files&amp;sp=edit&amp;id='.$id.'" method="post" enctype="multipart/form-data">
				
			<table class="formtable center">
			<tr>
				<td>Ordner</td>
				<td>
					<select name="folder" size="1">
					'.Folder::dropdown($folder).'
					</select>
				</td>
			</tr>
			<tr>
				<td>Angezeigter Name</td>
				<td><input type="text" class="text" name="name" value="'.h($file->filesName).'" maxlength="100" style="width:100%" /></td>
			</tr>
			<tr>
				<td>Datei neu hochladen <span class="italic">(optional)</span></td>
				<td><input type="file" name="file" /></td>
			</tr>
			<tr>
				<td class="center topspace" colspan="2">
					<input type="submit" class="button wide" value="Speichern" />
				</td>
			</tr>
			</table>
			
			</form>';
		
		if(count($_POST)) {
			$tmpl->content .= '
			<br />
			<div>Die &Auml;nderungen wurden gespeichert.</div>
			
			<script>
			document.location.href = "index.php";
			</script>
			';
		}
		
		$tmpl->content .= '
			
		</div>
		';
		
		$tmpl->output();
	}
	
	
	/**
	 * Datei löschen
	 */
	public static function deleteFile() {
		
		// Daten validieren
		if(!isset($_GET['id'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		$id = (int)$_GET['id'];
		
		
		if(!User::$login) {
			Template::bakeError('Du bist nicht eingeloggt!');
		}
		
		General::loadClass('Files');
		General::loadClass('Folder');
		
		$file = Files::get($id);
		
		if($file) {
			
			// Berechtigung
			if(!User::$admin AND $file->files_userID != User::$id) {
				Template::bakeError('Du hast keine Berechtigung!');
			}
			
			$path = utf8_decode('./files/'.Folder::getFolderPath($file->files_folderID).$file->filesPath);
			
			@unlink($path);
			@unlink('./thumbnails/'.$id.'.jpg');
			
			MySQL::query("
				DELETE FROM
					".Config::mysql_prefix."files
				WHERE
					filesID = '".$id."'
			", __FILE__, __LINE__);
			
		}
		
		// mit Ajax die Datei entfernen
		if(isset($_GET['ajax'])) {
			$tmpl = new Template;
			$tmpl->script = '$("#file'.$id.'").remove();';
			$tmpl->output();
		}
		
		// noscript-Variante
		else {
			self::displayOverview();
		}
	}
	
	
	/**
	 * Datei verschieben
	 */
	public static function moveFile() {
		
		if(!isset($_POST['id'], $_POST['target'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		if(!User::$login) {
			Template::bakeError('Du bist nicht eingeloggt!');
		}
		
		$id = (int)$_POST['id'];
		$target = (int)$_POST['target'];
		
		General::loadClass('Folder');
		General::loadClass('Files');
		
		
		$file = Files::get($id);
		
		// Berechtigung
		if(!User::$admin AND $file->files_userID != User::$id) {
			Template::bakeError('Du hast keine Berechtigung!');
		}
		
		if($target != $file->files_folderID) {
			
			$path = Folder::getFolderPath($target);
			$name_new = $file->filesPath;
			$name_new2 = $name_new;
			
			$destination = './files/'.$path.$name_new;
			
			$i = 1;
			
			// Datei schon vorhanden
			while(file_exists($destination)) {
				
				if(strlen($name_new > 96)) {
					$name_new = substr($name_new, -96, 96);
					$i++;
				}
				
				$name_new2 = $i.$name_new;
				$i++;
				
				$destination = './files/'.$path.$name_new2;
			}
			
			MySQL::query("
				UPDATE
					".Config::mysql_prefix."files
				SET
					filesPath = '".MySQL::escape($name_new2)."',
					files_folderID = ".$target."
				WHERE
					filesID = ".$id."
			", __FILE__, __LINE__);
			
			
			// Datei verschieben
			@rename(
				utf8_decode('./files/'.Folder::getFolderPath($file->files_folderID).$file->filesPath),
				$destination
			);
		
		}
		
		$tmpl = new Template;
		
		$tmpl->script = '
		if($("#folder'.$target.'").data("loaded")) {
			ajaxController.call("index.php?p=files&sp=toggle&load", $("#folder'.$target.'"), {"id":'.$target.', "toggle":-1}, false);
		}
		';
		
		$tmpl->output();
	}
	
	
	/**
	 * Datei herunterladen
	 */
	public static function downloadFile() {
		
		// Daten validieren
		if(!isset($_GET['id'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		$id = (int)$_GET['id'];
		
		
		General::loadClass('Files');
		General::loadClass('Folder');
		
		$file = Files::get($id);
		
		if(!$file) {
			Template::bakeError('Die Datei wurde nicht gefunden!');
		}
		
		// herunterladen
		$path = utf8_decode('./files/'.Folder::getFolderPath($file->files_folderID).$file->filesPath);
		
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$file->filesName.'"');
		echo file_get_contents($path);
	}
	
	
	/**
	 * Suchfunktion
	 */
	public static function doSearch() {
		
		if(!isset($_POST['search'])) {
			Template::bakeError('Daten unvollständig');
		}
		
		General::loadClass('Folder');
		General::loadClass('Files');
		
		$tmpl = new Template;
		
		$topfolder = User::getTopFolder();
		
		// alle zu durchsuchenden ermitteln
		$folders = Folder::getchildren_ids($topfolder, true);
		$folders[] = $topfolder;
		
		$conds = array(
			"files_folderID IN(".implode(", ", $folders).")"
		);
		
		// Suchfilter: alle Wörter kommt im angezeigten Namen vor
		$search = explode(" ", $_POST['search']);
		
		$searchfilter = array();
		
		foreach($search as $s) {
			if($s != '') {
				$searchfilter[] = "filesName LIKE '%".MySQL::escape(MySQL::escape($s))."%'";
			}
		}
		
		if(count($searchfilter)) {
			$conds[] = "(".implode(" AND ", $searchfilter).")";
		}
		
		
		$query = MySQL::query("
			SELECT
				".Config::mysql_prefix."files.*,
				userName
			FROM
				".Config::mysql_prefix."files
				LEFT JOIN ".Config::mysql_prefix."user
					ON userID = files_userID
			WHERE
				".implode(" AND ", $conds)."
			ORDER BY
				filesDate DESC
			LIMIT 250
		", __FILE__, __LINE__);
		
		$treffer = MySQL::rows($query);
		
		if($treffer == 0) {
			$tmpl->content = '
				<br />
				<div class="center">Die Suche lieferte keine Treffer.</div>
			';
		}
		else {
			$tmpl->content = '
				<p>Die Suche lieferte '.$treffer.' Treffer:</p>
				<div class="whitebox">';
			
			while($f = mysql_fetch_object($query)) {
				
				$path = Folder::getFolderPath($f->files_folderID, false, true);
				
				$tmpl->content .= self::getFileView(
					$f,
					$path,
					Folder::getFolderPath($f->files_folderID, true).$f->filesName
				);
				
			}
			
			$tmpl->content .= '
				</div>
			';
		}
		
		$tmpl->output();
	}
	
	
	/**
	 * Anzeige neuer Dateien seit dem letzten Besuch ausblenden
	 */
	public static function resetNewFiles() {
		
		// letzten Besuch auf den jetzigen Zeitpunkt setzen
		if(isset($_SESSION[Config::session_prefix.'lastvisit'])) {
			$_SESSION[Config::session_prefix.'lastvisit'] = time();
		}
		
		// Fenster ausblenden
		if(isset($_GET['ajax'])) {
			$tmpl = new Template;
			$tmpl->script = '$("#newfiles").fadeOut(400);';
			$tmpl->output();
		}
		
		// noscript: Übersicht anzeigen
		else {
			self::displayOverview();
		}
	}
	
	
	
	/*
	 * Hilfsfunktionen
	 */
	
	
	
	/**
	 * Ordnerinhalt generieren
	 * @param int $id Ordner-ID
	 */
	public static function getFolderView($id) {
		
		$content = '';
		
		// aufgeklappte Ordner ermitteln
		self::getToggle();
		
		// Unterordner durchgehen
		$folders = Folder::getchildren($id);
		
		foreach($folders as $f) {
			
			$toggle = in_array($f->folderID, self::$toggle);
			
			$content .= '
		<div class="folder" data-id="'.$f->folderID.'">
			<div class="file_left">
				<a href="index.php?p=files&amp;toggle='.$f->folderID.'" class="folder_toggle" data-id="'.$f->folderID.'" data-toggle="'.($toggle ? '1' : '0').'">
					<img src="img/ordner'.($toggle ? '-offen' : '').'.png" alt="" class="icon" />
					'.h($f->folderName).'
				</a>
			</div>
		</div>
		
		<div id="folder'.$f->folderID.'" class="folder_content" data-loaded="'.($toggle ? '1' : '0').'"'.($toggle ? '' : ' style="display:none"').'>
			';
			
			// Ordnerinhalt anzeigen, wenn aufgeklappt
			if($toggle) {
				$content .= self::getFolderView($f->folderID);
			}
			
			$content .= '
		</div>';
		}
		
		$path = Folder::getFolderPath($id, false, true);
		
		// Dateien im Ordner
		General::loadClass('Files');
		$files = Files::getall($id);
		
		foreach($files as $f) {
			$content .= self::getFileView($f, $path, $f->filesName);
		}
		
		if(!count($folders) AND !count($files)) {
			$content .= '
		<div class="emptyfolder italic">
			Der Odner ist leer.
		</div>';
		}
		
		
		return $content;
	}
	
	
	/**
	 * Datei-Anzeige erzeugen
	 * @param object $f Datei-Datensatz
	 * @param $path String Ordner-Pfad
	 * @param $name Name, der angezeigt werden soll
	 */
	public static function getFileView($f, $path, $name) {
		
		if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$path_url = $path.rawurlencode($f->filesPath);
		}
		else {
			$path_url = $path.rawurlencode(utf8_decode($f->filesPath));
		}
		
		$content = '
		<div class="file';
		
		if($f->filesThumbnail) {
			$content .= ' thumbnail';
		}
		
		$content .= '" id="file'.$f->filesID.'" data-id="'.$f->filesID.'"';
		
		if($f->filesThumbnail) {
			
			$content .= ' data-thumbnail="';
			
			// gespeicherter Thumbnail
			if($f->filesThumbnail == 1) {
				$content .= 'thumbnails/'.$f->filesID.'.jpg';
			}
			// Original-Bild
			else {
				$content .= 'files/'.$path_url;
			}
			
			$content .= '"';
		}
		
		$content .= '>
			<div class="file_left">
				<a href="files/'.$path_url.'" target="_blank" data-id="'.$f->filesID.'"';
		
		if(User::$admin OR (User::$login AND User::$id == $f->files_userID)) {
			$content .= ' class="draggable"';
		}
		
		$content .= '>
					<img src="img/'.Files::getIcon($f->filesPath).'" alt="" class="icon" />
					'.h($name).'
				</a>
			</div>
			<div class="file_right">
				<div class="file_size">
					'.Files::formatSize($f->filesSize).'
				</div>
				
				<div class="file_uploader">
					'.($f->userName ? h($f->userName) : '').'
				</div>
				
				<div class="file_datum">
					'.Files::formatDate($f->filesDate).'
				</div>
				
				<div class="file_icons">';
			
			// bearbeiten und löschen
			if(User::$admin OR (User::$login AND User::$id == $f->files_userID)) {
				$content .= '
					<a href="index.php?p=files&amp;sp=edit&amp;id='.$f->filesID.'" data-label="bearbeiten">
						<img src="img/bearbeiten.png" class="icon hover" alt="bearbeiten" title="bearbeiten / neu hochladen" />
					</a>
					<a href="index.php?p=files&amp;sp=delete&amp;id='.$f->filesID.'" class="ajax" data-confirm="Willst du die Datei wirklich l&ouml;schen?" data-label="l&ouml;schen">
						<img src="img/loeschen.png" class="icon hover" alt="l&ouml;schen" title="l&ouml;schen" />
					</a>';
			}
			
			$content .= '
					<div class="file_iconspacer"></div>
					
					<a href="files/'.$path.$f->filesPath.'" target="_blank" data-label="ansehen">
						<img src="img/ansehen.png" class="icon hover" alt="ansehen" title="ansehen" />
					</a>
					<a href="index.php?p=files&amp;sp=download&amp;id='.$f->filesID.'" data-label="speichern">
						<img src="img/download.png" class="icon hover" alt="speichern" title="speichern" />
					</a>
				</div>
			</div>
		</div>';
		
		return $content;
	}
	
	
	/**
	 * Aufgeklappte Ordner ermitteln
	 * entweder über die Adresse übergeben oder in der Session gespeichert
	 */
	public static function getToggle() {
		if(self::$toggle !== false) {
			return self::$toggle;
		}
		
		self::$toggle = array();
		
		// über die Session
		if(isset($_SESSION[Config::session_prefix.'toggle'])) {
			self::$toggle = $_SESSION[Config::session_prefix.'toggle'];
		}
		
		// über die Adresse
		if(isset($_GET['toggle'])) {
			self::$toggle = array_unique(array_merge(
				self::$toggle,
				array($_GET['toggle']),
				Folder::getparents($_GET['toggle'])
			));
			
			$_SESSION[Config::session_prefix.'toggle'] = self::$toggle;
		}
		
		return self::$toggle;
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