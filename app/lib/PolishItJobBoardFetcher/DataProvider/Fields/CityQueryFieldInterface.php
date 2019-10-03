<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for the "city" field in the query
 */
interface CityQueryFieldInterface
{

    /**
     * @return bool|array
     */
    public function getCity();

    /**
     * Checks if the array of the field contains the given string
     * @param  string|null $city
     * @return bool
     */
    public function hasCity(?string $city) : bool;

    public function allowsCustomCity() : bool;
}
