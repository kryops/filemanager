<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class FilesPage {
	
	/**
	 * Dateiübersicht anzeigen
	 */
	public static function displayOverview() {
		
		$tmpl = new Template;
		
		$tmpl->content = 'Dateiübersicht';
		
		$tmpl->output();
		
	}
	
	
	
	/**
	 * Seite auswählen, die geladen werden soll
	 */
	public static function dispatch() {
		
		self::displayOverview();
		
	}
	
}


?>