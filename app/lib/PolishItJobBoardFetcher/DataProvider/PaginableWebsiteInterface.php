<?php
namespace PolishItJobBoardFetcher\DataProvider;

use GuzzleHttp\Client;

use GuzzleHttp\Psr7\Response;

/**
 * The interface for the query creator.
 */
interface PaginableWebsiteInterface extends WebsiteInterface
{
    public function fetchOffersPage(Client $client, int $page) : Response;

    public function getCurrentPage() : ?int;

    public function getPageLimit() : ?int;

    public function getCurrentQueryUrl() : ?string;
}
