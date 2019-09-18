<?php

namespace PolishJobBoardFetcher\Website;

use DateTime;
use Exception;

use GuzzleHttp\Client;
use PolishJobBoardFetcher\Utility\ReplacePolishLettersTrait;
use PolishJobBoardFetcher\Model\JobOffer;
use PolishJobBoardFetcher\Model\Collection\JobOfferCollection;

/**
 * JustJoin.it API call class
 */
class NoFluffJobs implements WebsiteInterface
{
    use ReplacePolishLettersTrait;

    public const URL = "https://nofluffjobs.com/";

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
        if (!is_null($city)) {
            $city = $this->replacePolishLetters($city);
        }

        $options = [
          "json" => $this->getRequestBody($technology, $city, $exp)
        ];
        $response = $client->request("POST", self::URL."api/search/posting", $options);
        $body = $response->getBody()->getContents();
        $this->handleFetchResponse(json_decode($body, true));
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function getOffersCollection() : JobOfferCollection
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
            $this->offers[] = $this->createJobOfferModel($entry_data);
        }
    }

    /**
     * Creates a JobOffer from given data array fetched from NoFluffJobs website
     * @param  array    $entry_data Single offer
     * @return JobOffer
     */
    private function createJobOfferModel(array $entry_data) : JobOffer
    {
        $offer = new JobOffer();
        $offer->setTitle($entry_data["title"]);
        $offer->setTechnology([$entry_data["technology"]]);
        $offer->setExp(implode(", ", $entry_data["seniority"]));
        $offer->setUrl(self::URL."job/".$entry_data["url"]);

        if ($entry_data["locationCount"] > 1) {
            $city = [];
            foreach ($entry_data["location"]["places"] as $key => $place) {
                $city[] = $place["city"];
            }
            $city = implode(",", $city);
        } else {
            $city = $entry_data["location"]["places"][0]["city"];
        }

        $offer->setCity($city);

        $posted = (isset($entry_data["renewed"]))? $entry_data["renewed"] : $entry_data["posted"];
        $posted = substr($posted, 0, 10);
        $date = new DateTime();
        $date->setTimestamp($posted);
        $offer->setPostTime($date);

        $offer->setCompany($entry_data["name"]);
        $offer->setSalary("");

        return $offer;
    }

    /**
     * Creates the body for api POST call
     * @param  string|null $technology Query
     * @param  string|null $city       Query
     * @param  string|null $exp        Query
     * @return array              Returns the $body
     */
    private function getRequestBody(?string $technology, ?string $city, ?string $exp) : array
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
        $picked = [
          "technology" => [
            "javascript", "java", "angular",
            ".net", "react", "sql", "python",
            "rest", "spring", "php", "node",
            "aws", "hibernate", "c++",
            "jquery", "scala", "selenium",
            "redux", "android", "symfony",
            "ruby", "django", "swift",
            "spark", "c"
          ],
          "city" => [
            "remote", "warszawa", "wroclaw",
            "krakow", "gdansk", "poznan",
            "trojmiasto", "slask", "lodz", "katowice",
            "lublin", "szczecin", "bydgoszcz",
            "bialystok", "gdynia", "gliwice",
            "sopot"
          ],
          "exp" => [
            "trainee", "junior", "mid",
            "senior", "expert"
          ]
        ];

        $array_key_to_body_criteria = [
          "city" => "location",
          "technology" => "technology",
          "exp" => "seniority"
        ];

        if (!array_key_exists($picked_array_key, $picked)) {
            throw new Exception("The second variable has to be either: 'technology', 'city' or 'exp'");
        }

        //If the query value is null just return the body
        if (is_null($value)) {
            return $body;
        }

        if (in_array($value, $picked[$picked_array_key])) {
            if ($picked_array_key === "exp") {
                $body["criteriaSearch"][$array_key_to_body_criteria[$picked_array_key]] = [$value];
            } else {
                $body["criteriaSearch"][$array_key_to_body_criteria[$picked_array_key]]["picked"] = [$value];
            }
        } else {
            $body["criteriaSearch"][$array_key_to_body_criteria[$picked_array_key]]["custom"] = [$value];
        }

        return $body;
    }
}
