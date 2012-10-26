--
-- Update-Script für erweiterte Umfrage-Funktionen
-- 25.10.2012
--


-- --------------------------------------------------------
--
-- Tabelle `fmg_poll` erweitern
--
-- --------------------------------------------------------

ALTER TABLE `fmg_poll`
ADD (
  `pollDescription` text NOT NULL,
  `pollDescList` text NOT NULL,
  `pollOptionCount` int(10) NOT NULL
);
  
ALTER TABLE `fmg_poll`
CHANGE `pollAnswerList` `pollOptionList` text NOT NULL;

-- --------------------------------------------------------
--
-- für beretis existierende Umfragen pollOptionCount errechnen
--
-- --------------------------------------------------------

UPDATE `fmg_poll`
SET `pollOptionCount` = (LENGTH(`pollOptionList`) - LENGTH(REPLACE(`pollOptionList`, ',', '')) + 1);



UPDATE `fmg_poll`
SET `pollDescList` = REPEAT(',', `pollOptionCount`-1);
