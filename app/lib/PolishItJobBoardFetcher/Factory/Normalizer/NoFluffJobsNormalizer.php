<?php
namespace PolishItJobBoardFetcher\Factory\Normalizer;

use DateTime;

use PolishItJobBoardFetcher\DataProvider\Website\NoFluffJobs;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Location;

class NoFluffJobsNormalizer implements WebsiteOfferDataNormalizerInterface
{
    public function normalize($entry_data) : array
    {
        $array = [];
        $array["title"] = $entry_data["title"];
        $array["technology"] = [$entry_data["technology"]];
        $array["exp"] = implode(", ", $entry_data["seniority"]);

        $location = new LocationCollection();

        if ($entry_data["fullyRemote"]) {
            $remote_location = new Location();
            $remote_location->setAdress("Remote");
            $location[] = $remote_location;
        }

        foreach ($entry_data["location"]["places"] as $key => $place) {
            $location[] = $this->setLocation($place);
        }

        $url_job = new Url();
        $url_job->setUrl(NoFluffJobs::URL."job/".$entry_data["url"]);
        $url_job->setTitle("offer");
        $url_job->setLocation($location);

        $url_collection = new UrlCollection();
        $url_collection->addItem($url_job);

        $array["url"] = $url_collection;

        $posted = (isset($entry_data["renewed"]))? $entry_data["renewed"] : $entry_data["posted"];
        $posted = substr($posted, 0, 10);
        $date = new DateTime();
        $date->setTimestamp($posted);
        $array["post_time"] = $date;

        $array["company"] = $entry_data["name"];
        $array["salary"] = null;
        $array["contract_type"] = null;

        return $array;
    }

    protected function setLocation($place)
    {
        $adress = $place["city"].", ".$place["street"].", ".$place["postalCode"];
        $work_location = new Location();
        $work_location->setAdress(rtrim($adress, ", "));
        if (isset($place["geoLocation"])) {
            $work_location->setLatitude($place["geoLocation"]["latitude"]);
            $work_location->setLongitude($place["geoLocation"]["longitude"]);
        }

        return $work_location;
    }
}
