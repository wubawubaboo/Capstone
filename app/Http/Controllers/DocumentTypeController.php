<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentType\StoreDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentTypeController extends Controller
{
    public function store(StoreDocumentTypeRequest $request)
    {
        $validated = $request->validated();

        $documentType = new DocumentType();
        $documentType->name = $validated['name'];
        $documentType->base_fee = $validated['base_fee'];

        $this->applyTemplate($documentType, $request, $validated);

        $documentType->save();

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'CREATE', 'Documents', "Created document type '{$documentType->name}'.");

        return back()->with('success', 'Document type created.');
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        $validated = $request->validated();

        $documentType->name = $validated['name'];
        $documentType->base_fee = $validated['base_fee'];

        $this->applyTemplate($documentType, $request, $validated);

        $documentType->save();

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'UPDATE', 'Documents', "Updated document type '{$documentType->name}'.");

        return back()->with('success', 'Document type updated.');
    }

    private function applyTemplate(DocumentType $documentType, Request $request, array $validated): void
    {
        if (!$request->hasFile('template_file')) {
            return;
        }

        if ($documentType->template_path) {
            Storage::delete($documentType->template_path);
        }

        $file = $request->file('template_file');
        $path = $file->store('document_templates');

        $documentType->template_type = $validated['template_type'];
        $documentType->template_path = $path;
        $documentType->field_positions_json = null;
        $documentType->template_image_width = null;
        $documentType->template_image_height = null;

        if ($validated['template_type'] === 'image') {
            $dimensions = getimagesize(Storage::path($path));
            if ($dimensions) {
                $documentType->template_image_width = $dimensions[0];
                $documentType->template_image_height = $dimensions[1];
            }
        }
    }

    public function destroy(DocumentType $documentType)
    {
        abort_if($documentType->requests()->exists(), 422, 'Cannot delete a document type that already has requests. Deactivate it instead.');

        if ($documentType->template_path) {
            Storage::delete($documentType->template_path);
        }

        $name = $documentType->name;
        $documentType->delete();

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'DELETE', 'Documents', "Deleted document type '{$name}'.");

        return back()->with('success', 'Document type deleted.');
    }

    public function toggleActive(DocumentType $documentType)
    {
        $documentType->update(['is_active' => !$documentType->is_active]);

        return back()->with('success', 'Document type ' . ($documentType->is_active ? 'activated.' : 'deactivated.'));
    }

    public function updateFieldPositions(Request $request, DocumentType $documentType)
    {
        abort_unless($documentType->isImageTemplate(), 422, 'This document type does not use an image template.');

        $validated = $request->validate([
            'positions' => 'required|array',
            'positions.*.field_key' => 'required|string',
            'positions.*.x' => 'required|numeric',
            'positions.*.y' => 'required|numeric',
            'positions.*.width' => 'required|numeric',
            'positions.*.height' => 'required|numeric',
            'positions.*.font_size' => 'required|numeric',
            'positions.*.font_align' => 'required|string',
            'positions.*.font_weight' => 'required|string',
        ]);

        $documentType->update(['field_positions_json' => $validated['positions']]);

        return back()->with('success', 'Field layout saved.');
    }

    public function showTemplateFile(DocumentType $documentType)
    {
        abort_unless($documentType->template_path && Storage::exists($documentType->template_path), 404);

        return Storage::response($documentType->template_path);
    }

    public function destroyTemplate(DocumentType $documentType)
    {
        if ($documentType->template_path) {
            Storage::delete($documentType->template_path);
        }

        $documentType->update([
            'template_type' => null,
            'template_path' => null,
            'field_positions_json' => null,
            'template_image_width' => null,
            'template_image_height' => null,
        ]);

        return back()->with('success', 'Template removed.');
    }
}
