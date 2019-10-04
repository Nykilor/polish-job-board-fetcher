<?php
namespace PolishItJobBoardFetcher\Factory\Normalizer;

use DateTime;

use PolishItJobBoardFetcher\DataProvider\Website\Codersbase;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Salary;
use PolishItJobBoardFetcher\Model\Location;

class CodersbaseNormalizer implements WebsiteOfferDataNormalizerInterface
{
    public function normalize($entry_data) : array
    {
        $array = [];
        $array["title"] = $entry_data["title"];
        $array["technology"] = array_keys($entry_data["requiredSkills"]);
        $array["exp"] = $entry_data["experienceLevel"];

        $url_collection_model = new UrlCollection();
        $location_collection = new LocationCollection();

        $location = new Location();
        $location->setAdress($entry_data["officeCity"].", ".$entry_data["officeStreet"]);
        $location->setLatitude($entry_data["latitude"]);
        $location->setLongitude($entry_data["longitude"]);

        $location_collection[] = $location;

        $url_job = new Url();
        $url_job->setUrl(Codersbase::URL."/offer"."/".$entry_data["idInc"]);
        $url_job->setTitle("offer");
        $url_job->setLocation($location_collection);

        $url_collection_model[] = $url_job;

        $array["post_time"] = new DateTime($entry_data["date"]);
        $array["company"] = $entry_data["companyName"];

        $employmentType = $entry_data["employmentType"];

        if (isset($entry_data["salaryFrom"])) {
            $salary = new Salary();
            $salary->setFrom($entry_data["salaryFrom"]);
            $salary->setTo($entry_data["salaryTo"]);

            if ($employmentType === "b2b") {
                $salary->setGross(false);
            } elseif ($employmentType === "permanent") {
                $salary->setGross(true);
            } else {
                $salary->setGross(null);
            }

            $salary->setCurrency($entry_data["currency"]);
        } else {
            $salary = null;
        }


        $array["salary"] = $salary;

        $array["url"] = $url_collection_model;

        if ($employmentType === "both") {
            $employmentType = "b2b/permanent";
        }

        $array["contract_type"] = $employmentType;

        return $array;
    }
}
