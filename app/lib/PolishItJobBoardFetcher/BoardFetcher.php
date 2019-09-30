<?php
namespace PolishItJobBoardFetcher;

use GuzzleHttp\Client;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;

use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\Factory\JobOfferFactory;

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

    private $queryCreator = [];

    private $response = [];

    private $offers;

    public function __construct(array $client_config = [])
    {
        $this->client = (!empty($client_config)) ? new Client($client_config) : new Client();
        $this->offers = new JobOfferCollection();
    }

    public function setQuery(array $query)
    {
        $this->queryCreator = new WebsiteQueryCreator($query);
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

    public function fetch(bool $strict = false)
    {
        if (empty($this->websites) or empty($this->queryCreator)) {
            throw new \Exception("You need to first setQuery() and setWebsites()", 1);
        }

        foreach ($this->websites as $key => $class) {
            $class_instance = $class;

            $query = $this->queryCreator->getQueryForClass($class_instance);

            if ($strict === true) {
                //If any value in the query got null just go on to the next one
                foreach ($this->queryCreator->query as $original_query_key => $original_query_value) {
                    if (array_key_exists($original_query_key, $query) && !is_null($original_query_value) && is_null($query[$original_query_key])) {
                        continue;
                    }
                }
            } else {
                //If everything is null just go to next website
                if (empty(array_filter($query))) {
                    continue;
                }
            }

            $response = $class_instance->fetchOffers($this->client, $query);

            $factory = new JobOfferFactory();

            if ($class_instance instanceof HasJobOfferNormalizerInterface) {
                foreach ($class_instance->handleResponse($response) as $key => $entry_data) {
                    $this->offers[] = $factory->createJobOfferModel($class_instance->getNormalizer(), $entry_data);
                }
            } else {
                throw new \Exception("Class has to implement PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface", 1);
            }
        }
    }

    public function getJobOffersCollection() : JobOfferCollection
    {
        return $this->offers;
    }
}
