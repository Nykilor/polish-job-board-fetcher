<?php

namespace PolishItJobBoardFetcher\Website;

use DateTime;

use GuzzleHttp\Client;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;

use PolishItJobBoardFetcher\Model\Url;

use PolishItJobBoardFetcher\Utility\JobOfferFactoryTrait;
use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;
use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;

use Symfony\Component\DomCrawler\Crawler;

/**
 * JustJoin.it API call class
 */
class Pracuj implements WebsiteInterface, JobOfferFactoryInterface
{
    use JobOfferFactoryTrait;
    use ReplacePolishLettersTrait;
    use WebsiteInterfaceHelperTrait;

    private $url = "https://www.pracuj.pl";

    private $technology = [];

    private $city = [];

    private $category = [
      "5015" => [
        "devops", "analyst", "analityk",
        "administrator", "support", "wsparcie",
        "project_manager", "pm", "project manager",
        "project-manager", "tester", "tech_lead",
        "tl", "team leader", "tech lead",
        "scrum_master", "scrum", "sm",
        "agile coach",
      ],
      "5016" => [
        "backend", "fullstack", "frontend",
        "architect", "qa", "mobile",
        "embedded",
      ],
      "5026" => [
        "designer", "ux/ui", "design",
        "ux", "ui"
      ]
    ];

    private $experience = [
        "junior",
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
        return false;
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
        return false;
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
        $response = $client->request("GET", $this->url."/praca".$this->createQueryUrl($technology, $city, $exp, $category));
        $body = $response->getBody()->getContents();
        $this->handleFetchResponse($body);
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
        $array["title"] = $entry_data["jobTitle"];
        $array["technology"] = [];
        $array["exp"] = $entry_data["employmentLevel"];

        $url_collection_model = new UrlCollection();

        $url_company = new Url();
        $url_company->setUrl($entry_data["companyProfileUrl"]);
        $url_company->setTitle("company_homepage_middleman");

        $url_collection_model->addItem($url_company);

        $city = [];
        foreach ($entry_data["offers"] as $single_offer) {
            $url_job = new Url();
            $url_job->setUrl($this->url.$single_offer["offerUrl"]);
            $url_job->setTitle("offer");
            $url_job->setCity($single_offer["label"]);

            $city[] = $single_offer["label"];

            $url_collection_model->addItem($url_job);
        }

        $array["post_time"] = new DateTime($entry_data["lastPublicated"]);
        $array["company"] = $entry_data["employer"];
        $array["salary"] = $entry_data["salary"];

        $array["url"] = $url_collection_model;
        $array["city"] = implode(",", $city);

        return $array;
    }

    private function handleFetchResponse($body)
    {
        $crawler = new Crawler($body);
        $script = $crawler->filter("script");

        foreach ($script as $dom_element) {
            if (strpos($dom_element->nodeValue, "window.__INITIAL_STATE__") !== false) {
                $offers_dom_element = $dom_element;
                break;
            } else {
                $dom_element = null;
            }
        }

        $script_text = $dom_element->nodeValue;
        $json = "";
        preg_match("/\=(.*?)\n/", $script_text, $json);

        if (empty($json)) {
            throw new \Exception("No JSON offers found on the page.", 1);
        }

        $json = $json[0];
        //My regex is not perfect, we have to fix it by omiting few chars
        $valid_json = substr($json, 2, -3);
        //print $valid_json;
        $entry_data_array = json_decode($valid_json, true);

        foreach ($entry_data_array["offers"] as $key => $offer) {
            $this->offers[] = $this->createJobOfferModel($this->adaptFetchedDataForModelCreation($offer));
        }
    }


    private function createQueryUrl(?string $technology, ?string $city, ?string $exp, ?string $category) : string
    {
        $first_part = (is_null($technology))? "" : "/$technology";
        $second_part = "";

        if (!is_null($city)) {
            $second_part = "/".strtolower($this->replacePolishLetters($city)).";wp";
        }

        $special_case_array = ["mid", "regular", "senior"];
        $is_exp_null = is_null($exp);
        $is_exp_in_special_case_array = in_array($exp, $special_case_array);

        if (!$is_exp_null) {
            if (!$is_exp_in_special_case_array) {
                $first_part .= "-x44-$exp;kw";
            } elseif ($is_exp_in_special_case_array) {
                $second_part .= "?et=4";
            }
        }

        if (!empty($first_part) && strpos($first_part, ";kw") === false) {
            $first_part .= ";kw";
        }

        $url = "";

        if (!empty($first_part)) {
            $url .= $first_part;
        }

        if (!empty($second_part)) {
            $url .= $second_part;
        }

        //Categories specification IT - administration, programing, design
        if (is_null($category)) {
            $category_string = "5015%2c5016%2c5026";
        } else {
            $category_string = $category;
        }

        if (strpos($second_part, "?et=4") !== false) {
            $url .= "&cc=".$category_string;
        } else {
            $url .= "?cc=".$category_string;
        }

        return $url;
    }
}
