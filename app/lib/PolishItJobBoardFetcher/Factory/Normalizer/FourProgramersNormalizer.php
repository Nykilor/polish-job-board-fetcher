<?php
namespace PolishItJobBoardFetcher\Factory\Normalizer;

use DateTime;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Salary;
use PolishItJobBoardFetcher\Model\Location;

use PolishItJobBoardFetcher\Utility\ReplacePolishLettersTrait;

class FourProgramersNormalizer implements WebsiteOfferDataNormalizerInterface
{
    use ReplacePolishLettersTrait;
    public function normalize($entry_data) : array
    {
        $array = [];
        $array["title"] = $entry_data["title"];

        $technology = [];
        foreach ($entry_data["tags"] as $key => $tag) {
            $technology[] = $tag["name"];
        }
        $array["technology"] = $technology;

        $array["exp"] = null;

        $url_collection_model = new UrlCollection();
        $location_collection = new LocationCollection();

        if (isset($entry_data["firm"]) && isset($entry_data["firm"]["url"])) {
            $url_company = new Url();
            $url_company->setUrl($entry_data["firm"]["url"]);
            $url_company->setTitle("company_homepage_middleman");
            $url_collection_model[] = $url_company;
        }

        foreach ($entry_data["locations"] as $location_adress) {
            $location = new Location();

            $adress = [
              $location_adress["city"],
              $location_adress["street"],
              $location_adress["street_number"]
            ];
            $adress = array_filter($adress);

            $location->setAdress(implode(", ", $adress));

            $location_collection[] = $location;
        }

        if ($entry_data["remote"]["enabled"] && $entry_data["remote"]["range"] === 100) {
            $remote = new Location();
            $remote->setAdress("Remote");
            $location_collection[] = $remote;
        }

        $url_job = new Url();
        $url_job->setUrl($entry_data["url"]);
        $url_job->setTitle("offer");
        $url_job->setLocation($location_collection);

        $url_collection_model[] = $url_job;

        $array["post_time"] = new DateTime($entry_data["created_at"]);
        $array["company"] = $entry_data["firm"]["name"];

        $salary = null;

        $salary = new Salary();
        $salary->setFrom($entry_data["salary_from"]);
        $salary->setTo($entry_data["salary_to"]);
        $salary->setGross($entry_data["is_gross"]);
        $salary->setCurrency($entry_data["currency_symbol"]);

        $is_hourly = (strpos($entry_data["rate_label"], "godzinowo") !== false) ? true : false;

        if ($is_hourly) {
            $salary->setPeriod("hour");
        }

        $array["salary"] = $salary;

        $array["url"] = $url_collection_model;
        $array["contract_type"] = null;

        return $array;
    }
}
