<?php

namespace ThomasInstitut\FmtText;

/**
 * Allowed values for `FmtTextTextToken::$textDirection`.
 *
 * Mirrors the TS type `'' | 'ltr' | 'rtl'`.
 */
enum TextDirection: string
{
    case DEFAULT = '';
    case LTR = 'ltr';
    case RTL = 'rtl';
}
