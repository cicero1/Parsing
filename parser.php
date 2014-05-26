<?php
//error_reporting(E_ALL | E_STRICT);
if(!defined('STDIN'))     // check that the script is running in the CLI mode
   exit("This script should be executed via the CLI");

require_once('config.php');
require_once('classes\CatalogDataAccess.class.php');
require_once('classes\ContentPuller.class.php');
require_once('classes\ECatalogParser.class.php');

$parser = ECatalog_Parser::getInstance( new Rozetka_Content_Puller(),
                                        new Catalog_Data_Access());

if($argc > 1)
    $parser->processQuery(implode(' ',array_slice($argv, 1, 5))); // take only 4 words
else{
    echo 'enter a product name:';
    $parser->processQuery(fgets(STDIN));
}
echo $parser->getResponseMsg();