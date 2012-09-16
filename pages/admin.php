<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class AdminPage {
	
	/**
	 * Verfügbare Unterseiten
	 * Schlüssel: $_GET['sp']
	 * Wert: Name der Funktion, die aufgerufen werden soll
	 */
	public static $actions = array(
		'' => 'displayAdminPage',
		'sync' => 'syncFileSystem',
		'refresh_folders' => 'displayFolders'
	);
	
	
	/**
	 * Administrationsoberfläche anzeigen
	 */
	public static function displayAdminPage() {
		
		General::loadClass('Folder');
		
		$tmpl = new Template;
		
		$tmpl->title = 'Administration';
		
		$tmpl->content = '
		
		<h1>Administration</h1>
		
		<h2>Ordnerverwaltung</h2>
		
		<p>
			<a class="button ajax" href="index.php?p=admin&amp;sp=sync" data-target="#sync_result">
				<img src="img/synchronisieren.png" alt="" />
				mit dem Dateisystem synchronisieren
			</a>
			&nbsp;
			<span id="sync_result"></span>
		</p>
		
		<div id="folder_content">
			'.self::getFolders().'
		</div>
		
		<br />
		
		<h2>Benutzerverwaltung</h2>
		
		<p>noch nicht implementiert</p>
		';
		
		$tmpl->output();
		
	}
	
	
	/**
	 * Baumansicht aller Ordner ausgeben
	 */
	public static function displayFolders() {
		
		$tmpl = new Template;
		
		$tmpl->content = self::getFolders();
		
		$tmpl->output();
		
	}
	
	
	/**
	 * Baumansicht aller Ordner erzeugen
	 */
	public static function getFolders() {
		
		General::loadClass('Folder');
		
		$content = '';
		
		$folders = Folder::getall();
		
		foreach($folders as $row) {
			$content .= '
			
			<div class="folder" style="margin-left:'.($row->depth*20).'px">
				<img src="img/ordner.png" alt="" class="icon" />
				'.h($row->folderName).'
			</div>';
		}
		
		return $content;
	}
	
	
	/**
	 * Dateien und Ordner in der Datenbank mit dem Dateisystem synchronisieren
	 */
	public static function syncFileSystem() {
		
		General::loadClass('Folder');
		General::loadClass('Files');
		
		// alle bekannten Dateien und Ordner laden
		Folder::getall_raw();
		Files::getall();
		
		
		// Dateisystem durchgehen
		$stats = self::syncFolder(0);
		
		$folder_added = $stats[0];
		$folder_deleted = $stats[1];
		$files_added = $stats[2];
		$files_deleted = $stats[3];
		
		// Ausgabe
		$tmpl = new Template;
		
		$out = array();
		
		if($folder_added) {
			$out[] = $folder_added.' Ordner hinzugef&uuml;gt';
		}
		if($folder_deleted) {
			$out[] = $folder_deleted.' Ordner entfernt';
		}
		
		if($files_added) {
			$out[] = $files_added.' Datei'.($files_added == 1 ? '' : 'en').' hinzugef&uuml;gt';
		}
		if($files_deleted) {
			$out[] = $files_deleted.' Datei'.($files_deleted == 1 ? '' : 'en').' entfernt';
		}
		
		if(!count($out)) {
			$tmpl->content = 'Der Dateimanager ist bereits mit dem Dateisystem synchron.';
		}
		else {
			$tmpl->content = implode(', ', $out);
			
			// Ordneransicht neu laden
			$tmpl->script = 'ajaxController.call("index.php?p=admin&sp=refresh_folders", $("#folder_content"), false, true)';
		}
		
		$tmpl->output();
		
	}
	
	/**
	 * Dateien und Unterordner innerhalb eines Ordners synchronisieren
	 * @param int $id Ordner-ID
	 * @return array(hinzugefügte Unterordner, entfernte Unterordner, hinzugefügte Dateien, entfernte Dateien)
	 */
	public static function syncFolder($id) {
		
		$id = (int)$id;
		
		General::loadClass('Folder');
		General::loadClass('Files');
		
		$folder_added = 0;
		$folder_deleted = 0;
		$files_added = 0;
		$files_deleted = 0;
		
		$subfolders = Folder::getchildren($id);
		$files = Files::getall($id);
		
		$path = './files/'.Folder::getFolderPath($id);
		
		// Dateisystem durchgehen
		if($dir = @opendir($path)) {
			
			while($file = readdir($dir)) {
				
				$file_utf = utf8_encode($file);
				
				if($file != '.' AND $file != '..' AND $file != 'index.html') {
					
					// Ordner
					if(is_dir($path.$file)) {
						
						// Überprüfen, ob der Ordner bereits eingetragen ist
						foreach($subfolders as $key=>$f) {
							
							if($f->folderPath == $file_utf) {
								unset($subfolders[$key]);
								
								// rekursiv weitergehen
								$add = self::syncFolder($f->folderID);
								
								$folder_added += $add[0];
								$folder_deleted += $add[1];
								$files_added += $add[2];
								$files_deleted += $add[3];
								
								continue 2;
							}
							
						}
						
						// Unterordner eintragen
						MySQL::query("
							INSERT INTO
								".Config::mysql_prefix."folder
							SET
								folderName = '".MySQL::escape($file_utf)."',
								folderPath = '".MySQL::escape($file_utf)."',
								folderParent = ".$id."
						", __FILE__, __LINE__);
						
						$f = new StdClass;
						$f->folderID = MySQL::id();
						$f->folderName = $file_utf;
						$f->folderPath = $file_utf;
						$f->folderParent = $id;
						
						Folder::$raw[] = $f;
						
						if(Folder::$folder) {
							Folder::$folder = false;
						}
						
						$folder_added++;
						
						// rekursiv weitergehen
						$add = self::syncFolder(MySQL::id());
						
						$folder_added += $add[0];
						$files_added += $add[2];
						
					}
					
					// Datei
					else {
						
						// überprüfen, ob die Datei bereits eingetragen ist
						foreach($files as $key=>$f) {
							
							if($f->filesPath == $file_utf) {
								unset($files[$key]);
								continue 2;
							}
							
						}
						
						// Datei eintragen
						$size = @filesize($path.$file);
						$size = (int)$size;
						
						MySQL::query("
							INSERT INTO
								".Config::mysql_prefix."files
							SET
								filesName = '".MySQL::escape($file_utf)."',
								filesPath = '".MySQL::escape($file_utf)."',
								files_folderID = ".$id.",
								filesDate = ".time().",
								filesSize = ".$size."
						", __FILE__, __LINE__);
						
						$f = new StdClass;
						$f->filesID = MySQL::id();
						$f->filesName = $file_utf;
						$f->filesPath = $file_utf;
						$f->files_folderID = $id;
						$f->filesDate = time();
						$f->filesSize = $size;
						$f->filesThumbnail = 0;
						$f->files_userID = 0;
						
						Files::$files[] = $f;
						
						Files::createThumbnail($f->filesID, $file_utf, $path.$file);
						
						$files_added++;
						
					}
				}
			}
		}
		
		// nicht mehr existente Ordner und Dateien löschen
		foreach($subfolders as $f) {
			
			MySQL::query("
				DELETE FROM
					".Config::mysql_prefix."folder
				WHERE
					folderID = ".$f->folderID."
			", __FILE__, __LINE__);
			
			$folder_deleted++;
			
		}
		
		foreach($files as $f) {
			
			MySQL::query("
				DELETE FROM
					".Config::mysql_prefix."files
				WHERE
					filesID = ".$f->filesID."
			", __FILE__, __LINE__);
			
			@unlink('./thumbnails/'.$f->filesID.'.jpg');
			
			$files_deleted++;
			
		}
		
		
		// Statistik zurückgeben
		return array(
			$folder_added,
			$folder_deleted,
			$files_added,
			$files_deleted
		);
	}
	
	
	
	
	
	/**
	 * Seite auswählen, die geladen werden soll
	 */
	public static function dispatch() {
		
		// nicht eingeloggt
		if(!User::$login) {
			Template::bakeError('Du bist nicht eingeloggt!');
		}
		
		if(!User::$admin) {
			Template::bakeError('Du hast keine Berechtigung!');
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