<?php
namespace PolishItJobBoardFetcher\Factory\Normalizer;

use DateTime;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Salary;
use PolishItJobBoardFetcher\Model\Location;

class OlxNormalizer implements WebsiteOfferDataNormalizerInterface
{
    public function normalize($entry_data) : array
    {
        $adress = $entry_data["location"]["city"]["name"];

        if (isset($entry_data["location"]["district"]["name"])) {
            $adress .= ", ".$entry_data["location"]["district"]["name"];
        }

        if (isset($entry_data["location"]["region"]["name"])) {
            $adress .= ", ".$entry_data["location"]["region"]["name"];
        }

        $location = new LocationCollection();
        $work_location = new Location();
        $work_location->setAdress($adress);
        if (isset($entry_data["map"])) {
            $work_location->setLatitude($entry_data["map"]["lat"]);
            $work_location->setLongitude($entry_data["map"]["lon"]);
        }
        $location[] = $work_location;

        $url_job = new Url();
        $url_job->setUrl($entry_data["url"]);
        $url_job->setTitle("offer");
        $url_job->setLocation($location);


        $url_collection = new UrlCollection();
        $url_collection->addItem($url_job);

        $array["url"] = $url_collection;
        $array["title"] = $entry_data["title"];
        $array["company"] = $entry_data["user"]["name"];
        $array["post_time"] = new DateTime($entry_data["last_refresh_time"]);

        $salary = null;
        $contract = null;
        foreach ($entry_data["params"] as $key => $value) {
            switch ($value["key"]) {
            case 'salary':
              $salary = new Salary();
              $salary->setFrom($value["value"]["from"]);
              $salary->setTo($value["value"]["to"]);
              $salary->setCurrency($value["value"]["currency"]);
              $salary->setGross($value["value"]["gross"]);
              break;
            case 'contract':
              $contract = $value["value"]["label"];
              break;
        }
        }

        $array["salary"] = $salary;
        $array["contract_type"] = $contract;

        $array["technology"] = [];
        $array["exp"] = null;

        return $array;
    }
}
