<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Critical Alert</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:20px;">
            <table width="600" cellpadding="0" cellspacing="0"
                   style="background:#ffffff;border-radius:6px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.1);">

                <!-- Header -->
                <tr>
                    <td style="background:#c0392b;color:#ffffff;padding:16px 24px;">
                        <h2 style="margin:0;font-size:20px;">🚨 Critical System Alert</h2>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:24px;color:#333333;font-size:14px;line-height:1.6;">

                        <p>
                            A <strong>critical background job failure</strong> has been detected and requires immediate attention.
                        </p>

                        <h3 style="border-bottom:1px solid #e0e0e0;padding-bottom:6px;">
                            Alert Summary
                        </h3>

                        <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px;">
                            <tr>
                                <td width="40%"><strong>System / Integration</strong></td>
                                <td>{{ $marketplace ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Method Name</strong></td>
                                <td>{{ $jobName ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td style="color:#c0392b;"><strong>{{ $status ?? 'N/A' }}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Severity</strong></td>
                                <td style="color:#c0392b;"><strong>{{ $severity ?? 'N/A' }}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Timestamp</strong></td>
                                <td>{{ $timestamp ?? 'N/A' }}</td>
                            </tr>
                        </table>

                        <h3 style="border-bottom:1px solid #e0e0e0;padding-bottom:6px;margin-top:24px;">
                            Error Details
                        </h3>

                        <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:4px;font-family:monospace;">
                            {{ $reason }}
                        </div>


                        @if(!empty($extraInfo))
                            <h3 style="border-bottom:1px solid #e0e0e0;padding-bottom:6px;margin-top:24px;">
                                Additional Context
                            </h3>

                            <table width="100%" cellpadding="6" cellspacing="0"
                                   style="font-size:14px;border:1px solid #e0e0e0;border-collapse:collapse;">
                                @foreach($extraInfo as $key => $value)
                                    <tr>
                                        <td width="40%"
                                            style="border:1px solid #e0e0e0;background:#f9fafb;">
                                            <strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong>
                                        </td>
                                        <td style="border:1px solid #e0e0e0;">
                                            @if(is_array($value))
                                                <pre style="margin:0;font-size:12px;">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        <h3 style="border-bottom:1px solid #e0e0e0;padding-bottom:6px;margin-top:24px;">
                            Recommended Actions
                        </h3>

                        <ol>
                            <li>Review application logs for full stack trace.</li>
                            <li>Validate data types and payload structure.</li>
                            <li>Check recent deployments or configuration changes.</li>
                            <li>Fix the issue and retry the job.</li>
                            <li>Escalate to engineering if the issue persists.</li>
                        </ol>

                        <h3 style="border-bottom:1px solid #e0e0e0;padding-bottom:6px;margin-top:24px;">
                            Additional Information
                        </h3>

                        <table width="100%" cellpadding="6" cellspacing="0">
                            <tr>
                                <td><strong>Alert Source</strong></td>
                                <td>Automated Monitoring System</td>
                            </tr>
                        </table>

                        <p style="margin-top:24px;font-size:12px;color:#777777;">
                            This is an automated message. Please do not reply.
                        </p>

                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
