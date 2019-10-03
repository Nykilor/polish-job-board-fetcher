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
use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\DataProvider\WebsiteType\Redux;

use PolishItJobBoardFetcher\Factory\Normalizer\PracujNormalizer;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;
use PolishItJobBoardFetcher\Utility\WebsiteInterfaceHelperTrait;

/**
 * Pracuj.pl webstie redux scrapping class
 */
class Pracuj extends Redux implements
    WebsiteInterface,
    HasJobOfferNormalizerInterface,
    CategoryQueryFieldInterface,
    CityQueryFieldInterface,
    ContractTypeQueryFieldInterface,
    ExperienceQueryFieldInterface,
    TechnologyQueryFieldInterface,
    SalaryQueryFieldInterface
{
    use ReplacePolishLettersTrait;
    use WebsiteInterfaceHelperTrait;

    public const URL = "https://www.pracuj.pl";

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

    private $contractType = [
      "0" => [
        "permanent"
      ],
      "1" => [
        "contract_work"
      ],
      "2" => [
        "mandate_contract"
      ],
      "3" => [
        "b2b"
      ]
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

    public function getContractType()
    {
        return $this->contractType;
    }

    public function getSalary()
    {
        return $this->salary;
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
        $response = $client->request("GET", self::URL."/praca".$this->createQueryUrl($query["technology"], $query["city"], $query["experience"], $query["category"], $query["contract_type"], $query["salary"]));

        return $response;
    }

    public function getNormalizer() : WebsiteOfferDataNormalizerInterface
    {
        return new PracujNormalizer();
    }

    public function handleResponse(Response $response) : Generator
    {
        $body = $response->getBody()->getContents();

        $this->setInitialStateFromHtml($body);

        $initial_state = json_decode(substr($this->getInitialState(), 0, -3), true, 512, JSON_THROW_ON_ERROR);
        
        foreach ($initial_state["offers"] as $key => $offer) {
            yield $offer;
        }
    }

    /**
     * Creates the query url for this website.
     * @param  ?string $technology
     * @param  ?string $city
     * @param  ?string $exp
     * @param  ?string $category
     * @param  ?string $contract_type
     * @param  ?int    $salary
     * @return string                 The last portion of the url
     */
    private function createQueryUrl(?string $technology, ?string $city, ?string $exp, ?string $category, ?string $contract_type, ?int $salary) : string
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

        $url = $this->addGetVariableToUrl($url, "cc", $category_string);

        if (!is_null($contract_type)) {
            $url = $this->addGetVariableToUrl($url, "tc", $contract_type);
        }

        if (!is_null($salary)) {
            $url = $this->addGetVariableToUrl($url, "sal", $salary);
        }

        return $url;
    }

    /**
     * Adds a variable to the $_GET portion of the url
     * @param  string $url   The url to append the variable to
     * @param  string $key   The $_GET[$key]
     * @param  mixed  $value
     * @return string        Returns the $url with added variable
     */
    protected function addGetVariableToUrl(string $url, string $key, $value) : string
    {
        if (strpos($url, "?") === false) {
            return $url."?".$key."=".$value;
        } else {
            return $url."&".$key."=".$value;
        }
    }
}
