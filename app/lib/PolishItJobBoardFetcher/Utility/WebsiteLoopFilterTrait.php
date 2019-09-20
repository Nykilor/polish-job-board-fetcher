<?php
namespace PolishItJobBoardFetcher\Utility;

/**
 *
 */
trait WebsiteLoopFilterTrait
{
    private function constArrayContains(array $array, ?string $category) : bool
    {
        if (is_null($category)) {
            return false;
        }

        foreach ($array as $key => $category_depth1) {
            if (is_array($category_depth1)) {
                if ($key === $category) {
                    return true;
                }
                foreach ($category_depth1 as $category_depth2) {
                    if ($category === $category_depth2) {
                        return true;
                    }
                }
            } elseif (is_string($category_depth1) && $category_depth1 === $category) {
                return true;
            }
        }

        return false;
    }

    private function getAdaptedNameFromConstArray(array $array, string $category) : ?string
    {
        foreach ($array as $key => $category_depth1) {
            if (is_array($category_depth1)) {
                if ($key === $category) {
                    return $key;
                }
                foreach ($category_depth1 as $category_depth2) {
                    if ($category === $category_depth2) {
                        return $key;
                    }
                }
            } elseif ($category_depth1 === $category) {
                return $category;
            } elseif (is_string($category_depth1) && $category_depth1 === $category) {
                return $category_depth1;
            }
        }

        return null;
    }
}
