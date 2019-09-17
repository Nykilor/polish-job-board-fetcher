<?php
namespace PolishJobBoardFetcher;

use GuzzleHttp\Client;

use PolishJobBoardFetcher\Website\WebsiteInterface;

use PolishJobBoardFetcher\Model\JobOfferCollection;

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
     * @param  array  $websites Array of websites implementing PolishJobBoardFetcher\Website\WebsiteInterface
     * @param  array  $by       Array to filter the offers by, it can be by technology, city and exp
     */
    public function fetchFromBy(array $websites, array $by) : void
    {
        $offer_collection = new JobOfferCollection();

        $technology = (isset($by["technology"]))? $by["technology"] : null;
        $city = (isset($by["city"]))? $by["city"] : null;
        $exp = (isset($by["exp"]))? $by["exp"] : null;

        foreach ($websites as $key => $class) {
            $class_instance = new $class;
            if ($class_instance instanceof WebsiteInterface) {
                $class_instance->fetchOffers($this->client, $technology, $city, $exp);
                $offer_collection->merge($class_instance->getOffersCollection());
            } else {
                throw new \Exception("Array entries must be a instance of PolishJobBoardFetcher\Website\WebsiteInterface");
            }
        }

        $this->offers = $offer_collection;
    }

    public function getOffersCollection() :? JobOfferCollection
    {
        return $this->offers;
    }
}
