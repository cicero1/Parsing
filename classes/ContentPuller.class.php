<?php
/**
 * Content Puller
 *
 * Pulls the content from a site utilizing CURL; analyzes and parses
 * it with the help of regular expressions; eventually, retrieves needed
 * data from it.
 * @author Vitali Makovijchuk
 * @package VMParsing
 */
namespace VMParsing;
abstract class ContentPuller
{
    protected $_page_html;
    protected $request;

    function __constructor()  {}

    /**
     * Pulls items from a web site according to user request
     */
    abstract function pullItemsOnRequest($request);

    /**
     * Gets a items name as they are identified on a web site
     */
    abstract function getSoughtItemsName();

    /**
     * getPage
     *
     * Makes a GET request using cURL
     *
     * @param string $url of a page
     * @return string $html -source of a page fetched
     */
    protected function getPage($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'E-catalog-Parser');
        $html = curl_exec($ch);
        //$this->page_html = $html;
        curl_close($ch);
        return $html;
    }

    /**
     * fetchElementsFromPage
     *
     * Simplifies capturing elements from a webpage utilizing regexes
     * (PCRE) with named capturing groups
     *
     * @param string $url of a page
     * @param string $pattern
     *        a regex to be matched; has to contain named capturing groups like
     *        that (?<name>subpattern)
     * @return mixed $out
     *    in case of sole match returns an array of all capturing groups: $out[groupname1], $out[groupname2]...;
     *    in case of multiple matches returns 2d array of strings ordered so that $out[0] contains
     *    the first set of matches: $out[0][name1], $out[0][name2], $out[1] contains the second one, and so on...;
     *    otherwise returns NULL
     */
    protected function fetchElementsFromPage($url, $pattern)
    {
        $source = $this->getPage($url);
        $out = array();
        preg_match_all($pattern, $source, $out, PREG_SET_ORDER );
        foreach ($out as &$matches) {
            unset($matches[0]);                 // the matches of the root patterns are of no use
            $matches = array_unique($matches);
        }

        if (count($out) == 1)     // if there is a sole match extract the only result array member
            $out = array_shift($out);
        return $out;
    }

//    protected function fetchElement($pattern){} // TODO
//    protected function fetchVector($pattern, $number, $offset) // TODO
}

/**
 * Rozetka Content Puller
 *
 * This subclass "knows" about the Rozetka.com.ua site
 * structure and implements the logic of data pulling.
 * @author Vitali Makovijchuk
 */
class RozetkaContentPuller extends ContentPuller
{
    protected $categories = array();          // categories of the e-catalog as sets of their descriptions
    protected $s_category = array();          // sought category
    protected $items      = array();          // items of goods parsed

const SEARCH_URL = 'http://rozetka.com.ua/search/?section=%2F&text=';

const RE_PAGE_TYPE         = '#pageType\":\"(?<page_type>.*?)\"#s';
const RE_SEARCH_CATEGORIES = '#subl-i\".*?ref=\"(?<search_url>.*?)\".*?i-link\">(?<name>.*?)<.*?count\">\((?<found>\d+)\).*?subl#s';
const RE_CATALOG_CATEGORY  = '#<h1>\s*(?<name>.*?)\s*<.*?(?<root_url>[^\"]*)view=list#s';
const RE_PORTAL_CATEGORY   = '#<h1>\s*(?<name>.*?)\s*<.*?-href.+?\"(?<root_url>.*?)\"#s';
const RE_ROOT_URL       = '#count-catalog.*?ref=\"(?<root_url>.*?)\"#s';
const RE_CATEGORY_QTY   = '#QuantityTotal\":\"(?<total>\d+).*?:\"(?<available>\d+)#s';
const RE_PAGINATION     = '#>(?<pages_number>\d+)<\/a[^a]*?<\/ul#s';
const RE_CATEGORY_ITEMS = '#st-title.*?ref=\"(?<description_url>[^\"]*?)\".*?>(?<model>.*?)\v.*?-status (?:available|limited)\".*?-uah\">(?<price>.*?)<#s';

    function __construct()
    {
        parent::__constructor();
    }

    /**
     * Retrieves Rozetka's category of products name previously determined
     *
     * @return string a category of goods name as it is recorded in the e-catalog
     */
    function getSoughtItemsName()
    {
        return $this->s_category['name'];
    }

    /**
     * Pulls items from Rozetka.com.ua according to user request
     *
     * @param string $request
     * @return mixed
     *         a list of items in a predefined format on success or FALSE on failure
     */
    function pullItemsOnRequest($request)
    {
        // form a search request url
        $search_url = self::SEARCH_URL.$request;

        // Rozetka treats search requests in various ways. It can return a search results page or forward
        // a request either straight to a certain product category page or to a product portal page.
        $response =  $this->fetchElementsFromPage($search_url, self::RE_PAGE_TYPE);
        switch ($response['page_type']){
            case ('Portal')        :
                $this->s_category = $this->fetchElementsFromPage($search_url, self::RE_PORTAL_CATEGORY);
                break;
            case ('Catalog')       :
                $this->s_category = $this->fetchElementsFromPage($search_url, self::RE_CATALOG_CATEGORY);
                break;
            case ('SearchResults') :
                $this->s_category = $this->detectCategory($request);
                break;
            default :                 // something went amiss...
                return false;
        }
        // get all items of the category
        return $this->parseItemsFromCategory($this->s_category);
    }

    /**
     * Determines the most relevant category of goods to user query
     *
     * @param string $request user query
     * @return array $category
     *         an array with a category data in a predefined format on success or FALSE on failure.
     */
    protected function detectCategory($request)
    {
        $this->parseCategories(self::SEARCH_URL.$request);
        return $this->getMaxRelevanceCategory();
    }

    /**
     * ParseCategories
     *
     * This method operates with a search result page. It parses information about categories
     * of goods from the page navigation menu into such parts: a category name, a number of
     * finding in the category and a url to the search-in-thе-category page.
     * Lists them to the associative array $this->categories.
     *
     * @param string $search_url
     * @return array $categories
     */
    protected function parseCategories($search_url)
    {

        $this->categories = $this->fetchElementsFromPage($search_url, self::RE_SEARCH_CATEGORIES);
        return $this->categories;
    }

    /**
     * Evaluates relevance of categories to the search query and returns the one
     * with the max value. The category relevance is rated in this way:
     * relevance = number_of_finding_in_the_category/total_items_number;
     *
     * @return array $category
     */
    protected function getMaxRelevanceCategory()
    {
        // if there are no categories return
        if ((!$this->categories))
            return false;

        foreach($this->categories as &$c)
        {
            // to get quantity values we need first to go to search-in-category and category-root pages
            $c += $this->fetchElementsFromPage($c['search_url'], self::RE_ROOT_URL);
            $c += $this->fetchElementsFromPage($c['root_url'], self::RE_CATEGORY_QTY) ?:    // bypass Rozetka's irregularity
                  $this->fetchElementsFromPage($c['root_url'] .= 'filter/', self::RE_CATEGORY_QTY);
            if (($c['total'] < 5)||($c['available'] == 0)){
                $c['relevance'] = -1;            // discard minor category
                continue;
            }
            @$c['relevance'] = $c['found']/$c['total'];
        }
        // find max
        $max_relevance_category = $this->categories[0];
        foreach($this->categories as $c)
            if ($c['relevance']>$max_relevance_category['relevance'])
                $max_relevance_category = $c;
        return $max_relevance_category;
    }

    /**
     * Parses items of goods from a given category
     *
     * @param array $category
     * @return array $items 2d array of parsed items
     */
    protected function parseItemsFromCategory($category)
    {
        if(!($category && isset($category['root_url'])))
            return false;

        // determine the number of pages in category
        $pagination = $this->fetchElementsFromPage($category['root_url'].'view=list/', self::RE_PAGINATION);
        $pages_number = ($pagination['pages_number']) ? (int)$pagination['pages_number'] : 1;

        // parse items from each one
        for($p = 1; $p <= $pages_number; $p++){
            $current_page_items = $this->fetchElementsFromPage($category['root_url']."page=$p;".'sort=cheap;view=list/',
                self::RE_CATEGORY_ITEMS);
            $this->items = array_merge($this->items, $current_page_items);
        }

        // formatting
        foreach($this->items as &$item)
            $item['price'] = (int)str_replace('&thinsp;','',$item['price']);

        return $this->items;
    }
}