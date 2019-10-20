<?php
namespace PolishItJobBoardFetcher\Factory\Normalizer;

use DateTime;

use PolishItJobBoardFetcher\DataProvider\Website\JustJoinIt;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Salary;
use PolishItJobBoardFetcher\Model\Location;

class InstaJobsNormalizer implements WebsiteOfferDataNormalizerInterface
{
    public function normalize($entry_data) : array
    {
        $array = [];
        $array["title"] = $entry_data["title"];

        $array["technology"] = array_map(function ($entry) {
            return $entry["name"];
        }, $entry_data["skills"]);

        $location = new LocationCollection();

        if ($entry_data["remote"]) {
            $location_remote = new Location();
            $location_remote->setAdress("Remote");
            $location[] = $location_remote;
        }

        $location_city = new Location();
        $location_city->setLatitude($entry_data["latitude"]);
        $location_city->setLongitude($entry_data["longitude"]);
        $location_city->setAdress($entry_data["city"]);

        $location[] = $location_city;

        $url_job = new Url();
        $url_job->setUrl(JustJoinIt::URL."offers/".$entry_data["id"]);
        $url_job->setTitle("offer");
        $url_job->setLocation($location);

        $url_company = new Url();
        $url_company->setUrl($entry_data["company_url"]);
        $url_company->setTitle("company_homepage");

        $url_collection_model = new UrlCollection();
        $url_collection_model->addItem($url_job);
        $url_collection_model->addItem($url_company);

        $array["exp"] = $entry_data["experience_level"];
        $array["url"] = $url_collection_model;
        $array["post_time"] = new DateTime($entry_data["published_at"]);
        $array["company"] = $entry_data["company_name"];

        if (!is_null($entry_data["salary_from"])) {
            $salary = new Salary();
            $salary->setFrom($entry_data["salary_from"]);
            $salary->setTo($entry_data["salary_to"]);
            $salary->getCurrency($entry_data["salary_currency"]);
        } else {
            $salary = null;
        }

        $array["salary"] = $salary;
        $array["contract_type"] = $entry_data["employment_type"];

        return $array;
    }
}
