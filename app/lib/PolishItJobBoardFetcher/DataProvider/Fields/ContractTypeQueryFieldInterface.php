<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for Website classes
 */
interface ContractTypeQueryFieldInterface
{

  /**
   * @return bool|array
   */
    public function getContractType();

    public function hasContractType(?string $contractType) : bool;

    public function allowsCustomContractType() : bool;
}
