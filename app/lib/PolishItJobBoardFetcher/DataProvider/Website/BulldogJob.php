<?php

namespace PolishItJobBoardFetcher\DataProvider\Website;

use Generator;

use GuzzleHttp\Client;

use GuzzleHttp\Psr7\Response;

use PolishItJobBoardFetcher\DataProvider\Fields\CityQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\SalaryQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\CategoryQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\ExperienceQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\TechnologyQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\ContractTypeQueryFieldInterface;

use PolishItJobBoardFetcher\DataProvider\PaginableWebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\Exception\PageLimitExcededException;

use PolishItJobBoardFetcher\Factory\Normalizer\BulldogJobNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;

use Symfony\Component\DomCrawler\Crawler;

/**
 * BulldogJob.pl website scraping
 */
class BulldogJob implements
    HasJobOfferNormalizerInterface,
    CategoryQueryFieldInterface,
    CityQueryFieldInterface,
    ContractTypeQueryFieldInterface,
    ExperienceQueryFieldInterface,
    TechnologyQueryFieldInterface,
    SalaryQueryFieldInterface,
    PaginableWebsiteInterface
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
      "tester" => [
        "test", "testing", "testers"
      ],
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

    private $salary = [];

    private $currentPage = null;

    private $pageLimit = null;

    private $currentQueryUrl = null;

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

    public function getSalary()
    {
        return $this->salary;
    }

    public function getCurrentPage() : int
    {
        return $this->currentPage;
    }

    public function getPageLimit() : int
    {
        return $this->pageLimit;
    }

    public function getCurrentQueryUrl() : string
    {
        return $this->currentQueryUrl;
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

    public function hasSalary(?int $salary) : bool
    {
        return false;
    }

    public function allowsCustomSalary() : bool
    {
        return true;
    }

    public function fetchOffers(Client $client, array $query) : Response
    {
        $response = $client->request("GET", $this->createUrl($query));

        $body = $response->getBody();

        $content = (string) $body;
        //reset the stream pointer position
        $body->rewind();

        $this->setPagination(new Crawler($content));

        return $response;
    }

    public function fetchOffersPage(Client $client, int $page) : Response
    {
        if (is_null($this->pageLimit) or $page > $this->pageLimit) {
            throw new PageLimitExcededException("You're trying to fetch a non-existing page.");
        }

        $this->currentPage = $page;

        $response = $client->request("GET", $this->currentQueryUrl."?page=$page");

        return $response;
    }


    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new BulldogJobNormalizer();
    }

    public function filterOffersFromResponse(Response $response) : Generator
    {
        $content = (string) $response->getBody();

        $crawler = new Crawler($content);
        foreach ($crawler->filter(".results-list")->children("li.results-list-item:not(.subscribe-search)") as $dom_element) {
            yield $dom_element;
        }
    }

    public function createUrl(array $query) : string
    {
        //sets up the variables https://www.php.net/manual/en/function.extract.php
        extract($query);

        if (is_null($technology) && is_null($city) && is_null($experience) && is_null($category)) {
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

        if (!is_null($experience)) {
            $query .= "/experience_level,".$experience;
        }

        if (!is_null($category)) {
            $query .= "/role,".$category;
        }

        if (!is_null($salary)) {
            $query .= "/salary,".$salary."/with_salary,true";
        }

        $this->currentQueryUrl = self::URL."companies/jobs".$query;

        $this->pageLimit = null;
        $this->currentPage = null;

        return self::URL."companies/jobs".$query;
    }

    private function setPagination(Crawler $crawler) : void
    {
        $pagination_dom_element = $crawler->filter("section.search-results div.pagination");
        $this->currentPage = $pagination_dom_element->attr("data-current");
        $this->pageLimit = $pagination_dom_element->attr("data-total");
    }
}
