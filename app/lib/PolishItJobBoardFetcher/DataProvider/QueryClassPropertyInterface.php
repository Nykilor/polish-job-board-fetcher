<?php
namespace PolishItJobBoardFetcher\DataProvider;

/**
 * Interface for websites that need the query in the handleResponse method of WebsiteInterface
 */
interface QueryClassPropertyInterface
{
    public function setQuery(array $query) : void;

    public function getQuery() : array;
}
