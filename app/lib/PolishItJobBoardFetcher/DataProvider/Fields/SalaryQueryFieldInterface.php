<?php
namespace PolishItJobBoardFetcher\DataProvider\Fields;

/**
 * Interface for the "salary" field in the query
 */
interface SalaryQueryFieldInterface
{

    /**
     * @return bool|array
     */
    public function getSalary();

    /**
     * Checks if the array of the field contains the given string
     * @param  int|null $salary
     * @return bool
     */
    public function hasSalary(?int $salary) : bool;

    public function allowsCustomSalary() : bool;
}
