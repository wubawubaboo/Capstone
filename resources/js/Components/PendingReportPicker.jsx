import React from 'react';
import SearchAutocomplete from '@/Components/SearchAutocomplete';

export const pendingReportLabel = (report) =>
    report ? `Report #${report.id} - ${report.incident_type} (${report.user?.full_name ?? 'Unknown'})` : null;

/**
 * Finds a pending incident report that has no blotter case yet, from the
 * signed-in desk's own reports (see ReportController::searchPending).
 */
export default function PendingReportPicker({ searchUrl, selected, onSelect, onClear, error }) {
    return (
        <SearchAutocomplete
            searchUrl={searchUrl}
            selectedLabel={pendingReportLabel(selected)}
            onSelect={onSelect}
            onClear={onClear}
            error={error}
            minChars={1}
            placeholder="Search by report #, incident type or reporter name..."
            emptyText="No matching pending reports."
            renderItem={(report) => (
                <>
                    <div className="font-semibold text-slate-800">Report #{report.id} - {report.incident_type}</div>
                    <div className="text-xs text-slate-500">
                        {report.user?.full_name ?? 'Unknown'} · {new Date(report.created_at).toLocaleDateString()}
                    </div>
                </>
            )}
        />
    );
}
