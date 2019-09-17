<?php
namespace PolishJobBoardFetcher\Model;

use Iterator;
use Countable;
use ArrayAccess;
use JsonSerializable;

/**
 * A collection class for JobOffers
 */
class JobOfferCollection implements Countable, JsonSerializable, Iterator, ArrayAccess
{
    /**
     * @var array
     */
    private $items = [];
    private $position = 0;

    public function addItem(JobOffer $item)
    {
        $this->items[] = $item;
    }

    public function getItems() : array
    {
        return $this->items;
    }

    public function count()
    {
        return count($this->items);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function key()
    {
        return $this->position;
    }

    public function current()
    {
        return $this->items[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function valid()
    {
        return isset($this->items[$this->position]);
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetSet($offset, $value)
    {
        if (!($value instanceof JobOffer)) {
            throw new \InvalidArgumentException("Must be an instance of JobOffer");
        }

        if (empty($offset)) { //this happens when you do $collection[] = 1;
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    public function merge(JobOfferCollection $merge_with) : self
    {
        $this->items = array_merge($this->items, $merge_with->getItems());

        return $this;
    }

    public function jsonSerialize()
    {
        return $this->items;
        // $array = [];
        // if (!empty($this->items)) {
        //     foreach ($this->items as $position => $model) {
        //         $array[] = $model->jsonSerialize();
        //     }
        // }
    }
}
