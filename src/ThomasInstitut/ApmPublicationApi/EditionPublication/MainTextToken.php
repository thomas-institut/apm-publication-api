<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

use ThomasInstitut\FmtText\FmtTextToken;


class MainTextToken
{
    public MainTextTokenType $type;
    /**
     * @var string|array<FmtTextToken>
     */
    public string|array $text;
    public string $style;
}