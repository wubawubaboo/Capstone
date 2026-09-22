<?php

namespace App\Services\DocumentGeneration;

use App\Models\DocumentRequest;
use App\Support\DocumentFieldCatalog;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DocxMergeService
{
    /**
     * Merges requester data into the document type's .docx template
     * (secretaries author templates with ${field_key} tokens matching
     * DocumentFieldCatalog keys), then converts the result to PDF via a
     * headless LibreOffice conversion. PhpWord's own PDF writer renders
     * through an HTML intermediate that drops floating/wrapped images
     * (letterhead logos, seals, etc.), so LibreOffice is used instead for
     * layout fidelity to the original Word design.
     *
     * @return string absolute path to a temporary PDF file
     */
    public function generate(DocumentRequest $documentRequest): string
    {
        $templatePath = Storage::path($documentRequest->documentType->template_path);

        $templateProcessor = new TemplateProcessor($templatePath);

        foreach (DocumentFieldCatalog::resolve($documentRequest) as $key => $value) {
            $templateProcessor->setValue($key, htmlspecialchars((string) $value));
        }

        $workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'doc_merge_' . uniqid();
        mkdir($workDir, 0777, true);

        try {
            $mergedDocx = $workDir . DIRECTORY_SEPARATOR . 'merged.docx';
            $templateProcessor->saveAs($mergedDocx);

            $this->convertToPdf($mergedDocx, $workDir);

            $convertedPdf = $workDir . DIRECTORY_SEPARATOR . 'merged.pdf';

            if (!is_file($convertedPdf)) {
                throw new RuntimeException('LibreOffice did not produce a PDF for the merged document.');
            }

            $finalPdf = tempnam(sys_get_temp_dir(), 'doc_') . '.pdf';
            rename($convertedPdf, $finalPdf);

            return $finalPdf;
        } finally {
            $this->deleteDirectory($workDir);
        }
    }

    private function convertToPdf(string $docxPath, string $outDir): void
    {
        $profileDir = $outDir . DIRECTORY_SEPARATOR . 'profile';

        $process = new Process([
            config('services.libreoffice.binary', 'soffice'),
            '--headless',
            '--norestore',
            '-env:UserInstallation=file:///' . str_replace('\\', '/', $profileDir),
            '--convert-to', 'pdf',
            '--outdir', $outDir,
            $docxPath,
        ]);

        $process->setTimeout((int) config('services.libreoffice.timeout', 120));
        // php artisan serve is launched through composer -> npx concurrently,
        // which on Windows can hand child processes a stripped environment.
        // soffice.exe needs these system vars to even start; without them it
        // crashes immediately with exit code 1 and no output.
        $process->setEnv($this->windowsEnv());

        try {
            $process->run();
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Could not run LibreOffice for document conversion. Ensure it is installed and '
                . 'LIBREOFFICE_BINARY points to the soffice executable.',
                previous: $e
            );
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * @return array<string, string>
     */
    private function windowsEnv(): array
    {
        return array_merge(getenv(), array_filter([
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'SystemDrive' => getenv('SystemDrive') ?: 'C:',
            'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
            'USERPROFILE' => getenv('USERPROFILE') ?: null,
            'APPDATA' => getenv('APPDATA') ?: null,
            'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: null,
            'PATH' => getenv('PATH') ?: null,
        ]));
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
