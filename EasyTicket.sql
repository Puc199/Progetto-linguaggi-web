-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Mag 30, 2026 alle 08:00
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
-- Database: `Prova`
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

--
-- Dump dei dati per la tabella `biglietto`
--

INSERT INTO `biglietto` (`id`, `sigillo_fiscale`, `disponibilita`, `id_utente`, `id_evento_settore`, `posto`, `prezzo`, `data_acquisto`, `stato_rimborso`) VALUES
(252, 'E060210F3453B57C', 1, 5, 25664, 1, 24.90, '2026-05-29 16:48:42', 'nessuno');

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
(3, 'Festival', 'Festival e rassegne dal vivo');

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
(31, 'Note sotto le stelle', 'Una serata elegante tra musica dal vivo, luci soffuse e arrangiamenti intensi. Un evento pensato per chi vuole lasciarsi coinvolgere da un\'atmosfera raffinata e da un sound capace di emozionare fin dalle prime note.', 1, 1, 'img/eventi/1780049717_1780049539.png', 'programmato'),
(32, 'Ritmi di città', 'Un live energico e contemporaneo che porta sul palco sonorità moderne, presenza scenica e un coinvolgimento costante del pubblico. Ideale per chi cerca una serata dinamica, urbana e piena di ritmo.', 1, 2, 'img/eventi/1780049981_1779278225_Xnip2026-05-20_13-53-19.jpg', 'programmato'),
(33, 'Voci di scena', 'Uno spettacolo teatrale intenso e coinvolgente, costruito su dialoghi incisivi, interpretazioni curate e una messa in scena capace di alternare emozione, tensione e profondità narrativa.', 2, 4, 'img/eventi/1780050000_1779718179_1779281699_Xnip2026-05-20_14-54-24.jpg', 'programmato'),
(34, 'L\'arte della commedia', 'Una commedia brillante dal ritmo vivace, con personaggi ben caratterizzati, situazioni imprevedibili e momenti leggeri che accompagnano lo spettatore in una serata piacevole e divertente.', 2, 5, 'img/eventi/1780049882_1780049856df9b.png', 'programmato'),
(35, 'Estate in festa', 'Un festival all\'aperto che unisce musica, intrattenimento e atmosfera conviviale in un contesto ricco di energia. Perfetto per vivere una giornata speciale tra spettacolo, socialità e voglia di stare insieme.', 3, 7, 'img/eventi/1780049965_1779281317_Xnip2026-05-20_13-56-39.jpg', 'programmato');

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
(25654, 4554, 35, 24, 35.00, 400, 400),
(25655, 4554, 35, 23, 59.90, 230, 230),
(25656, 4554, 35, 25, 22.00, 150, 150),
(25657, 4554, 35, 22, 89.90, 120, 120),
(25658, 4555, 35, 24, 35.00, 400, 400),
(25659, 4555, 35, 23, 59.90, 230, 230),
(25660, 4555, 35, 25, 22.00, 150, 150),
(25661, 4555, 35, 22, 89.90, 120, 120),
(25662, 4543, 31, 1, 49.90, 120, 120),
(25663, 4543, 31, 2, 34.90, 150, 150),
(25664, 4543, 31, 3, 24.90, 80, 79),
(25665, 4544, 31, 1, 49.90, 120, 120),
(25666, 4544, 31, 2, 34.90, 150, 150),
(25667, 4544, 31, 3, 24.90, 80, 80),
(25668, 4545, 32, 4, 79.90, 180, 180),
(25669, 4545, 32, 5, 49.90, 370, 370),
(25670, 4545, 32, 6, 34.90, 250, 250),
(25671, 4545, 32, 7, 24.90, 100, 100),
(25672, 4546, 32, 4, 79.90, 180, 180),
(25673, 4546, 32, 5, 49.90, 370, 370),
(25674, 4546, 32, 6, 34.90, 250, 250),
(25675, 4546, 32, 7, 24.90, 100, 100),
(25676, 4547, 32, 4, 79.90, 180, 180),
(25677, 4547, 32, 5, 49.90, 370, 370),
(25678, 4547, 32, 6, 34.90, 250, 250),
(25679, 4547, 32, 7, 24.90, 100, 100),
(25680, 4548, 33, 12, 95.00, 320, 320),
(25681, 4548, 33, 13, 120.00, 180, 180),
(25682, 4548, 33, 14, 85.00, 150, 150),
(25683, 4548, 33, 15, 55.00, 300, 300),
(25684, 4549, 34, 16, 70.00, 260, 260),
(25685, 4549, 34, 17, 85.00, 140, 140),
(25686, 4549, 34, 18, 45.00, 300, 300),
(25687, 4550, 34, 16, 70.00, 260, 260),
(25688, 4550, 34, 17, 85.00, 140, 140),
(25689, 4550, 34, 18, 45.00, 300, 300),
(25690, 4551, 35, 22, 89.90, 120, 120),
(25691, 4551, 35, 23, 59.90, 230, 230),
(25692, 4551, 35, 24, 35.00, 400, 400),
(25693, 4551, 35, 25, 22.00, 150, 150),
(25694, 4552, 35, 22, 89.90, 120, 120),
(25695, 4552, 35, 23, 59.90, 230, 230),
(25696, 4552, 35, 24, 35.00, 400, 400),
(25697, 4552, 35, 25, 22.00, 150, 150),
(25698, 4553, 35, 22, 89.90, 120, 120),
(25699, 4553, 35, 23, 59.90, 230, 230),
(25700, 4553, 35, 24, 35.00, 400, 400),
(25701, 4553, 35, 25, 22.00, 150, 150);

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
(1, 'Easy Club Roma', 'Concerto', 'Roma', 'Via della Musica 10', 350),
(2, 'Easy Hall Torino', 'Concerto', 'Torino', 'Corso Rock 22', 900),
(3, 'Easy Arena Indoor', 'Concerto', 'Milano', 'Viale dei Concerti 15', 850),
(4, 'Teatro alla Scala', 'Teatro', 'Milano', 'Via Filodrammatici 2', 950),
(5, 'Teatro Argentina', 'Teatro', 'Roma', 'Largo di Torre Argentina 52', 700),
(6, 'Easy Comedy Theatre', 'Teatro', 'Roma', 'Via delle Commedie 5', 600),
(7, 'Easy Summer Park', 'Festival', 'Roma', 'Via del Festival 7', 900),
(8, 'Easy Street District', 'Festival', 'Milano', 'Zona Murales 18', 800),
(9, 'Easy Food Village', 'Festival', 'Napoli', 'Viale del Villaggio 21', 750);

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

--
-- Dump dei dati per la tabella `notifica`
--

INSERT INTO `notifica` (`id`, `id_utente`, `titolo`, `messaggio`, `data_creazione`, `letta`) VALUES
(1, 4, 'Rimborso automatico effettuato', 'L\'evento annullato ha generato un rimborso automatico di € 120,00 sul tuo wallet.', '2026-05-25 21:44:31', 0),
(2, 4, 'Rimborso automatico effettuato', 'L\'evento annullato ha generato un rimborso automatico di € 120,00 sul tuo wallet.', '2026-05-25 21:44:31', 0),
(3, 4, 'Rimborso automatico effettuato', 'L\'evento annullato ha generato un rimborso automatico di € 28,00 sul tuo wallet.', '2026-05-25 21:44:31', 0),
(4, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(5, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(6, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(7, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(8, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(9, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(10, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(11, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(12, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(13, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(14, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 25,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(15, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 30,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(16, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 30,00 sul tuo wallet.', '2026-05-26 11:19:04', 0),
(17, 4, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 30,00 sul tuo wallet.', '2026-05-26 14:10:23', 0),
(18, 4, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-26 16:11:40', 0),
(19, 4, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-26 16:11:40', 0),
(20, 4, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-26 16:11:40', 0),
(21, 4, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-26 16:13:08', 0),
(22, 4, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-26 16:13:08', 0),
(23, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-27 17:55:44', 0),
(24, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-27 17:55:44', 0),
(25, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 34,90 sul tuo wallet.', '2026-05-27 17:55:44', 0),
(26, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 34,90 sul tuo wallet.', '2026-05-27 17:55:44', 0),
(27, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 49,90 sul tuo wallet.', '2026-05-27 17:55:44', 0),
(28, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 49,90 sul tuo wallet.', '2026-05-27 17:55:44', 0),
(29, 5, 'Rimborso automatico effettuato', 'L\'evento annullato ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-27 19:00:29', 0),
(30, 5, 'Rimborso automatico effettuato', 'L\'evento annullato ha generato un rimborso automatico di € 24,90 sul tuo wallet.', '2026-05-27 19:00:29', 0),
(31, 5, 'Rimborso automatico effettuato', 'L\'evento annullato ha generato un rimborso automatico di € 34,90 sul tuo wallet.', '2026-05-27 20:03:42', 0),
(32, 5, 'Rimborso automatico effettuato', 'L\'evento annullato ha generato un rimborso automatico di € 34,90 sul tuo wallet.', '2026-05-27 20:03:42', 0),
(33, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:04:54', 0),
(34, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:10:57', 0),
(35, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:10:57', 0),
(36, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:18:29', 0),
(37, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:18:29', 0),
(38, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:18:29', 0),
(39, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:29:03', 0),
(40, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:29:03', 0),
(41, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:29:03', 0),
(42, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:33:53', 0),
(43, 5, 'Rimborso automatico effettuato', 'La replica annullata ha generato un rimborso automatico di € 19,90 sul tuo wallet.', '2026-05-28 22:33:53', 0);

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
(4543, 31, '2026-06-14 21:00:00', NULL, 'programmata'),
(4544, 31, '2026-06-15 21:00:00', NULL, 'programmata'),
(4545, 32, '2026-06-20 21:30:00', NULL, 'programmata'),
(4546, 32, '2026-06-21 21:30:00', NULL, 'programmata'),
(4547, 32, '2026-06-22 21:30:00', NULL, 'programmata'),
(4548, 33, '2026-06-18 20:30:00', NULL, 'programmata'),
(4549, 34, '2026-06-25 20:45:00', NULL, 'programmata'),
(4550, 34, '2026-06-26 20:45:00', NULL, 'programmata'),
(4551, 35, '2026-07-03 18:00:00', NULL, 'programmata'),
(4552, 35, '2026-07-04 18:00:00', NULL, 'programmata'),
(4553, 35, '2026-07-05 18:00:00', NULL, 'programmata'),
(4554, 35, '2026-07-06 18:00:00', NULL, 'programmata'),
(4555, 35, '2026-07-07 18:00:00', NULL, 'programmata');

-- --------------------------------------------------------

--
-- Struttura della tabella `rimborsi`
--

CREATE TABLE `rimborsi` (
  `id` int NOT NULL,
  `id_biglietto` int NOT NULL,
  `id_utente` int NOT NULL,
  `importo` decimal(10,2) NOT NULL,
  `data_richiesta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_elaborazione` datetime DEFAULT NULL,
  `stato` enum('pending','completato','fallito') NOT NULL DEFAULT 'pending',
  `motivo` varchar(255) DEFAULT 'Replica eliminata da admin',
  `note` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(1, 'Pit Roma Club', 'Area sotto palco in piedi o posti ravvicinati', 1, 49.90, 120),
(2, 'Parterre Roma Club', 'Area centrale standard', 1, 34.90, 150),
(3, 'Balconata Roma Club', 'Posti rialzati laterali e centrali', 1, 24.90, 80),
(4, 'Pit Gold Torino Hall', 'Area premium fronte palco', 2, 79.90, 180),
(5, 'Parterre Torino Hall', 'Area centrale evento', 2, 49.90, 370),
(6, 'Tribuna Torino Hall', 'Posti numerati in gradinata', 2, 34.90, 250),
(7, 'Galleria Torino Hall', 'Settore alto a visibilità ridotta', 2, 24.90, 100),
(8, 'Pit Gold Arena Indoor', 'Area premium vicino al palco', 3, 74.90, 170),
(9, 'Parterre Arena Indoor', 'Area centrale standard', 3, 44.90, 330),
(10, 'Tribuna Est Arena', 'Tribuna laterale est', 3, 32.90, 180),
(11, 'Tribuna Ovest Arena', 'Tribuna laterale ovest', 3, 32.90, 170),
(12, 'Platea Scala', 'Posti centrali e ravvicinati al palco', 4, 95.00, 320),
(13, 'Palchi Centrali Scala', 'Palchi con visuale privilegiata', 4, 120.00, 180),
(14, 'Palchi Laterali Scala', 'Palchi laterali a visibilità buona', 4, 85.00, 150),
(15, 'Galleria Scala', 'Settore superiore', 4, 55.00, 300),
(16, 'Platea Argentina', 'Settore principale fronte palco', 5, 70.00, 260),
(17, 'Palchi Argentina', 'Palchi laterali e centrali', 5, 85.00, 140),
(18, 'Galleria Argentina', 'Settore superiore standard', 5, 45.00, 300),
(19, 'Platea Premium Comedy', 'Prime file e zona centrale', 6, 55.00, 180),
(20, 'Platea Standard Comedy', 'Posti standard in sala', 6, 38.00, 240),
(21, 'Galleria Comedy', 'Posti rialzati', 6, 25.00, 180),
(22, 'VIP Garden Summer Park', 'Area premium con servizi dedicati', 7, 89.90, 120),
(23, 'Front Stage Summer Park', 'Area vicina al palco principale', 7, 59.90, 230),
(24, 'Area Festival Summer Park', 'Ingresso standard all area evento', 7, 35.00, 400),
(25, 'Relax Zone Summer Park', 'Area laterale con posti più tranquilli', 7, 22.00, 150),
(26, 'VIP Pass Street District', 'Accesso premium con visuale migliore', 8, 79.90, 100),
(27, 'Main Area Street District', 'Area principale del festival', 8, 39.90, 420),
(28, 'Street Lounge District', 'Zona relax laterale', 8, 24.90, 160),
(29, 'Urban Terrace District', 'Zona rialzata o panoramica', 8, 29.90, 120),
(30, 'VIP Experience Food Village', 'Area premium con accessi riservati', 9, 69.90, 90),
(31, 'Main Village Food', 'Area centrale del festival', 9, 34.90, 360),
(32, 'Taste Lounge Food', 'Zona tavoli e degustazioni', 9, 44.90, 140),
(33, 'Open Area Food', 'Settore standard a capienza ampia', 9, 19.90, 160);

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
(3, 'Riccardo', 'Pucci', '2002-09-01', 'lalala', '$2y$10$/YWWwtkfv6gy0Fy8C2sSkOYJGKdqXarwmtnLJBHwFxzquPf4y/3lW', 2, 7830.00),
(4, 'sa', 'sasa', '2002-09-01', 'sasasa', '$2y$10$OUyGFOTXLVlaYVCJ4h0.8eewSATBXN655zlMlt95Y/GDZ4cQie9Le', 2, 10245.20),
(5, 'Riccardo', 'Pucci', '2002-09-01', 'Pucc1994172', '$2y$10$WLQWxPiBcWTY7OfSH1TsS.C3Aro61dKLb9t93zIWZ0MQWEZcFKYqm', 2, 2015.30),
(6, '\'\';DELETE * FROM utente', 'A', '1911-01-01', 'cancellino', '$2y$10$FFXBrGT1UTmpGYYBzHcJ7uUHBdhZGWPvfR6gV2OT48qbzJvMAfOvy', 2, 3000.00);

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
-- Indici per le tabelle `rimborsi`
--
ALTER TABLE `rimborsi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rimborso_biglietto` (`id_biglietto`),
  ADD KEY `idx_rimborso_utente` (`id_utente`),
  ADD KEY `idx_rimborso_stato` (`stato`);

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
  ADD UNIQUE KEY `uk_settore_luogo_nome` (`id_luogo`,`nome`),
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT per la tabella `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `evento`
--
ALTER TABLE `evento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT per la tabella `evento_settore`
--
ALTER TABLE `evento_settore`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25718;

--
-- AUTO_INCREMENT per la tabella `luogo`
--
ALTER TABLE `luogo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT per la tabella `notifica`
--
ALTER TABLE `notifica`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT per la tabella `replica_evento`
--
ALTER TABLE `replica_evento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4560;

--
-- AUTO_INCREMENT per la tabella `rimborsi`
--
ALTER TABLE `rimborsi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT per la tabella `ruolo`
--
ALTER TABLE `ruolo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `settore`
--
ALTER TABLE `settore`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Limiti per la tabella `rimborsi`
--
ALTER TABLE `rimborsi`
  ADD CONSTRAINT `rimborsi_ibfk_1` FOREIGN KEY (`id_biglietto`) REFERENCES `biglietto` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rimborsi_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

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
