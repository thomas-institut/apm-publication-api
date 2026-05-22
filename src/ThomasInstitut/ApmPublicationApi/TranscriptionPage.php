<?php

namespace ThomasInstitut\ApmPublicationApi;

class TranscriptionPage
{
    public string $foliation;

    public int $pageNumber;
    public string $imageUrl;

    /**
     * @var array<TranscriptionColumn>
     */
    public array $columns;
}