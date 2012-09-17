<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


class Folder {
	
	/**
	 * Speicher für fertiges Ordnrer-Array
	 */
	public static $folder = false;
	
	/**
	 * Speicher für Rohdaten
	 */
	public static $raw = false;
	
	
	/**
	 * Datensatz eines Ordners auslesen
	 *
	 * @param int $id Ordner-ID
	 * @return object/false Datensatz
	 */
	public static function get($id) {
		
		// Daten sichern
		$id = (int)$id;
		
		if($id <= 0) {
			return false;
		}
		
		// in den Arrays suchen
		if(self::$folder !== false) {
			foreach(self::$folder as $row) {
				
				if($row->folderID == $id) {
					return $row;
				}
				
			}
		}
		
		else if(self::$raw !== false) {
			foreach(self::$raw as $row) {
				
				if($row->folderID == $id) {
					return $row;
				}
				
			}
		}
		
		// in der Datenbank suchen
		return MySQL::querySingle("
			SELECT
				*
			FROM
				".Config::mysql_prefix."folder
			WHERE
				folderID = ".$id."
		", __FILE__, __LINE__);
		
	}
	
	/**
	 * Fertig sortiertes Array zurückliefern
	 *
	 * @return array sortiertes Ordner-Array
	 */
	public static function getall() {
		
		// schon ausgelesen
		if(self::$folder !== false) {
			return self::$folder;
		}
		
		// Ordner rekursiv durchlaufen und Baum bilden
		self::$folder = self::getchildren(0, true);
		
		// Rohdaten zurücksetzen
		self::$raw = false;
		
		// zurückgeben
		return self::$folder;
		
	}
	
	/**
	 * Rohdaten-Array aus der Datenbank lesen (nach Namen sortiert)
	 *
	 * @return array Rohdaten
	 */
	public static function getall_raw() {
		
		// schon abgefragt
		if(self::$raw !== false) {
			return self::$raw;
		}
		
		$data = array();
		
		// MySQL-Abfrage
		$query = MySQL::query("
			SELECT
				*
			FROM
				".Config::mysql_prefix."folder
			ORDER BY
				folderName ASC
		", __FILE__, __LINE__);
		
		while($row = MySQL::fetch($query)) {
			$data[] = $row;
		}
		
		// speichern
		self::$raw = $data;
		
		// zurückgeben
		return $data;
		
	}
	
	
	/**
	 * Fertig sortiertes Array erzeugen
	 *
	 * @param int $parent gesuchte Eltern-Kategorie
	 * @param bool $recursive rekursiv durchlaufen @default false
	 * @param int $depth Verschachtelungstiefe @default 0
	 *
	 * @return array
	 */
	public static function getchildren($parent, $recursive=false, $depth=0) {
		
		$list = array();
		
		// Kategorie-Array benutzen
		if(self::$folder !== false) {
			$data = self::$folder;
		}
		
		// Rohdaten benutzen oder holen
		else {
			$data = self::getall_raw();
		}
		
		
		// alle Ordner durchgehen
		foreach($data as $row) {
			
			// ist Kind von aktuellem Ordner
			if($row->folderParent == $parent) {
				
				// ans Array anhängen
				$row->depth = $depth;
				$list[] = $row;
				
				// rekursiv die eigenen Kinder hinzufügen
				if($recursive) {
					$list = array_merge(
						$list,
						self::getchildren($row->folderID, true, $depth+1)
					);
				}
				
			}
			
		}
		
		// fertiges Array zurückgeben
		return $list;
		
	}
	
	/**
	 * ID-Liste von Ordner-Kindern erzeugen
	 *
	 * @param int $parent gesuchter Eltern-Orner
	 * @param bool $recursive rekursiv durchlaufen @default false
	 * @param bool $addparent Wurzelelement auch hinzufügen @default false
	 *
	 * @return array ID-Liste
	 */
	public static function getchildren_ids($parent, $recursive=false, $addparent=false) {
		
		$list = array();
		
		// Wurzelelement hinzufügen
		if($addparent) {
			$list[] = $parent;
		}
		
		// Kategorie-Array benutzen
		if(self::$folder !== false) {
			$data = self::$folder;
		}
		
		// Rohdaten benutzen oder holen
		else {
			$data = self::getall_raw();
		}
		
		
		// alle folder durchgehen
		foreach($data as $row) {
			
			// ist Kind von aktueller Kategorie
			if($row->folderParent == $parent) {
				
				// ans Array anhängen
				$list[] = $row->folderID;
				
				// rekursiv die eigenen Kinder hinzufügen
				if($recursive) {
					$list = array_merge(
						$list,
						self::getchildren_ids($row->folderID, true, false)
					);
				}
				
			}
			
		}
		
		// fertiges Array zurückgeben
		return $list;
		
	}
	
	/**
	 * Array aller Elternelemente erzeugen
	 *
	 * @param int $id Ordner
	 * @param bool $reverse Wurzel-Ordner zuerst @default true
	 *
	 * @return array
	 */
	public static function getparents($id, $reverse=true) {
		
		// Rohdaten-Array prefetchen
		if(self::$raw === false) {
			self::getall_raw();
		}
		
		$folder = self::get($id);
		
		// Kategorie nicht gefunden oder Wurzelkategorie
		if(!$folder OR !$folder->folderParent) {
			return array();
		}
		
		// rekursiv Elemente hinzufügen und zurückgeben
		
		// höchster Ordner zuerst
		if($reverse) {
			return array_merge(
				self::getparents($folder->folderParent, $reverse),
				array($folder->folderParent)
			);
		}
		// tiefster Ordner zuerst
		else {
			return array_merge(
				array($folder->folderParent),
				self::getparents($folder->folderParent, $reverse)
			);
		}
	}
	
	/**
	 * Ordner-Auswahlfeld erzeugen
	 *
	 * @param int/false $selected vorausgewählter Eintrag
	 * @param $disabled array/false Array mit ausgegrauten Einträgen
	 */
	public static function dropdown($selected=false, $disabled=false) {
		
		$out = '';
		
		// Abfragen und sortieren
		if(self::$folder === false) {
			self::getall();
		}
		
		// Wurzel-Ordner
		$out .= '<option value="0">- Hauptordner -</option>';
		
		foreach(self::$folder as $row) {
			$out .= '<option value="'.$row->folderID.'"';
			
			// ausgegraut
			if($disabled AND in_array($row->folderID, $disabled)) {
				$out .= ' disabled';
			}
			
			// ausgewählt
			else if($selected AND $row->folderID == $selected) {
				$out .= ' selected';
			}
			
			$out .= '>';
			
			// Verschachtelungstiefe
			for($i=0; $i<=$row->depth; $i++) {
				$out .= '&nbsp;&nbsp; ';
			}
			
			$out .= h($row->folderName)."</option>\n";
		}
		
		return $out;
	}
	
	
	/**
	 * Vollständigen Pfad eines Ordners ermitteln
	 * @param int $id Ordner-ID
	 * @param bool $names Anzeigenamen verwenden @default false
	 * @param bool $encode Pfade URL-enkodieren @default false
	 * @return string Pfad (mit abschließendem /)
	 */
	public static function getFolderPath($id, $names=false, $encode=false) {
		
		$folders = self::getparents($id, true);
		
		$folders[] = $id;
		
		foreach($folders as $key=>$val) {
			$f = self::get($val);
			if(!$f) {
				unset($folders[$key]);
				continue;
			}
			
			/*
			 * Kodierung
			 * PHP-Zugriff -> Windows+Linux UTF-8 dekodieren
			 * Browser-Zugriff
			 * -> Windows nur urlkodieren
			 * -> Linux erst UTF-8 dekodieren, dann urlkodieren
			 */
			if($encode) {
				if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
					$path = rawurlencode(utf8_decode($f->folderPath));
				}
				else {
					$path = rawurlencode($f->folderPath);
				}
			}
			else {
				$path = utf8_decode($f->folderPath);
			}
			
			$folders[$key] = ($names ? $f->folderName : $path).'/';
		}
		
		return implode('', $folders);
	}
	
	
	/**
	 * Ordner löschen
	 * @param int $id
	 */
	public static function delete($id) {
		
		General::loadClass('Files');
		
		// Rohdaten-Array prefetchen
		if(self::$raw === false) {
			self::getall_raw();
		}
		
		$f = self::get($id);
		
		if(!$f) {
			return false;
		}
		
		$path = './files/'.self::getFolderPath($id);
		
		
		// Unterordner löschen
		$folders = self::getchildren_ids($id, true, true);
		
		MySQL::query("
			DELETE FROM
				".Config::mysql_prefix."folder
			WHERE
				folderID IN(".implode(",", $folders).")
		", __FILE__, __LINE__);
		
		
		// Dateien löschen
		$files = Files::getall_ids($folders);
		
		if(count($files)) {
			
			MySQL::query("
				DELETE FROM
					".Config::mysql_prefix."files
				WHERE
					filesID IN(".implode(",", $files).")
			", __FILE__, __LINE__);
			
		}
		
		// Thumbnails löschen
		foreach($files as $fid) {
			
			@unlink('./thumbnails/'.$fid.'.jpg');
			
		}
		
		// auf dem Dateisystem löschen
		self::deleteFolderPath($path);
		
	}
	
	
	/**
	 * Ordner im Dateisystem löschen
	 * @param string $path
	 */
	public static function deleteFolderPath($path) {
		
		// nur gültige Ordner löschen
		if(strpos($path, 'files/') === false OR strpos($path, '../') !== false) {
			return false;
		}
		
		
		if($dir = @opendir($path)) {
			while($file = readdir($dir)) {
				if($file != '.' AND $file != '..') {
					
					// Unterordner rekursiv löschen
					if(is_dir($path.$file)) {
						self::deleteFolderPath($path.$file.'/');
					}
					else {
						@unlink($path.$file);
					}
					
				}
			}
			
			closedir($dir);
		}
		
		@rmdir(substr($path, 0, -1));
		
	}
	
	
	/**
	 * Ordnername Speicherungs-tauglich machen
	 * @param string $filename
	 * @return string bereinigter Dateiname
	 */
	public static function cleanFolderName($filename) {
		
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
	
}

?>