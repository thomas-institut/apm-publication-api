<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

use ThomasInstitut\ApmPublicationApi\Client\CompactFmtText;
use ThomasInstitut\FmtText\FmtTextToken;

class ApparatusSubEntry
{
    public SubEntryType $type;
    /**
     * @var string|array<string|FmtTextToken>
     */
    #[CompactFmtText]
    public string|array $text;

    /**
     * @var array<WitnessData>
     */
    public array $witnessData;
    public string $keyword;
    public int $position;
}