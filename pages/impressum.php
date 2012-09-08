<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class ImpressumPage {
	
	
	public static function displayImpressum() {
		
		$tmpl = new Template;
		
		$tmpl->title = 'Impressum';
		
		$tmpl->content = 'Impressum';
		
		$tmpl->output();
		
	}
	
	
	
	/**
	 * Seite auswählen, die geladen werden soll
	 */
	public static function dispatch() {
		
		self::displayImpressum();
		
	}
	
}


?>