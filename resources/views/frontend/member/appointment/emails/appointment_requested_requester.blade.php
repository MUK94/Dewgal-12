<!DOCTYPE html>
<html>
<head>
    <title>Your Appointment Request Submitted</title>
</head>
<body>
    <h1>Appointment Request Submitted Successfully!</h1>
    <p>Dear {{ $requester->first_name }},</p>
    <p>Your appointment request to **{{ $recipient->first_name . ' ' . $recipient->last_name }}** has been successfully submitted and is awaiting their review.</p>

    <h2>Appointment Details:</h2>
    <ul>
        <li><strong>With:</strong> {{ $recipient->first_name . ' ' . $recipient->last_name }}</li>
        <li><strong>Scheduled Date & Time:</strong> {{ $appointment->scheduled_at->format('d M Y, H:i') }}</li>
        <li><strong>Location:</strong> {{ $appointment->location ?? 'Not specified' }}</li>
        @if ($appointment->notes)
            <li><strong>Your Notes:</strong> {{ $appointment->notes }}</li>
        @endif
        <li><strong>Current Status:</strong> {{ ucfirst($appointment->status) }}</li>
    </ul>

    <p>We will notify you once {{ $recipient->first_name }} has reviewed and acted upon your request.</p>
    <p>Thank you!</p>
    <p>The {{ config('app.name') }} Team</p>
</body>
</html>
