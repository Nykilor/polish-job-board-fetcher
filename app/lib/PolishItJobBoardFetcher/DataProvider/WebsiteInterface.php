<?php
namespace PolishItJobBoardFetcher\DataProvider;

use Generator;

use GuzzleHttp\Client;

use GuzzleHttp\Psr7\Response;

/**
 * Interface for Website classes
 */
interface WebsiteInterface
{
    /**
     * The method to be called by BoardFetcher for each of the websites to initate the fetch of data.
     * @param  Client      $client           GuzzleHttp Client.
     * @param  array       $query            The query to fetch data by.
     */
    public function fetchOffers(Client $client, array $query) : Response;

    public function handleResponse(Response $response) : Generator;
}
