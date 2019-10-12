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
use PolishItJobBoardFetcher\DataProvider\PaginableWebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\Exception\PageLimitExcededException;

use PolishItJobBoardFetcher\Factory\Normalizer\FourProgramersNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;

/**
 * JustJoin.it API call class
 */
class FourProgramers implements
    WebsiteInterface,
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

    public const URL = "https://4programmers.net/";

    private $technology = [
      "javascript" => [
        "js"
      ],
      "java", "php", "c#",
      "python", "c++", "c",
      "ruby", "scala", "swift",
      "perl", "go"
    ];

    private $city = [
      "warszawa", "kraków", "poznań",
      "katowice", "wrocław", "gliwice",
      "bielsko-biała", "lublin", "warsaw",
      "białystok", "remote"
    ];

    private $category = [];

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
      "uop" => [
        "permanent"
      ]
    ];

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

    public function getCurrentPage() : ?int
    {
        return $this->currentPage;
    }

    public function getPageLimit() : ?int
    {
        return $this->pageLimit;
    }

    public function getCurrentQueryUrl() : ?string
    {
        return $this->currentQueryUrl;
    }

    public function hasTechnology(?string $technology) : bool
    {
        return $this->arrayContains($this->technology, $technology);
    }

    public function allowsCustomTechnology() : bool
    {
        return true;
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
        $response = $client->request("GET", $this->createUrl($query), [
          "headers" => [
            "accept" => "application/json"
          ]
        ]);

        $body = $response->getBody();

        $content = (string) $body;

        $this->setPagination(json_decode($content, true, 512, JSON_THROW_ON_ERROR));

        //reset the stream pointer position
        $body->rewind();

        return $response;
    }

    public function fetchOffersPage(Client $client, int $page) : Response
    {
        if (is_null($this->pageLimit) or $page > $this->pageLimit) {
            throw new PageLimitExcededException("You're trying to fetch a non-existing page.");
        }

        $this->currentPage = $page;

        $current_query_url = $this->currentQueryUrl."&page=".$page;

        $response = $client->request("GET", $current_query_url, [
          "headers" => [
            "accept" => "application/json"
          ]
        ]);

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new FourProgramersNormalizer();
    }

    public function filterOffersFromResponse(Response $response) : Generator
    {
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($body["jobs"]["data"] as $key => $offer_array) {
            yield $offer_array;
        }
    }

    public function createUrl(array $query) : string
    {
        extract($query);

        $url = [
          "q" => null,
          "city" => null,
          "tags" => [],
          "salary" => null,
          "locations" => [],
          "json" => 1
        ];
        $q = [];

        if (!is_null($experience)) {
            $q[] = $experience;
        }

        if (!is_null($technology)) {
            if ($this->hasTechnology($technology)) {
                $url["tags"][] = $technology;
            } else {
                $q[] = $technology;
            }
        }

        if (!is_null($city)) {
            if ($this->hasCity($city)) {
                if ($city !== "remote") {
                    $url["locations"][] = $city;
                } else {
                    $url["remote"] = 1;
                    $url["remote_range"] = 100;
                }
            } else {
                $url["city"] = $city;
            }
        }

        if (!is_null($salary)) {
            $url["salary"] = $salary;
        }

        if (!is_null($contract_type)) {
            $q[] = $contract_type;
        }

        if (!empty($q)) {
            $url["q"] = implode(" ", $q);
        }

        $url = array_filter($url);

        $get = http_build_query($url);

        $this->currentQueryUrl = self::URL."Praca?".$get;

        return $this->currentQueryUrl;
    }

    private function setPagination(array $body) : void
    {
        $this->currentPage = $body["jobs"]["meta"]["current_page"];
        $this->pageLimit = $body["jobs"]["meta"]["last_page"];
    }
}
