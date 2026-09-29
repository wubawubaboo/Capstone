// Helpers for calendar pages whose server loads one month at a time via
// `?month=YYYY-MM` (see Controller::calendarMonth).

/** First day of the month named by a "YYYY-MM" string, or of the current month. */
export function parseMonth(value) {
    const match = /^(\d{4})-(\d{2})$/.exec(value ?? '');
    if (!match) {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    }
    return new Date(Number(match[1]), Number(match[2]) - 1, 1);
}

/** The "YYYY-MM" query value for the month containing `date`. */
export function monthParam(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}
