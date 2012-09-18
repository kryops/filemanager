<?php
error_reporting(E_ALL);

define('FILEMANAGER', true);


/*
 * globale Dateien einbinden und Grundeinstellungen setzen
 */
include '../common/general.php';
include '../common/mysql.php';
include '../common/folder.php';


ignore_user_abort(true);
date_default_timezone_set('Europe/Berlin');

General::setCacheHeaders();

if(!@include '../config/config.php') {
	
	echo '
	
	Der Dateimanager wurde noch nicht installiert!
	
	';
	
	die();
}


MySQL::setConfig(
	Config::mysql_host,
	Config::mysql_user,
	Config::mysql_password,
	Config::mysql_db
);


/*
 * Sicherheitsschlüssel überprüfen
 */
if(!isset($_GET['key']) OR $_GET['key'] != Config::key) {
	die('Sicherheitsüberprüfung fehlgeschlagen!');
}


/*
 * Mail-Queue abfragen und Mails verschicken
 */

// Dateiliste generieren
$query = MySQL::query("
	SELECT
		*
	FROM
		".Config::mysql_prefix."files
		LEFT JOIN ".Config::mysql_prefix."mail
			ON mail_filesID = filesID
	WHERE
		mail_filesID IS NOT NULL
", __FILE__, __LINE__);

$content = '';

while($row = MySQL::fetch($query)) {
	
	$id = $row->filesID;
	
	$path_url = Folder::getFolderPath($row->files_folderID, false, true);
	$path_names = Folder::getFolderPath($row->files_folderID, true);
	
	if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		$fpath = rawurlencode($row->filesPath);
	}
	else {
		$fpath = rawurlencode(utf8_decode($row->filesPath));
	}
	
	$content .= '
	<li><a href="'.Config::url.'/files/'.$path_url.$fpath.'">'.h($path_names.$row->filesName).'</a></li>';
	
}

// Mails verschicken
$query = MySQL::query("
	SELECT
		userName,
		userEmail
	FROM
		".Config::mysql_prefix."user
	WHERE
		userEmailNotification = 1
", __FILE__, __LINE__);

while($row = MySQL::fetch($query)) {
	@mail(
		$row->userEmail,
		'['.Config::name.'] Neue Dateien',
		'<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8" />
</head>
<body>

<p>Hallo '.h($row->userName).',</p>
<p>Folgende Dateien wurden hochgeladen oder ge&auml;ndert:</p>

<ul>
'.$content.'
</ul>

<br /><br /><br />
<span style="font-size:87.5%;color:#888888">Wenn du diese E-Mails nicht mehr erhalten willst, &auml;ndere deine Einstellungen <a href="'.Config::url.'/index.php?p=settings">hier</a></span>

</body>
</html>',
	"From: ".Config::mail_addr."\nContent-type: text/html; charset=utf-8\nX-Mailer: PHP/".phpversion()
	);
}


// Mail-Queue löschen
MySQL::query("
	DELETE FROM
		".Config::mysql_prefix."mail
", __FILE__, __LINE__);



echo "Mailversand-Cronjob erfolgreich abgearbeitet.";


?>