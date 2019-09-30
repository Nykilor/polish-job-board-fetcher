<?php
namespace PolishItJobBoardFetcher\Factory;

use PolishItJobBoardFetcher\Model\JobOffer;

class JobOfferFactory
{
    /**
     * Creates the JobOffer from given array
     * @param  array    $entry_data
     * @return JobOffer
     */
    public function createJobOfferModel(WebsiteOfferDataNormalizerInterface $normalizer, $entry_data) : JobOffer
    {
        $job_offer_class_variables_array = $normalizer->normalize($entry_data);
        $offer = new JobOffer();
        $offer->setTitle($job_offer_class_variables_array["title"]);
        $offer->setTechnology($job_offer_class_variables_array["technology"]);
        $offer->setExp($job_offer_class_variables_array["exp"]);
        $offer->setUrl($job_offer_class_variables_array["url"]);
        $offer->setPostTime($job_offer_class_variables_array["post_time"]);
        $offer->setCompany($job_offer_class_variables_array["company"]);
        $offer->setSalary($job_offer_class_variables_array["salary"]);
        $offer->setContractType($job_offer_class_variables_array["contract_type"]);

        return $offer;
    }
}
