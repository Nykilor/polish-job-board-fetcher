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

use PolishItJobBoardFetcher\Factory\Normalizer\OlxNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;

/**
 * Olx.pl API call class
 */
class Olx implements
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

    public const URL = "https://olx.pl/";

    private $technology = [];

    private $city = [
      "2,17871" => ["warszawa"],
      "4,8959" => ["kraków"],
      "3,19701" => ["wrocław"],
      "1,13983" => ["poznań"],
      "5,15983" => ["sopot"],
      "5,5849" => ["gdynia"],
      "5,5659" => ["gdańsk"],
      "18,1079" => ["białystok"],
      "6,3231" => ["bielsko-biała"],
      "15,4019" => ["bydgoszcz"],
      "6,4765" => ["częstochowa"],
      "6,6091" => ["gliwice"],
      "6,7691" => ["katowice"],
      "13,7971" => ["kielce"],
      "8,10119" => ["lublin"],
      "7,10609" => ["łódź"],
      "14,12673" => ["olsztyn"],
      "12,12885" => ["opole"],
      "15,38395" => ["toruń"],
      "17,15241" => ["rzeszów"],
      "11,16705" => ["szczecin"]
    ];

    private $category = [];

    private $experience = [
      "młodszy" => ["junior"],
      "programista" => ["mid", "middle"],
      "senior" => [
        "specjalista",
        "specialist"
      ]
    ];

    private $contractType = [
      "part" => [
        "permanent",
        "uop"
      ],
      "contract" => [
        "contract_work"
      ],
      "zlecenie" => [
        "mandate_contract"
      ],
      "selfemployment" => [
        "b2b"
      ]
    ];

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
        return false;
    }

    public function allowsCustomTechnology() : bool
    {
        return true;
    }

    public function hasCategory(?string $category) : bool
    {
        return false;
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

    public function fetchOffers(Client $client, array $query) : Response
    {
        $response = $client->request("GET", $this->createUrl($query));

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new OlxNormalizer();
    }

    public function filterOffersFromResponse(Response $response) : Generator
    {
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (isset($body["data"])) {
            foreach ($body["data"] as $offer) {
                yield $offer;
            }
        }
    }

    public function createUrl(array $query) : string
    {
        //sets up the variables https://www.php.net/manual/en/function.extract.php
        extract($query);

        $url = "offset=0&limit=10&filter_refiners=spell_checker";

        $query = (!is_null($technology)) ? $technology : "";
        if (!is_null($experience) && empty($query)) {
            $query .= $experience;
        } else {
            $query .= "%20".$experience;
        }

        if (!empty($query)) {
            $url .= "&query=".$query;
        }

        $url .= "&category_id=56";

        if (!is_null($city)) {
            $city = explode(",", $city);
            $url .= "&region_id=".$city[0]."&city_id=".$city[1];
        }

        if (!is_null($contract_type)) {
            $url .= "&filter_enum_contract%5B0%5D=".$contract_type;
        }

        return self::URL."api/v1/offers/?".$url;
    }
}
