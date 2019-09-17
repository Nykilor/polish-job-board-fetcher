<?php
namespace PolishJobBoardFetcher\Website;

use GuzzleHttp\Client;

use PolishJobBoardFetcher\Model\JobOfferCollection;

/**
 * Interface for Website classes
 */
interface WebsiteInterface
{
    /**
     * The method to be called by BoardFetcher for each of the websites to initate the fetch of data.
     * @param  Client $client     GuzzleHttp Client.
     * @param  string $technology The technology to fetch by.
     * @param  string $city       The city to fetch by.
     * @param  string $exp        The experience to fetch by.
     */
    public function fetchOffers(Client $client, string $technology, string $city, string $exp);

    /**
     * Should return the JobOfferCollection.
     * @return JobOfferCollection
     */
    public function getOffersCollection() : JobOfferCollection;
}
