function formatDateTime(dbDate, timezone = null) {
    if (!dbDate) return 'N/A';

    const date = new Date(dbDate);
    if (isNaN(date.getTime())) return dbDate;

    // Auto-detect user's timezone if not provided
    const userTimezone = timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;

    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: userTimezone
    };

    const formatter = new Intl.DateTimeFormat('en-US', options);
    const parts = formatter.formatToParts(date);
    
    const values = {};
    parts.forEach(part => {
        values[part.type] = part.value;
    });

    let hour = parseInt(values.hour);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    if (hour > 12) hour -= 12;
    if (hour === 0) hour = 12;

    return `${values.month}/${values.day}/${values.year} ${String(hour).padStart(2, '0')}:${values.minute} ${ampm}`;
}