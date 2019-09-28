<?php
namespace PolishItJobBoardFetcher\DataProvider;

use GuzzleHttp\Client;

use GuzzleHttp\Psr7\Response;

/**
 * Interface for Website classes
 */
interface WebsiteInterface
{
    public function getUrl() : string;

    /**
     * The method to be called by BoardFetcher for each of the websites to initate the fetch of data.
     * @param  Client      $client           GuzzleHttp Client.
     * @param  string|null $technology       The technology to fetch by.
     * @param  string|null $city             The city to fetch by.
     * @param  string|null $exp              The experience to fetch by.
     * @param  string|null $contract_type    The contract type to fetch by.
     */
    public function fetchOffers(Client $client, ?string $technology, ?string $city, ?string $exp, ?string $category, ?string $contract_type) : Response;

    /**
     * Handles the given response and initates the creation of offers.
     * @param Response $response The Guzzle http response created by the fetchOffers method.
     */
    public function handleResponse(Response $response) : void;

    /**
     * @return bool|array
     */
    public function getTechnology();

    public function hasTechnology(?string $technology) : bool;

    public function allowsCustomTechnology() : bool;

    /**
     * @return bool|array
     */
    public function getCategory();

    public function hasCategory(?string $category) : bool;

    public function allowsCustomCategory() : bool;

    /**
     * @return bool|array
     */
    public function getCity();

    public function hasCity(?string $city) : bool;

    public function allowsCustomCity() : bool;

    /**
     * @return bool|array
     */
    public function getExperience();

    public function hasExperience(?string $exp) : bool;

    public function allowsCustomExperience() : bool;

    /**
     * @return bool|array
     */
    public function getContractType();

    public function hasContractType(?string $contractType) : bool;

    public function allowsCustomContractType() : bool;

    /**
     * Checks if the value is inside the $array, if it is it will look for the $look_for inside key/values
     * and if it is inside values it will return the key
     * @param  array  $array    ["key" => ["value", "value"] ... ]
     * @param  string $look_for The value to look for inside the $array
     * @return string|null          If it finds something it will return the value else null.
     */
    public function getAdaptedNameFromArray(array $array, string $look_for) : ?string;
}
