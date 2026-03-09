<?php

namespace App\Support;

use RuntimeException;

class OmrIdentifierSequence
{
    private const ROWS = ['1', '2'];

    private const LETTERS = ['a', 'b', 'c', 'd', 'e'];

    /**
     * @var array<int, string>|null
     */
    private static ?array $catalog = null;

    /**
     * @var array<string, bool>|null
     */
    private static ?array $catalogLookup = null;

    /**
     * @return array<int, string>
     */
    public static function catalog(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }

        $letterCombinations = [];

        foreach (range(1, count(self::LETTERS)) as $length) {
            $letterCombinations[$length] = self::buildLetterCombinations($length);
        }

        $catalog = [];

        foreach (self::ROWS as $row) {
            foreach ($letterCombinations[1] as $letters) {
                $catalog[] = $row.$letters;
            }
        }

        foreach (range(1, count(self::LETTERS)) as $secondBlockLength) {
            foreach (range(1, count(self::LETTERS)) as $firstBlockLength) {
                foreach (self::ROWS as $firstRow) {
                    foreach ($letterCombinations[$firstBlockLength] as $firstLetters) {
                        foreach (self::ROWS as $secondRow) {
                            foreach ($letterCombinations[$secondBlockLength] as $secondLetters) {
                                $catalog[] = $firstRow.$firstLetters.$secondRow.$secondLetters;
                            }
                        }
                    }
                }
            }
        }

        self::$catalog = $catalog;
        self::$catalogLookup = array_fill_keys($catalog, true);

        return self::$catalog;
    }

    public static function totalCombinations(): int
    {
        return count(self::catalog());
    }

    public static function normalize(string $identifier): string
    {
        return trim(strtolower($identifier));
    }

    public static function isValid(string $identifier): bool
    {
        $identifier = self::normalize($identifier);

        if (self::$catalogLookup === null) {
            self::catalog();
        }

        return isset(self::$catalogLookup[$identifier]);
    }

    public static function ensureValid(string $identifier): string
    {
        $normalizedIdentifier = self::normalize($identifier);

        if (! self::isValid($normalizedIdentifier)) {
            throw new RuntimeException(self::validationMessage());
        }

        return $normalizedIdentifier;
    }

    public static function validationMessage(): string
    {
        return 'Formato de identificador invalido. Ejemplos validos: 1a, 2a, 1a2b, 1ab2cd.';
    }

    /**
     * @param  array<int, string|null>  $usedIdentifiers
     */
    public static function nextAvailable(array $usedIdentifiers): string
    {
        $usedLookup = [];

        foreach ($usedIdentifiers as $usedIdentifier) {
            if ($usedIdentifier === null) {
                continue;
            }

            $normalized = self::normalize($usedIdentifier);

            if ($normalized !== '') {
                $usedLookup[$normalized] = true;
            }
        }

        foreach (self::catalog() as $candidate) {
            if (! isset($usedLookup[$candidate])) {
                return $candidate;
            }
        }

        throw new RuntimeException('No hay identificadores disponibles en el catálogo OMR.');
    }

    /**
     * @return array<int, string>
     */
    private static function buildLetterCombinations(int $length, int $startIndex = 0, string $prefix = ''): array
    {
        if ($length === 0) {
            return [$prefix];
        }

        $combinations = [];
        $maxStart = count(self::LETTERS) - $length;

        foreach (range($startIndex, $maxStart) as $index) {
            $nextPrefix = $prefix.self::LETTERS[$index];
            $combinations = [
                ...$combinations,
                ...self::buildLetterCombinations($length - 1, $index + 1, $nextPrefix),
            ];
        }

        return $combinations;
    }
}
