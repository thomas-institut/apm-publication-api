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
     * Builds an array of FmtTextToken objects from a JSON-decoded array
     * (i.e., the structure produced by `JSON.stringify(fmtText)` on the TS side).
     *
     * @param array<int, mixed> $jsonDecodedArray
     * @return array<FmtTextToken>
     * @throws MappingError
     */
    public static function fromFmtTextJsonDecodedArray(array $jsonDecodedArray): array
    {
        $inferFmtTextTokenClass =
            /**
             * @param FmtTextTokenType $type
             * @return class-string<FmtTextTextToken|FmtTextGlueToken|FmtTextMarkToken|FmtTextEmptyToken>
             */
            static fn (FmtTextTokenType $type): string => match ($type) {
                FmtTextTokenType::TEXT  => FmtTextTextToken::class,
                FmtTextTokenType::GLUE  => FmtTextGlueToken::class,
                FmtTextTokenType::MARK  => FmtTextMarkToken::class,
                FmtTextTokenType::EMPTY => FmtTextEmptyToken::class,
            };

        /**
         * @var array<FmtTextToken>
         */
        return (new MapperBuilder())
            ->infer(FmtTextToken::class, $inferFmtTextTokenClass)
            ->allowSuperfluousKeys()
            ->mapper()
            ->map('array<' . FmtTextToken::class . '>', $jsonDecodedArray);
    }
}
