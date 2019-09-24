<?php
namespace PolishItJobBoardFetcher\DataProvider;

use PolishItJobBoardFetcher\Model\Collection\JobOfferCollection;

use PolishItJobBoardFetcher\Model\JobOffer;

/**
 * Interface for Website classes
 */
interface JobOfferFactoryInterface
{

    /**
     * Given array of variables avaliable in JobOffer will create a JobOffer object.
     * @param  array  $job_offer_class_variables_array Array of variables.
     * @return JobOffer
     */
    public function createJobOfferModel(array $job_offer_class_variables_array) : JobOffer;

    /**
     * Will adapt the given single offer array from a website for JobOffer creation
     * @param         $single_entry Single offer from a website
     * @return array               Array of data ready to be used in createJobOfferModel method
     */
    public function adaptFetchedDataForModelCreation($single_entry) : array;

    /**
     * Should return the JobOfferCollection.
     * @return JobOfferCollection
     */
    public function getJobOfferCollection() : JobOfferCollection;
}
