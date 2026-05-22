<?php

namespace ThomasInstitut\ApmPublicationApi;

use ThomasInstitut\Settable\MissingRequiredValueException;
use ThomasInstitut\Settable\WrongValueTypeException;

class TextPublicationData extends PublicationData
{
    public string $text;

    /**
     * @param array<string, mixed> $config
     * @throws MissingRequiredValueException
     * @throws WrongValueTypeException
     */
    public function fromArray(array $config): void
    {
        parent::fromArray($config);
        $this->text = $config['text'] ?? '';
    }
}