<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Case Report - {{ $blotter->case_number }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 0 0 8px 0; text-transform: uppercase; color: #0a2342; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .header { text-align: center; border-bottom: 2px solid #0a2342; padding-bottom: 10px; margin-bottom: 16px; }
        .header .barangay { font-size: 13px; font-weight: bold; }
        .header .subtitle { font-size: 10px; color: #64748b; }
        .status-badge { display: inline-block; margin-top: 4px; padding: 2px 8px; background: #e2e8f0; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .section { margin-bottom: 16px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 4px 6px; vertical-align: top; width: 50%; }
        .label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; display: block; }
        .value { font-size: 11px; font-weight: bold; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 10px; }
        table.mediations { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.mediations th { background: #0a2342; color: #fff; font-size: 9px; text-transform: uppercase; padding: 6px; text-align: left; }
        table.mediations td { padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; vertical-align: top; }
        .notes { white-space: pre-wrap; }
        .confidential { background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: 4px; padding: 8px 10px; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="barangay">Barangay {{ $blotter->barangay->name ?? '' }}</div>
        <div class="subtitle">Case / Blotter Record Report</div>
        <h1>Case #{{ $blotter->case_number }}</h1>
        <span class="status-badge">{{ $blotter->status }}</span>
    </div>

    <div class="section">
        <h2>Case Information</h2>
        <table class="info">
            <tr>
                <td>
                    <span class="label">Complainant</span>
                    <span class="value">{{ $blotter->complainant_name ?? $blotter->report->user->full_name ?? 'Anonymous' }}</span>
                </td>
                <td>
                    <span class="label">Respondent</span>
                    <span class="value">{{ $blotter->receiver->full_name ?? $blotter->receiver_name ?? 'Unknown' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Nature of Complaint</span>
                    <span class="value">{{ $blotter->incident_type ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="label">Date Filed</span>
                    <span class="value">{{ optional($blotter->official_entry_date)->format('F j, Y g:i A') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Incident Description</h2>
        <div class="box notes">{{ $blotter->incident_description ?: 'No description provided.' }}</div>
    </div>

    @if($blotter->vawcDetail)
        <div class="section">
            <h2>VAWC Confidential Details</h2>
            <table class="info">
                <tr>
                    <td>
                        <span class="label">Officer In Charge</span>
                        <span class="value">{{ $blotter->vawcDetail->officer->full_name ?? 'Unassigned' }}</span>
                    </td>
                    <td>
                        <span class="label">Settlement Record</span>
                        <span class="value">{{ $blotter->vawcDetail->isSettled() ? 'On File' : 'Not Settled' }}</span>
                    </td>
                </tr>
            </table>
            <div class="confidential notes" style="margin-top: 6px;">{{ $blotter->vawcDetail->confidential_notes ?: 'No confidential notes recorded.' }}</div>
        </div>
    @endif

    <div class="section">
        <h2>Mediation / Hearing History ({{ $blotter->mediations->count() }})</h2>
        @if($blotter->mediations->isNotEmpty())
            <table class="mediations">
                <thead>
                    <tr>
                        <th style="width: 12%">Session</th>
                        <th style="width: 22%">Date &amp; Time</th>
                        <th style="width: 15%">Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blotter->mediations->sortBy('meeting_number') as $mediation)
                        <tr>
                            <td>#{{ $mediation->meeting_number }}</td>
                            <td>{{ optional($mediation->scheduled_date)->format('F j, Y g:i A') }}</td>
                            <td>{{ $mediation->status }}</td>
                            <td class="notes">{{ $mediation->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No mediation hearings were scheduled for this case.</p>
        @endif
    </div>

    <div class="footer">
        Generated by {{ $generatedBy }} on {{ $generatedAt->format('F j, Y g:i A') }} &middot; This document is system-generated and intended for official barangay use only.
    </div>
</body>
</html>
