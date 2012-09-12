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
		'toggle' => 'saveToggle'
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
		
		$tmpl->content = '
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
			<form action="index.php?p=search" class="ajaxform" data-target="#filecontent">
			
				<input type="text" class="text iconinput" name="search" placeholder="Dateien suchen" /><input type="image" src="img/suchen.png" class="input_icon" />
				
			</form>
		</div>
		
		<div id="contenttop_select">
			<form action="index.php" method="get">
				<input type="hidden" name="p" value="files" />
				Anzeigen:
				
				<select id="select_topfolder" name="top" size="1">';
		
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
		
		
		// Ordner erstmalig aufklappen
		if(isset($_GET['load'])) {
			General::loadClass('Files');
			Files::load(self::$toggle);
			
			$tmpl->content = self::getFolderView($id);
		}
		
		
		$tmpl->output();
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
		<div class="folder">
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
		
		
		// Dateien im Ordner
		General::loadClass('Files');
		$files = Files::getall($id);
		
		foreach($files as $f) {
			$content .= '
		<div class="file">
			<div class="file_left">
				<a>
					<img src="img/document-pdf.png" alt="" class="icon" />
					'.h($f->filesName).'
				</a>
			</div>
			<div class="file_right">
				<div class="file_size">
					Größe
				</div>
				
				<div class="file_uploader">
					Uploader
				</div>
				
				<div class="file_datum">
					Datum
				</div>
				
				<div class="file_icons">
					<a>
						<img src="img/bearbeiten.png" class="icon hover" alt="bearbeiten" title="bearbeiten" />
					</a>
					<a>
						<img src="img/loeschen.png" class="icon hover" alt="l&ouml;schen" title="l&ouml;schen" />
					</a>
					
					<div class="file_iconspacer"></div>
					
					<a>
						<img src="img/ansehen.png" class="icon hover" alt="ansehen" title="ansehen" />
					</a>
					<a>
						<img src="img/download.png" class="icon hover" alt="speichern" title="speichern" />
					</a>
				</div>
			</div>
		</div>';
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