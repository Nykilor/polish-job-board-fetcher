<?php
namespace PolishItJobBoardFetcher\Utility;

use PolishItJobBoardFetcher\Model\JobOffer;

/**
 * createJobOfferModel method trait.
 */
trait JobOfferFactoryTrait
{
    public function createJobOfferModel(array $job_offer_class_variables_array) : JobOffer
    {
        $offer = new JobOffer();
        $offer->setTitle($job_offer_class_variables_array["title"]);
        $offer->setTechnology($job_offer_class_variables_array["technology"]);
        $offer->setExp($job_offer_class_variables_array["exp"]);
        $offer->setUrl($job_offer_class_variables_array["url"]);
        $offer->setCity($job_offer_class_variables_array["city"]);
        $offer->setPostTime($job_offer_class_variables_array["post_time"]);
        $offer->setCompany($job_offer_class_variables_array["company"]);
        $offer->setSalary($job_offer_class_variables_array["salary"]);

        return $offer;
    }
}
