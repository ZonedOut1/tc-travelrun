-- phpMyAdmin SQL Dump
-- version 2.11.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 02, 2013 at 05:29 AM
-- Server version: 5.1.57
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: '<dbname>'
--

-- --------------------------------------------------------

--
-- Table structure for table 'lastdrugs'
--

CREATE VIEW lastdrugs AS
  select stock.item AS item,
         stock.country AS country,
         max(stock.utctime) AS lastutc
  from stock
  where stock.item in (
    select item.itemid
    from item
    where (item.itemtype = 3)
  )
  group by stock.item, stock.country;

-- --------------------------------------------------------

--
-- Table structure for table 'lastflowers'
--

CREATE VIEW lastflowers AS
  select stock.item AS item,
         stock.country AS country,
         max(stock.utctime) AS lastutc
  from stock
  where stock.item in (260, 617, 263, 264, 267, 271, 272, 277, 276, 385, 282)
  group by stock.item, stock.country;


-- --------------------------------------------------------

--
-- Table structure for table 'lastplushies'
--

CREATE VIEW lastplushies AS
  select stock.item AS item,
         stock.country AS country,
         max(stock.utctime) AS lastutc
  from stock
  where stock.item in (186, 187, 215, 258, 261, 266, 268, 269, 273, 274, 281, 384, 618)
  group by stock.item, stock.country;

