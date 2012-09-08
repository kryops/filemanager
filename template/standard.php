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
	
	if($_GET['p'] != 'files') {
		?>
	<a class="button" href="index.php">
		<img src="img/zurueck.png" alt="" />
		&Uuml;bersicht
	</a>
		<?php
	}
	
	// nicht eingeloggt
	if(!User::$login) {
		if($_GET['p'] != 'login') {
			?>
	<a class="button" href="index.php?p=login">
		<img src="img/login.png" alt="" />
		Login
	</a>
			<?php
		}
		if($_GET['p'] != 'register') {
			?>
	<a class="button" href="index.php?p=register">
		<img src="img/registrieren.png" alt="" />
		Registrieren
	</a>
			<?php
		}
	}
	
	// eingeloggt
	else {
		if(User::$admin AND $_GET['p'] != 'admin') {
			?>
	<a class="button" href="index.php?p=admin">
		<img src="img/admin.png" alt="" />
		Admin
	</a>
			<?php
		}
		if($_GET['p'] != 'upload') {
			?>
	<a class="button" href="index.php?p=upload">
		<img src="img/hochladen.png" alt="" />
		Hochladen
	</a>
			<?php
		}
		if($_GET['p'] != 'settings') {
			?>
	<a class="button" href="index.php?p=settings">
		<img src="img/einstellungen.png" alt="" />
		Einstellungen
	</a>
			<?php
		}
		?>
	<a class="button" href="index.php?p=logout">
		<img src="img/logout.png" alt="" />
		Logout
	</a>
		<?php
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

<img id="ajaxload" src="img/ajax.gif" alt="laden..." />

<script src="js/jquery.js"></script>
<script src="js/general.js"></script>

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