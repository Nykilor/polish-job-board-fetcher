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

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\Exception\NotExistingNoFluffJobsBodyKey;

use PolishItJobBoardFetcher\Factory\Normalizer\NoFluffJobsNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;
use PolishItJobBoardFetcher\Utility\JobOfferFactoryTrait;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;

/**
 * NoFluffJobs.it API call class
 */
class NoFluffJobs implements
    WebsiteInterface,
    HasJobOfferNormalizerInterface,
    CategoryQueryFieldInterface,
    CityQueryFieldInterface,
    ExperienceQueryFieldInterface,
    TechnologyQueryFieldInterface,
    SalaryQueryFieldInterface
{
    use ReplacePolishLettersTrait;
    use JobOfferFactoryTrait;
    use WebsiteInterfaceHelperTrait;

    public const URL = "https://nofluffjobs.com/";

    private $technology = [
      "javascript" => [
        "js"
      ],
      "java", "angular",
      ".net", "react", "sql", "python",
      "rest", "spring", "php", "node",
      "aws", "hibernate", "c++",
      "jquery", "scala", "selenium",
      "redux", "android", "symfony",
      "ruby", "django", "swift",
      "spark", "c"
    ];

    private $city = [
      "remote", "warszawa", "wrocław",
      "kraków", "gdańsk", "poznań",
      "trójmiasto", "śląsk", "łódz", "katowice",
      "lublin", "szczecin", "bydgoszcz",
      "białystok", "gdynia", "gliwice",
      "sopot", "zdalnie"
    ];

    private $category = [
      "backend", "frontend", "fullstack",
      "mobile",
      "testing" => [
        "test", "tester", "testers"
      ],
      "devops",
      "project-manager" => [
          "pm", "project manager"
      ],
      "support",
      "business-intelligence" => [
        "bi", "buisness intelligence"
      ],
      "business-analyst" => [
        "ba", "buisness analyst"
      ],
      "hr" => [
        "human resource"
      ],
      "it-trainee",
      "ux" => [
        "ux/ui", "design", "designer",
        "ui"
      ]
    ];

    private $experience = [
        "trainee" => [
          "intern"
        ],
        "junior",
        "mid" => [
          "regular", "medium"
        ],
        "senior" => [
          "specjalista",
          "specialist"
        ],
        "expert"
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

    public function getSalary()
    {
        return $this->salary;
    }

    public function allowsCustomTechnology() : bool
    {
        return true;
    }

    public function hasTechnology(?string $technology) : bool
    {
        return $this->arrayContains($this->technology, $technology);
    }

    public function allowsCustomCategory() : bool
    {
        return false;
    }

    public function hasCategory(?string $category) : bool
    {
        return $this->arrayContains($this->category, $category);
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
        $city = $query["city"];
        if (!is_null($city)) {
            $city = $this->replacePolishLetters($city);

            if ($city === "zdalnie") {
                $city = "remote";
            }
        }

        $options = [
          "json" => $this->getRequestBody($query["technology"], $city, $query["experience"], $query["category"], $query["salary"])
        ];

        $response = $client->request("POST", self::URL."api/search/posting", $options);

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new NoFluffJobsNormalizer();
    }

    public function handleResponse(Response $response) : Generator
    {
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($body["postings"] as $key => $entry_data) {
            yield $entry_data;
        }
    }

    /**
     * Creates the body for api POST call
     * @param  string|null $technology Query
     * @param  string|null $city       Query
     * @param  string|null $exp        Query
     * @param  string|null $category   Query
     * @param  int|null    $salary     Query
     * @return array              Returns the $body
     */
    private function getRequestBody(?string $technology, ?string $city, ?string $exp, ?string $category, ?int $salary) : array
    {
        $body = [
          "criteriaSearch" => [
            "category" => [],
            "country" => [],
            "employment" => [],
            "location" => [
              "custom" => [],
              "picked" => []
            ],
            "more" => [
              "custom" => [],
              "picked" => []
            ],
            "salary" => [],
            "seniority" => [],
            "technology" => [
              "custom" => [],
              "picked" => [],
            ]
          ]
        ];

        if (!is_null($salary)) {
            $body["salary"] = [
            "currency" => "pln",
            "greaterThan" => $salary,
            "period" => "m"
          ];
        }

        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "technology", $technology);
        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "city", $city);
        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "exp", $exp);
        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "category", $category);

        return $body;
    }

    /**
     * Handles the weird body creating that the website uses.
     * @param  array  $body             the request body
     * @param  string $picked_array_key "technology"|"exp"|"city"|"category"
     * @param  mixed  $value            the value for the search query
     * @return array                    the request body
     */
    protected function setSearchCriteriaOnCustomOrPicked(array $body, string $picked_array_key, $value) : array
    {

        //If the query value is null just return the body
        if (is_null($value)) {
            return $body;
        }

        $city_without_polish_letters = array_map(function ($city) {
            return $this->replacePolishLetters($city);
        }, $this->city);

        $picked = [
          "technology" => $this->technology,
          "city" => $city_without_polish_letters,
          "exp" => $this->getStringKeyOrValueFromArray($this->experience),
          "category" => $this->getStringKeyOrValueFromArray($this->category)
        ];

        $array_key_to_body_criteria = [
          "city" => "location",
          "technology" => "technology",
          "exp" => "seniority",
          "category" => "category"
        ];

        if (array_key_exists($picked_array_key, $array_key_to_body_criteria)) {
            $body_criteria_key = $array_key_to_body_criteria[$picked_array_key];
        } else {
            throw new NotExistingNoFluffJobsBodyKey("The key $picked_array_key is not allowed in the query to this website");
        }

        if (in_array($value, $picked[$picked_array_key])) {
            if (in_array($picked_array_key, ["exp", "category"])) {
                $body["criteriaSearch"][$body_criteria_key] = [$value];
            } else {
                $body["criteriaSearch"][$body_criteria_key]["picked"] = [$value];
            }
        } else {
            $body["criteriaSearch"][$body_criteria_key]["custom"] = [$value];
        }

        return $body;
    }


    /**
     * Will return all the strings in keys or values for depth 1
     * @param  array  $array
     * @return array
     */
    protected function getStringKeyOrValueFromArray(array $array) : array
    {
        $keys = array_keys($array);
        $values = array_values($array);
        $merged = array_merge($keys, $values);

        return array_filter($merged, "is_string");
    }
}
