<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class AdminPage {
	
	
	public static function displayAdminPage() {
		
		$tmpl = new Template;
		
		$tmpl->title = 'Administration';
		
		$tmpl->content = 'Administration';
		
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
		
		if(!User::$admin) {
			Template::bakeError('Du hast keine Berechtigung!');
		}
		
		self::displayAdminPage();
		
	}
	
}


?>