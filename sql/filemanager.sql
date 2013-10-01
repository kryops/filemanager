-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 24. Okt 2012 um 18:02
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
  `filesName` varchar(255) NOT NULL,
  `filesPath` varchar(255) NOT NULL,
  `files_folderID` int(10) unsigned NOT NULL,
  `files_userID` int(10) unsigned NOT NULL,
  `filesDate` int(10) unsigned NOT NULL,
  `filesSize` int(10) unsigned NOT NULL,
  `filesThumbnail` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`filesID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fmg_folder`
--

CREATE TABLE IF NOT EXISTS `fmg_folder` (
  `folderID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folderName` varchar(255) NOT NULL,
  `folderPath` varchar(255) NOT NULL,
  `folderParent` int(11) NOT NULL,
  PRIMARY KEY (`folderID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fmg_mail`
--

CREATE TABLE IF NOT EXISTS `fmg_mail` (
  `mail_filesID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`mail_filesID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fmg_poll`
--

CREATE TABLE IF NOT EXISTS `fmg_poll` (
  `pollID` int(10) NOT NULL AUTO_INCREMENT,
  `pollTitle` text NOT NULL,
  `pollStartDate` int(10) NOT NULL,
  `pollEndDate` int(10) NOT NULL,
  `pollAnswerCount` int(10) NOT NULL,
  `pollOptionList` text NOT NULL,
  `pollType` tinyint(1) unsigned NOT NULL,
  `pollDescription` text NOT NULL,
  `pollDescList` text NOT NULL,
  `pollOptionCount` int(10) NOT NULL,
  PRIMARY KEY (`pollID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fmg_pollstatus`
--

CREATE TABLE IF NOT EXISTS `fmg_pollstatus` (
  `pollstatus_pollID` int(10) NOT NULL,
  `pollstatus_userID` int(10) NOT NULL,
  `pollstatusAnswer` text NOT NULL
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
