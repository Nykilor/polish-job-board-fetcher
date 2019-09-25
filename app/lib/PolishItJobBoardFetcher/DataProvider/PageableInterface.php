<?php
namespace PolishItJobBoardFetcher\DataProvider;

/**
 * If we scrap a webpage and it is divided by pages.
 */
interface PageableInterface
{
    public function getCurrentPage() : int;

    public function getNextPageUrl() : string;

    public function fetchNextPage();
}
