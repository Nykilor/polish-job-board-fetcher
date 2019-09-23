<?php

namespace PolishItJobBoardFetcher\Website;

use DateTime;

use GuzzleHttp\Client;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;

use PolishItJobBoardFetcher\Model\Url;

use PolishItJobBoardFetcher\Utility\JobOfferFactoryTrait;
use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;

/**
 * JustJoin.it API call class
 */
class JustJoinIt implements WebsiteInterface, JobOfferFactoryInterface
{
    use JobOfferFactoryTrait;
    use WebsiteInterfaceHelperTrait;

    private $url = "https://justjoin.it/";

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

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, ?string $technology, ?string $city, ?string $exp, ?string $category)
    {
        $response = $client->request("GET", $this->url."api/offers");
        $body = $response->getBody()->getContents();
        $this->handleFetchResponse(json_decode($body, true), $technology, $city, $exp, $category);
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function getJobOfferCollection() : JobOfferCollection
    {
        return $this->offers;
    }

    /**
     * Implementation of JobOfferFactoryInterface
     */
    public function adaptFetchedDataForModelCreation($entry_data) : array
    {
        $array = [];
        $array["title"] = $entry_data["title"];

        $array["technology"] = array_map(function ($entry) {
            return $entry["name"];
        }, $entry_data["skills"]);
        $city = ($entry_data["remote"]) ? "Remote" : $entry_data["city"];

        $url_job = new Url();
        $url_job->setUrl($this->url."offers/".$entry_data["id"]);
        $url_job->setTitle("offer");
        $url_job->setCity($city);

        $url_company = new Url();
        $url_company->setUrl($entry_data["company_url"]);
        $url_company->setTitle("company_homepage");

        $url_collection_model = new UrlCollection();
        $url_collection_model->addItem($url_job);
        $url_collection_model->addItem($url_company);

        $array["exp"] = $entry_data["experience_level"];
        $array["url"] = $url_collection_model;
        $array["city"] = $city;
        $array["post_time"] = new DateTime($entry_data["published_at"]);
        $array["company"] = $entry_data["company_name"];

        if (!is_null($entry_data["salary_from"])) {
            $salary = $entry_data["salary_from"]." - ".$entry_data["salary_to"]." ".$entry_data["salary_currency"];
        } else {
            $salary = "";
        }
        $array["salary"] = $salary;

        return $array;
    }

    /**
     * Filter fetched offers $by variables and adds it to $this->offers
     * @param  array  $body       Fetch body.
     * @param  string|null $technology Technology f.i. "php"
     * @param  string|null $city       City f.i. "Poznań"
     * @param  string|null $exp        Experience f.i. "Junior"
     */
    private function handleFetchResponse(array $body, ?string $technology, ?string $city, ?string $exp, ?string $category) : void
    {
        //Because JustJoin.it returns every offer they have with a single api call we need to filter what we want by ourselfs,
        //Because some websites use certain "technologies" of this one as categories we had to split the technology into technology and category
        $look_for_in_marker_icon = [];

        if (!is_null($category)) {
            $look_for_in_marker_icon[] = strtolower($category);
        }

        if (!is_null($technology)) {
            $look_for_in_marker_icon[] = strtolower($technology);
        }

        foreach ($body as $key => $offer_array) {
            if (is_null($city) or $city === strtolower($offer_array["city"]) or ($city === "remote" && $offer_array["remote"])) {
                if (is_null($exp) or $exp === $offer_array["experience_level"]) {
                    if (empty($look_for_in_marker_icon) or in_array($offer_array["marker_icon"], $look_for_in_marker_icon)) {
                        $this->offers[] = $this->createJobOfferModel($this->adaptFetchedDataForModelCreation($offer_array));
                    }
                }
            }
        }
    }
}
