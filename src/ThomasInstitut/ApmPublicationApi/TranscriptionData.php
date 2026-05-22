<?php

namespace ThomasInstitut\ApmPublicationApi;

class TranscriptionData extends PublicationData
{
    public string $documentName;
    public string $docType;
    /**
     * @var array<TranscriptionPage>
     */
    public array $pages;

}