<!DOCTYPE html>
<html>
<head>
    <title>You Have a New Appointment Request!</title>
</head>
<body>
    <h1>New Appointment Request!</h1>
    <p>Dear {{ $recipient->first_name }},</p>
    <p>You have received a new appointment request from **{{ $requester->first_name . ' ' . $requester->last_name }}**.</p>

    <h2>Appointment Details:</h2>
    <ul>
        <li><strong>From:</strong> {{ $requester->first_name . ' ' . $requester->last_name }}</li>
        <li><strong>Scheduled Date & Time:</strong> {{ $appointment->scheduled_at->format('d M Y, H:i') }}</li>
        <li><strong>Location:</strong> {{ $appointment->location ?? 'Not specified' }}</li>
        @if ($appointment->notes)
            <li><strong>Notes from Requester:</strong> {{ $appointment->notes }}</li>
        @endif
        <li><strong>Current Status:</strong> {{ ucfirst($appointment->status) }}</li>
    </ul>

    <p>Please log in to your account to review and either **Accept** or **Reject** this appointment request.</p>
    {{-- Assuming a route to the appointments dashboard --}}
    <p><a href="{{ url('/appointments') }}">Manage Your Appointments</a></p>

    <p>Thank you!</p>
    <p>The {{ config('app.name') }} Team</p>
</body>
</html>
