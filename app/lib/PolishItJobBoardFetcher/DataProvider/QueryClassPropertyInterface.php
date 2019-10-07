<?php
namespace PolishItJobBoardFetcher\DataProvider;

/**
 * Interface for websites that need the query in the filterOffersFromResponse method of WebsiteInterface
 */
interface QueryClassPropertyInterface
{
    public function setQuery(array $query) : void;

    public function getQuery() : array;
}
