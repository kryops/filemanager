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
		'refresh_folders' => 'displayFolders',
		'folder_edit' => 'displayFolderEditPage',
		'folder_edit_send' => 'editFolder',
		'folder_delete' => 'deleteFolder',
		'folder_add' => 'displayFolderAddPage',
		'folder_add_send' => 'addFolder'
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
			<a class="button ajax" href="index.php?p=admin&amp;sp=sync">
				<img src="img/synchronisieren.png" alt="" />
				mit dem Dateisystem synchronisieren
			</a>
		</p>
		
		<br />
		
		<p>
			<a class="button" href="index.php?p=admin&amp;sp=folder_add">
				<img src="img/ordner-hinzufuegen.png" alt="" />
				Ordner erstellen
			</a>
		</p>
		
		<br />
		
		<div id="folder_content">
			'.self::getFolders().'
		</div>
		
		<br /><br />
		
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
			
			<div class="folder" style="margin-left:'.($row->depth*15).'px">
				<img src="img/ordner-offen.png" alt="" class="icon" />
				'.h($row->folderName).'
				&nbsp;
				<a href="index.php?p=admin&amp;sp=folder_edit&amp;id='.$row->folderID.'" title="'.h($row->folderName).' bearbeiten">
					<img src="img/bearbeiten.png" alt="bearbeiten" class="icon hover" />
				</a>
				<a href="index.php?p=admin&amp;sp=folder_delete&amp;id='.$row->folderID.'" title="'.h($row->folderName).' l&ouml;schen" class="ajax" data-confirm="Soll der Ordner mitsamt allen Dateien und Unterordnern wirklich unwiderruflich gel&ouml;scht werden?">
					<img src="img/loeschen.png" alt="l&ouml;schen" class="icon hover" />
				</a>
			</div>';
		}
		
		return $content;
	}
	
	
	/**
	 * Dateien und Ordner in der Datenbank mit dem Dateisystem synchronisieren
	 */
	public static function syncFileSystem() {
		
		@set_time_limit(300);
		
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
			
			$folder_files = Files::getall_ids(Folder::getchildren_ids($f->folderID, true, true));
			
			if(count($folder_files)) {
				
				MySQL::query("
					DELETE FROM
						".Config::mysql_prefix."files
					WHERE
						filesID IN(".implode(",", $folder_files).")
				", __FILE__, __LINE__);
				
				$files_deleted += count($folder_files);
				
			}
			
			
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
	 * Ordner löschen
	 */
	public static function deleteFolder() {
		
		if(!isset($_GET['id'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		$id = (int)$_GET['id'];
		
		General::loadClass('Folder');
		
		Folder::delete($id);
		
		
		// Ausgabe
		$tmpl = new Template;
		
		$tmpl->content = 'Der Ordner wurde gelöscht.';
		
		$tmpl->script = 'ajaxController.call("index.php?p=admin&sp=refresh_folders", $("#folder_content"), false, true)';
		
		$tmpl->output();
		
	}
	
	
	/**
	 * Formular zum Bearbeiten eines Ordners anzeigen
	 */
	public static function displayFolderEditPage() {
		
		if(!isset($_GET['id'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		$id = (int)$_GET['id'];
		
		General::loadClass('Folder');
		
		$f = Folder::get($id);
		
		if(!$f) {
			Template::bakeError('Der Ordner existiert nicht!');
		}
		
		$tmpl = new Template;
		$tmpl->title = 'Ordner bearbeiten';
		
		$tmpl->content = '
		
		<div class="center">
		
			<h1>Ordner bearbeiten</h1>
			
			<form action="index.php?p=admin&amp;sp=folder_edit_send&amp;id='.$id.'" method="post" class="ajaxform" data-target="#form_result">
			
			<table class="formtable center">
			<tr>
				<td>Ort</td>
				<td>
					<select name="parent" size="1">
					'.Folder::dropdown($f->folderParent, Folder::getchildren_ids($f->folderID, true, true)).'
					</select>
				</td>
			</tr>
			<tr>
				<td>Angezeigter Name</td>
				<td><input type="text" class="text" name="name" value="'.h($f->folderName).'" maxlength="100" required /></td>
			</tr>
			<tr>
				<td>Name im Dateisystem</td>
				<td><input type="text" class="text" name="path" value="'.h($f->folderPath).'" maxlength="100" required /></td>
			</tr>
			<tr>
				<td class="center topspace" colspan="2">
					<input type="submit" class="button wide" value="Speichern" />
				</td>
			</tr>
			</table>
			
			</form>
		
		<div class="center" id="form_result"></div>
		
		</div>
		';
		
		$tmpl->output();
		
	}
	
	
	/**
	 * Ordner-Änderungen speichern 
	 */
	public static function editFolder() {
		
		// Validierung
		if(!isset($_GET['id'], $_POST['parent'], $_POST['name'], $_POST['path'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		$id = (int)$_GET['id'];
		$parent = (int)$_POST['parent'];
		
		if(trim($_POST['name']) == '') {
			Template::bakeError('Kein Name eingegeben!');
		}
		
		if(trim($_POST['path']) == '' OR $_POST['path'] == '.' OR $_POST['path'] == '..' OR strpos($_POST['path'], '/') !== false OR strpos($_POST['path'], '\\') !== false) {
			Template::bakeError('Ungültiger Pfad eingegeben!');
		}
		
		
		General::loadClass('Folder');
		
		$f = Folder::get($id);
		
		if(!$f) {
			Template::bakeError('Der Ordner existiert nicht!');
		}
		
		
		$source = './files/'.Folder::getFolderPath($f->folderParent);
		$destination = './files/'.Folder::getFolderPath($parent);
		
		// Existenz des Ziels überprüfen
		if($parent != $f->folderParent OR $_POST['path'] != $f->folderPath) {
			
			if(file_exists($destination.$_POST['path'])) {
				Template::bakeError('Ein Ordner mit diesem Namen existiert bereits im Zielverzeichnis!');
			}
			
		}
		
		// auf dem Dateisystem verschieben
		if(!@rename($source.$f->folderPath, $destination.$_POST['path'])) {
			Template::bakeError('Verschieben fehlgeschlagen! Ungültiger Name im Dateisystem?');
		}
		
		// speichern
		MySQL::query("
			UPDATE
				".Config::mysql_prefix."folder
			SET
				folderName = '".MySQL::escape($_POST['name'])."',
				folderPath = '".MySQL::escape($_POST['path'])."',
				folderParent = ".$parent."
			WHERE
				folderID = ".$id."
		", __FILE__, __LINE__);
		
		
		// Weiterleitung
		$tmpl = new Template;
		$tmpl->redirect('index.php?p=admin');
		
	}
	
	
	/**
	 * Formular zum Erstellen eines Ordners anzeigen
	 */
	public static function displayFolderAddPage() {
		
		General::loadClass('Folder');
		
		
		$tmpl = new Template;
		$tmpl->title = 'Ordner erstellen';
		
		$tmpl->content = '
		
		<div class="center">
		
			<h1>Ordner erstellen</h1>
			
			<form action="index.php?p=admin&amp;sp=folder_add_send" method="post" class="ajaxform" data-target="#form_result">
			
			<table class="formtable center">
			<tr>
				<td>Ort</td>
				<td>
					<select name="parent" size="1">
					'.Folder::dropdown().'
					</select>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" class="text" name="name" maxlength="96" required /></td>
			</tr>
			<tr>
				<td class="center topspace" colspan="2">
					<input type="submit" class="button wide" value="Speichern" />
				</td>
			</tr>
			</table>
			
			</form>
		
		<div class="center" id="form_result"></div>
		
		</div>
		';
		
		$tmpl->output();
		
	}
	
	/**
	 * Ordner erstellen
	 */
	public static function addFolder() {
		
		// Validierung
		if(!isset($_POST['parent'], $_POST['name'])) {
			Template::bakeError('Daten unvollständig!');
		}
		
		General::loadClass('Folder');
		
		$path = Folder::getFolderPath($_POST['parent']);
		
		$name_new = Folder::cleanFolderName($_POST['name']);
		$name_new2 = $name_new;
		
		$destination = './files/'.$path.$name_new;
		
		$i = 1;
		
		// Ordner schon vorhanden
		while(file_exists($destination)) {
			
			if(strlen($name_new > 96)) {
				$name_new = substr($name_new, -96, 96);
				$i++;
			}
			
			$name_new2 = $i.$name_new;
			$i++;
			
			$destination = './files/'.$path.$name_new2;
		}
		
		// erstellen
		if(!@mkdir($destination)) {
			Template::bakeError('Fehler beim Erstellen des Ordners! Schreibrechte vorhanden?');
		}
		
		MySQL::query("
			INSERT INTO
				".Config::mysql_prefix."folder
			SET
				folderName = '".MySQL::escape($_POST['name'])."',
				folderPath = '".MySQL::escape($name_new2)."',
				folderParent = ".(int)$_POST['parent']."
		", __FILE__, __LINE__);
		
		
		// Weiterleitung
		$tmpl = new Template;
		$tmpl->redirect('index.php?p=admin');
		
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