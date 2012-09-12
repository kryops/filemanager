<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class UploadPage {
	
	
	public static function displayUploadPage() {
		
		$folder = isset($_POST['folder']) ? (int)$_POST['folder'] : User::getTopFolder();
		
		General::loadClass('Folder');
		General::loadClass('Files');
		
		$tmpl = new Template;
		$tmpl->title = 'Hochladen';
		
		$tmpl->content = '
		
		<div class="center">
		
			<h1>Dateien hochladen</h1>';
		
		if(isset($_FILES['file'])) {
			
			$path = Folder::getFolderPath($folder);
			
			foreach($_FILES['file']['name'] as $key=>$name) {
				
				// Fehlerbehandlung
				if($_FILES['file']['error'][$key] > 0) {
					$tmpl->content .= '<div class="error">'.h($name).': Fehler aufgetreten!</div>';
				}
				else if(!Files::isAllowed($name)) {
					$tmpl->content .= '<div class="error">'.h($name).': Dateityp nicht erlaubt!</div>';
				}
				else {
					
					$name_new = Files::cleanFileName($name);
					
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
					
					// speichern und eintragen
					move_uploaded_file($_FILES['file']['tmp_name'][$key], $destination);
					
					MySQL::query("
						INSERT INTO
							".Config::mysql_prefix."files
						SET
							filesName = '".MySQL::escape($name)."',
							filesPath = '".MySQL::escape($name_new)."',	
							files_folderID = ".$folder.",
							files_userID = ".User::$id.",
							filesDate = ".time().",
							filesSize = ".(int)$_FILES['file']['size'][$key]."
					", __FILE__, __LINE__);
					
					
					$tmpl->content .= '<div class="green">'.h($name).' erfolgreich hochgeladen</div>';
					
				}
				
			}
			
			$tmpl->content .= '<br />';
		}
		
		$tmpl->content .= '
			
			<form action="index.php?p=upload" method="post" enctype="multipart/form-data">
				
			<table class="formtable center">
			<tr>
				<td>in Ordner</td>
				<td>
					<select name="folder" size="1">
					'.Folder::dropdown($folder).'
					</select>
				</td>
			</tr>
			<tr>
				<td>Datei(en)</td>
				<td><input type="file" name="file[]" multiple /></td>
			</tr>
			<tr>
				<td class="center topspace" colspan="2">
					<input type="submit" class="button wide" value="Hochladen" />
				</td>
			</tr>
			</table>
			
			</form>
			
		</div>
		';
		
		$tmpl->output();
		
	}
	
	
	
	/**
	 * Seite auswählen, die geladen werden soll
	 */
	public static function dispatch() {
		
		// nicht eingeloggt
		if(!User::$login) {
			Template::bakeError('Du bist nicht eingeloggt!');
		}
		
		
		self::displayUploadPage();
		
	}
	
}


?>