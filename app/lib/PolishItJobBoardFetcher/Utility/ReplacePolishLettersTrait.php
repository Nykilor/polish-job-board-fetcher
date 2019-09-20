<?php
namespace PolishItJobBoardFetcher\Utility;

/**
 * Replaces polish letters
 */
trait ReplacePolishLettersTrait
{
    public function replacePolishLetters(string $string) : string
    {
        $string = str_replace(
            ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż'],
            ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'A', 'C', 'E', 'L', 'N', 'O', 'S', 'Z', 'Z'],
            $string
        );

        return $string;
    }
}
