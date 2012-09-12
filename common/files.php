<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


class Files {
	
	public static $files_loaded = array();
	
	
	public static $files = array();
	
	
	
	
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
	
	
}

?>