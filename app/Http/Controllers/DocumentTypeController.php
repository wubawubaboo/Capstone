<?php

namespace App\Http\Controllers;

use App\Enums\DocumentTemplateType;
use App\Http\Requests\DocumentType\StoreDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateFieldPositionsRequest;
use App\Models\DocumentType;
use App\Models\SystemLog;
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

        if ($request->hasFile('template_file')) {
            $documentType->attachTemplate($request->file('template_file'), DocumentTemplateType::from($validated['template_type']));
        }

        $documentType->save();

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'CREATE', 'Documents', "Created document type '{$documentType->name}'.");

        return back()->with('success', 'Document type created.');
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        $validated = $request->validated();

        $documentType->name = $validated['name'];
        $documentType->base_fee = $validated['base_fee'];

        if ($request->hasFile('template_file')) {
            $documentType->attachTemplate($request->file('template_file'), DocumentTemplateType::from($validated['template_type']));
        }

        $documentType->save();

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'UPDATE', 'Documents', "Updated document type '{$documentType->name}'.");

        return back()->with('success', 'Document type updated.');
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

        $state = $documentType->is_active ? 'Activated' : 'Deactivated';
        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'UPDATE', 'Documents', "{$state} document type '{$documentType->name}'.");

        return back()->with('success', 'Document type ' . ($documentType->is_active ? 'activated.' : 'deactivated.'));
    }

    public function updateFieldPositions(UpdateFieldPositionsRequest $request, DocumentType $documentType)
    {
        abort_unless($documentType->isImageTemplate(), 422, 'This document type does not use an image template.');

        $validated = $request->validated();

        $documentType->update(['field_positions_json' => $validated['positions']]);

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'UPDATE', 'Documents', "Updated the field layout of document type '{$documentType->name}'.");

        return back()->with('success', 'Field layout saved.');
    }

    public function showTemplateFile(DocumentType $documentType)
    {
        abort_unless($documentType->template_path && Storage::exists($documentType->template_path), 404);

        return Storage::response($documentType->template_path);
    }

    public function destroyTemplate(DocumentType $documentType)
    {
        abort_unless($documentType->hasTemplate(), 422, 'This document type has no template to remove.');

        $documentType->clearTemplate();

        SystemLog::logAction(Auth::user()->barangay_id, Auth::id(), 'UPDATE', 'Documents', "Removed the auto-generation template from document type '{$documentType->name}'.");

        return back()->with('success', 'Template removed.');
    }
}
