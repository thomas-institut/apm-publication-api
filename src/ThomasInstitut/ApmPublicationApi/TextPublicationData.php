<?php

namespace ThomasInstitut\ApmPublicationApi;

class TextPublicationData extends PublicationData
{
    public string $text;

    public function fromArray(array $config): void
    {
        parent::fromArray($config);
        $this->text = $config['text'] ?? '';
    }
}