<?php

namespace App\Services\DocumentGeneration;

use App\Models\DocumentRequest;

class DocumentGenerationService
{
    public function __construct(
        private DocxMergeService $docxMergeService,
        private ImageMergeService $imageMergeService,
    ) {
    }

    /**
     * Generates a filled PDF for the request's document type template and
     * returns the absolute path to the temporary PDF file.
     */
    public function generate(DocumentRequest $documentRequest): string
    {
        $documentType = $documentRequest->documentType;

        if ($documentType->isDocxTemplate()) {
            return $this->docxMergeService->generate($documentRequest);
        }

        return $this->imageMergeService->generate($documentRequest);
    }
}
