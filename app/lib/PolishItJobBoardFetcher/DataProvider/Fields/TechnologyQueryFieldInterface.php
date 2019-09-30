<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for Website classes
 */
interface TechnologyQueryFieldInterface
{

  /**
   * @return bool|array
   */
    public function getTechnology();

    public function hasTechnology(?string $technology) : bool;

    public function allowsCustomTechnology() : bool;
}
