<?php

namespace App\Services\DocumentGeneration;

use App\Models\DocumentRequest;
use App\Support\DocumentFieldCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ImageMergeService
{
    /**
     * Renders the document type's image template as a PDF background with
     * the requester data overlaid at the saved field_positions_json box
     * coordinates.
     *
     * @return string absolute path to a temporary PDF file
     */
    public function generate(DocumentRequest $documentRequest): string
    {
        $documentType = $documentRequest->documentType;
        $imagePath = $this->imageDataUri($documentType->template_path);
        $values = DocumentFieldCatalog::resolve($documentRequest);

        $boxes = collect($documentType->field_positions_json ?? [])->map(fn ($box) => [
            ...$box,
            'value' => $values[$box['field_key']] ?? '',
        ]);

        $width = $documentType->template_image_width ?: 800;
        $height = $documentType->template_image_height ?: 1131;

        $pdf = Pdf::loadView('pdf.document-image-template', [
            'imagePath' => $imagePath,
            'imageWidth' => $width,
            'imageHeight' => $height,
            'boxes' => $boxes,
        ])->setPaper([0, 0, $width, $height]);

        $tempPdf = tempnam(sys_get_temp_dir(), 'doc_') . '.pdf';
        $pdf->save($tempPdf);

        return $tempPdf;
    }

    /**
     * dompdf resolves <img src> as a URI, and a raw filesystem path (with
     * backslashes on Windows) doesn't parse reliably as one, which silently
     * drops the background image. A base64 data URI avoids path/URI parsing
     * entirely.
     */
    private function imageDataUri(string $path): string
    {
        $mimeType = Storage::mimeType($path) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode(Storage::get($path));
    }
}
