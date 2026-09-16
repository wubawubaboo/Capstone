// resources/js/Components/BarangayForm.jsx
import React from 'react';

export default function BarangayForm({ data, setData, errors, onSubmit, onCancel, isEditing, processing }) {
    return (
        <form onSubmit={onSubmit} className="p-4 bg-white shadow rounded mb-6">
            <div className="mb-4">
                <label className="block text-gray-700 text-sm font-bold mb-2">Barangay Name</label>
                <input
                    type="text"
                    value={data.name}
                    onChange={e => setData('name', e.target.value)}
                    placeholder="Enter barangay name"
                    className="border p-2 rounded w-full"
                />
                {errors.name && <div className="text-red-500 text-sm mt-1">{errors.name}</div>}
            </div>
            
            <div className="mb-4">
                <label className="block text-gray-700 text-sm font-bold mb-2">Contact Number</label>
                <input
                    type="text"
                    value={data.contact_number}
                    onChange={e => setData('contact_number', e.target.value)}
                    placeholder="Enter contact number"
                    className="border p-2 rounded w-full"
                />
                {errors.contact_number && <div className="text-red-500 text-sm mt-1">{errors.contact_number}</div>}
            </div>

            <div className="flex items-center gap-2">
                <button 
                    disabled={processing} 
                    type="submit" 
                    className="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50 hover:bg-blue-700 transition"
                >
                    {isEditing ? 'Save Changes' : 'Create Barangay'}
                </button>
                {onCancel && (
                    <button 
                        type="button" 
                        onClick={onCancel} 
                        className="px-4 py-2 text-gray-600 border rounded hover:bg-gray-100 transition"
                    >
                        Cancel
                    </button>
                )}
            </div>
        </form>
    );
}