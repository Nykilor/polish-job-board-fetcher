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

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\DataProvider\WebsiteType\Redux;
use PolishItJobBoardFetcher\Factory\Normalizer\CodersbaseNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;
use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;

/**
 * Pracuj.pl webstie redux scrapping class
 */
class Codersbase extends Redux implements
    WebsiteInterface,
    HasJobOfferNormalizerInterface,
    CategoryQueryFieldInterface,
    CityQueryFieldInterface,
    ContractTypeQueryFieldInterface,
    ExperienceQueryFieldInterface,
    TechnologyQueryFieldInterface,
    SalaryQueryFieldInterface
{
    use ReplacePolishLettersTrait;
    use WebsiteInterfaceHelperTrait;

    public const URL = "https://www.codersbase.it";

    private $technology = [
      "javascript" => [
        "js"
      ],
      "php", "ruby", "python",
      "java", ".net", "scala",
      "c", "golang", "sap",
      "other"
    ];

    private $city = [
      "remote",
      "warszawa",
      "kraków",
      "wrocław",
      "poznań",
      "trójmiasto",
      "łódź",
      "katowice",
      "szczecin",
      "rzeszów",
      "białystok",
      "gliwice",
      "bielsko-biała"
    ];

    private $category = [
      "mobile",
      "devops",
      "ui" => [
        "ux/ui", "design", "ux",
        "designer"
      ],
      "pm" => [
        "project_manager", "project manager", "project-manager"
      ],
    ];

    private $experience = [
        "junior",
        "mid" => [
          "medium",
          "regular",
          "mid"
        ],
        "senior" => [
          "specjalista",
          "specialist"
        ],
        "intern" => [
          "trainee"
        ]
    ];

    private $contractType = [
      "b2b",
      "permanent",
    ];

    private $salary = [];

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
        return $this->arrayContains($this->city, $city);
    }

    public function allowsCustomCity() : bool
    {
        return false;
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
        return $this->arrayContains($this->contractType, $contractType);
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
        $response = $client->request("GET", self::URL);

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new CodersbaseNormalizer();
    }

    public function handleResponse(Response $response) : Generator
    {
        $body = $response->getBody()->getContents();

        $this->setInitialStateFromHtml($body);
        var_dump(preg_replace('/\\\\/', "", $this->getInitialState()));
        exit();
        $initial_state = json_decode(substr($this->getInitialState(), 0, -2), true, 512, JSON_THROW_ON_ERROR);
        var_dump($initial_state);
        exit();
        foreach ($initial_state["offers"] as $key => $offer) {
            yield $offer;
        }
    }
}
