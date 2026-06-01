<?php

namespace ThomasInstitut\ApmPublicationApi\EditionPublication;

enum MainTextTokenType: string
{
    case Text = 'text';
    case Glue = 'glue';
    case Empty = 'empty';
    case ParagraphEnd = 'paragraph_end';
    case NumberingLabel = 'numbering_label';
    case FoliationChangeMarker = 'foliation_change_marker';
}