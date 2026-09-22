import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import FieldPositionEditor from './FieldPositionEditor';

const TEMPLATE_BADGE = {
    docx: { label: 'Word (.docx)', className: 'bg-blue-100 text-blue-700' },
    image: { label: 'Image', className: 'bg-purple-100 text-purple-700' },
};

const DocumentTypeFormModal = ({ documentType, fieldCatalog, onClose }) => {
    const isEdit = !!documentType;

    const form = useForm({
        name: documentType?.name || '',
        base_fee: documentType?.base_fee || '',
        template_type: documentType?.template_type || '',
        template_file: null,
    });
    const { data, setData, processing, errors } = form;

    const submit = (e) => {
        e.preventDefault();

        const options = { onSuccess: () => onClose(), preserveScroll: true };

        if (isEdit) {
            // PHP never populates $_FILES for a real PUT/PATCH request body,
            // so a template file upload on edit would silently be dropped.
            // Spoof the method over POST instead, which Laravel understands
            // via the `_method` field and which PHP parses correctly.
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(route('secretary.document-types.update', documentType.id), options);
        } else {
            form.post(route('secretary.document-types.store'), options);
        }
    };

    return (
        <div className="fixed inset-0 flex items-center justify-center bg-slate-900 bg-opacity-60 z-50 p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg border border-slate-200 max-h-[90vh] overflow-y-auto">
                <div className="p-4 border-b flex justify-between items-center bg-[#0a2342] text-white rounded-t-xl">
                    <h3 className="text-lg font-bold">{isEdit ? 'Edit Document Type' : 'Add Document Type'}</h3>
                    <button onClick={onClose} className="text-white hover:text-gray-200 font-bold text-xl">&times;</button>
                </div>

                <form onSubmit={submit} className="p-6 space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1">Name</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full border-slate-300 rounded text-sm p-2"
                            required
                        />
                        {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1">Base Fee (optional)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Leave blank if this document is free"
                            value={data.base_fee}
                            onChange={(e) => setData('base_fee', e.target.value)}
                            className="w-full border-slate-300 rounded text-sm p-2"
                        />
                        {errors.base_fee && <p className="text-red-500 text-xs mt-1">{errors.base_fee}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-700 mb-2">Auto-Generation Template</label>
                        <div className="flex gap-2 mb-3">
                            {[
                                { value: '', label: 'None' },
                                { value: 'docx', label: 'Word (.docx)' },
                                { value: 'image', label: 'Image' },
                            ].map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onClick={() => setData((d) => ({ ...d, template_type: opt.value, template_file: null }))}
                                    className={`flex-1 py-2 rounded-md text-sm font-medium border transition ${
                                        data.template_type === opt.value
                                            ? 'bg-[#0a2342] text-white border-[#0a2342]'
                                            : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                    }`}
                                >
                                    {opt.label}
                                </button>
                            ))}
                        </div>

                        {data.template_type === 'docx' && (
                            <div>
                                <input
                                    type="file"
                                    accept=".docx"
                                    onChange={(e) => setData('template_file', e.target.files[0] || null)}
                                    className="w-full text-sm"
                                />
                                <p className="text-xs text-slate-500 mt-1">
                                    Author the .docx with merge tokens matching the field names below, e.g. <code>${'{full_name}'}</code>.
                                </p>
                                {fieldCatalog && (
                                    <div className="mt-2 border border-slate-200 rounded p-2 bg-slate-50 max-h-40 overflow-y-auto">
                                        <p className="text-xs font-bold text-slate-600 mb-1">Available field names</p>
                                        <ul className="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-0.5">
                                            {Object.entries(fieldCatalog).map(([key, label]) => (
                                                <li key={key} className="text-xs text-slate-600 flex justify-between gap-2">
                                                    <span>{label}</span>
                                                    <code className="text-slate-500">${'{' + key + '}'}</code>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        )}

                        {data.template_type === 'image' && (
                            <div>
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg"
                                    onChange={(e) => setData('template_file', e.target.files[0] || null)}
                                    className="w-full text-sm"
                                />
                                <p className="text-xs text-slate-500 mt-1">
                                    After saving, use "Manage Fields" to place autofill boxes on the image.
                                </p>
                                {fieldCatalog && (
                                    <div className="mt-2 border border-slate-200 rounded p-2 bg-slate-50 max-h-40 overflow-y-auto">
                                        <p className="text-xs font-bold text-slate-600 mb-1">Available field names</p>
                                        <ul className="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-0.5">
                                            {Object.entries(fieldCatalog).map(([key, label]) => (
                                                <li key={key} className="text-xs text-slate-600">{label}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        )}

                        {documentType?.template_type && (
                            <p className="text-xs text-slate-500 mt-1">
                                Current template: {TEMPLATE_BADGE[documentType.template_type]?.label}. Upload a new file above to replace it.
                            </p>
                        )}
                        {errors.template_file && <p className="text-red-500 text-xs mt-1">{errors.template_file}</p>}
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <button type="button" onClick={onClose} className="px-4 py-2 bg-slate-200 text-slate-700 rounded text-sm font-bold">CANCEL</button>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-emerald-600 text-white rounded text-sm font-bold">
                            {processing ? 'SAVING...' : 'SAVE'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default function DocumentTypesTab({ documentTypes, fieldCatalog }) {
    const [formModalType, setFormModalType] = useState(undefined); // undefined = closed, null = create, object = edit
    const [fieldEditorType, setFieldEditorType] = useState(null);

    const toggleActive = (type) => {
        router.post(route('secretary.document-types.toggle-active', type.id), {}, { preserveScroll: true });
    };

    const deleteType = (type) => {
        if (confirm(`Delete document type "${type.name}"? This cannot be undone.`)) {
            router.delete(route('secretary.document-types.destroy', type.id), { preserveScroll: true });
        }
    };

    return (
        <div>
            <div className="flex justify-end mb-4">
                <button
                    onClick={() => setFormModalType(null)}
                    className="bg-slate-800 text-white px-4 py-2 rounded-md text-sm font-bold hover:bg-slate-900"
                >
                    + Add Document Type
                </button>
            </div>

            <div className="overflow-x-auto border border-slate-200 rounded">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-slate-200 bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                            <th className="py-3 px-4">Name</th>
                            <th className="py-3 px-4">Base Fee</th>
                            <th className="py-3 px-4">Template</th>
                            <th className="py-3 px-4">Requests</th>
                            <th className="py-3 px-4">Status</th>
                            <th className="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {documentTypes.map((type) => {
                            const badge = TEMPLATE_BADGE[type.template_type];
                            return (
                                <tr key={type.id} className="hover:bg-slate-50">
                                    <td className="py-3 px-4 font-medium text-slate-800">{type.name}</td>
                                    <td className="py-3 px-4 text-slate-600">
                                        {type.base_fee !== null ? `₱${Number(type.base_fee).toFixed(2)}` : 'Free'}
                                    </td>
                                    <td className="py-3 px-4">
                                        {badge ? (
                                            <span className={`px-2 py-1 rounded text-xs font-bold ${badge.className}`}>{badge.label}</span>
                                        ) : (
                                            <span className="px-2 py-1 rounded text-xs font-bold bg-slate-100 text-slate-500">None</span>
                                        )}
                                    </td>
                                    <td className="py-3 px-4 text-slate-600">{type.requests_count ?? 0}</td>
                                    <td className="py-3 px-4">
                                        <button
                                            onClick={() => toggleActive(type)}
                                            className={`px-2 py-1 rounded text-xs font-bold ${type.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'}`}
                                        >
                                            {type.is_active ? 'ACTIVE' : 'INACTIVE'}
                                        </button>
                                    </td>
                                    <td className="py-3 px-4 text-right">
                                        <div className="flex justify-end gap-2 flex-wrap">
                                            {type.template_type === 'image' && (
                                                <button
                                                    onClick={() => setFieldEditorType(type)}
                                                    className="bg-purple-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-purple-700"
                                                >
                                                    MANAGE FIELDS
                                                </button>
                                            )}
                                            <button
                                                onClick={() => setFormModalType(type)}
                                                className="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700"
                                            >
                                                EDIT
                                            </button>
                                            <button
                                                onClick={() => deleteType(type)}
                                                className="bg-red-700 text-white px-3 py-1 rounded text-xs font-bold hover:bg-red-800"
                                            >
                                                DELETE
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                        {documentTypes.length === 0 && (
                            <tr>
                                <td colSpan="6" className="py-6 text-center text-slate-500">No document types configured yet.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {formModalType !== undefined && (
                <DocumentTypeFormModal documentType={formModalType} fieldCatalog={fieldCatalog} onClose={() => setFormModalType(undefined)} />
            )}

            {fieldEditorType && (
                <FieldPositionEditor
                    documentType={fieldEditorType}
                    fieldCatalog={fieldCatalog}
                    onClose={() => setFieldEditorType(null)}
                />
            )}
        </div>
    );
}
