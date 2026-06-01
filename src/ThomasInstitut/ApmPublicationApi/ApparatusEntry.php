<?php

namespace ThomasInstitut\ApmPublicationApi;

use ThomasInstitut\FmtText\FmtTextToken;

class ApparatusEntry
{

    public int $from;
    public int $to;
    /**
     * @var string|array<FmtTextToken>
     */
    public string|array $preLemma;
    /**
     * @var string|array<FmtTextToken>
     */
    public string|array $postLemma;

    public string $lemmaText;
    /**
     * @var string|array<FmtTextToken>
     */
    public string|array $separator;

    /**
     * @var array<ApparatusSubEntry>
     */
    public array $subEntries;
}