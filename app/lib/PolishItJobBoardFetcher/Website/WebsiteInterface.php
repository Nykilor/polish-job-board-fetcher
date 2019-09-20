<?php
namespace PolishItJobBoardFetcher\Website;

use GuzzleHttp\Client;

/**
 * Interface for Website classes
 */
interface WebsiteInterface
{
    /**
     * The method to be called by BoardFetcher for each of the websites to initate the fetch of data.
     * @param  Client $client     GuzzleHttp Client.
     * @param  string|null $technology The technology to fetch by.
     * @param  string|null $city       The city to fetch by.
     * @param  string|null $exp        The experience to fetch by.
     */
    public function fetchOffers(Client $client, ?string $technology, ?string $city, ?string $exp, ?string $category);

    public function hasTechnology(?string $technology) : bool;

    public function allowsCustomTechnology() : bool;

    public function hasCategory(?string $category) : bool;

    public function allowsCustomCategory() : bool;

    public function hasCity(?string $city) : bool;

    public function allowsCustomCity() : bool;

    public function hasExperience(?string $exp) : bool;

    public function allowsCustomExperience() : bool;
}
