// Tell the server which time zone this browser is in (e.g. "Asia/Karachi"), so date filters
// treat "10 Sep" as 10 Sep here, matching the local dates the tables display.
// A cookie rather than a URL parameter so it also reaches plain links like the CSV download.
export function rememberTimezone() {
    try {
        const zone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (zone) {
            document.cookie = `tz=${encodeURIComponent(zone)}; path=/; max-age=31536000; samesite=lax`;
        }
    } catch {
        // Very old browsers: the server falls back to UTC.
    }
}
