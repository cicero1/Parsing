<?php
/**
 * Content Puller
 *
 * Pulls the content from a site, making use of CURL; analyzes and parses
 * it with the help of regular expressions; eventually, retrieves needed
 * data from it.
 * @author Vitali Makovijchuk
 * @package E-Catalog-Parser
 */

abstract class Content_Puller
{
    protected $_curl_handler;
    protected $_page_html;
    protected $request;

    function __constructor()  {}

    /**
     * Pulls items from an e-catalog according to user's request
     *
     * @param string $request
     * @return array list of items in a predefined format
     */
    abstract function pullItemsOnRequest($request);

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
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'E-catalog-Parser');
        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }

    /**
     * fetchElementsFromPage
     *
     * Provides a handy way of extracting elements from a webpage utilizing regexes
     * (PCRE) with named capturing groups
     *
     * @param string $url of a page
     * @param string $pattern
     *        a regex to be matched; has to contain named capturing groups
     *        - (?<name>subpattern)
     * @return mixed
     *    in case of sole match returns an array of all capturing groups: $out[groupname1], $out[groupname2]...;
     *    in case of multiple matches returns 2d array of strings ordered so that $out[0] contains
     *    the first set of matches: $out[0][name1], $out[0][name2], $out[1] contains the second one, and so on...;
     *    otherwise returns NULL
     */
    protected function fetchElementsFromPage($url, $pattern)
    {
        $html=getPage($url);
        $out=array();
        preg_match_all($pattern, $html, $out, PREG_SET_ORDER );
        foreach ($out as &$matches) {
            unset($matches[0]);                 // the matches of the root patterns are of no use
            $matches = array_unique($matches);
        }

        if (count($out) == 1)     // if there is a single match extract the only result array member
        $out = array_shift($out);
        return $out;
    }

//    protected function fetchElements($pattern, $number, $offset){}// TODO

}

/**
 * Rozetka Content Puller
 *
 * This subclass "knows" about the Rozetka.ua site structure
 * and implements the logic of data pulling.
 * @author Vitali Makovijchuk
 */
class Rozetka_Content_Puller extends Content_Puller
{
    protected $categories = array();
    protected $items      = array();
    protected $s_category;             // sought category

const SEARCH_URL = 'http://rozetka.com.ua/search/?section=%2F&text=REQUEST&redirected=1';

const RE_CATEGORIES = '#subl-i.*?ref=\"(?<search_url>[^"]*?)\".*?i-link\">(?<name>.*?)<.*?i-count\">\((?<found>\d+)\)#s';
const RE_CAT_ROOT_URL = '#count-catalog.*?href=\"(?<cat_root_url>[^"]*?)\"#s';
const RE_CATEGORY_ITEMS_QTY = '#QuantityTotal\":\"(?<total_qty>\d+)#s';
const RE_PAGINATION = '#>(?<pages_number>\d+)<\/a[^a]*?<\/ul#s';
const RE_CATEGORY_ITEMS_AVAIL = '#st-title.*?ref=\"(?<description_url>[^\"]*?)\".*?>(?<model>.*?)\v.*?-status (?:available|limited)\".*?-uah\">(?<price>.*?)<#s';


    function __construct()
    {
        parent::__constructor();
    }

    function pullItemsOnRequest($request)
    {
        if(!$this->s_category = $this->detectCategory($request))
            return false;
        return $this->parseItemsFromCategory($this->s_category);
    }

    /**
     * gets category of goods name as it is recorded in the e-catalog
     *
     * @return string an e-catalog category name
     */
    function getSoughtCategory()
    {
        return $this->s_category['name'];
    }

    /**
     * Determines the category of goods
     *
     * @param string $request user request
     * @return string $category the address of category at the e-catalog
     */
    protected function detectCategory($request)
    {
        // firstly, we form a search page url
        $search_url=str_replace('REQUEST', $request, self::SEARCH_URL);
        // next we operate with a search result page: fetch information about categories
        // of goods from the page's menu; parse it into meaningful parts: a name of the category,
        // a number of finding in the category and a url to search-in-thе-category page;
        // list them to an associative array $categories;
        $this->categories = $this->fetchElementsFromPage($search_url, self::RE_CATEGORIES);
        // if get nothing found return
        if (!$this->categories)
            return false;
        // else evaluate relevance of categories to the instant search query
        foreach($this->categories as &$c)
        {
            $c += $this->fetchElementsFromPage($c['search_url'], self::RE_CAT_ROOT_URL);
            if (!(@$total_qty = $this->fetchElementsFromPage($c['cat_root_url'], self::RE_TOTAL_QTY))) // bypass Rozetka's structure irregularity
                  @$total_qty = $this->fetchElementsFromPage($c['cat_root_url'].'filter/', self::RE_TOTAL_QTY);
            $c += $total_qty;
            @$c['relevance'] = $c['found']/$c['total_qty'];
        }
        // eventually find the max value
        $max_relevance_category = $this->categories[0];
        foreach($this->categories as $c)
             if ($c['relevance']>$max_relevance_category['relevance'])
                $max_relevance_category = $c;

        return $this->s_category = $max_relevance_category;
    }

    /**
     * Parses items of goods
     *
     * @param string $category
     * @return array $items 2d array of parsed items
     */
    protected function parseItemsFromCategory($category)
    {
        // determine the number of pages in category
        $pagination = fetchElementsFromPage($category['cat_root_url'].'view=list/', self::RE_PAGINATION);
        $pages_number = ($pagination['pages_number']) ? (int)$pagination['pages_number'] : 1;

        // parse items from each one
        for($p = 1; $p <= $pages_number; $p++){
            $current_page_items = fetchElementsFromPage($category['cat_root_url']."page=$p;".'sort=cheap;view=list/',
                                                        self::RE_CATEGORY_ITEMS_AVAIL);
            $this->items = array_merge($this->items, $current_page_items);
        }
        foreach($this->items as &$item)
            $item['price'] = (int)str_replace('&thinsp;','',$item['price']);

        return $this->items;
    }

}