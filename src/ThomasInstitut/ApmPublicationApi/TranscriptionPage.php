<?php

namespace ThomasInstitut\ApmPublicationApi;

class TranscriptionPage
{
    public string $foliation;
    public int $pageNumber;
    public string $imageUrl;
    public string $thumbnailUrl;
    /**
     * @var bool True is a page with text, false if it is, for example, an empty page at the end of a book.
     */
    public bool $isTextPage;

    /**
     * @var array<TranscriptionColumn>
     */
    public array $columns;
}