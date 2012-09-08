<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class UploadPage {
	
	
	public static function displayUploadPage() {
		
		$tmpl = new Template;
		
		$tmpl->title = 'Hochladen';
		
		$tmpl->content = 'Hochladen';
		
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