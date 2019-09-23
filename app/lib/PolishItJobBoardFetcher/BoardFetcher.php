<?php
namespace PolishItJobBoardFetcher;

use Exception;

use GuzzleHttp\Client;

use PolishItJobBoardFetcher\Website\WebsiteInterface;

use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;

use PolishItJobBoardFetcher\Website\JobOfferFactoryInterface;

/**
 * The main class to fetch the data with
 */
class BoardFetcher
{
    /**
     * @var Client
     */
    public $client;

    /**
     * @var JobOfferCollection
     */
    private $offers;

    public function __construct(array $client_config = [])
    {
        $this->client = (!empty($client_config)) ? new Client($client_config) : new Client();
    }

    /**
     * Initate a fetch from given $websites filtered $by["technology"|"city"|"exp"]
     * @param  array  $websites Array of websites implementing PolishItJobBoardFetcher\Website\WebsiteInterface
     * @param  array  $by       Array to filter the offers by, it can be by technology, city and exp
     */
    public function fetchFromBy(array $websites, array $by) : void
    {
        $offer_collection = new JobOfferCollection();
        $fall_back_array = [];

        $technology = (isset($by["technology"]))? $by["technology"] : null;
        $city = (isset($by["city"]))? $by["city"] : null;
        $exp = (isset($by["exp"]))? $by["exp"] : null;
        $category = (isset($by["category"]))? $by["category"] : null;

        //The list would be just to long if we let the user scroll trough it all
        if (is_null($technology) && is_null($city) && is_null($exp) && is_null($category)) {
            throw new Exception("You need to specify either city, technology, exp or category");
        }

        foreach ($websites as $key => $class) {
            $class_instance = new $class;
            if ($class_instance instanceof WebsiteInterface) {
                if (!is_null($exp) and (!$class_instance->allowsCustomExperience() or $class_instance->hasExperience($exp))) {
                    $exp_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getExperience(), $exp);
                } else {
                    $exp_adapted = $exp;
                }

                if (!is_null($city) and (!$class_instance->allowsCustomCity() or $class_instance->hasCity($city))) {
                    $city_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getCity(), $city);
                } else {
                    $city_adapted = $city;
                }

                if (!is_null($category) and (!$class_instance->allowsCustomCategory() or $class_instance->hasCategory($category))) {
                    $category_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getCategory(), $category);
                } else {
                    $category_adapted = $category;
                }

                if (!is_null($technology) and (!$class_instance->allowsCustomTechnology() or $class_instance->hasTechnology($technology))) {
                    $technology_adapted = $class_instance->getAdaptedNameFromArray($class_instance->getTechnology(), $technology);
                } else {
                    $technology_adapted = $technology;
                }
                //If everything is null just go to next website
                if (is_null($technology_adapted) && is_null($city_adapted) && is_null($exp_adapted) && is_null($category_adapted)) {
                    continue;
                }

                $class_instance->fetchOffers($this->client, $technology_adapted, $city_adapted, $exp_adapted, $category_adapted);
                if ($class_instance instanceof JobOfferFactoryInterface) {
                    $offer_collection->merge($class_instance->getJobOfferCollection());
                } else {
                    throw new \Exception("Website has to be an instance of JobOfferFactoryInterface", 1);
                }
            } else {
                throw new \Exception("Array entries must be a instance of PolishItJobBoardFetcher\Website\WebsiteInterface");
            }
        }

        $this->offers = $offer_collection;
    }

    public function getOffersCollection() :? JobOfferCollection
    {
        return $this->offers;
    }
}
