<?php

namespace PolishJobBoardFetcher\Website;

use DateTime;
use DOMElement;

use GuzzleHttp\Client;

use PolishJobBoardFetcher\Model\JobOffer;
use PolishJobBoardFetcher\Model\Collection\JobOfferCollection;

use Symfony\Component\DomCrawler\Crawler;

/**
 * JustJoin.it API call class
 */
class BulldogJob implements WebsiteInterface
{
    public const URL = "https://bulldogjob.pl/";
    /**
     * Array containing the JobOffers made from the data fetched.
     * @var JobOfferCollection
     */
    private $offers;

    public function __construct()
    {
        $this->offers = new JobOfferCollection();
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, ?string $technology, ?string $city, ?string $exp)
    {
        $response = $client->request("GET", self::URL."companies/jobs".$this->createQueryUrl($technology, $city, $exp));
        $body = $response->getBody()->getContents();
        $this->handleFetchResponse($body);
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function getOffersCollection() : JobOfferCollection
    {
        return $this->offers;
    }

    private function createJobOfferModel(array $entry_data) : JobOffer
    {
        $offer = new JobOffer();
        $offer->setTitle($entry_data["title"]);
        $offer->setTechnology($entry_data["technology"]);
        $offer->setExp($entry_data["exp"]);
        $offer->setUrl($entry_data["url"]);
        $offer->setCity($entry_data["city"]);
        $offer->setPostTime(new DateTime($entry_data["post_time"]));
        $offer->setCompany($entry_data["company"]);
        $offer->setSalary($entry_data["salary"]);

        return $offer;
    }

    private function handleFetchResponse($body)
    {
        $crawler = new Crawler($body);
        foreach ($crawler->filter(".results-list")->children("li.results-list-item:not(.subscribe-search)") as $dom_element) {
            $this->offers[] = $this->createJobOfferModel($this->createOfferDataArrayFromDomElement($dom_element));
        }
    }

    private function createOfferDataArrayFromDomElement(DOMElement $dom_element) : array
    {
        $array = [];
        $crawler = new Crawler($dom_element);

        $array["url"] = $dom_element->getAttribute("data-item-url");
        $array["title"] = $crawler->filter("a.result-header-name")->text();
        $array["company"] = $crawler->filter("span.pop-black.desktop")->text();
        $array["city"] = trim($crawler->filter("span.pop-mobile")->text());
        $array["post_time"] = $crawler->filter("p.result-desc-meta span.inline-block")->text();

        $salary = $crawler->filter("p.result-desc-meta span.pop-green");

        $array["salary"] = (!is_null($salary->getNode(0))) ? $salary->text() : "";

        $technology = [];
        foreach ($crawler->filter("ul.tags")->children("li") as $key => $technology_dom_element) {
            $technology_crawler = new Crawler($technology_dom_element);
            $technology[] = $technology_crawler->filter("div.btn")->text();
        }
        $array["technology"] = $technology;
        $array["exp"] = "";

        return $array;
    }

    private function createQueryUrl(?string $technology, ?string $city, ?string $exp) : string
    {
        if (is_null($technology) && is_null($city) && is_null($exp)) {
            return "";
        }

        $query = "/s";

        if (!is_null($city)) {
            $query .= "/city,$city";
        }

        if (!is_null($technology)) {
            $query .= "/skills,$technology";
        }

        if (!is_null($exp)) {
            $query .= "/experience_level,$exp";
        }

        return $query;
    }
}
