<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class RegisterPage {
	
	/**
	 * Verfügbare Unterseiten
	 * Schlüssel: $_GET['sp']
	 * Wert: Name der Funktion, die aufgerufen werden soll
	 */
	public static $actions = array(
		'' => 'displayForm',
		'send' => 'register'
	);
	
	
	
	/**
	 * Registrierungs-Formular anzeigen
	 */
	public static function displayForm() {
		$tmpl = new Template;
		
		$tmpl->title = 'Registrieren';
		
		$tmpl->content = '
		
		<div class="center">
			
			<h1>Registrieren</h1>
			
			<form action="index.php?p=register&amp;sp=send" method="post" class="ajaxform" data-target="#register_result">
			
			<table class="formtable center">
			<tr>
				<td>Benutzername</td>
				<td><input type="text" class="text" name="username" required autofocus maxlength="50" /></td>
			</tr>
			<tr>
				<td>E-Mail-Adresse</td>
				<td><input type="text" class="text" name="email" required maxlength="100" /></td>
			</tr>
			<tr>
				<td>Passwort</td>
				<td><input type="password" class="text" name="pw1" required /></td>
			</tr>
			<tr>
				<td class="italic">(wiederholen)</td>
				<td><input type="password" class="text" name="pw2" required /></td>
			</tr>
			<tr>
				<td class="center topspace" colspan="2">
					<input type="submit" class="button wide" value="Registrieren" />
				</td>
			</tr>
			</table>
			
			</form>
			
			<br />
			<div class="center" id="register_result"></div>
		</div>
		
		';
		
		$tmpl->output();
	}
	
	
	/**
	 * Registrierung abschließen
	 */
	public static function register() {
		
		$tmpl = new Template;
		
		// Validierung
		if(!isset($_POST['username'], $_POST['email'], $_POST['pw1'], $_POST['pw2'])) {
			$tmpl->abort('Daten unvollständig!');
		}
		
		if(trim($_POST['username']) == '') {
			$tmpl->abort('Kein Benutzername eingegeben!');
		}
		
		if(trim($_POST['email']) == '') {
			$tmpl->abort('Keine E-Mail-Adresse eingegeben!');
		}
		
		if(strpos($_POST['email'], '@') === false) {
			$tmpl->abort('Ungültige E-Mail-Adresse eingegeben!');
		}
		
		if($_POST['pw1'] != $_POST['pw2']) {
			$tmpl->abort('Die Passwörter sind unterschiedlich!');
		}
		
		if($_POST['pw1'] == '') {
			$tmpl->abort('Kein Passwort eingegeben!');
		}
		
		// Name noch verfügbar?
		$data = MySQL::querySingle("
			SELECT
				userID
			FROM
				".Config::mysql_prefix."user
			WHERE
				userName = '".MySQL::escape($_POST['username'])."'
		", __FILE__, __LINE__);
		
		if($data) {
			$tmpl->abort('Der Benutzername ist bereits vorhanden!');
		}
		
		// Adminberechtigungen, falls erster Benutzer
		$admin = 0;
		
		$admindata = MySQL::querySingle("
			SELECT
				COUNT(*) AS Anzahl
			FROM
				".Config::mysql_prefix."user
		", __FILE__, __LINE__);
		
		if(!$admindata->Anzahl) {
			$admin = 1;
		}
		
		
		// Speichern
		MySQL::query("
			INSERT INTO
				".Config::mysql_prefix."user
			SET
				userName = '".MySQL::escape($_POST['username'])."',
				userPassword = '".User::encryptPassword($_POST['pw1'])."',
				userEmail = '".MySQL::escape($_POST['email'])."',
				userOnline = ".time().",
				userAdmin = ".$admin."
		", __FILE__, __LINE__);
		
		$id = mysql_insert_id();
		
		// Einloggen
		User::login($_POST['username'], $_POST['pw1']);
		
		$tmpl->redirect('index.php');
	}
	
	
	
	/**
	 * Seite auswählen, die geladen werden soll
	 */
	public static function dispatch() {
		
		// eingeloggt
		if(User::$login) {
			Template::bakeError('Du bist bereits eingeloggt!');
		}
		
		
		if(isset(self::$actions[$_GET['sp']])) {
			$action = self::$actions[$_GET['sp']];
			self::$action();
		}
		
		// Seite nicht gefunden
		else {
			Template::bakeError('Die Seite wurde nicht gefunden!');
		}
		
	}
	
}

?>