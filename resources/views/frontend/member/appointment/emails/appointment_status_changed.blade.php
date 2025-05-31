<!DOCTYPE html>
<html>

<head>
    <title>Appointment Update: Status Changed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        h1,
        h2 {
            color: #0056b3;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        ul li {
            margin-bottom: 5px;
        }

        strong {
            font-weight: bold;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 15px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Your Appointment Status Has Changed!</h1>

        <p>Dear {{ $user->first_name ?? 'Valued User' }},</p>

        @php
            // Determine who the "other party" is relative to the email recipient ($user)
            $otherParty = null;
            if (isset($requester) && $user->id === $requester->id) {
                $otherParty = $recipient;
            } elseif (isset($recipient) && $user->id === $recipient->id) {
                $otherParty = $requester;
            }
        @endphp

        @if ($status === 'deleted')
            <p>Please note that your appointment scheduled for
                <strong>{{ $appointment->scheduled_at->format('d M Y, H:i') }}</strong>
                @if ($otherParty)
                    with <strong>{{ $otherParty->first_name ?? 'N/A' }} {{ $otherParty->last_name ?? '' }}</strong>
                @endif
                has been <b>deleted</b>.
            </p>

            @if ($user->id === ($requester->id ?? null)) {{-- If recipient is requester --}}
                <p>This action was performed by **{{ $requester->first_name ?? 'N/A' }}
                    {{ $requester->last_name ?? '' }}** (the requester)
                    @if (isset($currentUser) && $currentUser->user_type === 'admin')
                        or an administrator
                    @endif.
                </p>
            @elseif ($user->id === ($recipient->id ?? null))
                {{-- If recipient is recipient --}}
                <p>This action was performed by **{{ $recipient->first_name ?? 'N/A' }}
                    {{ $recipient->last_name ?? '' }}** (the recipient)
                    @if (isset($currentUser) && $currentUser->user_type === 'admin')
                        or an administrator
                    @endif.
                </p>
            @endif
            <p>If you have any questions, please contact the relevant party.</p>
        @else
            {{-- For 'accepted', 'declined', 'cancelled', 'updated', or any other status change --}}
            <p>This is an update regarding your appointment scheduled for
                <strong>{{ $appointment->scheduled_at->format('d M Y, H:i') }}</strong>
                @if ($otherParty)
                    with <strong>{{ $otherParty->first_name ?? 'N/A' }} {{ $otherParty->last_name ?? '' }}</strong>.
                @else
                    .
                @endif
            </p>

            <p>The status of your appointment has been updated to: <strong>{{ ucfirst($status) }}</strong>.</p>

            @if ($status === 'accepted')
                <p>Great news! Your appointment has been accepted.</p>
            @elseif ($status === 'declined')
                <p>Unfortunately, your appointment has been declined. You may wish to contact the other party or propose
                    a new time.</p>
            @elseif ($status === 'cancelled')
                <p>Please note that this appointment has been cancelled.</p>
            @elseif ($status === 'updated')
                <p>The details of your appointment have been modified. Please review the updated information below.</p>
            @endif

            <h2>Appointment Details:</h2>
            <ul>
                <li><strong>Original Requester:</strong> {{ $requester->first_name ?? 'N/A' }}
                    {{ $requester->last_name ?? '' }}</li>
                <li><strong>Original Recipient:</strong> {{ $recipient->first_name ?? 'N/A' }}
                    {{ $recipient->last_name ?? '' }}</li>
                <li><strong>Scheduled Date & Time:</strong> {{ $appointment->scheduled_at->format('d M Y, H:i') }}</li>
                <li><strong>Location:</strong> {{ $appointment->location ?? 'Not specified' }}</li>
                @if ($appointment->notes)
                    <li><strong>Notes:</strong> {{ $appointment->notes }}</li>
                @endif
                <li><strong>Current Status:</strong> {{ ucfirst($appointment->status) }}</li>
            </ul>
        @endif

        <p>For more details, please log in to your account.</p>
        <p><a href="{{ url('/appointments') }}" class="button">View Your Appointments</a></p>

        <p>Best regards,</p>
        <p>The {{ config('app.name') }} Team</p>
    </div>
</body>

</html>
