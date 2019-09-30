<?php
namespace PolishItJobBoardFetcher\Factory;

interface WebsiteOfferDataNormalizerInterface
{
    /**
     * Normalizes the single offer data from a website for the factory to handle
     * @param  mixed $entry_data The single offer data
     * @return array              Array for the JobOfferFactory to use
     */
    public function normalize($entry_data) : array;
}
