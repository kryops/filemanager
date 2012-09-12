-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 09. Sep 2012 um 19:43
-- Server Version: 5.5.16
-- PHP-Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `filemanager`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fmg_files`
--

CREATE TABLE IF NOT EXISTS `fmg_files` (
  `filesID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filesName` varchar(100) NOT NULL,
  `filesPath` varchar(100) NOT NULL,
  `files_folderID` int(10) unsigned NOT NULL,
  `files_userID` int(10) unsigned NOT NULL,
  `filesDate` int(10) unsigned NOT NULL,
  `filesSize` int(10) unsigned NOT NULL,
  `filesThumbnail` varchar(50) NOT NULL,
  PRIMARY KEY (`filesID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fmg_folder`
--

CREATE TABLE IF NOT EXISTS `fmg_folder` (
  `folderID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folderName` varchar(50) NOT NULL,
  `folderPath` varchar(50) NOT NULL,
  `folderParent` int(11) NOT NULL,
  PRIMARY KEY (`folderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fmg_user`
--

CREATE TABLE IF NOT EXISTS `fmg_user` (
  `userID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userName` varchar(50) NOT NULL,
  `userPassword` char(32) NOT NULL,
  `userEmail` varchar(100) NOT NULL,
  `userEmailNotification` tinyint(1) unsigned NOT NULL,
  `userOnline` int(10) unsigned NOT NULL,
  `userAdmin` tinyint(1) unsigned NOT NULL,
  `userPassForgotten` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
