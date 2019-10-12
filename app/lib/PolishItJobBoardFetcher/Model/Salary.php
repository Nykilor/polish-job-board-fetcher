<?php
namespace PolishItJobBoardFetcher\Model;

use JsonSerializable;

/**
 * Salary class
 */
class Salary implements JsonSerializable
{
    /**
     * @var int
     */
    private $from = null;

    /**
     * @var int
     */
    private $to = null;

    /**
     * @var bool|null
     */
    private $gross = null;

    /**
     * @var string
     */
    private $currency = "pln";

    /**
     * @var string
     */
    private $period = "month";

    public function jsonSerialize() : array
    {
        return get_object_vars($this);
    }

    /**
     * Get the value of From
     *
     * @return int
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set the value of From
     *
     * @param int|null $from
     *
     * @return self
     */
    public function setFrom(?int $from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get the value of To
     *
     * @return int|null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set the value of To
     *
     * @param int|null $to
     *
     * @return self
     */
    public function setTo(?int $to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get the value of Gross
     *
     * @return bool|null
     */
    public function getGross()
    {
        return $this->gross;
    }

    /**
     * Set the value of Gross
     *
     * @param bool|null $gross
     *
     * @return self
     */
    public function setGross(?bool $gross)
    {
        $this->gross = $gross;

        return $this;
    }

    /**
     * Get the value of Currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set the value of Currency
     *
     * @param string $currency
     *
     * @return self
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the value of Period
     *
     * @return string
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * Set the value of Period
     *
     * @param string $period
     *
     * @return self
     */
    public function setPeriod(string $period)
    {
        $valid_titles = ["month", "hour", "year"];

        if (!in_array($period, $valid_titles)) {
            $imploded_titles = implode(",", $valid_titles);
            throw new \Exception("The period can't be '$period', allowed periods are: '$imploded_titles'", 1);
        }

        $this->period = $period;

        return $this;
    }
}
