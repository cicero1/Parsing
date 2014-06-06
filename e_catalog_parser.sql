-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 26, 2014 at 03:07 PM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `e_catalog_parser`
--
CREATE DATABASE IF NOT EXISTS `e_catalog_parser` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `e_catalog_parser`;

-- --------------------------------------------------------

--
-- Table structure for table `parsed_items`
--

CREATE TABLE IF NOT EXISTS `parsed_items` (
  `name` varchar(40) NOT NULL,
  `model` varchar(80) NOT NULL,
  `price` int(6) unsigned NOT NULL,
  `description_url` varchar(80) NOT NULL,
  `parsing_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`parsing_id`,`model`),
  KEY `name` (`name`,`price`),
  KEY `id_idx` (`parsing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `parsing_acts`
--

CREATE TABLE IF NOT EXISTS `parsing_acts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(31) NOT NULL,
  `response_data` varchar(70) DEFAULT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `parsed_items`
--
ALTER TABLE `parsed_items`
  ADD CONSTRAINT `id` FOREIGN KEY (`parsing_id`) REFERENCES `parsing_acts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
