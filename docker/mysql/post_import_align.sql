-- Eseguire dopo import dump phpMyAdmin: allinea `risposte` e crea `survey_submit_attempts`
-- come nello schema definito dalle migrations Laravel (non in init/: non va eseguito al bootstrap del volume).

USE `sondaggi_db`;

ALTER TABLE `risposte`
  ADD COLUMN `client_id` CHAR(36) NULL AFTER `sondaggio_id`,
  MODIFY COLUMN `session_fingerprint` CHAR(64) NULL,
  ADD COLUMN `ip_hash` CHAR(64) NULL AFTER `session_fingerprint`;

ALTER TABLE `risposte`
  ADD UNIQUE KEY `uk_risposte_sondaggio_utente` (`sondaggio_id`, `utente_id`),
  ADD KEY `idx_risposte_client` (`sondaggio_id`, `client_id`),
  ADD KEY `idx_risposte_fingerprint` (`sondaggio_id`, `session_fingerprint`);

CREATE TABLE IF NOT EXISTS `survey_submit_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sondaggio_id` INT UNSIGNED NOT NULL,
  `ip_hash` CHAR(64) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_sondaggio_ip_time` (`sondaggio_id`, `ip_hash`, `attempted_at`),
  CONSTRAINT `fk_survey_submit_attempts_sondaggio`
    FOREIGN KEY (`sondaggio_id`) REFERENCES `sondaggi` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
