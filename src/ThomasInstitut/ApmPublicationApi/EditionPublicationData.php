<?php

namespace ThomasInstitut\ApmPublicationApi;

class EditionPublicationData extends PublicationData
{

    public string $languageCode;
    /**
     * @var array<MainTextToken>
     */
    public array $mainText;

    /**
     * @var array<Apparatus>
     */
    public array $apparatuses;

    /**
     * @var array<EditionWitnessInfo>
     */
    public array $witnesses;
    /**
     * @var array<SiglaGroup>
     */
    public array $siglaGroups;

}