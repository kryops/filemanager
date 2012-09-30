<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class ImpressumPage {
	
	
	public static function displayImpressum() {
		
		$tmpl = new Template;
		
		$tmpl->title = 'Impressum';
		
		$tmpl->content = '
	<p>Dateimanager &copy; 2012 Michael Strobel</p>
	<p>michael [at] kryops [.] de</p>
	<br />
	
	<p>Lizensiert unter der <a href="http://opensource.org/licenses/mit-license.php" target="_blank" class="italic">MIT-Lizenz</a></p>
	<p class="italic"><a href="https://github.com/kryops/filemanager">&raquo; Code auf GitHub</a></p>
	
	<br />
	<br />
	
	<p>
	Umfragemodul von Ernesto Els&auml;&szlig;er.
	<br />
	Einige Icons von <a href="http://p.yusukekamiyamane.com/" target="_blank" class="italic">Yusuke Kamiyamane</a>. Alle Rechte vorbehalten.
	<br />
	Lizensiert unter einer <a href="http://creativecommons.org/licenses/by/3.0/deed.de" target="_blank" class="italic">Creative Commons Namensnennung 3.0 Lizenz</a>.
	</p>
		';
		
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