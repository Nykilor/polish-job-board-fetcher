<?php
namespace PolishItJobBoardFetcher\Model;

use PolishItJobBoardFetcher\Model\Url;
use PolishItJobBoardFetcher\Model\Collection\UrlCollection;

use JsonSerializable;

/**
 * A model class for JobOffers
 */
class JobOffer implements JsonSerializable
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $exp;
    /**
     * @var array
     */
    private $technology;
    /**
     * @var string
     */
    private $city;
    /**
     * @var UrlCollection
     */
    private $url;
    /**
     * @var \DateTime
     */
    private $postTime;

    /**
     * @var string
     */
    private $company;

    /**
     * @var string
     */
    private $salary;

    /**
     * Implementation of JsonSerializable
     * @return array Array with all variables of the class.
     */
    public function jsonSerialize() : array
    {
        return get_object_vars($this);
    }

    /**
     * Get the value of Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of Title
     *
     * @param string title
     *
     * @return self
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of Exp
     *
     * @return string
     */
    public function getExp()
    {
        return $this->exp;
    }

    /**
     * Set the value of Exp
     *
     * @param string exp
     *
     * @return self
     */
    public function setExp($exp)
    {
        $this->exp = $exp;

        return $this;
    }

    /**
     * Get the value of Technology
     *
     * @return array
     */
    public function getTechnology()
    {
        return $this->technology;
    }

    /**
     * Set the value of Technology
     *
     * @param array technology
     *
     * @return self
     */
    public function setTechnology(array $technology)
    {
        $this->technology = $technology;

        return $this;
    }

    /**
     * Get the value of City
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set the value of City
     *
     * @param string city
     *
     * @return self
     */
    public function setCity(string $city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get the value of Url
     *
     * @return UrlCollection
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the value of Url
     *
     * @param UrlCollection url
     *
     * @return self
     */
    public function setUrl(UrlCollection $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the value of Post Time
     *
     * @return \DateTime
     */
    public function getPostTime()
    {
        return $this->postTime;
    }

    /**
     * Set the value of Post Time
     *
     * @param \DateTime postTime
     *
     * @return self
     */
    public function setPostTime(\DateTime $postTime)
    {
        $this->postTime = $postTime;

        return $this;
    }

    /**
     * Get the value of Company
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the value of Company
     *
     * @param string company
     *
     * @return self
     */
    public function setCompany(string $company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get the value of Salary
     *
     * @return string
     */
    public function getSalary()
    {
        return $this->salary;
    }

    /**
     * Set the value of Salary
     *
     * @param string salary
     *
     * @return self
     */
    public function setSalary(string $salary)
    {
        $this->salary = $salary;

        return $this;
    }
}
