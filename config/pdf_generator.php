<?php

return [
    // The pdf server posts its disk usage with this token (header X-Disk-Usage-Token). Empty: the endpoint refuses everything.
    'disk_usage_token' => env('PDF_DISK_USAGE_TOKEN'),

    // From this percentage on the disk is critical: a mail goes to the addresses below.
    'disk_usage_critical_percentage' => (int) env('PDF_DISK_USAGE_CRITICAL', 80),

    // Comma separated addresses. Empty: no mail (the overview still shows the status).
    'disk_usage_alert_to' => env('PDF_DISK_USAGE_ALERT_TO'),

    // The pdf server reports every hour or so: at most one mail per this many hours while the disk stays critical.
    'disk_usage_alert_interval_hours' => (int) env('PDF_DISK_USAGE_ALERT_INTERVAL_HOURS', 24),

    // pdf:check-stale mails when the newest measurement is older than this many hours (the server reports hourly: 3).
    // Empty or 0 = off. Set it on production only.
    'disk_usage_stale_after_hours' => (int) env('PDF_DISK_USAGE_STALE_AFTER_HOURS', 0),

    // A script on the pdf server that fails (ssh/scp, see RemoteScripts) mails the same addresses, at most once per this many hours.
    'failure_alert_interval_hours' => (int) env('PDF_SERVER_FAILURE_ALERT_INTERVAL_HOURS', 6),
];
