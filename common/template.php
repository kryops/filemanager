<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}


/**
 * 
 * Template-Klasse
 * Gibt eine Seite als HTML oder eine AJAX-Antwort als JSON aus
 *
 */

class Template {
	
	/**
	 * Verfügbare Templates
	 */
	public static $templates = array(
		'standard'=>true
	);
	
	
	
	/**
	 * Seitentitel
	 */
	public $title = '';
	
	/**
	 * Seiteninhalt (HTML)
	 */
	public $content = '';
	
	/**
	 * JavaScript, das beim Laden ausgeführt werden soll
	 */
	public $script = '';
	
	/**
	 * Fehlermeldung
	 */
	public $error = '';
	
	
	// Einstellungen
	
	/**
	 * Ausgabe als JSON-Array für AJAX
	 */
	public $ajax = null;
	
	/**
	 *
	 */
	public $template = 'standard';
	
	
	/**
	 * Template ausgeben
	 */
	public function output() {
		
		// Überprüfung, ob Ausgabe als JSON
		if($this->ajax === null) {
			$this->ajax = isset($_GET['ajax']);
		}
		
		// Ausgabe als JSON
		if($this->ajax) {
			
			// Inhalt und Script leeren, wenn Fehler
			if(!empty($this->error)) {
				$this->content = '';
				$this->script = '';
			}
			
			// Queries oder Query-Anzahl einfügen
			$queries = MySQL::getQueries();
			
			if(Config::debug) {
				$queries = print_r($queries, true);
			}
			else {
				$queries = count($queries);
			}
			
			// Ausgabe-Array erstellen
			$out = array(
				'benchmark'=>General::getBenchmark(true),
				'queries'=>$queries,
				'error'=>$this->error,
				'title'=>$this->title,
				'content'=>$this->content,
				'script'=>$this->script
			);
			
			// JSON-MIME-Type
			header("Content-Type: application/json");
			
			// ausgeben
			echo json_encode($out);
			
		}
		
		// Ausgabe als HTML
		else {
			
			if(!isset(self::$templates[$this->template])) {
				$this->template = 'standard';
			}
			
			// Template einbinden
			include './template/'.$this->template.'.php';
			
		}
	}
	
	/**
	 * Fehler sofort ausgeben und abbrechen
	 *
	 * @param string $error Fehlermeldung
	 */
	public function abort($error) {
		
		$this->error = $error;
		
		$this->output();
		
		die();
		
	}
	
	/**
	 * Sofortige Weiterleitung
	 *
	 * @param string $url Ziel-Adrese
	 */
	public function redirect($url) {
		
		$this->script = 'url("'.$url.'");';
		
		// Variante ohne JavaScript
		$this->content = '<meta http-equiv="refresh" content="0; URL='.h($url).'" />';
		
		$this->output();
		
		die();
		
	}
	
	/**
	 * neues Template-Objekt erstellen und Fehler ausgeben
	 */
	public static function bakeError($error) {
		$tmpl = new Template;
		$tmpl->abort($error);
	}
}

?>