<?php
namespace PolishItJobBoardFetcher\Model\Collection;

use Iterator;
use Countable;
use ArrayAccess;
use JsonSerializable;

use PolishItJobBoardFetcher\Model\Url;

/**
 * A collection class for Url
 */
class UrlCollection implements Countable, JsonSerializable, Iterator, ArrayAccess
{
    /**
     * @var array
     */
    private $items = [];
    private $position = 0;

    public function addItem(Url $item)
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
        if (!($value instanceof Url)) {
            throw new \InvalidArgumentException("Must be an instance of Url");
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
    }
}
