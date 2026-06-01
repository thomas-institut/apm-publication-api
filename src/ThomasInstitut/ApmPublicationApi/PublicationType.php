<?php

namespace ThomasInstitut\ApmPublicationApi;

enum PublicationType: string
{
    case Transcription = 'transcription';
    case Edition = 'edition';
    case Text = 'text';
}