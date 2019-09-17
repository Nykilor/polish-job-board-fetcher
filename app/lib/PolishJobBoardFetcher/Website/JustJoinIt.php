<?php

namespace PolishJobBoardFetcher\Website;

use DateTime;

use GuzzleHttp\Client;

use PolishJobBoardFetcher\Model\JobOffer;
use PolishJobBoardFetcher\Model\JobOfferCollection;

/**
 * JustJoin.it API call class
 */
class JustJoinIt implements WebsiteInterface
{
    public const URL = "https://justjoin.it/";

    /**
     * Array containing the JobOffers made from the data fetched.
     * @var JobOfferCollection
     */
    private $offers;

    public function __construct()
    {
        $this->offers = new JobOfferCollection();
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function fetchOffers(Client $client, string $technology, string $city, string $exp)
    {
        $response = $client->request("GET", self::URL."api/offers");
        $body = $response->getBody()->getContents();
        $this->handleFetchResponse(json_decode($body, true), $technology, $city, $exp);
    }

    /**
     * Implementation of the WebsiteInterface
     */
    public function getOffersCollection() : JobOfferCollection
    {
        return $this->offers;
    }

    /**
     * Creates a JobOffer from given data array fetched from JustJoin.it website
     * @param  array    $entry_data Single offer
     * @return JobOffer
     */
    public function createJobOfferModel(array $entry_data) : JobOffer
    {
        $offer = new JobOffer();
        $offer->setTitle($entry_data["title"]);

        $technology = [];
        foreach ($entry_data["skills"] as $key => $skill) {
            $technology[] = $skill["name"];
        }
        $offer->setTechnology($technology);
        $offer->setExp($entry_data["experience_level"]);
        $offer->setUrl(self::URL."offers/".$entry_data["id"]);
        $offer->setCity($entry_data["city"]);
        $offer->setPostTime(new DateTime($entry_data["published_at"]));

        return $offer;
    }

    /**
     * Filter fetched offers $by variables and adds it to $this->offers
     * @param  array  $body       Fetch body.
     * @param  string $technology Technology f.i. "php"
     * @param  string $city       City f.i. "Poznań"
     * @param  string $exp        Experience f.i. "Junior"
     */
    private function handleFetchResponse(array $body, string $technology, string $city, string $exp) : void
    {
        //Because JustJoin.it returns every offer they have with a single api call we need to filter what we want by ourselfs
        foreach ($body as $key => $offer_array) {
            if (is_null($city) or strtolower($city) === strtolower($offer_array["city"])) {
                if (is_null($exp) or strtolower($exp) === strtolower($offer_array["experience_level"])) {
                    if (is_null($technology) or strtolower($technology) === strtolower($offer_array["marker_icon"])) {
                        $this->offers[] = $this->createJobOfferModel($offer_array);
                    }
                }
            }
        }
    }
}
