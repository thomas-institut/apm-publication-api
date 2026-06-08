<?php

namespace ThomasInstitut\FmtText;

class FmtText
{

    /**
     * @param FmtTextToken[] $fmtText
     * @return string|array<string|FmtTextToken>
     */
    public static function toCompactFmtText(array $fmtText): string|array
    {
        $compacted = [];
        foreach ($fmtText as $token) {
            $normalized = self::normalizeToken($token);
            $compacted[] = self::getCompactToken($normalized);
        }

        /** @var array<string|FmtTextToken> $result */
        $result = [];
        foreach ($compacted as $item) {
            if (is_string($item)) {
                $lastIndex = count($result) - 1;
                if ($lastIndex >= 0 && is_string($result[$lastIndex])) {
                    $result[$lastIndex] .= $item;
                } else {
                    $result[] = $item;
                }
            } else {
                $result[] = $item;
            }
        }

        if (count($result) === 1 && is_string($result[0])) {
            return $result[0];
        }

        return $result;
    }

    public static function normalizeToken(FmtTextToken $token): FmtTextToken
    {
        if ($token instanceof FmtTextTextToken) {
            if ($token->fontStyle === FontStyle::Normal) {
                $token->fontStyle = null;
            }
            if ($token->fontWeight === FontWeight::Normal) {
                $token->fontWeight = null;
            }
            if ($token->verticalAlign === VerticalAlign::Baseline) {
                $token->verticalAlign = null;
            }
            if ($token->textDirection === TextDirection::Default) {
                $token->textDirection = null;
            }
            if ($token->fontSize === 1.0) {
                $token->fontSize = null;
            }
        }

        return $token;
    }

    public static function getCompactToken(FmtTextToken $token): string|FmtTextToken
    {
        if ($token instanceof FmtTextTextToken) {
            if (
                $token->fontStyle === null &&
                $token->fontWeight === null &&
                $token->verticalAlign === null &&
                $token->fontSize === null &&
                $token->classList === null &&
                $token->textDirection === null
            ) {
                return $token->text;
            }
        }

        if ($token instanceof FmtTextGlueToken) {
            if (
                $token->space === null &&
                $token->width === null &&
                $token->stretch === null &&
                $token->shrink === null
            ) {
                return ' ';
            }
        }

        return $token;
    }

}