<?php
namespace PolishItJobBoardFetcher\DataProvider\WebsiteType;

use PolishItJobBoardFetcher\Exception\ReduxInitialStateNotFound;

use Symfony\Component\DomCrawler\Crawler;

class Redux
{
    private $initialState = null;

    public function setInitialStateFromHtml(string $body) : void
    {
        $crawler = new Crawler($body);
        $script = $crawler->filter("script");

        foreach ($script as $dom_element) {
            if (strpos($dom_element->nodeValue, "window.__INITIAL_STATE__") !== false) {
                $offers_dom_element = $dom_element;
                break;
            } else {
                $dom_element = null;
            }
        }

        if (is_null($dom_element)) {
            throw new ReduxInitialStateNotFound("No initial state found on the website.", 1);
        }

        $script_text = $dom_element->nodeValue;
        $json = "";
        preg_match("/\=(.*?)\n/", $script_text, $json);

        if (empty($json)) {
            throw new ReduxInitialStateNotFound("No initial state found on the website.", 1);
        }

        $json = $json[0];
        //My regex is not perfect, we have to fix it by omiting few chars
        $valid_json = substr($json, 2, -3);

        $this->initialState = json_decode($valid_json, true);
    }

    public function getInitialState() : array
    {
        return $this->initialState;
    }
}
