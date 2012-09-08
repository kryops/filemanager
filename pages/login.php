<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}



class LoginPage {
	
/**
	 * Verfügbare Unterseiten
	 * Schlüssel: $_GET['sp']
	 * Wert: Name der Funktion, die aufgerufen werden soll
	 */
	public static $actions = array(
		'' => 'displayForm',
		'send' => 'login'
	);
	
	
	
	/**
	 * Login-Formular anzeigen
	 */
	public static function displayForm() {
		$tmpl = new Template;
		
		$tmpl->title = 'Login';
		
		$tmpl->content = '
		
		<div class="center">
			
			<h1>Login</h1>
			
			<form action="index.php?p=login&amp;sp=send" method="post" class="ajaxform" data-target="#login_result">
			
			<table class="formtable center">
			<tr>
				<td>Benutzername</td>
				<td><input type="text" class="text" name="username" required autofocus maxlength="50" /></td>
			</tr>
			<tr>
				<td>Passwort</td>
				<td><input type="password" class="text" name="password" required /></td>
			</tr>
			<tr>
				<td class="center" colspan="2">
					<input type="checkbox" name="auto" id="login_auto" />
					<label for="login_auto">eingeloggt bleiben</label>
				</td>
			</tr>
			<tr>
				<td class="center topspace" colspan="2">
					<input type="submit" class="button wide" value="Einloggen" />
				</td>
			</tr>
			</table>
			
			</form>
			
			<br />
			<div class="center" id="login_result"></div>
		</div>
		
		';
		
		$tmpl->output();
	}
	
	
	/**
	 * Einloggen
	 */
	public static function login() {
		
		$tmpl = new Template;
		
		// Validierung
		if(!isset($_POST['username'], $_POST['password'])) {
			$tmpl->abort('Daten unvollständig!');
		}
		
		if(trim($_POST['username']) == '') {
			$tmpl->abort('Kein Benutzername eingegeben!');
		}
		
		if($_POST['password'] == '') {
			$tmpl->abort('Kein Passwort eingegeben!');
		}
		
		
		// Einloggen
		$login = User::login($_POST['username'], $_POST['password'], isset($_POST['auto']));
		
		// fehlgeschlagen
		if(!$login) {
			$tmpl->abort(User::$error);
		}
		
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