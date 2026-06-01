<?php

namespace ThomasInstitut\ApmPublicationApi;

class WitnessData
{
    public int $witnessIndex;
    public int $hand;
    public string $location;
    public string $siglum = '';
    public bool $omitSiglum = false;
    public bool $forceHandDisplay = false;
}