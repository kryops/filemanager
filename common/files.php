<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


class Files {
	
	/**
	 * Zuordnung von Dateitypen zu Icon-Grafiken
	 * Schlüssel = Dateiendung
	 * Wert = Grafik
	 */
	public static $file_icons = array(
		'default' => 'document.png',
		
		'txt' => 'document-text.png',
		'rtf' => 'document-text.png',
		
		'doc' => 'document-word.png',
		'docx' => 'document-word.png',
		'dot' => 'document-word.png',
		'xls' => 'document-excel-table.png',
		'xlsx' => 'document-excel-table.png',
		'ppt' => 'document-powerpoint.png',
		'pptx' => 'document-powerpoint.png',
		'pot' => 'document-powerpoint.png',
		
		'pdf' => 'document-pdf.png',
		
		'jpg' => 'document-image.png',
		'jpeg' => 'document-image.png',
		'bmp' => 'document-image.png',
		'gif' => 'document-image.png',
		'png' => 'document-image.png',
		'tif' => 'document-image.png',
		'xcf' => 'document-image.png',
		'tga' => 'document-image.png',
		'ico' => 'document-image.png',
		
		'mp3' => 'document-music.png',
		'wma' => 'document-music.png',
		'wav' => 'document-music.png',
		'ogg' => 'document-music.png',
		'aac' => 'document-music.png',
		'mpga' => 'document-music.png',
		'm2a' => 'document-music.png',
		'm4a' => 'document-music.png',
		
		'mkv' => 'document-film.png',
		'mpg' => 'document-film.png',
		'mp1' => 'document-film.png',
		'mp4' => 'document-film.png',
		'flv' => 'document-film.png',
		'mov' => 'document-film.png',
		'avi' => 'document-film.png',
		'wmv' => 'document-film.png',
		
		'psd' => 'document-photoshop.png',
		
		'htm' => 'document-code.png',
		'html' => 'document-code.png',
		'css' => 'document-code.png',
		'js' => 'document-code.png',
		'json' => 'document-code.png',
		'php' => 'document-code.png',
		'c' => 'document-code.png',
		'cpp' => 'document-code.png',
		'java' => 'document-code.png',
		'class' => 'document-code.png',
		'lisp' => 'document-code.png',
		'ini' => 'document-code.png',
		
		'zip' => 'document-archiv.png',
		'rar' => 'document-archiv.png',
		'7z' => 'document-archiv.png',
		'iso' => 'document-archiv.png',
		'cab' => 'document-archiv.png',
		'gz' => 'document-archiv.png',
		'tar' => 'document-archiv.png',
		'bzip' => 'document-archiv.png',
		'bz2' => 'document-archiv.png',
		'tgz' => 'document-archiv.png',
		
		'exe' => 'document-application.png',
		'jar' => 'document-jar.png'
	);
	
	
	
	public static $forbidden_files = array(
		'php',
		'htm',
		'html',
		'htaccess',
		'htusers',
		'htpasswd',
		'htgroups'
	);
	
	
	/**
	 * Array der Ordner, von welchen schon Dateien geladen wuden
	 */
	public static $files_loaded = array();
	
	
	public static $files = array();
	
	
	
	/**
	 * Den Datensatz einer Datei laden
	 * @param int $id
	 */
	public static function get($id) {
		
		return MySQL::querySingle("
			SELECT
				".Config::mysql_prefix."files.*,
				userName
			FROM
				".Config::mysql_prefix."files
				LEFT JOIN ".Config::mysql_prefix."user
					ON userID = files_userID
			WHERE
				filesID = ".(int)$id."
		", __FILE__, __LINE__);
		
	}
	
	
	/**
	 * Alle Dateien aus bestimmten Ordnern auslesen
	 * @param array|int|false $folders
	 * 		Ordner, aus welchen die Dateien geladen werden sollen
	 * 		false = alle Ordner
	 * @return array Datei-Datensätze
	 */
	public static function getall($folders=false) {
		
		if($folders !== false AND !is_array($folders)) {
			$folders = array($folders);
		}
		
		
		// Dateien laden
		self::load($folders);
		
		// nicht filtern
		if($folders === false) {
			return self::$files;
		}
		
		// nach Ordnern filtern
		$f = array();
		
		foreach(self::$files as $file) {
			if(in_array($file->files_folderID, $folders)) {
				$f[] = $file;
			}
		}
		
		return $f;
	}
	
	
	/**
	 * Alle Datei-IDs aus bestimmten Ordnern auslesen
	 * @param array|int|false $folders
	 * 		Ordner, aus welchen die Dateien geladen werden sollen
	 * 		false = alle Ordner
	 * @return array der IDs
	 */
	public static function getall_ids($folders=false) {
		
		if($folders !== false AND !is_array($folders)) {
			$folders = array($folders);
		}
		
		// Dateien laden
		self::load($folders);
		
		// nach Ordnern filtern
		$f = array();
		
		foreach(self::$files as $file) {
			if($folders === false OR in_array($file->files_folderID, $folders)) {
				$f[] = $file->filesID;
			}
		}
		
		return $f;
	}
	
	
	/**
	 * Dateien in bestimmten Ordnern laden
	 * @param array|false $folders
	 */
	public static function load($folders=false) {
		$conds = array();
		
		// alle Dateien geladen
		if(self::$files_loaded === true) {
			return;
		}
		
		// nur die Dateien laden, die noch nicht geladen wurden
		if($folders !== false) {
			
			foreach($folders as $key=>$val) {
				$folders[$key] = (int)$val;
			}
			
			
			$folders = array_diff(
				$folders,
				self::$files_loaded
			);
			
			self::$files_loaded = array_merge(
				self::$files_loaded,
				$folders
			);
			
			if(count($folders)) {
				$conds[] = "files_folderID IN(".implode(", ", $folders).")";
			}
			else {
				return;
			}
		}
		else {
			self::$files_loaded = true;
			
			// zurücksetzen, wenn alle geladen werden
			self::$files = array();
		}
		
		
		$sql = "
			SELECT
				".Config::mysql_prefix."files.*,
				userName
			FROM
				".Config::mysql_prefix."files
				LEFT JOIN ".Config::mysql_prefix."user
					ON userID = files_userID
		";
		
		if(count($conds)) {
			$sql .= "
			WHERE
				".implode(" AND ", $conds);
		}
		
		$query = MySQL::query($sql, __FILE__, __LINE__);
		
		while($row = MySQL::fetch($query)) {
			self::$files[] = $row;
		}
		
	}
	
	
	/**
	 * Dateityp ermitteln
	 * @param string $filename
	 * @return Datei-Endung
	 */
	public static function getFileType($filename) {
		$filename = explode('.', $filename);
		$filename = array_pop($filename);
		return strtolower($filename);
	}
	
	
	/**
	 * Ermittelt das Datei-Icon zu einem Dateinamen
	 * @param string $filename Dateiname
	 * @return string Dateiname der Icon-Grafik
	 */
	public static function getIcon($filename) {
		
		// keine Dateiendung
		if(strpos($filename, '.') === false) {
			return self::$file_icons['default'];
		}
		
		$filetype = self::getFileType($filename);
		
		
		// bekannte Dateiendung
		if(isset(self::$file_icons[$filetype])) {
			return self::$file_icons[$filetype];
		}
		
		// unbekannte Dateiendung
		return self::$file_icons['default'];
		
	}
	
	
	/**
	 * Dateigröße formatieren
	 * @param int $size Dateigröße in Bytes
	 * @return string formatierte Dateigröße
	 */
	public static function formatSize($size) {
		// > 1 GB
		if($size > 1073741824) {
			return round($size/1073741824, 1).'GB';
		}
		// > 10 MB
		else if($size > 10485760) {
			return round($size/1048576).'MB';
		}
		// > 1 MB
		else if($size > 1048576) {
			return round($size/1048576, 1).'MB';
		}
		// > 1 KB
		else if($size > 1024) {
			return round($size/1024).'KB';
		}
		// < 1 KB
		else {
			return $size.'B';
		}
	}
	
	/**
	 * Datum formatieren
	 * @param int $date Unix-Timestamp
	 * @return string formatiertes Datum
	 */
	public static function formatDate($date) {
		return strftime('%d.%m.%y', $date);
	}
	
	
	/**
	 * Ermitteln, ob ein Dateityp erlaubt ist
	 * @param string $filename
	 * @return boolean
	 */
	public static function isAllowed($filename) {
		return !in_array(
			self::getFileType($filename),
			self::$forbidden_files
		);
	}
	
	
	/**
	 * Dateiname Speicherungs-tauglich machen
	 * @param string $filename
	 * @return string bereinigter Dateiname
	 */
	public static function cleanFileName($filename) {
		
		$filename = str_replace(
			array(' ', 'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', '..'),
			array('_', 'ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '.'),
			$filename
		);
		
		$filename = preg_replace('/[^a-zA-Z0-9\.\-_]/Uis', '', $filename);
		
		// Länge auf 100 begrenzt
		if(strlen($filename) > 100) {
			$filename = substr($filename, -100, 100);
		}
		
		// keine Datei darf mit einem Punkt beginnen
		if(strpos($filename, '.') !== false) {
			$fn = explode('.', $filename);
			if($fn[0] == '') {
				$filename = '_'.$filename;
			}
		}
		// gar kein Dateiname mehr übrig
		else if(strlen($filename) == 0) {
			$filename = '_';
		}
		
		return $filename;
	}
	
	
	/**
	 * Thumbnail zu einer Datei erzeugen
	 * @param int $id
	 * @param string $filename gespeicherter Dateiname (filesPath) @default false
	 * @param stirng $path gesamter Dateipfad @default false
	 */
	public static function createThumbnail($id, $filename=false, $path=false) {
		
		// Dateitypen den bilderzeugenden Funktionen zuordnen
		$functions = array(
			'jpg' => 'imagecreatefromjpeg',
			'jpeg' => 'imagecreatefromjpeg',
			'png' => 'imagecreatefrompng',
			'gif' => 'imagecreatefromgif'
		);
		
		$maxwidth = 100;
		$maxheight = 100;
		
		
		$id = (int)$id;
		
		$thumbnail = 0;
		
		// Dateiname und Pfad nicht übergeben
		if($filename === false OR $path === false) {
			
			$file = Files::get($id);
			
			if(!$file) {
				return false;
			}
			
			$filename = $file->filesPath;
			
			$path = './files/'.Folder::getFolderPath($file->files_folderID).$filename;
			
		}
		
		$filetype = self::getFileType($filename);
		
		
		// Erlaubter Dateityp
		if(isset($functions[$filetype])) {
			if($size = @getimagesize($path)) {
				
				// Originalbild als Thumbnail verwenden
				if($size[0] <= $maxwidth AND $size[1] <= $maxheight) {
					$thumbnail = 2;
				}
				
				// Bild verkleinern und Thumbnail speichern
				else {
					$new_width = $size[0];
					$new_height = $size[1];
					
					if($new_width > $maxwidth) {
						$new_width = $maxwidth;
						$new_height = round(($maxwidth/$size[0])*$size[1]);
					}
					
					if($new_height > $maxheight) {
						$new_height = $maxheight;
						$new_width = round(($maxheight/$size[1])*$size[0]);
					}
					
					// erzeugen und speichern
					$pic = $functions[$filetype]($path);
					$pic_new = imagecreatetruecolor($new_width, $new_height);
					$weiss = ImageColorAllocate($pic_new, 255, 255, 255);
					imagefill($pic_new, 0, 0, $weiss);
					imagecopyresampled($pic_new, $pic, 0, 0, 0, 0, $new_width, $new_height, $size[0], $size[1]);
					
					imagejpeg($pic_new, './thumbnails/'.$id.'.jpg', 85);
					
					$thumbnail = 1;
				}
			}
		}
		
		
		// MySQL-Datensatz aktualisieren
		MySQL::query("
			UPDATE
				".Config::mysql_prefix."files
			SET
				filesThumbnail = ".$thumbnail."
			WHERE
				filesID = ".$id."
		", __FILE__, __LINE__);
		
	}
	
	
	/**
	 * E-Mail-Benachrichtigung für eine Datei eintragen
	 * @param int $id Datei-ID
	 */
	public static function addNotification($id) {
		
		MySQL::query("
			INSERT IGNORE INTO
				".Config::mysql_prefix."mail
			SET
				mail_filesID = ".(int)$id."
		", __FILE__, __LINE__);
		
	}
	
}

?>