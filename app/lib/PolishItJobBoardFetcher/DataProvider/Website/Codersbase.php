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
use PolishItJobBoardFetcher\DataProvider\QueryClassPropertyInterface;
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\DataProvider\WebsiteType\Redux;
use PolishItJobBoardFetcher\Exception\EmptyQueryPropertyException;

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
    SalaryQueryFieldInterface,
    QueryClassPropertyInterface
{
    use ReplacePolishLettersTrait;
    use WebsiteInterfaceHelperTrait;

    public const URL = "https://www.codersbase.it";

    private $technology = [
      "js" => [
        "javascript"
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
      "big data" => [
        "data"
      ],
      "testers" => [
        "test", "testing", "tester"
      ]
    ];

    private $experience = [
        "junior",
        "mid" => [
          "medium",
          "regular",
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
      "permanent" => [
        "uop"
      ]
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

    public function getQuery() : array
    {
        return $this->query;
    }

    public function setQuery(array $query) : void
    {
        $this->query = $query;
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
        $response = $client->request("GET", $this->createUrl($query));

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new CodersbaseNormalizer();
    }

    public function filterOffersFromResponse(Response $response) : Generator
    {
        if (empty($this->query)) {
            throw new EmptyQueryPropertyException("You need first too set the query property by using setQuery()");
        }

        $body = $response->getBody()->getContents();
        $this->setInitialStateFromHtml($body);
        //remove slashes
        $initial_state = preg_replace(['/\\\\/'], "", $this->getInitialState());
        //Removes the description key from the json because the content that is there dosn't allow for decoding in php
        $initial_state = preg_replace('/"description":"[\s\S]+?",/', "", $initial_state);
        $initial_state = json_decode(substr($initial_state, 0, -2), true, 512, JSON_THROW_ON_ERROR);

        foreach ($initial_state["offers"]["offers"] as $key => $offer) {
            $filtered_offer = $this->filterByQuery($offer);
            if (empty($filtered_offer)) {
                continue;
            } else {
                yield $filtered_offer;
            }
        }
    }

    public function createUrl(array $query) : string
    {
        return self::URL;
    }

    /**
     * Returns the $offer_array back or empty array if it does not meet the query requirements
     * that are stated in $this->query
     * @param  array $offer_array
     * @return array
     */
    private function filterByQuery(array $offer_array) : array
    {
        $look_for_in_main_skill = [];

        if (!is_null($this->query["category"])) {
            $look_for_in_main_skill[] = strtolower($this->query["category"]);
        }

        if (!is_null($this->query["technology"])) {
            $look_for_in_main_skill[] = strtolower($this->query["technology"]);
        }

        if ($this->query["city"] === "trójmiasto") {
            $city = ["sopot", "gdańsk", "gdynia"];
        } else {
            $city = [$this->query["city"]];
        }

        if (empty($city[0]) or in_array(strtolower($offer_array["officeCity"]), $city) or ($city[0] === "remote" && $offer_array["fullRemote"])) {
            if (is_null($this->query["experience"]) or $this->query["experience"] === strtolower($offer_array["experienceLevel"])) {
                if (empty($look_for_in_main_skill) or in_array(strtolower($offer_array["mainSkill"]), $look_for_in_main_skill)) {
                    if (is_null($this->query["contract_type"]) or
                        $this->query["contract_type"] === strtolower($offer_array["employmentType"]) or
                        $offer_array["employmentType"] === "both"
                      ) {
                        if (is_null($this->query["salary"]) or
                            (isset($offer_array["salaryFrom"]) and $this->query["salary"] <= intval($offer_array["salaryFrom"])) or
                            (isset($offer_array["salaryTo"]) and $this->query["salary"] <= intval($offer_array["salaryTo"]))
                          ) {
                            return $offer_array;
                        }
                    }
                }
            }
        }

        return [];
    }
}
