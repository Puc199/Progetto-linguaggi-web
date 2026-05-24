-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Mag 22, 2026 alle 20:44
-- Versione del server: 8.0.44
-- Versione PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `EasyTicket`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `biglietto`
--

CREATE TABLE `biglietto` (
  `id` int NOT NULL,
  `sigillo_fiscale` varchar(20) NOT NULL,
  `disponibilita` tinyint(1) NOT NULL DEFAULT '1',
  `id_utente` int NOT NULL,
  `id_evento_settore` int NOT NULL,
  `posto` int NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `data_acquisto` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stato_rimborso` enum('nessuno','rimborsato') NOT NULL DEFAULT 'nessuno'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `categoria`
--

CREATE TABLE `categoria` (
  `id` int NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `categoria`
--

INSERT INTO `categoria` (`id`, `nome`, `descrizione`) VALUES
(1, 'Concerto', 'Eventi musicali dal vivo'),
(2, 'Teatro', 'Spettacoli teatrali e musical'),
(3, 'Festival', 'Festival e rassegne dal vivo'),
(4, 'Sport', 'Eventi sportivi e partite'),
(5, 'Evento culturale', 'Mostre, spettacoli e incontri culturali');

-- --------------------------------------------------------

--
-- Struttura della tabella `evento`
--

CREATE TABLE `evento` (
  `id` int NOT NULL,
  `titolo` varchar(120) NOT NULL,
  `descrizione` text,
  `id_categoria` int NOT NULL,
  `id_luogo` int NOT NULL,
  `immagine` varchar(255) DEFAULT NULL,
  `stato` enum('programmato','annullato','completato') NOT NULL DEFAULT 'programmato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `evento`
--

INSERT INTO `evento` (`id`, `titolo`, `descrizione`, `id_categoria`, `id_luogo`, `immagine`, `stato`) VALUES
(2, 'Concerto Rock Easy Live', 'Concerto rock con band emergenti e posti numerati in arena.', 1, 1, 'img/eventi/1779278236_Xnip2026-05-20_13-51-50.jpg', 'programmato'),
(3, 'Concerto Pop Easy Night', 'Serata pop con artisti mainstream e settori VIP e tribuna.', 1, 2, 'img/eventi/1779278225_Xnip2026-05-20_13-53-19.jpg', 'programmato'),
(4, 'Concerto Acustico Easy Lounge', 'Concerto acustico in teatro con atmosfera intima.', 1, 3, 'img/eventi/1779281753_Xnip2026-05-20_14-55-44.jpg', 'programmato'),
(5, 'Spettacolo Teatrale Easy Comedy', 'Commedia brillante per tutta la famiglia.', 2, 2, 'img/eventi/1779281699_Xnip2026-05-20_14-54-24.jpg', 'programmato'),
(6, 'Dramma Easy Classic', 'Spettacolo drammatico ispirato ai classici.', 2, 4, 'img/eventi/1779281712_Xnip2026-05-20_14-54-32.jpg', 'programmato'),
(7, 'Musical Easy Show', 'Musical con coreografie e orchestra dal vivo.', 2, 1, 'img/eventi/1779281720_Xnip2026-05-20_14-54-41.jpg', 'programmato'),
(8, 'Easy Summer Festival', 'Ecco una versione più piccola e locale, adatta a un festival comunale:\r\n\r\n---\r\n\r\n**Festival Estivo Comunale**\r\n\r\nUn appuntamento imperdibile per tutta la comunità! Il nostro festival porta musica, arte e divertimento nel cuore della città, creando un\'atmosfera unica e familiare.\r\n\r\nTre giorni di spettacoli con artisti locali e nazionali, spazi gastronomici con le eccellenze del territorio, laboratori per bambini e installazioni artistiche. Un evento pensato per riunire grandi e piccini in un\'atmosfera festosa e accogliente.\r\n\r\nVivi la magia della musica dal vivo in un contesto intimo e accessibile. Il nostro festival è l\'occasione perfetta per scoprire nuovi talenti, gustare specialità locali e passare momenti indimenticabili con amici e famiglia.\r\n \r\n**🎵 Genere:** Multi-genere (Pop, Rock, Indie, Musica Locale)  \r\n👥 Capienza: 3.500 posti  \r\n🎪 Oltre la musica: Street food, laboratori, area bimbi, artigianato locale', 3, 12, 'img/eventi/1779281317_Xnip2026-05-20_13-56-39.jpg', 'programmato'),
(9, 'Easy Street Art Festival', 'Festival di street art con performance live.', 3, 5, 'img/eventi/1779290898_Xnip2026-05-20_17-28-08.jpg', 'programmato'),
(10, 'Easy Food & Music Festival', 'Festival che unisce cibo di strada e concerti live.', 3, 4, 'img/eventi/1779290860_Xnip2026-05-20_17-27-25.jpg', 'programmato');

-- --------------------------------------------------------

--
-- Struttura della tabella `evento_settore`
--

CREATE TABLE `evento_settore` (
  `id` int NOT NULL,
  `id_replica_evento` int NOT NULL,
  `id_evento` int NOT NULL,
  `id_settore` int NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `posti_totali` int NOT NULL,
  `posti_disponibili` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `evento_settore`
--

INSERT INTO `evento_settore` (`id`, `id_replica_evento`, `id_evento`, `id_settore`, `prezzo`, `posti_totali`, `posti_disponibili`) VALUES
(150, 5, 2, 14, 65.00, 1500, 1500),
(151, 5, 2, 17, 45.00, 1500, 1500),
(152, 5, 2, 30, 25.00, 1000, 1000),
(153, 6, 2, 14, 62.00, 1400, 1400),
(154, 6, 2, 17, 44.00, 1600, 1600),
(155, 6, 2, 30, 24.00, 1000, 1000),
(156, 7, 2, 14, 60.00, 1300, 1300),
(157, 7, 2, 17, 43.00, 1700, 1700),
(158, 7, 2, 30, 23.00, 1000, 1000),
(159, 8, 3, 14, 68.00, 1500, 1500),
(160, 8, 3, 17, 48.00, 1400, 1400),
(161, 8, 3, 30, 28.00, 1100, 1100),
(162, 9, 3, 14, 66.00, 1400, 1400),
(163, 9, 3, 17, 47.00, 1500, 1500),
(164, 9, 3, 30, 27.00, 1100, 1100),
(165, 10, 3, 14, 64.00, 1300, 1300),
(166, 10, 3, 17, 46.00, 1600, 1600),
(167, 10, 3, 30, 26.00, 1100, 1100),
(168, 11, 4, 14, 70.00, 1500, 1500),
(169, 11, 4, 17, 50.00, 1500, 1500),
(170, 11, 4, 30, 30.00, 1000, 1000),
(171, 12, 4, 14, 68.00, 1400, 1400),
(172, 12, 4, 17, 49.00, 1500, 1500),
(173, 12, 4, 30, 29.00, 1100, 1100),
(174, 13, 4, 14, 66.00, 1300, 1300),
(175, 13, 4, 17, 48.00, 1600, 1600),
(176, 13, 4, 30, 28.00, 1100, 1100),
(177, 14, 5, 24, 55.00, 1800, 1800),
(178, 14, 5, 27, 35.00, 1200, 1200),
(179, 14, 5, 14, 20.00, 1000, 1000),
(180, 15, 5, 24, 54.00, 1700, 1700),
(181, 15, 5, 27, 34.00, 1300, 1300),
(182, 15, 5, 14, 19.00, 1000, 1000),
(183, 16, 5, 24, 53.00, 1600, 1600),
(184, 16, 5, 27, 33.00, 1300, 1300),
(185, 16, 5, 14, 18.00, 1100, 1100),
(186, 17, 6, 24, 58.00, 1800, 1800),
(187, 17, 6, 27, 38.00, 1100, 1100),
(188, 17, 6, 14, 22.00, 1100, 1100),
(189, 18, 6, 24, 56.00, 1700, 1700),
(190, 18, 6, 27, 37.00, 1200, 1200),
(191, 18, 6, 14, 21.00, 1100, 1100),
(192, 19, 6, 24, 54.00, 1600, 1600),
(193, 19, 6, 27, 36.00, 1200, 1200),
(194, 19, 6, 14, 20.00, 1200, 1200),
(195, 20, 7, 24, 60.00, 1900, 1900),
(196, 20, 7, 27, 40.00, 1100, 1100),
(197, 20, 7, 14, 24.00, 1000, 1000),
(198, 21, 7, 24, 58.00, 1800, 1800),
(199, 21, 7, 27, 39.00, 1100, 1100),
(200, 21, 7, 14, 23.00, 1100, 1100),
(201, 22, 7, 24, 56.00, 1700, 1700),
(202, 22, 7, 27, 38.00, 1200, 1200),
(203, 22, 7, 14, 22.00, 1100, 1100),
(204, 23, 8, 17, 32.00, 1500, 1500),
(205, 23, 8, 30, 22.00, 1500, 1500),
(206, 23, 8, 32, 18.00, 1000, 1000),
(207, 24, 8, 17, 31.00, 1400, 1400),
(208, 24, 8, 30, 21.00, 1600, 1600),
(209, 24, 8, 32, 17.00, 1000, 1000),
(210, 25, 8, 17, 30.00, 1300, 1300),
(211, 25, 8, 30, 20.00, 1700, 1700),
(212, 25, 8, 32, 16.00, 1000, 1000),
(213, 26, 9, 17, 34.00, 1600, 1600),
(214, 26, 9, 30, 24.00, 1400, 1400),
(215, 26, 9, 32, 19.00, 1000, 1000),
(216, 27, 9, 17, 33.00, 1500, 1500),
(217, 27, 9, 30, 23.00, 1500, 1500),
(218, 27, 9, 32, 18.00, 1000, 1000),
(219, 28, 9, 17, 32.00, 1400, 1400),
(220, 28, 9, 30, 22.00, 1600, 1600),
(221, 28, 9, 32, 17.00, 1000, 1000),
(222, 29, 10, 24, 42.00, 1800, 1800),
(223, 29, 10, 27, 28.00, 1200, 1200),
(224, 29, 10, 14, 18.00, 1000, 1000);

-- --------------------------------------------------------

--
-- Struttura della tabella `luogo`
--

CREATE TABLE `luogo` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `citta` varchar(100) NOT NULL,
  `indirizzo` varchar(150) DEFAULT NULL,
  `capienza` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `luogo`
--

INSERT INTO `luogo` (`id`, `nome`, `tipo`, `citta`, `indirizzo`, `capienza`) VALUES
(1, 'San Siro', 'Stadio', 'Milano', 'Piazzale Angelo Moratti', 75000),
(2, 'Teatro alla Scala', 'Teatro', 'Milano', 'Via Filodrammatici 2', 2030),
(3, 'Arena di Verona', 'Arena', 'Verona', 'Piazza Bra 1', 15000),
(4, 'Auditorium Parco della Musica', 'Auditorium', 'Roma', 'Via Pietro de Coubertin 30', 3000),
(5, 'Teatro Argentina', 'Teatro', 'Roma', 'Largo di Torre Argentina 52', 700),
(6, 'Easy Club Roma', 'Club', 'Roma', 'Via della Musica 10', 350),
(7, 'Easy Arena Indoor', 'Arena indoor', 'Milano', 'Viale dei Concerti 15', 2800),
(8, 'Easy Hall Torino', 'Sala concerti', 'Torino', 'Corso Rock 22', 1800),
(9, 'Easy Comedy Theatre', 'Teatro', 'Roma', 'Via delle Commedie 5', 600),
(10, 'Easy Classic Theatre', 'Teatro', 'Firenze', 'Piazza del Dramma 3', 900),
(11, 'Easy Musical Theatre', 'Teatro', 'Bologna', 'Corso degli Spettacoli 12', 1300),
(12, 'Easy Summer Park', 'Parco', 'Roma', 'Via del Festival 7', 3500),
(13, 'Easy Street District', 'Area festival', 'Milano', 'Zona Murales 18', 2500),
(14, 'Easy Food Village', 'Area festival', 'Napoli', 'Viale del Villaggio 21', 3000),
(15, 'Easy Multipurpose Hall', 'Palazzetto', 'Verona', 'Via degli Eventi 9', 2200);

-- --------------------------------------------------------

--
-- Struttura della tabella `notifica`
--

CREATE TABLE `notifica` (
  `id` int NOT NULL,
  `id_utente` int NOT NULL,
  `titolo` varchar(150) NOT NULL,
  `messaggio` text NOT NULL,
  `data_creazione` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `letta` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `replica_evento`
--

CREATE TABLE `replica_evento` (
  `id` int NOT NULL,
  `id_evento` int NOT NULL,
  `data_ora_inizio` datetime NOT NULL,
  `data_ora_fine` datetime DEFAULT NULL,
  `stato` enum('programmata','annullata','completata') NOT NULL DEFAULT 'programmata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `replica_evento`
--

INSERT INTO `replica_evento` (`id`, `id_evento`, `data_ora_inizio`, `data_ora_fine`, `stato`) VALUES
(5, 2, '2027-07-10 21:00:00', NULL, 'programmata'),
(6, 2, '2027-07-11 21:00:00', NULL, 'programmata'),
(7, 2, '2027-07-12 21:00:00', NULL, 'programmata'),
(8, 3, '2027-07-20 21:00:00', NULL, 'programmata'),
(9, 3, '2027-07-21 21:00:00', NULL, 'programmata'),
(10, 3, '2027-07-22 21:00:00', NULL, 'programmata'),
(11, 4, '2027-08-05 21:00:00', NULL, 'programmata'),
(12, 4, '2027-08-06 21:00:00', NULL, 'programmata'),
(13, 4, '2027-08-07 21:00:00', NULL, 'programmata'),
(14, 5, '2027-09-01 20:30:00', NULL, 'programmata'),
(15, 5, '2027-09-02 20:30:00', NULL, 'programmata'),
(16, 5, '2027-09-03 20:30:00', NULL, 'programmata'),
(17, 6, '2027-09-10 20:30:00', NULL, 'programmata'),
(18, 6, '2027-09-11 20:30:00', NULL, 'programmata'),
(19, 6, '2027-09-12 20:30:00', NULL, 'programmata'),
(20, 7, '2027-10-01 21:00:00', NULL, 'programmata'),
(21, 7, '2027-10-02 21:00:00', NULL, 'programmata'),
(22, 7, '2027-10-03 21:00:00', NULL, 'programmata'),
(23, 8, '2027-06-15 16:00:00', NULL, 'programmata'),
(24, 8, '2027-06-16 16:00:00', NULL, 'programmata'),
(25, 8, '2027-06-17 16:00:00', NULL, 'programmata'),
(26, 9, '2027-05-10 15:00:00', NULL, 'programmata'),
(27, 9, '2027-05-11 15:00:00', NULL, 'programmata'),
(28, 9, '2027-05-12 15:00:00', NULL, 'programmata'),
(29, 10, '2027-04-20 17:00:00', NULL, 'programmata'),
(30, 10, '2027-04-21 17:00:00', NULL, 'programmata'),
(31, 10, '2027-04-22 17:00:00', NULL, 'programmata');

-- --------------------------------------------------------

--
-- Struttura della tabella `ruolo`
--

CREATE TABLE `ruolo` (
  `id` int NOT NULL,
  `nome` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `ruolo`
--

INSERT INTO `ruolo` (`id`, `nome`) VALUES
(1, 'admin'),
(2, 'cliente');

-- --------------------------------------------------------

--
-- Struttura della tabella `settore`
--

CREATE TABLE `settore` (
  `id` int NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  `id_luogo` int DEFAULT NULL,
  `prezzo_base` decimal(10,2) NOT NULL DEFAULT '0.00',
  `posti_totali` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `settore`
--

INSERT INTO `settore` (`id`, `nome`, `descrizione`, `id_luogo`, `prezzo_base`, `posti_totali`) VALUES
(14, 'Parterre in piedi', 'Area sotto il palco senza posti numerati.', NULL, 0.00, 0),
(15, 'Parterre numerato', 'Posti numerati vicino al palco.', NULL, 0.00, 0),
(16, 'VIP', 'Posti premium con servizi aggiuntivi.', 1, 120.00, 500),
(17, 'Tribuna centrale', 'Posti centrali numerati con buona visibilità.', 1, 75.00, 12000),
(18, 'Tribuna laterale', 'Posti numerati laterali.', 1, 60.00, 10000),
(19, 'Curva', 'Settore popolare, più distante dal palco.', 1, 45.00, 18000),
(20, 'Anello superiore', 'Posti in alto con vista panoramica.', 1, 35.00, 14000),
(21, 'Galleria', 'Posti in galleria o anello superiore coperto.', NULL, 0.00, 0),
(22, 'Platea', 'Posti vicini al palco, piano terra.', 2, 95.00, 700),
(23, 'Platea VIP', 'Posti centrali in platea con migliore visibilità.', 2, 150.00, 180),
(24, 'Palchi', 'Palchi laterali o privati.', 2, 130.00, 250),
(25, 'Prima galleria', 'Primo ordine di galleria.', 2, 70.00, 450),
(26, 'Seconda galleria', 'Secondo ordine di galleria, più economico.', 2, 55.00, 450),
(27, 'Balconata', 'Posti in balconata superiore.', 3, 50.00, 4000),
(28, 'Area palco frontale', 'Zona frontale al palco, più costosa.', NULL, 0.00, 0),
(29, 'Area prato', 'Zona prato in piedi, vista buona.', 1, 40.00, 15000),
(30, 'Area prato lontana', 'Zona prato più lontana, prezzo ridotto.', 1, 28.00, 5500),
(31, 'Area family', 'Zona dedicata a famiglie, più tranquilla.', 3, 35.00, 1200),
(32, 'Area food & relax', 'Zona con sedute sparse vicino agli stand food.', 4, 25.00, 300),
(33, 'Platea Teatro Argentina', 'Posti vicini al palco, piano terra.', 5, 85.00, 250),
(34, 'Palchi Teatro Argentina', 'Palchi laterali o privati.', 5, 120.00, 120),
(35, 'Prima galleria Teatro Argentina', 'Primo ordine di galleria.', 5, 60.00, 180),
(36, 'Seconda galleria Teatro Argentina', 'Secondo ordine di galleria.', 5, 45.00, 150),
(37, 'VIP Easy Club Roma', 'Area premium vicino al palco.', 6, 70.00, 40),
(38, 'Pista Easy Club Roma', 'Area centrale in piedi.', 6, 30.00, 220),
(39, 'Balconata Easy Club Roma', 'Zona rialzata con vista palco.', 6, 45.00, 90);

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `id` int NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `data_nascita` date NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_ruolo` int NOT NULL,
  `saldo` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `data_nascita`, `username`, `password`, `id_ruolo`, `saldo`) VALUES
(1, 'admin', 'admin', '2002-09-01', 'admin', '$2y$10$X1ZLv7OFoLPY3P3KyWpOWOonhT2mHgPo75RqFBGkwHSBi23A2.Rxa', 1, 0.00),
(2, 'Riccardo', 'Pucci', '2002-09-01', 'Pucc199', '$2y$10$JwPad7ScwK4Q1ukO9usftuhda67xZa5d4mUL5q4uUHfV0V/ffCHbG', 2, 125.00),
(3, 'Riccardo', 'Pucci', '2002-09-01', 'lalala', '$2y$10$wT6VlslQzfc7Fsp6rLkiRuVgQhwYnGroUiG0kWJ1WaOUxU.qW2E3O', 2, 7820.00),
(4, 'sa', 'sasa', '2002-09-01', 'sasasa', '$2y$10$wwZ4uBdzxPRpNwpwQdzav.QAyIzWkvvoeWAY07aMguzf.vW32y1Jq', 2, 10000.00);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `biglietto`
--
ALTER TABLE `biglietto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sigillo_fiscale` (`sigillo_fiscale`),
  ADD KEY `idx_biglietto_utente` (`id_utente`),
  ADD KEY `idx_biglietto_evento_settore` (`id_evento_settore`);

--
-- Indici per le tabelle `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_categoria_nome` (`nome`);

--
-- Indici per le tabelle `evento`
--
ALTER TABLE `evento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evento_categoria` (`id_categoria`),
  ADD KEY `idx_evento_luogo` (`id_luogo`);

--
-- Indici per le tabelle `evento_settore`
--
ALTER TABLE `evento_settore`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_es_replica` (`id_replica_evento`),
  ADD KEY `idx_es_evento` (`id_evento`),
  ADD KEY `idx_es_settore` (`id_settore`);

--
-- Indici per le tabelle `luogo`
--
ALTER TABLE `luogo`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `notifica`
--
ALTER TABLE `notifica`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifica_utente` (`id_utente`);

--
-- Indici per le tabelle `replica_evento`
--
ALTER TABLE `replica_evento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_replica_evento_evento` (`id_evento`);

--
-- Indici per le tabelle `ruolo`
--
ALTER TABLE `ruolo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ruolo_nome` (`nome`);

--
-- Indici per le tabelle `settore`
--
ALTER TABLE `settore`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_settore_nome` (`nome`),
  ADD KEY `idx_settore_luogo` (`id_luogo`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_utente_username` (`username`),
  ADD KEY `idx_utente_ruolo` (`id_ruolo`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `biglietto`
--
ALTER TABLE `biglietto`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT per la tabella `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `evento`
--
ALTER TABLE `evento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `evento_settore`
--
ALTER TABLE `evento_settore`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT per la tabella `luogo`
--
ALTER TABLE `luogo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT per la tabella `notifica`
--
ALTER TABLE `notifica`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `replica_evento`
--
ALTER TABLE `replica_evento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT per la tabella `ruolo`
--
ALTER TABLE `ruolo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `settore`
--
ALTER TABLE `settore`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `biglietto`
--
ALTER TABLE `biglietto`
  ADD CONSTRAINT `biglietto_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`),
  ADD CONSTRAINT `biglietto_ibfk_2` FOREIGN KEY (`id_evento_settore`) REFERENCES `evento_settore` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `evento`
--
ALTER TABLE `evento`
  ADD CONSTRAINT `evento_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id`),
  ADD CONSTRAINT `evento_ibfk_2` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id`);

--
-- Limiti per la tabella `evento_settore`
--
ALTER TABLE `evento_settore`
  ADD CONSTRAINT `evento_settore_ibfk_1` FOREIGN KEY (`id_replica_evento`) REFERENCES `replica_evento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evento_settore_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evento_settore_ibfk_3` FOREIGN KEY (`id_settore`) REFERENCES `settore` (`id`);

--
-- Limiti per la tabella `notifica`
--
ALTER TABLE `notifica`
  ADD CONSTRAINT `notifica_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `replica_evento`
--
ALTER TABLE `replica_evento`
  ADD CONSTRAINT `replica_evento_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `settore`
--
ALTER TABLE `settore`
  ADD CONSTRAINT `settore_ibfk_luogo` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id`);

--
-- Limiti per la tabella `utente`
--
ALTER TABLE `utente`
  ADD CONSTRAINT `utente_ibfk_1` FOREIGN KEY (`id_ruolo`) REFERENCES `ruolo` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
