<?php

namespace ThomasInstitut\FmtText;

class FmtTextEmptyToken implements FmtTextToken
{
    public FmtTextTokenType $type = FmtTextTokenType::EMPTY;
}