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
 * @version 0.2
 * @package E-Catalog-Parser
 */

class ECatalog_Parser
{
    private static $instance;
    protected static $content_puller;
    protected static $catalog_data_access;
    protected static $current_state = 'START';

    protected static $items_name;
    protected static $items_number;

    private function __clone() {}
    private	function __construct()
    {
        setlocale(LC_ALL, 'Ukrainian_Ukraine');
        mb_internal_encoding('utf8');
    }

    /**
     * getInstance
     *
     * @param Content_Puller
     * @param Catalog_Data_Access
     * @return ECatalog_Parser $instance;
     */
    static function getInstance(Content_Puller $cp, Catalog_Data_Access $cda)
    {
        if(!isset(self::$instance)){
            self::$instance = new ECatalog_Parser();
            self::$content_puller = $cp ;
            self::$catalog_data_access = $cda;
        }
        return self::$instance;
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

        $items  = self::$content_puller->pullItemsOnRequest($query);
        self::$items_number = count($items);
        if(!(is_array($items)&&self::$items_number)){
            self::$current_state = 'NO_ITEMS';
            if(STORE_FAILED_REQUESTS)
                self::$catalog_data_access->save($query, self::getResponseMsg());
            return false;
        }

        self::$current_state = 'DONE';
        self::$items_name = self::$content_puller->getSoughtItemsName();
        self::$catalog_data_access->save($query, self::getResponseMsg(), self::$items_name, $items);
        self::$catalog_data_access->exportCsv(self::getFileName($query));

        return self::$items_number;
    }
    /**
     * Forms the response string
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
        // the query ain't to be more than 30 characters in length.
        if(!preg_match('#^[-a-zа-яё0-9 ]*$#iu', $query)
            ||mb_strlen($query)>30||mb_strlen($query)<3)
            return false;
        return true;
    }
    /**
     * Makes up a filename for a csv file
     *
     * @param string $query
     * @return string
     */
    static private function getFileName($query)
    {
        $name = CSV_DIR.mb_convert_encoding(trim($query), OUTPUT_ENCODING).date(' d.m.y H-i-s');
        // if there is already such a file append a number
        $num = '';
        for($i=1; file_exists($name.$num.'.csv'); $i++)
            $num = sprintf('(%02d)',$i);
        return $name.$num.'.csv';
    }
}
?>
