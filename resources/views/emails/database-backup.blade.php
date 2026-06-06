<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:system-ui,-apple-system,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px">
<table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)">
<tr><td style="padding:32px 32px 0">
<h1 style="font-size:18px;margin:0 0 4px;color:#1a1a1a">Database Backup</h1>
<p style="margin:0 0 24px;color:#52525b;font-size:14px;line-height:1.5">A new backup of <strong>{{ $dbName }}</strong> has been completed and is attached to this email.</p>
</td></tr>
<tr><td style="padding:0 32px">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fafafa;border-radius:8px;font-size:13px">
<tr><td style="padding:12px 16px;color:#52525b;border-bottom:1px solid #e4e4e7">Database</td><td style="padding:12px 16px;font-weight:600;color:#1a1a1a;border-bottom:1px solid #e4e4e7;text-align:right">{{ $dbName }}</td></tr>
<tr><td style="padding:12px 16px;color:#52525b;border-bottom:1px solid #e4e4e7">Date</td><td style="padding:12px 16px;font-weight:600;color:#1a1a1a;border-bottom:1px solid #e4e4e7;text-align:right">{{ $date }}</td></tr>
<tr><td style="padding:12px 16px;color:#52525b">Size</td><td style="padding:12px 16px;font-weight:600;color:#1a1a1a;text-align:right">{{ $size }}</td></tr>
</table>
</td></tr>
<tr><td style="padding:24px 32px 32px">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="background:#6366f1;border-radius:8px;padding:12px 24px">
<a href="cid:backup.sql" style="color:#fff;text-decoration:none;font-size:14px;font-weight:500">⬇ Download Backup (.sql)</a>
</td></tr>
</table>
<p style="margin:16px 0 0;font-size:12px;color:#a1a1aa;text-align:center;line-height:1.5">The .sql file is attached to this email.<br>Save it to a secure location. You can use it to restore the database if needed.</p>
</td></tr>
</table>
<p style="margin:16px 0 0;font-size:11px;color:#a1a1aa;text-align:center">{{ config('app.name', 'Akatabo') }} &mdash; Automated backup</p>
</td></tr></table>
</body>
</html>
