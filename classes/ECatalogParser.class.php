<?php
/**
 * E-Catalog-Parser
 *
 * A domain model class for parsing of e-catalogs.
 * It is designed to work in association with the ContentPuller and CatalogDataAccess.
 * The class implements a major portion of a domain logic of the application.
 * Handles the flow of execution and provides the current state of query process.
 *
 * @author Vitali Makovijchuk
 * @version 0.1
 * @package E-Catalog-Parser
 */

class ECatalog_Parser
{

    const RM_INVALIDE_QUERY  = "The parameter must contain only letters and numbers.\n
                                Max length is 30 characters and 4 words";
    const RM_NO_ITEMS        = 'There are no such items available';
    const RM_DONE            = 'Done';

    private static $instance;
    private $responseMassage;
	protected static $content_puller;
    protected static $catalog_data_access;


    private function __clone() {}
    private	function __construct()	{}

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
     * getResponseMessage
     *
     * @return string $responseMassage
     */
    function getResponseMessage()
    {
        return $this->responseMassage;
    }

    /**
     * Validates query string
     *
     * Makes sure that the request appears valid.
     * @param string $query
     * @return boolean
     */
    private function validateQuery($query)
    {
        $query = trim($query);
        // the query ain't to be more than 4 words and 30 characters in length.
        if(!preg_match('#^[a-zA-Z0-9_ ]*$#i', $query)||strlen($query)>30||strlen($query)<3||str_word_count($query)>4)
            return false;
        return true;
    }

    /**
     * Process query
     *
     * @param string $query user's query
     */
    function processQuery($query)
    {
        if ($this->validateQuery($query)){
            $this->responseMassage = self::RM_INVALIDE_QUERY;
            return;
        }
        if(!$items = $this->content_puller->pullItemsOnRequest($query)){
            $this->responseMassage = self::RM_NO_ITEMS;
            return;
        }
		$this->catalog_data_access->save($query, $this->content_puller->getSoughtCategory(), $items);
        $this->responseMassage = self::RM_DONE;
    }

}
?>