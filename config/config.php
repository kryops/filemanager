<?php

if(!defined('FILEMANAGER')) {
	die('Access denied!');
}

/**
 * 
 * Projekt-Einstellungen
 *
 */

class Config {
	
	const name = 'TINF11B Dateimanager';
	
	const folder_top = 1;
	
	const debug = true;
	
	const mysql_host = 'localhost';
	const mysql_user = 'root';
	const mysql_password = '';
	const mysql_db = 'filemanager';
	const mysql_prefix = 'fmg_';
	
	const key = 'adfg54hd7j6zuk65da4gdfg';
	
	const session_prefix = 'fmg_';
	
	const cookie_name = 'fmg';
	const cookie_lifetime = 31536000;
	
}



?>