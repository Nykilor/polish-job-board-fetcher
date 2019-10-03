<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for the "contract_type" field in the query
 */
interface ContractTypeQueryFieldInterface
{

    /**
     * @return bool|array
     */
    public function getContractType();

    /**
     * Checks if the array of the field contains the given string
     * @param  string|null $contractType
     * @return bool
     */
    public function hasContractType(?string $contractType) : bool;

    public function allowsCustomContractType() : bool;
}
