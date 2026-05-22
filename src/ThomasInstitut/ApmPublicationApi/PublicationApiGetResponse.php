<?php

namespace ThomasInstitut\ApmPublicationApi;

use ThomasInstitut\StandardApi\SuccessResponse;

class PublicationApiGetResponse extends SuccessResponse
{
    public PublicationData $publicationData;
}