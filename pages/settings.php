<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class SettingsPage {
	
	/**
	 * Verfügbare Unterseiten
	 * Schlüssel: $_GET['sp']
	 * Wert: Name der Funktion, die aufgerufen werden soll
	 */
	public static $actions = array(
		'' => 'displayForm',
		'send' => 'save'
	);
	
	
	
	/**
	 * Registrierungs-Formular anzeigen
	 */
	public static function displayForm() {
		$tmpl = new Template;
		
		$tmpl->title = 'Einstellungen';
		
		$tmpl->content = '
		
		<div class="center">
			
			<h1>Einstellungen</h1>
			
			<form action="index.php?p=settings&amp;sp=send" method="post" class="ajaxform" data-target="#settings_result">
			
			<table class="formtable center">
			<tr>
				<td>E-Mail-Adresse</td>
				<td><input type="text" class="text" name="email" required maxlength="100" value="'.h(User::$data->userEmail).'" /></td>
			</tr>
			<tr>
				<td class="center" colspan="2">
					<input type="checkbox" name="notifications" id="settings_notifications" '.(User::$data->userEmailNotification ? 'checked' : '').' />
					<label for="settings_notifications">Benachrichtigung bei neuen Dateien</label>
				</td>
			</tr>
			<tr>
				<td>Passwort &auml;ndern</td>
				<td><input type="password" class="text" name="pw1" /></td>
			</tr>
			<tr>
				<td class="italic">(wiederholen)</td>
				<td><input type="password" class="text" name="pw2" /></td>
			</tr>
			<tr>
				<td class="center topspace" colspan="2">
					<input type="submit" class="button wide" value="Speichern" />
				</td>
			</tr>
			</table>
			
			</form>
			
			<br />
			<div class="center" id="settings_result"></div>
		</div>
		
		';
		
		$tmpl->output();
	}
	
	
	/**
	 * Einstellungen speichern
	 */
	public static function save() {
		
		$tmpl = new Template;
		
		// Validierung
		if(!isset($_POST['email'], $_POST['pw1'], $_POST['pw2'])) {
			$tmpl->abort('Daten unvollständig!');
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
		
		
		// Passwortänderung
		$pw = User::$data->userPassword;
		
		if($_POST['pw1'] != '') {
			$pw = User::encryptPassword($_POST['pw1']);
		}
		
		// Benachrichtigungen
		$notifications = isset($_POST['notifications']) ? 1 : 0;
		
		// speichern
		MySQL::query("
			UPDATE
				".Config::mysql_prefix."user
			SET
				userEmail = '".MySQL::escape($_POST['email'])."',
				userEmailNotification = ".$notifications.",
				userPassword = '".$pw."'
			WHERE
				userID = ".User::$id."
		", __FILE__, __LINE__);
		
		
		// Benutzerdaten aktualisieren
		User::$data->userEmail = $_POST['email'];
		User::$data->userEmailNotification = $notifications;
		User::$data->userPassword = $pw;
		
		// Cookie erneuern
		if(isset($_COOKIE[Config::cookie_name])) {
			User::cookie();
		}
		
		$tmpl->content = 'Die Einstellungen wurden gespeichert.';
		
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