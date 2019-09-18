<?php
namespace PolishJobBoardFetcher\Utility;

/**
 * Replaces polish letters
 */
trait ReplacePolishLettersTrait
{
    public function replacePolishLetters(string $string) : string
    {
        $string = strtolower($string);
        $string = str_replace(
            ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'],
            ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'],
            $string
        );
        return strtr($string, "ĄĆĘŁŃÓŚŻŹśąćęłńóśżź", "acelnoszzsacelnoszz");
    }
}
