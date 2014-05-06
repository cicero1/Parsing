<?php
/**
 * CatalogDataAccess
 *
 * @author Vitali Makovijchuk
 * @package E-Catalog-Parser
 */

class Catalog_Data_Access
{
    /**
     * @staticvar PDO The database connection object.
     */
    protected static $PDO;

    function __construct()
    {
        $this->PDO = new PDO(DB_DSN, DB_USER, DB_PASS);
        //$this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeDbase();
    }

    /**
     * initializeDbase
     *
     * Builds up a general structure of data base by creating tables and their references.
     */
    private function initializeDbase()
    {
        $q_init ='
  CREATE DATABASE IF NOT EXISTS `e_catalog_parser`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
  USE e_catalog_parser;

  CREATE TABLE IF NOT EXISTS `parsing_acts`
  (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(31) NOT NULL,
  `date` date NOT NULL,
   PRIMARY KEY (`id`));
   CREATE TABLE IF NOT EXISTS `parsed_items` (
  `name` varchar(40) NOT NULL,
  `model` varchar(80) NOT NULL,
  `price` int(6) unsigned NOT NULL,
  `detailed_description_url` varchar(80) NOT NULL,
  `parsing_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`parsing_id`,`model`),
  KEY `name` (`name`,`price`),
  KEY `id_idx` (`parsing_id`),
  CONSTRAINT `id` FOREIGN KEY (`parsing_id`) REFERENCES `parsing_acts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE);';
        $this->PDO->exec($q_init);
    }

    /**
     * Save
     *
     * Saves results of new parsing process.
     *
     * @param string $query user query (e.g. 'фотоаппарат')
     * @param string $goodsname name of goods as they are recorded in the catalog (e.g. 'Фотоаппараты')
     * @param array $itemset items have been parsed passed in as 2d array
     */
    function save($query, $goodsname, array $itemset)
    {
        $this->PDO->exec('INSERT INTO `e_catalog_parsing`.`parsing_acts`
        VALUES ( NULL, '.$query.', current_date());');
        $id=$this->PDO->lastInsertId();
        $stm=$this->PDO->prepare('INSERT INTO `e_catalog_parsing`.`parsed_items`
        (`name`, `model`, `price`, `detailed_description_url`, `parsing_id`)
        VALUES (:name, :model, :price, `description_url`, :parsing_id)');
        foreach($itemset as $item)
            $stm->execute(array($goodsname, $item['model'], $item['price'], $item['description_url'], $id));
    }

    /**
     * exportCsv
     *
     * Creates the .csv file containing items have been obtained during the last successful parsing.
     * Items are ordered by price value.
     */
    function exportCsv()
    {
        if(file_exists($pathname=CVS_FILE)) {
            $pparts = pathinfo(CVS_FILE);
            for($i=1; file_exists($pathname); $i++)
                $pathname=$pparts['dirname'].'/'.$pparts['filename'].$i.'.'.$pparts['extension'];
        }

        $this->PDO->exec("DELIMITER |
        SELECT `name`, `model`, `price`, `detailed_description_url`
        INTO OUTFILE '.$pathname.'
        FIELDS TERMINATED BY ';'
        LINES TERMINATED BY '\n'
        FROM `parsed_items`
        WHERE parsing_id=(SELECT max(parsing_id) FROM `parsed_items`)
        ORDER BY `price`|
        DELIMITER ;");
    }

    //TODO
    function getItemByID(){}
    function dropOutdated(){}
}

?>