<?php

namespace PolishItJobBoardFetcher\DataProvider\Website;

use Generator;

use GuzzleHttp\Client;

use GuzzleHttp\Psr7\Response;

use PolishItJobBoardFetcher\DataProvider\Fields\CityQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\CategoryQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\ExperienceQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\TechnologyQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\ContractTypeQueryFieldInterface;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\Factory\Normalizer\BulldogJobNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;

use Symfony\Component\DomCrawler\Crawler;

/**
 * JustJoin.it API call class
 */
class BulldogJob implements
    WebsiteInterface,
    HasJobOfferNormalizerInterface,
    CategoryQueryFieldInterface,
    CityQueryFieldInterface,
    ContractTypeQueryFieldInterface,
    ExperienceQueryFieldInterface,
    TechnologyQueryFieldInterface
{
    use WebsiteInterfaceHelperTrait;
    use ReplacePolishLettersTrait;

    public const URL = "https://bulldogjob.pl/";

    private $technology = [
      "java",
      "javascript" => [
        "js"
      ],
      "html",
      "php", "python", "c++",
      ".net", "ruby", "kotlin"
    ];

    private $city = [];

    private $category = [
      "backend", "fullstack", "frontend",
      "devops", "analyst", "administrator",
      "tester",
      "project_manager" => [
        "pm", "project manager", "project-manager"
      ],
      "support", "architect", "qa",
      "mobile",
      "designer" => [
        "ux/ui", "design", "ux",
        "ui"
      ],
      "tech_lead" => [
        "tl", "team leader", "tech lead"
      ],
      "embedded",
      "scrum_master" => [
        "scrum", "sm"
      ]
    ];

    private $experience = [
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

    private $contractType = [];

    private $query = [];

    public function getTechnology()
    {
        return $this->technology;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getExperience()
    {
        return $this->experience;
    }

    public function getContractType()
    {
        return $this->contractType;
    }

    public function hasTechnology(?string $technology) : bool
    {
        return $this->arrayContains($this->technology, $technology);
    }

    public function allowsCustomTechnology() : bool
    {
        return false;
    }

    public function hasCategory(?string $category) : bool
    {
        return $this->arrayContains($this->category, $category);
    }

    public function allowsCustomCategory() : bool
    {
        return false;
    }

    public function hasCity(?string $city) : bool
    {
        return false;
    }

    public function allowsCustomCity() : bool
    {
        return true;
    }

    public function hasExperience(?string $exp) : bool
    {
        return $this->arrayContains($this->experience, $exp);
    }

    public function allowsCustomExperience() : bool
    {
        return false;
    }

    public function hasContractType(?string $contractType) : bool
    {
        return false;
    }

    public function allowsCustomContractType() : bool
    {
        return false;
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, array $query) : Response
    {
        $response = $client->request("GET", self::URL."companies/jobs".$this->createQueryUrl($query["technology"], $query["city"], $query["experience"], $query["category"]));

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new BulldogJobNormalizer();
    }

    public function handleResponse(Response $response) : Generator
    {
        $body = $response->getBody()->getContents();

        $crawler = new Crawler($body);
        foreach ($crawler->filter(".results-list")->children("li.results-list-item:not(.subscribe-search)") as $dom_element) {
            yield $dom_element;
        }
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
            $query .= "/experience_level,".$exp;
        }

        if (!is_null($category)) {
            $query .= "/role,".$category;
        }

        return $query;
    }
}
