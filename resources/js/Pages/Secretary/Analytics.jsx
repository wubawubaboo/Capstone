import React from 'react';
import IncidentDashboard from '@/Components/Analytics/IncidentDashboard';
import { BarList, Card } from '@/Components/Analytics/parts';

export default function Analytics({ dashboard, barangayName, boundary, documentVolumes, serviceStats }) {
    const toItems = (rows) => rows.map((row) => ({ label: row.name, count: row.count }));

    return (
        <IncidentDashboard
            dashboard={dashboard}
            title="Secretary Analytics"
            heading={barangayName ? `Barangay ${barangayName}` : 'Barangay analytics'}
            scope="Resident reports and blotter cases handled by the secretary desk"
            routeName="secretary.analytics"
            boundary={boundary}
        >
            <div className="pt-2">
                <h2 className="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3">Other barangay services this period</h2>
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <Card title="Document requests" subtitle="By document type">
                        <BarList items={toItems(documentVolumes)} emptyText="No document requests in this period." />
                    </Card>
                    <Card title="Service requests" subtitle="By service type">
                        <BarList items={toItems(serviceStats)} emptyText="No service requests in this period." />
                    </Card>
                </div>
            </div>
        </IncidentDashboard>
    );
}
