<?php
namespace PolishItJobBoardFetcher\Utility;

/**
 *
 */
trait WebsiteInterfaceHelperTrait
{
    /**
     * Check the array if it has the string
     * @param  array   $array    ["key" => ["value", "value"] ... ]
     * @param  string|null $look_for The value to look for inside the $array
     * @return bool
     */
    private function arrayContains(array $array, ?string $look_for) : bool
    {
        if (is_null($look_for)) {
            return false;
        }

        foreach ($array as $key => $category_depth1) {
            if (is_array($category_depth1)) {
                if ($key === $look_for) {
                    return true;
                }
                foreach ($category_depth1 as $category_depth2) {
                    if ($look_for === $category_depth2) {
                        return true;
                    }
                }
            } elseif (is_string($category_depth1) && $category_depth1 === $look_for) {
                return true;
            }
        }

        return false;
    }
}
