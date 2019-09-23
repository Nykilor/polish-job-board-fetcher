<?php

namespace PolishItJobBoardFetcher\Website;

use DateTime;
use Exception;

use GuzzleHttp\Client;
use PolishItJobBoardFetcher\Model\Collection\UrlCollection;

use PolishItJobBoardFetcher\Model\Url;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;
use PolishItJobBoardFetcher\Utility\JobOfferFactoryTrait;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;
use PolishItJobBoardFetcher\Model\JobOffer;
use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;

/**
 * JustJoin.it API call class
 */
class NoFluffJobs implements WebsiteInterface, JobOfferFactoryInterface
{
    use ReplacePolishLettersTrait;
    use JobOfferFactoryTrait;
    use WebsiteInterfaceHelperTrait;

    private $url = "https://nofluffjobs.com/";

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
      "mobile", "testing", "devops",
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
        "trainee",
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

    /**
     * Array containing the JobOffers made from the data fetched.
     * @var JobOfferCollection
     */
    private $offers;

    public function __construct()
    {
        $this->offers = new JobOfferCollection();
    }

    public function getUrl() : string
    {
        return $this->url;
    }

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

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, ?string $technology, ?string $city, ?string $exp, ?string $category)
    {
        if (!is_null($city)) {
            $city = $this->replacePolishLetters($city);

            if ($city === "zdalnie") {
                $city = "remote";
            }
        }

        $options = [
          "json" => $this->getRequestBody($technology, $city, $exp, $category)
        ];

        $response = $client->request("POST", $this->url."api/search/posting", $options);
        $body = $response->getBody()->getContents();
        $this->handleFetchResponse(json_decode($body, true));
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function getJobOfferCollection() : JobOfferCollection
    {
        return $this->offers;
    }

    /**
     * Starts to create JobOffers by given response
     * @param  array  $response The api call response
     */
    public function handleFetchResponse(array $response) : void
    {
        foreach ($response["postings"] as $key => $entry_data) {
            $this->offers[] = $this->createJobOfferModel($this->adaptFetchedDataForModelCreation($entry_data));
        }
    }

    /**
     * Creates a JobOffer from given data array fetched from NoFluffJobs website
     * @param  array    $entry_data Single offer
     * @return JobOffer
     */
    public function adaptFetchedDataForModelCreation($entry_data) : array
    {
        $array = [];
        $array["title"] = $entry_data["title"];
        $array["technology"] = [$entry_data["technology"]];
        $array["exp"] = implode(", ", $entry_data["seniority"]);

        if ($entry_data["locationCount"] > 1) {
            $city = ($entry_data["fullyRemote"])? ["remote"] : [];
            foreach ($entry_data["location"]["places"] as $key => $place) {
                $city[] = $place["city"];
            }
            $city = implode(",", $city);
        } else {
            $city = $entry_data["location"]["places"][0]["city"];
        }
        $array["city"] = $city;

        $url_job = new Url();
        $url_job->setUrl($this->url."job/".$entry_data["url"]);
        $url_job->setTitle("offer");
        $url_job->setCity($city);

        $url_collection = new UrlCollection();
        $url_collection->addItem($url_job);

        $array["url"] = $url_collection;

        $posted = (isset($entry_data["renewed"]))? $entry_data["renewed"] : $entry_data["posted"];
        $posted = substr($posted, 0, 10);
        $date = new DateTime();
        $date->setTimestamp($posted);
        $array["post_time"] = $date;

        $array["company"] = $entry_data["name"];
        $array["salary"] = "";

        return $array;
    }

    /**
     * Creates the body for api POST call
     * @param  string|null $technology Query
     * @param  string|null $city       Query
     * @param  string|null $exp        Query
     * @param  string|null $category   Query
     * @return array              Returns the $body
     */
    private function getRequestBody(?string $technology, ?string $city, ?string $exp, ?string $category) : array
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

        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "technology", $technology);
        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "city", $city);
        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "exp", $exp);
        $body = $this->setSearchCriteriaOnCustomOrPicked($body, "category", $category);

        return $body;
    }

    /**
     * Handles the weird body creating that the website uses.
     * @param  array  $body             the request body
     * @param  string $picked_array_key "technology"|"exp"|"city"
     * @param  string|null $value            the value for the search query
     * @return array                    the request body
     */
    protected function setSearchCriteriaOnCustomOrPicked(array $body, string $picked_array_key, ?string $value) : array
    {
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

        if (!array_key_exists($picked_array_key, $picked)) {
            throw new Exception("The second variable has to be either: 'technology', 'city' or 'exp'");
        }

        //If the query value is null just return the body
        if (is_null($value)) {
            return $body;
        }

        if (in_array($value, $picked[$picked_array_key])) {
            if (in_array($picked_array_key, ["exp", "category"])) {
                $body["criteriaSearch"][$array_key_to_body_criteria[$picked_array_key]] = [$value];
            } else {
                $body["criteriaSearch"][$array_key_to_body_criteria[$picked_array_key]]["picked"] = [$value];
            }
        } else {
            $body["criteriaSearch"][$array_key_to_body_criteria[$picked_array_key]]["custom"] = [$value];
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
