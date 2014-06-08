<?php
namespace VMParsing;
//   General
const STORE_FAILED_REQUESTS = true;  // whether to save failed request data to a database
const OUTPUT_ENCODING = 'cp1251';    // .csv files encoding; should be compatible with your system
const CSV_DIR = 'C:/wamp/www/e-catalog-parser/data/'; // .csv files output directory, fully qualified path on MySQL Server machine

//   Database settings (MySQL)
const DB_DSN='mysql:dbname=e_catalog_parser;host=127.0.0.1;charset=UTF8';
const DB_USER='root';
const DB_PASS='';

//   E-catalog-specific settings