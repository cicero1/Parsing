<?php
//error_reporting(E_ALL | E_STRICT);
if(!defined('STDIN'))     // checks that the script is running in the CLI mode
   exit("This script should be executed via the CLI");

require_once("config.php");
require_once("classes\CatalogDataAccess.class.php");
require_once("classes\ContentPuller.class.php");
require_once("classes\ECatalogParser.class.php");

$parser = ECatalog_Parser::getInstance( new Rozetka_Content_Puller(),
                                        new Catalog_Data_Access()    );


$stdin = fopen("php://stdin", "r");
do  {
        fputs($stdin, $parser->getResponseMessage());
        $query = fgets($stdin);
        $parser->processQuery($query);
    }while(($parser->getResponseMessage() !== "Done"));
fclose($stdin);

?>