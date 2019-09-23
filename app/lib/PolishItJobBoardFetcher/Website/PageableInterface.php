<?php

/**
 * If we scrap a webpage and it is divided by pages.
 */
interface PageableInterface
{
    public $pageLimit = 1;

    public function fetchNextPage();
}
