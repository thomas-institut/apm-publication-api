<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

use ThomasInstitut\FmtText\FmtTextToken;

class ApparatusSubEntry
{
    public SubEntryType $type;
    /**
     * @var string|array<FmtTextToken>
     */
    public string|array $text;

    public WitnessData $witnessData;
    public string $keyword;
    public int $position;
}