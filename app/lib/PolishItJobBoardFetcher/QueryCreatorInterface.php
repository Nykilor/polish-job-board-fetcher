<?php
namespace PolishItJobBoardFetcher;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;

/**
 * The interface for the query creator.
 */
interface QueryCreatorInterface
{
    public function getQueryForClass(WebsiteInterface $class_instance) : array;
}
