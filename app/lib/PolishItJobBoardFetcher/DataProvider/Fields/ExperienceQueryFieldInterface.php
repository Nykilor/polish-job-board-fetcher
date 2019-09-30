<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for Website classes
 */
interface ExperienceQueryFieldInterface
{

  /**
   * @return bool|array
   */
    public function getExperience();

    public function hasExperience(?string $exp) : bool;

    public function allowsCustomExperience() : bool;
}
