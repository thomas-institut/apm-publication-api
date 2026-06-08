<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

use ThomasInstitut\FmtText\FmtTextToken;


class MainTextToken
{
    public MainTextTokenType $type;
    /**
     * CompactFmtText
     *
     * @var string|array<string|FmtTextToken>
     */
    public string|array $text;
    public string $style;
}