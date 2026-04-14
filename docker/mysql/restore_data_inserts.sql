-- Dati estratti da phpMyAdmin dump (solo INSERT, ordine FK).
-- Schema atteso: migrazioni Laravel (utenti con remember_token nullable, ecc.).
SET NAMES utf8mb4;

INSERT INTO `utenti` (`id`, `nome`, `email`, `password_hash`, `data_creazione`) VALUES
(4, 'Gioele', 'gioelecavallo13@gmail.com', '$2y$10$pm1UKdiX9m/x1BTVKbzTWOc9qE6mhkWYHNcpnZ8zu7xgTR3Z4/Km6', '2026-03-25 09:24:33');

INSERT INTO `sondaggi` (`id`, `titolo`, `descrizione`, `autore_id`, `is_pubblico`, `data_creazione`) VALUES
(2, 'Aboliamo la maturità!', 'La maturità è davvero ancora utile nel sistema scolastico di oggi o è solo una tradizione ormai superata?\r\n\r\nQuesto sondaggio nasce per raccogliere opinioni sincere su uno degli argomenti più discussi tra studenti: l’esame di maturità. Stress inutile o prova fondamentale?\r\n\r\nEsprimi il tuo punto di vista e scopri cosa ne pensano gli altri!', 4, 1, '2026-03-27 18:10:28'),
(3, 'Riforma della giustizia', 'Negli ultimi tempi si è discusso molto della riforma della giustizia in Italia, che propone cambiamenti importanti come la separazione delle carriere tra giudici e pubblici ministeri e una riorganizzazione degli organi di controllo.\r\n\r\nQuesto sondaggio ti chiede una scelta semplice: sei favorevole o contrario a questa riforma?', 4, 1, '2026-03-27 18:13:15'),
(4, 'Razze di cani più amate', 'Quali sono le razze di cani più amate?\r\nI cani sono tra gli animali più affettuosi e fedeli, ma ognuno ha le sue preferenze: c’è chi ama i cani grandi e protettivi, chi quelli piccoli e vivaci, e chi invece impazzisce per quelli super giocherelloni.\r\n\r\nPartecipa al sondaggio e scegli le razze che ti piacciono di più! 🐾\r\nPuoi votare una o più opzioni.', 4, 1, '2026-03-27 19:00:26'),
(5, 'Destinare 10.000 euro per il laboratorio TDP (informatica)', 'Il nostro laboratorio di Informatica (TDP) ha a disposizione un budget di 10.000 euro da investire per migliorare attrezzature, strumenti e ambiente di lavoro.\r\n\r\nQuesto sondaggio serve a raccogliere le vostre opinioni su come destinare al meglio questi fondi, così da rendere il laboratorio più moderno, efficiente e utile per tutti gli studenti.\r\n\r\nOgni voto aiuterà a scegliere le priorità più importanti: nuove tecnologie, postazioni migliori, software o altre risorse didattiche.\r\n\r\nPartecipa e contribuisci a migliorare il laboratorio! 💡', 4, 0, '2026-03-27 20:21:53');

INSERT INTO `domande` (`id`, `sondaggio_id`, `testo`, `tipo`, `ordine`) VALUES
(6, 3, 'Sei favorevole alla riforma della giustizia?', 'singola', 1),
(7, 4, 'Quale razza ti piace di più?', 'multipla', 1),
(8, 2, 'Sei favorevole all’abolizione dell’esame di maturità?', 'singola', 1),
(9, 2, 'Quanto pensi sia stressante la maturità?', 'singola', 2),
(10, 2, 'Se potessi cambiare una cosa della maturità, cosa sarebbe?', 'singola', 3),
(11, 5, 'sei d\'accordo?', 'singola', 1);

INSERT INTO `opzioni` (`id`, `domanda_id`, `testo`, `ordine`) VALUES
(17, 6, 'Sì, sono favorevole', 1),
(18, 6, 'No, sono contrario', 2),
(19, 6, 'Mi astengo / non so', 3),
(20, 7, 'Labrador Retriever', 1),
(21, 7, 'Golden Retriever', 2),
(22, 7, 'Pastore Tedesco', 3),
(23, 7, 'Bulldog (Inglese o Francese)', 4),
(24, 7, 'Barboncino', 5),
(25, 7, 'Chihuahua', 6),
(26, 7, 'Husky Siberiano', 7),
(27, 7, 'Beagle', 8),
(28, 7, 'Rottweiler', 9),
(29, 7, 'Dobermann', 10),
(30, 7, 'Border Collie', 11),
(31, 7, 'Carlino', 12),
(32, 7, 'Maltese', 13),
(33, 7, 'Shiba Inu', 14),
(34, 7, 'Akita Inu', 15),
(35, 7, 'Dalmata', 16),
(36, 7, 'Jack Russell Terrier', 17),
(37, 7, 'Alano', 18),
(38, 7, 'Cocker Spaniel', 19),
(39, 7, 'Meticcio (incrocio)', 20),
(40, 8, 'Sì, andrebbe abolito completamente', 1),
(41, 8, 'No, è una prova importante', 2),
(42, 8, 'Solo in parte (andrebbe modificato)', 3),
(43, 8, 'Non ho un’opinione precisa', 4),
(44, 9, 'Estremamente stressante', 1),
(45, 9, 'Abbastanza stressante', 2),
(46, 9, 'Poco stressante', 3),
(47, 9, 'Per niente stressante', 4),
(48, 10, 'Eliminare alcune prove', 1),
(49, 10, 'Renderla più pratica', 2),
(50, 10, 'Ridurre la difficoltà', 3),
(51, 10, 'Abolirla completamente', 4),
(52, 11, 'Sì, sono favorevole', 1),
(53, 11, 'No, non sono favorevole', 2),
(54, 11, 'Mi astengo', 3);

INSERT INTO `contatti` (`id`, `nome`, `email`, `messaggio`, `data_invio`) VALUES
(1, 'Gioele', 'gioelecavallo13@gmail.com', 'vorrei informazioni.', '2026-03-25 09:45:48');

-- AUTO_INCREMENT allineati al dump originale (prossimi insert senza id esplicito).
ALTER TABLE `contatti` AUTO_INCREMENT = 2;
ALTER TABLE `dettaglio_risposte` AUTO_INCREMENT = 5;
ALTER TABLE `domande` AUTO_INCREMENT = 12;
ALTER TABLE `opzioni` AUTO_INCREMENT = 55;
ALTER TABLE `risposte` AUTO_INCREMENT = 5;
ALTER TABLE `sondaggi` AUTO_INCREMENT = 6;
ALTER TABLE `utenti` AUTO_INCREMENT = 5;
