<?php
namespace PolishItJobBoardFetcher;

use Generator;

use GuzzleHttp\Client;

use GuzzleHttp\Psr7\Response;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\PaginableWebsiteInterface;
use PolishItJobBoardFetcher\DataProvider\QueryClassPropertyInterface;

use PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface;

use PolishItJobBoardFetcher\Exception\ClassDoesNotExistException;
use PolishItJobBoardFetcher\Exception\MissingClassPropertyException;
use PolishItJobBoardFetcher\Exception\ClassMissingInterfaceException;

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

    private $offers;

    public function __construct(array $client_config = [])
    {
        $this->client = (!empty($client_config)) ? new Client($client_config) : new Client();
        $this->offers = new JobOfferCollection();
    }

    /**
     * @return JobOfferCollection Curently fetched data storage.
     */
    public function getJobOffersCollection() : JobOfferCollection
    {
        return $this->offers;
    }

    /**
     * Sets the query to fetch/filter by.
     * @param array $query
     */
    public function setQuery(array $query)
    {
        $this->queryCreator = new WebsiteQueryCreator($query);
    }

    /**
     * Set the websites to get data from.
     * @param array $websites
     */
    public function setWebsites(array $websites)
    {
        foreach ($websites as $key => $website) {
            if (class_exists($website)) {
                $class_instance = new $website;
            } else {
                throw new ClassDoesNotExistException("$website does not exist.", 1);
            }

            if ($class_instance instanceof WebsiteInterface) {
                $this->websites[] = $class_instance;
            } else {
                throw new ClassMissingInterfaceException("Missing PolishItJobBoardFetcher\DataProvider\Website\WebsiteInterface.", 1);
            }
        }
    }

    /**
     * Initate the fetching of the data for setted websites by setted query
     * @param  bool $yield_response If true the method will return an array where
     * [PolishItJobBoardFetcher\DataProvider\Website\WebsiteInterface => GuzzleHttp\Psr7\Response]
     * @param  bool $strict         If true the websites that f.i. don't allow a technology "php" will be omited
     * @param  int $max_pages       If a websites chunks the results in pages we can set up the max amount of pages to fetch
     * @return Generator|void
     */
    public function fetch(bool $yield_response = false, bool $strict = false, int $max_pages = 1)
    {
        //will throw exception if not
        $this->isQueryAndWebsiteSet();

        foreach ($this->websites as $key => $class) {
            $class_instance = $class;

            $query = $this->queryCreator->getQueryForClass($class_instance);

            if ($strict === true) {
                //If any value in the query got null just go on to the next one
                foreach ($this->queryCreator->query as $original_query_key => $original_query_value) {
                    if (array_key_exists($original_query_key, $query) && !is_null($original_query_value) && is_null($query[$original_query_key])) {
                        //https://www.php.net/manual/en/control-structures.break.php
                        continue 2;
                    }
                }
            } else {
                //If everything is null just go to next website
                if (empty(array_filter($query))) {
                    continue;
                }
            }

            $response = $class_instance->fetchOffers($this->client, $query);

            if ($yield_response) {
                yield [get_class($class_instance) => $response];
            }

            $factory = new JobOfferFactory();

            if ($class_instance instanceof HasJobOfferNormalizerInterface) {
                if ($class_instance instanceof QueryClassPropertyInterface) {
                    $class_instance->setQuery($query);
                }

                $this->createJobOffersFromResponse($class_instance, $response, $factory);

                if ($class_instance instanceof PaginableWebsiteInterface) {
                    $next_page_response = $this->fetchNextPage($class_instance, $max_pages);

                    if ($yield_response) {
                        yield [get_class($class_instance) => $next_page_response];
                    }

                    $this->createJobOffersFromResponse($class_instance, $next_page_response, $factory);
                }
            } else {
                throw new ClassMissingInterfaceException("Missing PolishItJobBoardFetcher\DataProvider\HasJobOfferNormalizerInterface.", 1);
            }
        }
    }

    /**
     * Uses the given $class_instance's method to filter the data and create a JobOffer from it.
     * @param WebsiteInterface $class_instance The website instance.
     * @param Response         $response       The response from the website.
     * @param JobOfferFactory  $factory
     */
    private function createJobOffersFromResponse(WebsiteInterface $class_instance, Response $response, JobOfferFactory $factory) : void
    {
        $normalizer_instance = $class_instance->getNormalizer();

        foreach ($class_instance->filterOffersFromResponse($response) as $key => $entry_data) {
            $this->offers[] = $factory->createJobOfferModel($normalizer_instance, $entry_data);
        }
    }

    /**
     * Fetches the pages of given website untill the $max_pages limit is meet or there's no more pages.
     * @param  PaginableWebsiteInterface $class_instance
     * @param  int                       $max_pages      Max amout of pages.
     * @return Generator                                 Returns the GuzzleHttp\Psr7\Response
     */
    private function fetchNextPage(PaginableWebsiteInterface $class_instance, int $max_pages) : Generator
    {
        $current_page = $class_instance->getCurrentPage();
        $limit = $class_instance->getPageLimit();

        if ($max_pages > 1 and $current_page !== $limit) {
            $fetch_page_limit = ($max_pages <= $limit) ? $max_pages : $limit;
            for ($page_to_fetch = $current_page + 1; $page_to_fetch <= $fetch_page_limit; $page_to_fetch++) {
                $response = $class_instance->fetchOffersPage($this->client, $page_to_fetch);

                yield $response;
            }
        }
    }

    /**
     * Method that checks if the query and website property of this class is set.
     */
    protected function isQueryAndWebsiteSet() : void
    {
        $is_websites_empty = empty($this->websites);
        $is_query_creator_empty = empty($this->queryCreator);

        if ($is_websites_empty or $is_query_creator_empty) {
            $msg = "You need to call";
            $msg .= ($is_websites_empty) ? " setWebsites()" : "";
            $msg .= ($is_query_creator_empty) ? " setQuery()" : "";
            throw new MissingClassPropertyException($msg);
        }
    }
}
