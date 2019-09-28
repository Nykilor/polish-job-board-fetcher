<?php
namespace PolishItJobBoardFetcher;

use Exception;
use Generator;

use GuzzleHttp\Client;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\JobOfferFactoryInterface;

use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;

/**
 * The main class to fetch the data with
 */
class BoardFetcher
{
    /**
     * @var Client
     */
    public $client;

    private $websites = [];

    private $query = [];

    private $response = [];

    public function __construct(array $client_config = [])
    {
        $this->client = (!empty($client_config)) ? new Client($client_config) : new Client();
    }

    public function setWebsites(array $websites)
    {
        foreach ($websites as $key => $website) {
            if (class_exists($website)) {
                $class_instance = new $website;
            } else {
                throw new \Exception("Array needs to contain an existing class.", 1);
            }

            if ($class_instance instanceof WebsiteInterface) {
                $this->websites[] = $class_instance;
            } else {
                throw new \Exception("Class has to instance of PolishItJobBoardFetcher\DataProvider\Website\WebsiteInterface class.", 1);
            }
        }
    }
    //Do this in all the websites.
    public function setQuery(array $by) : void
    {
        $avaliable_query_variables = [
          "technology" => null,
          "city" => null,
          "exp" => null,
          "category" => null,
          "contract_type" => null
        ];

        foreach ($by as $key => $value) {
            if (in_array($key, $avaliable_query_variables)) {
                $avaliable_query_variables[$key] = $value;
            }
        }

        //The list would be just to long if we let the user scroll trough it all
        if (empty(array_filter($avaliable_query_variables))) {
            throw new Exception("You need to specify valid query values.");
        }

        $this->query = $avaliable_query_variables;
    }

    public function fetch(bool $handle_data = true) : Generator
    {
        if (empty($this->websites) or empty($this->query)) {
            throw new \Exception("You need to first setQuery() and setWebsites()", 1);
        }

        foreach ($this->websites as $key => $class) {
            $class_instance = $class;

            $variables = $this->getAdaptedQueryVariableValuesForWebsite($class_instance);

            //If everything is null just go to next website
            if (empty(array_filter($variables))) {
                continue;
            }

            list($technology_adapted, $city_adapted, $exp_adapted, $category_adapted, $contract_type_adapted) = $variables;


            $response = $class_instance->fetchOffers($this->client, $technology_adapted, $city_adapted, $exp_adapted, $category_adapted, $contract_type_adapted);

            if ($handle_data) {
                if ($class_instance instanceof JobOfferFactoryInterface) {
                    $class_instance->handleResponse($response);

                    yield $class_instance->getJobOfferCollection();
                } else {
                    throw new \Exception("Website has to be an instance of JobOfferFactoryInterface", 1);
                }
            } else {
                yield $response;
            }
        }
    }

    public function getOffersCollection() :? JobOfferCollection
    {
        return $this->offers;
    }

    protected function getAdaptedQueryVariableValuesForWebsite(WebsiteInterface $class_instance) : array
    {
        list($technology, $city, $exp, $category, $contract_type) = $this->query;

        //EXPERIENCE
        if (!is_null($exp) and (!$class_instance->allowsCustomExperience() or $class_instance->hasExperience($exp))) {
            $exp_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getExperience(), $exp);
        } else {
            $exp_adapted = $exp;
        }
        //CITY
        if (!is_null($city) and (!$class_instance->allowsCustomCity() or $class_instance->hasCity($city))) {
            $city_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getCity(), $city);
        } else {
            $city_adapted = $city;
        }
        //CATEGORY
        if (!is_null($category) and (!$class_instance->allowsCustomCategory() or $class_instance->hasCategory($category))) {
            $category_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getCategory(), $category);
        } else {
            $category_adapted = $category;
        }
        //TECHNOLOGY
        if (!is_null($technology) and (!$class_instance->allowsCustomTechnology() or $class_instance->hasTechnology($technology))) {
            $technology_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getTechnology(), $technology);
        } else {
            $technology_adapted = $technology;
        }
        //CONTRACT_TYPE
        if (!is_null($contract_type) and (!$class_instance->allowsCustomContractType() or $class_instance->hasContractType($contract_type))) {
            $contract_type_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getContractType(), $contract_type);
        } else {
            $contract_type_adapted = $contract_type;
        }

        return [$technology_adapted, $city_adapted, $exp_adapted, $category_adapted, $contract_type_adapted];
    }
}
