<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for Website classes
 */
interface CategoryQueryFieldInterface
{

  /**
   * @return bool|array
   */
    public function getCategory();

    public function hasCategory(?string $category) : bool;

    public function allowsCustomCategory() : bool;
}
