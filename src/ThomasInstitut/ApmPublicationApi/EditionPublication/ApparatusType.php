<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

enum ApparatusType: string
{
    case Criticus = 'criticus';
    case Fontium = 'fontium';
    case Comparativus = 'comparativus';
    case Marginalia = 'marginalia';
}