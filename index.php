<?php
error_reporting(E_ALL);

/**
 * Projektkonstante
 */
define('FILEMANAGER', true);


/*
 * globale Dateien einbinden und Grundeinstellungen setzen
 */
include './common/general.php';

General::$loadtime = microtime(true);
General::globalPHPSettings();
General::setCacheHeaders();

if(!@include './config/config.php') {
	
	echo '
	
	Der Dateimanager wurde noch nicht installiert.
	<br /><br />
	Kopiere die Datei config/config-sample.php, erstelle daraus die config/config.php und passe dort alle wichtigen Einstellungen an.
	<br />
	Den Import f&uuml;r die MySQL-Datenbank findest du im Ordner sql.
	<br /><br />
	Die cronjobs/mail.php wickelt den Mailversand ab, sie sollte periodisch aufgerufen werden.
	<br /><br />
	Die Ordner files und thumbnails ben&ouml;tigen PHP-Schreibrechte.
	
	';
	
	die();
}


/*
 * überall benötigte Klassen laden
 */
// MySQL
General::loadClass('MySQL');

MySQL::setConfig(
	Config::mysql_host,
	Config::mysql_user,
	Config::mysql_password,
	Config::mysql_db
);

// Benutzer und Login
General::loadClass('User');

User::checkLogin();

// Template
General::loadClass('Template');


/*
 * Seiten dispatchen
 */
if(!isset($_GET['p'])) {
	$_GET['p'] = 'files';
}
if(!isset($_GET['sp'])) {
	$_GET['sp'] = '';
}


/*
 * Schnell-Aktionen
 */

// Logout
if($_GET['p'] == 'logout') {
	
	User::logout();
	
	$tmpl = new Template;
	$tmpl->redirect('index.php');

}


/*
 * Seiten laden
 */

$pages = General::$pages;

if(isset($pages[$_GET['p']])) {
	include './pages/'.$_GET['p'].'.php';
	$pages[$_GET['p']]::dispatch();
}

// Seite nicht gefunden
else {
	Template::bakeError('Die Seite wurde nicht gefunden!');
}


?>