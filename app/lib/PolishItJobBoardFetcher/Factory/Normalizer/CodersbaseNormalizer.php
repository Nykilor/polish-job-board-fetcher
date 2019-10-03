<?php
namespace PolishItJobBoardFetcher\Factory\Normalizer;

use DateTime;

use PolishItJobBoardFetcher\DataProvider\Website\Pracuj;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Salary;
use PolishItJobBoardFetcher\Model\Location;

use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;

class CodersbaseNormalizer implements WebsiteOfferDataNormalizerInterface
{
    public function normalize($entry_data) : array
    {
        $array = [];
        $array["title"] = $entry_data["jobTitle"];
        $array["technology"] = [];
        $array["exp"] = $entry_data["employmentLevel"];

        $url_collection_model = new UrlCollection();

        $url_company = new Url();
        $url_company->setUrl($entry_data["companyProfileUrl"]);
        $url_company->setTitle("company_homepage_middleman");

        $url_collection_model[] = $url_company;

        $city = [];
        foreach ($entry_data["offers"] as $single_offer) {
            $location_collection = new LocationCollection();
            $location = new Location();
            $location->setAdress($single_offer["label"]);
            if (strlen($single_offer["geoCoordinates"]) > 2) {
                $geo = explode(", ", $single_offer["geoCoordinates"]);
                $location->setLatitude($geo[0]);
                $location->setLongitude($geo[1]);
            }

            $location_collection[] = $location;

            $url_job = new Url();
            $url_job->setUrl(Pracuj::URL.$single_offer["offerUrl"]);
            $url_job->setTitle("offer");
            $url_job->setLocation($location_collection);

            $url_collection_model[] = $url_job;
        }

        $array["post_time"] = new DateTime($entry_data["lastPublicated"]);
        $array["company"] = $entry_data["employer"];

        $salary = null;

        if (!empty($entry_data["salary"])) {
            //They use some escaping, you can check for yourself by going print bin2hex($salary_string)
            //and visiting f.i. https://codebeautify.org/hex-string-converter
            $salary_string = str_replace(["&nbsp;", "&#322;", "&ndash;"], ["", "ł", "-"], $entry_data["salary"]);

            $salary_substr = substr($salary_string, 0, strpos($salary_string, "zł"));

            $salary_from_to = explode("-", $salary_substr);

            $salary = new Salary();
            $salary->setFrom($salary_from_to[0]);
            $salary->setTo($salary_from_to[1]);
            $salary->setGross(true);

            $is_hourly = (strpos($salary_string, "godz") !== false) ? true : false;

            if ($is_hourly) {
                $salary->setPeriod("hour");
            }
        }


        $array["salary"] = $salary;

        $array["url"] = $url_collection_model;
        $array["contract_type"] = implode(",", $entry_data["typesOfContract"]);

        return $array;
    }
}
