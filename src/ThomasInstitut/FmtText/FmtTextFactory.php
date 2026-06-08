<?php

namespace ThomasInstitut\FmtText;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;

class FmtTextFactory
{
    /**
     * Builds an array of FmtTextToken objects from a plain string.
     *
     * Replicates the behaviour of `fromString` in `FmtText.ts`: every run of
     * non-whitespace characters becomes a text token, and every whitespace
     * character (space, newline, tab) becomes a glue token.
     *
     * @param string $str
     * @return array<FmtTextToken>
     */
    public static function fromString(string $str): array
    {
        $fmtText = [];
        $currentWord = '';
        $length = mb_strlen($str);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($str, $i, 1);
            if ($char === ' ' || $char === "\n" || $char === "\t") {
                if ($currentWord !== '') {
                    $textToken = new FmtTextTextToken();
                    $textToken->text = $currentWord;
                    $fmtText[] = $textToken;
                    $currentWord = '';
                }
                $fmtText[] = new FmtTextGlueToken();
            } else {
                $currentWord .= $char;
            }
        }
        if ($currentWord !== '') {
            $textToken = new FmtTextTextToken();
            $textToken->text = $currentWord;
            $fmtText[] = $textToken;
        }
        return $fmtText;
    }

    /**
     * @param array<int, mixed> $jsonDecodedArray
     * @return array<FmtTextToken>
     * @throws MappingError
     */
    public static function fromFmtTextJsonDecodedArray(array $jsonDecodedArray): array
    {
        $tokens = [];
        foreach ($jsonDecodedArray as $data) {
            $tokens[] = self::mapToken($data);
        }
        return $tokens;
    }

    /**
     * @param string|array<string|FmtTextToken|array<string, mixed>> $compactFmtText
     * @return array<FmtTextToken>
     * @throws MappingError
     */
    public static function fromCompactFmtText(string|array $compactFmtText): array
    {
        if (is_string($compactFmtText)) {
            return self::fromString($compactFmtText);
        }

        $result = [];
        foreach ($compactFmtText as $item) {
            if ($item instanceof FmtTextToken) {
                $result[] = $item;
            } elseif (is_string($item)) {
                $tokens = self::fromString($item);
                foreach ($tokens as $token) {
                    $result[] = $token;
                }
            } elseif (is_array($item)) {
                $result[] = self::mapToken($item);
            }
        }
        return $result;
    }

    /**
     * @param mixed $data
     * @return FmtTextToken
     * @throws MappingError
     */
    private static function mapToken(mixed $data): FmtTextToken
    {
        $inferFmtTextTokenClass =
            /**
             * @param FmtTextTokenType $type
             * @return class-string<FmtTextTextToken|FmtTextGlueToken|FmtTextMarkToken|FmtTextEmptyToken>
             */
            static fn (FmtTextTokenType $type): string => match ($type) {
                FmtTextTokenType::Text  => FmtTextTextToken::class,
                FmtTextTokenType::Glue  => FmtTextGlueToken::class,
                FmtTextTokenType::Mark  => FmtTextMarkToken::class,
                FmtTextTokenType::Empty => FmtTextEmptyToken::class,
            };

        return (new MapperBuilder())
            ->infer(FmtTextToken::class, $inferFmtTextTokenClass)
            ->allowSuperfluousKeys()
            ->mapper()
            ->map(FmtTextToken::class, $data);
    }
}
