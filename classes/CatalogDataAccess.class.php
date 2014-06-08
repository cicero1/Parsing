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
//        self::$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec('SET NAMES utf8');
    }

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
        $id  = self::$pdo->lastInsertId();
        $stm = self::$pdo->prepare('INSERT INTO `parsed_items`
            (name, model, price, description_url, parsing_id)
            VALUES (?, ?, ?, ?, ?);');
        foreach ($itemset as $item)
            $stm->execute(array($name, $item['model'], $item['price'], $item['description_url'], $id));
    }

    /**
     * exportCsv
     *
     * Creates the .csv file containing items have been obtained during the last successful parsing.
     * Items are ordered by price value.
     * @param string $query
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

    function getItem() {}  //TODO
    function dropOutdated() {} //TODO
}