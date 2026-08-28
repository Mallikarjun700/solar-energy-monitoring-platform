<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Solar Energy Alert</title>
</head>
<body>
    <h2>Solar Energy Alert</h2>

    <p>
        A new alert has been detected in the solar monitoring platform.
    </p>

    <p>
        <strong>Alert Type:</strong>
        {{ $alert->alert_type }}
    </p>

    <p>
        <strong>Severity:</strong>
        {{ $alert->severity->value }}
    </p>

    <p>
        <strong>Device:</strong>
        {{ $alert->device_id }}
    </p>

    <p>
        <strong>Message:</strong>
        {{ $alert->message }}
    </p>

    <p>
        <strong>Triggered At:</strong>
        {{ $alert->triggered_at }}
    </p>
</body>
</html>