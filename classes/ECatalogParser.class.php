<?php
/**
 * E-Catalog-Parser
 *
 * A domain model class for parsing of e-catalogs.
 * It is designed to work in association with the ContentPuller and CatalogDataAccess.
 * The class implements a major portion of a domain logic of the application.
 * Handles the flow of execution and provides information of the current state of query process.
 *
 * @author Vitali Makovijchuk
 * @version 0.3
 * @package VMParsing
 */
namespace VMParsing;

class ECatalogParser
{
    private static $instance;
    protected static $current_state = 'START';

    protected static $content_puller;
    protected static $catalog_data_access;

    protected static $items_name;
    protected static $items_number;

    private function __clone() {}
    private function __construct()
    {
        setlocale(LC_ALL, 'Ukrainian_Ukraine');
        mb_internal_encoding('utf8');
    }

    /**
     * getInstance
     *
     * @param ContentPuller
     * @param CatalogDataAccess
     * @return ECatalogParser instance;
     */
    static function getInstance(ContentPuller $cp, CatalogDataAccess $cda)
    {
        if(!isset(self::$instance)){
            self::$instance = new ECatalogParser();
            self::$content_puller = $cp ;
            self::$catalog_data_access = $cda;
        }
        return self::$instance;
    }
    /**
     * Validates query string
     *
     * Makes sure that the request appears valid.
     * @param string $query
     * @return boolean
     */
    static private function validateQuery($query)
    {
        $query = trim($query);
        // the query ain't to be more than 30 characters (alphanumeric) in length.
        if(!preg_match('#^[-a-zа-яё0-9 ]*$#iu', $query)
            ||mb_strlen($query)>30||mb_strlen($query)<3)
            return false;
        return true;
    }
    /**
     * Processes query
     *
     * @param string $query user's query
     * @return mixed
     *         a number of items fetched on success
     *         false on failure
     */
    function processQuery($query)
    {
        $query = trim($query);
        if (!$this->validateQuery($query)){
            self::$current_state = 'INVALID_Q';
            return false;
        }
        // the very parsing is going on here
        $items  = self::$content_puller->pullItemsOnRequest($query);
        self::$items_number = count($items);
        // if we get no items save only the failure response
        if(!(is_array($items)&&self::$items_number)){
            self::$current_state = 'NO_ITEMS';
            if(STORE_FAILED_REQUESTS)
                self::$catalog_data_access->save($query, self::getResponseMsg());
            return false;
        }
        // otherwise store results to DB and create CSV file
        self::$current_state = 'DONE';
        self::$items_name = self::$content_puller->getSoughtItemsName();
        self::$catalog_data_access->save($query, self::getResponseMsg(), self::$items_name, $items);
        self::$catalog_data_access->exportCsv($query);

        return self::$items_number;
    }
    /**
     * Retrieves the response string
     *
     * @return string
     */
    function getResponseMsg()
    {
        $msg = array('START'     => 'Enter a product name:',
                     'NO_ITEMS'  => 'There are no such items available.',
                     'INVALID_Q' => 'The query must contain only letters and digits (3 through 30).',
                     'DONE'      => '{NUM} items from "{NAME}" fetched.');

        if (self::$current_state == 'DONE'){
            $msg[self::$current_state] = str_replace('{NAME}', self::$items_name, $msg[self::$current_state]);
            $msg[self::$current_state] = str_replace('{NUM}',  self::$items_number, $msg[self::$current_state]);
        }
        return $msg[self::$current_state];
    }
}
?>
