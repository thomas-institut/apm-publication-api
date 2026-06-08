<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

use ThomasInstitut\ApmPublicationApi\Client\CompactFmtText;
use ThomasInstitut\FmtText\FmtTextToken;

class ApparatusEntry
{

    public int $from;
    public int $to;
    /**
     * @var string|array<string|FmtTextToken>
     */
    #[CompactFmtText]
    public string|array $preLemma;
    /**
     * @var string|array<string|FmtTextToken>
     */
    #[CompactFmtText]
    public string|array $postLemma;

    public string $lemmaText;
    /**
     * @var string|array<string|FmtTextToken>
     */
    #[CompactFmtText]
    public string|array $separator;

    /**
     * @var array<ApparatusSubEntry>
     */
    public array $subEntries;
}