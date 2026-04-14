-- Svuota solo le tabelle applicative (non tocca sessions, migrations, cache, jobs, ...).
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `dettaglio_risposte`;
TRUNCATE TABLE `risposte`;
TRUNCATE TABLE `survey_submit_attempts`;
TRUNCATE TABLE `opzioni`;
TRUNCATE TABLE `domande`;
TRUNCATE TABLE `sondaggi`;
TRUNCATE TABLE `contatti`;
TRUNCATE TABLE `utenti`;
SET FOREIGN_KEY_CHECKS = 1;
