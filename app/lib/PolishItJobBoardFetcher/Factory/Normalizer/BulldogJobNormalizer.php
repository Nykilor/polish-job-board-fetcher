<?php
namespace PolishItJobBoardFetcher\Factory\Normalizer;

use DateTime;
use DOMElement;

use PolishItJobBoardFetcher\Factory\WebsiteOfferDataNormalizerInterface;

use PolishItJobBoardFetcher\Model\Collection\UrlCollection;
use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Salary;
use PolishItJobBoardFetcher\Model\Location;

use Symfony\Component\DomCrawler\Crawler;

class BulldogJobNormalizer implements WebsiteOfferDataNormalizerInterface
{
    public function normalize($entry_data) : array
    {
        if (!($entry_data instanceof DOMElement)) {
            throw new \Exception("Variables has to be an instance of DOMElement class.", 1);
        }

        $array = [];
        $crawler = new Crawler($entry_data);

        $city = explode(",", trim($crawler->filter("span.pop-mobile")->text()));
        $location = new LocationCollection();

        foreach ($city as $key => $adress) {
            $work_location = new Location();

            if ($adress === "Remotely") {
                $adress = "Remote";
            }

            $work_location->setAdress($adress);
            $location[] = $work_location;
        }
        $url_job = new Url();
        $url_job->setUrl($entry_data->getAttribute("data-item-url"));
        $url_job->setTitle("offer");
        $url_job->setLocation($location);

        $url_collection = new UrlCollection();
        $url_collection->addItem($url_job);

        $array["url"] = $url_collection;
        $array["title"] = $crawler->filter("a.result-header-name")->text();
        $array["company"] = trim($crawler->filter("span.pop-black.desktop")->text());
        $array["post_time"] = new DateTime($crawler->filter("p.result-desc-meta span.inline-block")->text());

        $salary = $crawler->filter("p.result-desc-meta span.pop-green");

        $salary_object = null;
        if (!is_null($salary->getNode(0))) {
            $salary = trim($salary->text());
            $salary = str_replace([' ', "PLN"], '', $salary);
            $salary = explode("-", $salary);
            $salary_object = new Salary();
            $salary_object->setFrom($salary[0]);
            $salary_object->setTo($salary[1]);
        }
        $array["salary"] = $salary_object;

        $technology = [];
        foreach ($crawler->filter("ul.tags")->children("li") as $key => $technology_dom_element) {
            $technology_crawler = new Crawler($technology_dom_element);
            $technology[] = $technology_crawler->filter("div.btn")->text();
        }
        $array["technology"] = $technology;
        $array["exp"] = null;
        $array["contract_type"] = null;

        return $array;
    }
}
