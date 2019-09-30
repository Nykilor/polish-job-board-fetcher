<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for Website classes
 */
interface CityQueryFieldInterface
{

  /**
   * @return bool|array
   */
    public function getCity();

    public function hasCity(?string $city) : bool;

    public function allowsCustomCity() : bool;
}
