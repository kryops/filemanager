<?php
error_reporting(E_ALL);

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}

// HTML-Seite
header("Content-Type: text/html");

?><!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title><?php

				if(!empty($this->title)) {
					echo $this->title.' &bull; ';
				}
				
				echo h(Config::name);

			?></title>
	<link rel="stylesheet" type="text/css" href="css/main.css" />
	<link rel="shortcut icon" href="favicon.ico" />
</head>
<body>

<div id="wrapper">

<div id="headline">
	<a href="index.php">
		<img src="img/logo.png" id="logo" alt="" />
		<?php echo h(Config::name); ?>
	</a>
</div>

<div id="menu">
	<?php 
	
	/*
	 * Navi-Eintrag: array($_GET['p'], icon-Dateiname, Label, (bool) anzeigen?)
	 */
	$navi = array(
		
		// Benutzer eingeloggt
		array(
			'files',
			'dateien.png',
			'Dateien',
			true
		),
		array(
			'settings',
			'einstellungen.png',
			'Einstellungen',
			User::$login
		),
		// Admin
		array(
			'admin',
			'admin.png',
			'Admin',
			User::$admin
		),
		array(
			'logout',
			'logout.png',
			'Logout',
			User::$login
		),
		
		// nicht eingeloggt
		array(
			'login',
			'login.png',
			'Login',
			!User::$login
		),
		array(
			'register',
			'registrieren.png',
			'Registrieren',
			!User::$login
		),
	);
	
	
	foreach($navi as $nav) {
		if($nav[3]) {
			echo '
	<a class="button'.($_GET['p'] == $nav[0] ? ' active' : '').'" href="index.php?p='.$nav[0].'">
		<img src="img/'.$nav[1].'" alt="" />
		'.$nav[2].'
	</a>';
		}
	}
	
	
	
	?>
</div>

<div id="content">

<?php
	
	// Seiteninhalt
	echo $this->content;
	
	// Fehler aufgetreten
	if($this->error != '') {
		echo '<div class="error center">'.$this->error.'</div>';
	}
	
?>

</div>

<div id="footer">
	&copy; 2012 Michael Strobel
	&nbsp; &bull; &nbsp;
	<a href="index.php?p=impressum">Impressum</a>
</div>

</div>

<div id="thumbnail"></div>

<img id="ajaxload" src="img/ajax.gif" alt="laden..." />

<script src="js/jquery.js"></script>
<script src="js/jquery-ui.js"></script>
<script src="js/general.js"></script>

<?php
if($this->script != '') {
	echo '<script>
'.$this->script.'
</script>'; 
}
?>

<!-- <?php echo General::getBenchmark(true) ?>s<?php

// im Debug-Modus MySQL-Queries anzeigen
if(Config::debug) {
	echo "\n\n";
	print_r(MySQL::getQueries());
	echo "\n\n";
}

?> -->

</body>
</html>