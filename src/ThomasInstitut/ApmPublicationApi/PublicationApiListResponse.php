<?php

namespace ThomasInstitut\ApmPublicationApi;


use ThomasInstitut\StandardApi\SuccessResponse;

class PublicationApiListResponse extends SuccessResponse
{
    /**
     * @var PublicationListing[]
     */
    public array $publications = [];
}