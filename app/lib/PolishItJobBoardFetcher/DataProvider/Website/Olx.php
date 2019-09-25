<?php

namespace PolishItJobBoardFetcher\DataProvider\Website;

use DateTime;

use GuzzleHttp\Client;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;

use PolishItJobBoardFetcher\Model\Url;

use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;
use PolishItJobBoardFetcher\Utility\JobOfferFactoryTrait;
use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;
use PolishItJobBoardFetcher\DataProvider\JobOfferFactoryInterface;

/**
 * olx.pl API call class
 */
class Olx implements WebsiteInterface, JobOfferFactoryInterface
{
    use JobOfferFactoryTrait;
    use WebsiteInterfaceHelperTrait;
    use ReplacePolishLettersTrait;

    private $url = "https://olx.pl/";

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
        "permanent"
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

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, ?string $technology, ?string $city, ?string $exp, ?string $category, ?string $contract_type)
    {
        $response = $client->request("GET", $this->url."api/v1/offers/?".$this->createQueryUrl($technology, $city, $exp, $category, $contract_type));
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

    private function handleFetchResponse($body)
    {
        if (isset($body["data"])) {
            foreach ($body["data"] as $offer) {
                $this->offers[] = $this->createJobOfferModel($this->adaptFetchedDataForModelCreation($offer));
            }
        }
    }

    /**
     * Implementation of JobOfferFactoryInterface
     */
    public function adaptFetchedDataForModelCreation($offer) : array
    {
        $city = $offer["location"]["city"]["name"];

        if (isset($offer["location"]["district"]["name"])) {
            $city .= ", ".$offer["location"]["district"]["name"];
        }

        if (isset($offer["location"]["region"]["name"])) {
            $city .= ", ".$offer["location"]["region"]["name"];
        }

        $url_job = new Url();
        $url_job->setUrl($offer["url"]);
        $url_job->setTitle("offer");
        $url_job->setCity($city);

        $url_collection = new UrlCollection();
        $url_collection->addItem($url_job);

        $array["url"] = $url_collection;
        $array["title"] = $offer["title"];
        $array["company"] = "";
        $array["city"] = $city;
        $array["post_time"] = new DateTime($offer["last_refresh_time"]);

        $salary = "";
        $contract = "";
        foreach ($offer["params"] as $key => $value) {
            switch ($value["key"]) {
              case 'salary':
                $salary = $value["name"]." ";
                $salary .= $value["value"]["from"]." - ".$value["value"]["to"];
                break;
              case 'contract':
                $contract = $value["value"]["label"];
                break;
          }
        }

        $array["salary"] = $salary;
        $array["contract_type"] = $contract;

        $array["technology"] = [];
        $array["exp"] = "";

        return $array;
    }

    /**
     * Creates the end of the url that queries the website
     * @param  string|null $technology
     * @param  string|null $city
     * @param  string|null $exp
     * @param  string|null $category
     * @return string              URL for query
     */
    private function createQueryUrl(?string $technology, ?string $city, ?string $exp, ?string $category, ?string $contract_type) : string
    {
        $url = "offset=0&limit=10&filter_refiners=spell_checker";

        $query = (!is_null($technology)) ? $technology : "";
        if (!is_null($exp) && empty($query)) {
            $query .= $exp;
        } else {
            $query .= "%20".$exp;
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

        return $url;
    }
}
