<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for the "category" field in the query
 */
interface CategoryQueryFieldInterface
{

    /**
     * @return bool|array
     */
    public function getCategory();

    /**
     * Checks if the array of the field contains the given string
     * @param  string|null $category
     * @return bool
     */
    public function hasCategory(?string $category) : bool;

    public function allowsCustomCategory() : bool;
}
