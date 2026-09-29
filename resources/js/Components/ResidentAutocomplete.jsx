import React from 'react';
import SearchAutocomplete from '@/Components/SearchAutocomplete';

export default function ResidentAutocomplete({ placeholder = 'Search by name or phone number...', ...props }) {
    return (
        <SearchAutocomplete
            {...props}
            placeholder={placeholder}
            emptyText="No matching residents found."
            renderItem={(resident) => (
                <>
                    <div className="font-semibold text-slate-800">{resident.full_name}</div>
                    <div className="text-xs text-slate-500">
                        {resident.phone_number}
                        {resident.address ? ` · ${resident.address}` : ''}
                    </div>
                </>
            )}
        />
    );
}
