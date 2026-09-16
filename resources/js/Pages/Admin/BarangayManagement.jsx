// resources/js/Pages/Admin/BarangayManagement.jsx
import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BarangayForm from '@/Components/BarangayForm';

export default function BarangayManagement({ barangays }) {
    const [editing, setEditing] = useState(null);
    const { data, setData, post, put, delete: destroy, reset, errors, processing } = useForm({
        name: '',
        contact_number: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editing) {
            put(route('admin.barangays.update', editing.id), { 
                onSuccess: () => closeForm() 
            });
        } else {
            post(route('admin.barangays.store'), { 
                onSuccess: () => closeForm() 
            });
        }
    };

    const closeForm = () => {
        setEditing(null);
        reset();
    };

    const handleEditClick = (barangay) => {
        setEditing(barangay);
        setData({ 
            name: barangay.name,
            contact_number: barangay.contact_number || '' 
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this barangay? This action cannot be undone.')) {
            destroy(route('admin.barangays.destroy', id));
        }
    };

    return (
        <AdminLayout>
            <Head title="Manage Barangays" />
            
            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
                <h2 className="text-2xl font-semibold mb-6 text-gray-800">Barangay Management</h2>
                
                {/* Form Component */}
                <BarangayForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    onSubmit={handleSubmit}
                    onCancel={editing ? closeForm : null}
                    isEditing={!!editing}
                    processing={processing}
                />

                {/* Data Table */}
                <div className="bg-white shadow rounded-lg overflow-hidden mt-6">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact Number
                                </th>
                                <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {barangays.map(barangay => (
                                <tr key={barangay.id} className="hover:bg-gray-50 transition">
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {barangay.name}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {barangay.contact_number || <span className="italic text-gray-400">Not set</span>}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button 
                                            onClick={() => handleEditClick(barangay)} 
                                            className="text-indigo-600 hover:text-indigo-900 mr-4 transition"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            onClick={() => handleDelete(barangay.id)} 
                                            className="text-red-600 hover:text-red-900 transition"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            
                            {barangays.length === 0 && (
                                <tr>
                                    <td colSpan="3" className="px-6 py-8 text-center text-sm text-gray-500">
                                        No barangays found. Add one above to get started.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}