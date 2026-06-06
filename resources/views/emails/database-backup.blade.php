<div style="font-family:system-ui,sans-serif;max-width:520px;margin:0 auto;padding:24px;color:#1a1a1a;line-height:1.6">
    <h1 style="font-size:18px;margin:0 0 8px">Database Backup</h1>
    <p style="margin:0 0 16px;color:#52525b">A new backup of <strong>{{ $dbName }}</strong> has been completed.</p>
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <tr><td style="padding:6px 0;color:#52525b">Database</td><td style="padding:6px 0;font-weight:600">{{ $dbName }}</td></tr>
        <tr><td style="padding:6px 0;color:#52525b">Date</td><td style="padding:6px 0;font-weight:600">{{ $date }}</td></tr>
        <tr><td style="padding:6px 0;color:#52525b">Size</td><td style="padding:6px 0;font-weight:600">{{ $size }}</td></tr>
    </table>
    <p style="margin:16px 0 0;font-size:12px;color:#a1a1aa">The SQL dump is attached to this email.</p>
</div>
