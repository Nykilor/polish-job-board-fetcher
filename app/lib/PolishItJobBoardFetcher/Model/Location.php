<?php
namespace PolishItJobBoardFetcher\Model;

use JsonSerializable;

/**
 * Location class
 */
class Location implements JsonSerializable
{
    /**
     * @var string
     */
    private $adress = null;

    /**
     * @var string
     */
    private $latitude = null;

    /**
     * @var string
     */
    private $longitude = false;

    public function jsonSerialize() : array
    {
        return get_object_vars($this);
    }

    /**
     * Get the value of Adress
     *
     * @return string|null
     */
    public function getAdress()
    {
        return $this->adress;
    }

    /**
     * Set the value of Adress
     *
     * @param string|null adress
     *
     * @return self
     */
    public function setAdress(?string $adress)
    {
        $this->adress = $adress;

        return $this;
    }

    /**
     * Get the value of Latitude
     *
     * @return string|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set the value of Latitude
     *
     * @param string|null latitude
     *
     * @return self
     */
    public function setLatitude(?string $latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get the value of Longitude
     *
     * @return string|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set the value of Longitude
     *
     * @param string|null longitude
     *
     * @return self
     */
    public function setLongitude(?string $longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }
}
