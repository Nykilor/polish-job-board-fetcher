<?php

namespace PolishItJobBoardFetcher\Website;

use DateTime;
use DOMElement;

use GuzzleHttp\Client;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;

use PolishItJobBoardFetcher\Model\Url;

use PolishItJobBoardFetcher\Utility\WebsiteLoopFilterTrait;
use PolishItJobBoardFetcher\Utility\JobOfferFactoryTrait;
use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * JustJoin.it API call class
 */
class BulldogJob implements WebsiteInterface, JobOfferFactoryInterface
{
    use JobOfferFactoryTrait;
    use WebsiteLoopFilterTrait;
    use ReplacePolishLettersTrait;

    public const URL = "https://bulldogjob.pl/";

    public const TECHNOLOGY = [
      "java", "javascript", "html",
      "php", "python", "c++",
      ".net", "ruby", "kotlin"
    ];

    public const CITY = true;

    public const CATEGORY = [
      "backend", "fullstack", "frontend",
      "devops", "analyst", "administrator",
      "tester",
      "project_manager" => [
        "pm", "project manager", "project-manager"
      ],
      "support", "architect", "qa",
      "mobile",
      "designer" => [
        "ux/ui", "design", "ux"
      ],
      "tech_lead" => [
        "tl", "team leader", "tech lead"
      ],
      "embedded",
      "scrum_master" => [
        "scrum", "sm"
      ]
    ];

    public const EXPERIENCE = [
      "junior",
      "medium" => [
        "regular",
        "mid"
      ],
      "senior" => [
        "specjalista",
        "specialist"
      ]
    ];

    /**
     * Array containing the JobOffers made from the data fetched.
     * @var JobOfferCollection
     */
    private $offers;

    public function __construct()
    {
        $this->offers = new JobOfferCollection();
    }

    public function hasTechnology(?string $technology) : bool
    {
        if (!is_null($technology) && in_array(strtolower($technology), self::TECHNOLOGY)) {
            return true;
        } else {
            return false;
        }
    }

    public function allowsCustomTechnology() : bool
    {
        return false;
    }

    public function hasCategory(?string $category) : bool
    {
        return $this->constArrayContains(self::CATEGORY, $category);
    }

    public function allowsCustomCategory() : bool
    {
        return false;
    }

    public function hasCity(?string $city) : bool
    {
        return true;
    }

    public function allowsCustomCity() : bool
    {
        return true;
    }

    public function hasExperience(?string $exp) : bool
    {
        return $this->constArrayContains(self::EXPERIENCE, $exp);
    }

    public function allowsCustomExperience() : bool
    {
        return false;
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, ?string $technology, ?string $city, ?string $exp, ?string $category)
    {
        var_dump(self::URL."companies/jobs".$this->createQueryUrl($technology, $city, $exp, $category));
        exit();
        $response = $client->request("GET", self::URL."companies/jobs".$this->createQueryUrl($technology, $city, $exp, $category));
        $body = $response->getBody()->getContents();
        $this->handleFetchResponse($body);
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function getJobOfferCollection() : JobOfferCollection
    {
        return $this->offers;
    }

    private function handleFetchResponse($body)
    {
        $crawler = new Crawler($body);
        foreach ($crawler->filter(".results-list")->children("li.results-list-item:not(.subscribe-search)") as $dom_element) {
            $this->offers[] = $this->createJobOfferModel($this->adaptFetchedDataForModelCreation($dom_element));
        }
    }

    /**
     * Implementation of JobOfferFactoryInterface
     */
    public function adaptFetchedDataForModelCreation($dom_element) : array
    {
        if (!($dom_element instanceof DOMElement)) {
            throw new \Exception("Variables has to be an instance of DOMElement class.", 1);
        }

        $array = [];
        $crawler = new Crawler($dom_element);

        $city = trim($crawler->filter("span.pop-mobile")->text());

        $url_job = new Url();
        $url_job->setUrl($dom_element->getAttribute("data-item-url"));
        $url_job->setTitle("offer");
        $url_job->setCity($city);

        $url_collection = new UrlCollection();
        $url_collection->addItem($url_job);

        $array["url"] = $url_collection;
        $array["title"] = $crawler->filter("a.result-header-name")->text();
        $array["company"] = $crawler->filter("span.pop-black.desktop")->text();
        $array["city"] = $city;
        $array["post_time"] = new DateTime($crawler->filter("p.result-desc-meta span.inline-block")->text());

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

    /**
     * Creates the end of the url that queries the website
     * @param  string|null $technology
     * @param  string|null $city
     * @param  string|null $exp
     * @param  string|null $category
     * @return string              URL for query
     */
    private function createQueryUrl(?string $technology, ?string $city, ?string $exp, ?string $category) : string
    {
        if (is_null($technology) && is_null($city) && is_null($exp) && is_null($category)) {
            return "";
        }

        $query = "/s";

        if (!is_null($city) && strtolower($this->replacePolishLetters($city)) === "trojmiasto") {
            $city = "Gdańsk,Gdynia,Sopot";
        }

        //We don't check if the variables exist in const because BoardFetcher does it for us
        if (!is_null($city) && $city !== "remote") {
            $query .= "/city,$city";
        }

        if ($city === "remote") {
            $query .= "/remote,true";
        }

        if (!is_null($technology)) {
            $query .= "/skills,$technology";
        }

        if (!is_null($exp)) {
            $query .= "/experience_level,".$this->getAdaptedNameFromConstArray(self::EXPERIENCE, $exp);
        }

        if (!is_null($category)) {
            $query .= "/role,".$this->getAdaptedNameFromConstArray(self::CATEGORY, $category);
        }

        return $query;
    }
}
