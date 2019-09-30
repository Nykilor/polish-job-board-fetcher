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

use PolishItJobBoardFetcher\Factory\Normalizer\JustJoinItNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;

/**
 * JustJoin.it API call class
 */
class JustJoinIt implements
    WebsiteInterface,
    HasJobOfferNormalizerInterface,
    CategoryQueryFieldInterface,
    CityQueryFieldInterface,
    ContractTypeQueryFieldInterface,
    ExperienceQueryFieldInterface,
    TechnologyQueryFieldInterface
{
    use WebsiteInterfaceHelperTrait;

    public const URL = "https://justjoin.it/";

    private $technology = [
      "javascript" => [
        "js"
      ],
      "html", "php",
      "ruby", "python", "java",
      ".net", "scala", "c",
      "golang", "sap", "other"
    ];

    private $city = [
      "warszawa", "kraków", "wrocław",
      "poznań", "trójmiasto", "sopot",
      "gdynia", "gdańsk", "remote",
      "world", "białystok", "bielsko-biała",
      "bydgoszcz", "częstochowa", "gliwice",
      "katowice", "kielce", "lublin",
      "łódź", "olsztyn", "opole",
      "toruń", "rzeszów", "szczecin"
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
      "game",
      "security",
      "blockchain",
      "data",
    ];

    private $experience = [
        "junior",
        "mid" => [
          "regular", "medium"
        ],
        "senior" => [
          "specjalista",
          "specialist"
        ]
    ];

    private $contractType = [
      "b2b",
      "permanent",
      "mandate_contract"
    ];

    protected $variables = [];

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

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, array $query) : Response
    {
        $response = $client->request("GET", self::URL."api/offers");
        $this->variables = $query;

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new JustJoinItNormalizer();
    }

    public function handleResponse(Response $response) : Generator
    {
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        //Because JustJoin.it returns every offer they have with a single api call we need to filter what we want by ourselfs,
        //Because some websites use certain "technologies" of this one as categories we had to split the technology into technology and category
        $look_for_in_marker_icon = [];

        if (!is_null($this->variables["category"])) {
            $look_for_in_marker_icon[] = strtolower($this->variables["category"]);
        }

        if (!is_null($this->variables["technology"])) {
            $look_for_in_marker_icon[] = strtolower($this->variables["technology"]);
        }

        foreach ($body as $key => $offer_array) {
            if (is_null($this->variables["city"]) or $this->variables["city"] === strtolower($offer_array["city"]) or ($this->variables["city"] === "remote" && $offer_array["remote"])) {
                if (is_null($this->variables["experience"]) or $this->variables["experience"] === $offer_array["experience_level"]) {
                    if (empty($look_for_in_marker_icon) or in_array($offer_array["marker_icon"], $look_for_in_marker_icon)) {
                        if (is_null($this->variables["contract_type"]) or strtolower($this->variables["contract_type"]) === $offer_array["employment_type"]) {
                            yield $offer_array;
                        }
                    }
                }
            }
        }
    }
}
