<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

use ThomasInstitut\ApmPublicationApi\Client\CompactFmtText;
use ThomasInstitut\FmtText\FmtTextToken;


class MainTextToken
{
    public MainTextTokenType $type;
    /**
     * @var string|array<string|FmtTextToken>
     */
    #[CompactFmtText]
    public string|array $text = '';
    public ?string $style = null;
}