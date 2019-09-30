<?php
namespace PolishItJobBoardFetcher\Model;

use JsonSerializable;

use PolishItJobBoardFetcher\Model\Collection\LocationCollection;

/**
 * URL class
 */
class Url implements JsonSerializable
{

   /**
   * The url adress;
   * @var string
   */
    private $url;
    /**
     * The title of the url.
     * @var string
     */
    private $title;

    /**
     * The city that the offer url refers too
     * @var LocationCollection|null
     */
    private $location = null;

    /**
     * Implementation of JsonSerializable
     * @return array Array with all variables of the class.
     */
    public function jsonSerialize() : array
    {
        return get_object_vars($this);
    }

    /**
     * Get the value of The url adress;
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the value of The url adress;
     *
     * @param string url
     *
     * @return self
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the value of The title of the url.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of The title of the url.
     *
     * @param string title
     *
     * @return self
     */
    public function setTitle(string $title)
    {
        $valid_titles = ["offer", "company_homepage", "company_homepage_middleman"];

        if (!in_array($title, $valid_titles)) {
            $imploded_titles = implode(",", $valid_titles);
            throw new \Exception("The title can only be: '$imploded_titles'", 1);
        }

        $this->title = $title;

        return $this;
    }



    /**
     * Get the value of The city that the url refers too
     *
     * @return LocationCollection|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the value of The city that the url refers too
     *
     * @param Location|null location
     *
     * @return self
     */
    public function setLocation(?LocationCollection $location)
    {
        $this->location = $location;

        return $this;
    }
}
