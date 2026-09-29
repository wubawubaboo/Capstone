import React from 'react';
import IncidentDashboard from '@/Components/Analytics/IncidentDashboard';

export default function Analytics({ dashboard, barangayName }) {
    return (
        <IncidentDashboard
            dashboard={dashboard}
            title="VAWC Analytics"
            heading={barangayName ? `VAWC Desk · Barangay ${barangayName}` : 'VAWC Desk'}
            scope="Confidential: VAWC-flagged reports and VAWC cases only"
            routeName="vawc.analytics"
        />
    );
}
