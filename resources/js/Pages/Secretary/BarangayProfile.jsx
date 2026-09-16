// resources/js/Pages/Secretary/BarangayProfile.jsx
import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import BarangayForm from '@/Components/BarangayForm';

export default function BarangayProfile({ barangay }) {
    const { data, setData, put, processing, errors } = useForm({
        name: barangay?.name || '',
        contact_number: barangay?.contact_number || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('secretary.barangay.update')); 
    };

    return (
        <SecretaryLayout>
            <Head title="Barangay Profile" />
            <div className="max-w-3xl mx-auto sm:px-6 lg:px-8 py-8">
                <h2 className="text-2xl font-semibold mb-6 text-gray-800">Edit Barangay Details</h2>
                
                <BarangayForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    onSubmit={handleSubmit}
                    isEditing={true}
                    processing={processing}
                />
            </div>
        </SecretaryLayout>
    );
}