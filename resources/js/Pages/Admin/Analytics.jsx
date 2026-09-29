import React from 'react';
import IncidentDashboard from '@/Components/Analytics/IncidentDashboard';

export default function Analytics({ dashboard, barangays, selectedBarangay, boundary }) {
    const selected = barangays.find((barangay) => barangay.id === selectedBarangay);

    return (
        <IncidentDashboard
            dashboard={dashboard}
            title="City-Level Analytics"
            heading={selected ? `Barangay ${selected.name}` : 'Citywide overview'}
            scope="All barangays' resident reports and blotter cases, excluding confidential VAWC records"
            routeName="admin.analytics"
            params={{ barangay: selectedBarangay }}
            boundary={boundary}
            onSelectBarangay={(barangay, visit) => visit({ barangay: barangay.id })}
            filters={(visit) => (
                <select
                    value={selectedBarangay ?? ''}
                    onChange={(e) => visit({ barangay: e.target.value || null })}
                    aria-label="Barangay"
                    className="border border-slate-300 rounded-lg bg-white text-xs font-semibold text-slate-700 py-2 pl-3 pr-8"
                >
                    <option value="">All barangays</option>
                    {barangays.map((barangay) => (
                        <option key={barangay.id} value={barangay.id}>{barangay.name}</option>
                    ))}
                </select>
            )}
        />
    );
}
