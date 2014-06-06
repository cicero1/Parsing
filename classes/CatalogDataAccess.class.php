<?php
/**
 * CatalogDataAccess
 *
 * @author Vitali Makovijchuk
 * @package VMParsing
 */
namespace VMParsing;
class CatalogDataAccess {

    /**
     * @staticvar PDO The database connection object.
     */
    protected static $pdo;

    function __construct() {
        self::$pdo = new \PDO(DB_DSN, DB_USER, DB_PASS);
//        $this->initializeDbase();
//        self::$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec('SET NAMES utf8');
    }

    /**
     * initializeDBase
     *
     * Builds up a general structure of data base by creating tables and their references.

    private function initializeDBase() {
        $q_init = '
  CREATE DATABASE IF NOT EXISTS `e_catalog_parser`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
  USE e_catalog_parser;

  CREATE TABLE IF NOT EXISTS `parsing_acts`
  (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(31) NOT NULL,
  `response_data` varchar(75),
  `date` date NOT NULL,
   PRIMARY KEY (`id`));
   CREATE TABLE IF NOT EXISTS `parsed_items` (
  `name` varchar(40) NOT NULL,
  `model` varchar(80) NOT NULL,
  `price` int(6) unsigned NOT NULL,
  `description_url` varchar(80) NOT NULL,
  `parsing_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`parsing_id`,`model`),
  KEY `name` (`name`,`price`),
  KEY `id_idx` (`parsing_id`),
  CONSTRAINT `id` FOREIGN KEY (`parsing_id`) REFERENCES `parsing_acts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE);';
        self::$PDO->exec($q_init);
    }
*/

    /**
     * Saves results of new parsing process.
     *
     * @param string $query user query (e.g. 'фотоаппарат')
     * @param string $data parsing data
     * @param string $name name of products as they are recorded in the catalog (e.g. 'Фотоаппараты')
     * @param array $itemset items have been parsed passed in as 2d array
     */
    function save($query, $data, $name = null, $itemset = array()) {
        self::$pdo->exec("INSERT INTO `parsing_acts`
            VALUES ( NULL, '$query', '$data', current_date());");
        $id = self::$pdo->lastInsertId();
        $stm = self::$pdo->prepare('INSERT INTO `parsed_items`
            (name, model, price, description_url, parsing_id)
            VALUES (?, ?, ?, ?, ?);');
        foreach ($itemset as $item)
            $stm->execute(array($name, $item['model'], $item['price'], $item['description_url'], $id));
    }
    /**
     * Makes up a filename for a csv file
     *
     * @param string $query
     * @return string
     */
    private function getFileName($query)
    {
        $name = CSV_DIR.mb_convert_encoding(trim($query), OUTPUT_ENCODING).date(' d.m.y H-i-s');
        // if there is already such a file append a number
        $num = '';
        for($i=1; file_exists($name.$num.'.csv'); $i++)
            $num = sprintf('(%02d)',$i);
        return $name.$num.'.csv';
    }
    /**
     * exportCsv
     *
     * Creates the .csv file containing items have been obtained during the last successful parsing.
     * Items are ordered by price value.
     */
    function exportCsv($query) {
        self::$pdo->exec("SELECT `name` , `model` , `price` , `description_url`
        INTO OUTFILE '".$this->getFileName($query)."'
        CHARACTER SET ".OUTPUT_ENCODING."
        FIELDS TERMINATED BY ';'
        LINES TERMINATED BY '\n'
        FROM `parsed_items`
        WHERE parsing_id = (SELECT max( parsing_id )
                            FROM `parsed_items` )
        ORDER BY `price`");
    }


    function getItem() {}  //TODO
    function dropOutdated() {} //TODO
}