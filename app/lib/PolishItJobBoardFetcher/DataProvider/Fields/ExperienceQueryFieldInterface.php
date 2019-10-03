<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for the "experience" field in the query
 */
interface ExperienceQueryFieldInterface
{

    /**
     * @return bool|array
     */
    public function getExperience();

    /**
     * Checks if the array of the field contains the given string
     * @param  string|null $exp
     * @return bool
     */
    public function hasExperience(?string $exp) : bool;

    public function allowsCustomExperience() : bool;
}
