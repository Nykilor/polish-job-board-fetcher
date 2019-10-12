<?php
namespace PolishItJobBoardFetcher\Utility;

/**
 * createJobOfferModel method trait.
 */
trait AddGetVariableToUrlTrait
{
    /**
     * Adds a variable to the $_GET portion of the url
     * @param  string $url   The url to append the variable to
     * @param  string $key   The $_GET[$key]
     * @param  mixed  $value
     * @return string        Returns the $url with added variable
     */
    protected function addGetVariableToUrl(string $url, string $key, $value) : string
    {
        if (strpos($url, "?") === false) {
            return $url."?".$key."=".$value;
        } else {
            return $url."&".$key."=".$value;
        }
    }
}
