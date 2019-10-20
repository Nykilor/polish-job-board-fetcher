<?php

namespace PolishItJobBoardFetcher\DataProvider\Website;

use Generator;

use GuzzleHttp\Client;

use GuzzleHttp\Psr7\Response;

use PolishItJobBoardFetcher\DataProvider\Fields\CityQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\SalaryQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\ExperienceQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\TechnologyQueryFieldInterface;
use PolishItJobBoardFetcher\DataProvider\Fields\ContractTypeQueryFieldInterface;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\QueryClassPropertyInterface;
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\Exception\EmptyQueryPropertyException;

use PolishItJobBoardFetcher\Factory\Normalizer\JustJoinItNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;

/**
 * InstaJobs.it API call class
 */
class InstaJobs implements
    WebsiteInterface,
    HasJobOfferNormalizerInterface,
    CityQueryFieldInterface,
    ContractTypeQueryFieldInterface,
    ExperienceQueryFieldInterface,
    TechnologyQueryFieldInterface,
    SalaryQueryFieldInterface,
    QueryClassPropertyInterface
{
    use WebsiteInterfaceHelperTrait;

    public const URL = "https://www.instajobs.pl/";

    private $technology = [
      ".net", "java", "c++",
      "python", "ruby", "sql",
      "javascript", "typescript", "react",
      "angular", "scala", "ios",
      "android", "node", "docker",
      "git", "kotlin", "rest api",
      "linux", "aws", "kubernetes",
      "php", "css", "jira",
      "html", "perl", "swift",
      "mongodb", "jquery", "jenkins",
      "spring"
    ];

    private $city = [
      "białystok", "bielsko-biała", "bydgoszcz",
      "częstochowa", "gliwice", "katowice",
      "kielce", "lublin", "łódź",
      "olsztyn", "opole", "toruń",
      "rzeszów", "szczecin", "warszawa",
      "kraków", "wrocław", "poznań",
      "trójmiasto", "remote"
    ];

    private $experience = [
        2 => ["junior"],
        3 => [
          "regular",
          "medium",
          "mid"
        ],
        4 => [
          "specjalista",
          "specialist",
          "senior"
        ]
    ];

    private $contractType = [
      "b2b",
      "permanent" => [
        "uop"
      ]
    ];

    private $salary = [];

    protected $query = [];

    public function getTechnology()
    {
        return $this->technology;
    }

    public function getCity()
    {
        return $this->city;
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

    public function hasCity(?string $city) : bool
    {
        if (!is_null($city) && in_array(strtolower($city), $this->city)) {
            return true;
        } else {
            return false;
        }
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
        return new JustJoinItNormalizer();
    }

    public function filterOffersFromResponse(Response $response) : Generator
    {
        if (empty($this->query)) {
            throw new EmptyQueryPropertyException("You need first too set the query property by using setQuery()");
        }

        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);



        foreach ($body as $key => $offer_array) {
            if (is_null($this->query["city"]) or strpos(strtolower($offer_array["company"]["adress"]), $this->query["city"]) !== false) {
                if (is_null($this->query["experience"]) or  in_array($this->query["experience"], $offer_array["seniorityLevels"])) {
                    $offer_technologies = array_column($offer_array["requiredSkills"], "name");
                    $offer_technologies = array_map("strtolower", $offer_technologies);

                    if (is_null($this->query["technology"]) or in_array($this->query["technology"], $offer_technologies)) {
                        $contract_type = []; //TODO from here up to next
                        if (is_null($this->query["contract_type"]) or strtolower($this->query["contract_type"]) === $offer_array["employment_type"]) {
                            if (is_null($this->query["salary"]) or
                                $this->query["salary"] <= $offer_array["salary_from"] or
                                $this->query["salary"] <= $offer_array["salary_to"]
                              ) {
                                yield $offer_array;
                            }
                        }
                    }
                }
            }
        }
    }

    public function createUrl(array $query) : string
    {
        return "https://instajobs.azurewebsites.net/api/joboffers";
    }
}
