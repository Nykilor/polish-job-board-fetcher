<?php
namespace PolishItJobBoardFetcher\DataProvider;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

/**
 * Interface for Website classes
 */
interface HasJobOfferNormalizerInterface
{

    /**
     * The normalizer to create an array for JobOffer model creation
     * @return WebsiteOfferDataNormalizerInterface
     */
    public function getNormalizer() : WebsiteOfferDataNormalizerInterface;
}
