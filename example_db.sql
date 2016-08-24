-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Creato il: Ago 24, 2016 alle 16:55
-- Versione del server: 10.1.13-MariaDB
-- Versione PHP: 7.0.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `brands`
--

INSERT INTO `brands` (`id`, `name`) VALUES
(1, 'opel'),
(2, 'mercedes');

-- --------------------------------------------------------

--
-- Struttura della tabella `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brands_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `cars`
--

INSERT INTO `cars` (`id`, `name`, `brands_id`) VALUES
(3, 'astra', 1),
(4, 'panda', 2),
(5, '500', 1),
(6, 'slk', 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `cars_colors`
--

CREATE TABLE `cars_colors` (
  `cars_id` int(11) NOT NULL,
  `colors_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `cars_colors`
--

INSERT INTO `cars_colors` (`cars_id`, `colors_id`) VALUES
(3, 1),
(5, 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `cars_extras`
--

CREATE TABLE `cars_extras` (
  `cars_id` int(11) NOT NULL,
  `extras_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `cars_extras`
--

INSERT INTO `cars_extras` (`cars_id`, `extras_id`) VALUES
(3, 1),
(4, 3),
(5, 1),
(6, 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `colors`
--

CREATE TABLE `colors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `colors`
--

INSERT INTO `colors` (`id`, `name`) VALUES
(1, 'red'),
(2, 'blue');

-- --------------------------------------------------------

--
-- Struttura della tabella `extras`
--

CREATE TABLE `extras` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `extras`
--

INSERT INTO `extras` (`id`, `name`) VALUES
(1, 'clima'),
(2, 'abs'),
(3, 'carbon'),
(4, 'hifi');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `brands_id` (`brands_id`);

--
-- Indici per le tabelle `cars_colors`
--
ALTER TABLE `cars_colors`
  ADD KEY `cars_id` (`cars_id`,`colors_id`),
  ADD KEY `colors_id` (`colors_id`);

--
-- Indici per le tabelle `cars_extras`
--
ALTER TABLE `cars_extras`
  ADD UNIQUE KEY `cars_id` (`cars_id`,`extras_id`),
  ADD KEY `extras_id` (`extras_id`);

--
-- Indici per le tabelle `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `extras`
--
ALTER TABLE `extras`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT per la tabella `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT per la tabella `colors`
--
ALTER TABLE `colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT per la tabella `extras`
--
ALTER TABLE `extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`brands_id`) REFERENCES `brands` (`id`);

--
-- Limiti per la tabella `cars_colors`
--
ALTER TABLE `cars_colors`
  ADD CONSTRAINT `cars_colors_ibfk_1` FOREIGN KEY (`cars_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `cars_colors_ibfk_2` FOREIGN KEY (`colors_id`) REFERENCES `colors` (`id`);

--
-- Limiti per la tabella `cars_extras`
--
ALTER TABLE `cars_extras`
  ADD CONSTRAINT `cars_extras_ibfk_1` FOREIGN KEY (`cars_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `cars_extras_ibfk_2` FOREIGN KEY (`extras_id`) REFERENCES `extras` (`id`);
