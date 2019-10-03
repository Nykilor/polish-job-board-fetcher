<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for the "technology" field in the query
 */
interface TechnologyQueryFieldInterface
{

    /**
     * @return bool|array
     */
    public function getTechnology();

    /**
     * Checks if the array of the field contains the given string
     * @param  string|null $technology
     * @return bool
     */
    public function hasTechnology(?string $technology) : bool;

    public function allowsCustomTechnology() : bool;
}
