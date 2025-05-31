<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Appointment;
use App\Models\User;
use App\Models\ExpressInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AppointmentAdminController extends Controller
{
    /**
     * Display the appointment details and form to create a new appointment.
     */
    public function index()
    {
        $currentUser = Auth::user();
        $acceptedInterestStatus = 1;
        $appointments = Appointment::paginate(3);

        $interestsSent = ExpressInterest::where('interested_by', $currentUser->id)
            ->where('status', $acceptedInterestStatus)
            ->with('recipient')
            ->get();

        $interestsReceived = ExpressInterest::where('user_id', $currentUser->id)
            ->where('status', $acceptedInterestStatus)
            ->with('sender')
            ->get();

        // Initialize a collection to hold the unique "other party" User models for the dropdown
        $usersForAppointmentDropdown = collect();

        // Add recipients from interests sent by the current user
        foreach ($interestsSent as $interest) {
            if ($interest->recipient) { // Ensure recipient exists
                $usersForAppointmentDropdown->push($interest->recipient);
            }
        }

        // Add senders from interests received by the current user
        foreach ($interestsReceived as $interest) {
            if ($interest->sender) {
                $usersForAppointmentDropdown->push($interest->sender);
            }
        }

        $usersForAppointmentDropdown = $usersForAppointmentDropdown->unique('id')->sortBy('first_name');


        // Return the view, passing only the relevant data
        return view('admin.members.appointments.index', compact('appointments', 'usersForAppointmentDropdown'));
    }

    /**
     * Update the specified appointment in storage.
     */
    /**
     * Accept the appointment (Recipient action).
     */
    public function accept(Appointment $appointment)
    {
        // Only the recipient can accept
        if (Auth::id() !== $appointment->recipient_id) {
            return back()->with('error', 'You are not authorized to accept this appointment.');
        }

        if ($appointment->status !== 'pending') {
            return back()->with('error', 'Only pending appointments can be accepted.');
        }

        $appointment->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Notify parties about acceptance: Requester and Admin
        $this->notifyStatusChange($appointment, 'accepted');

        return back()->with('success', 'Appointment accepted successfully. The requester and admin have been notified.');
    }

    public function destroy(Appointment $appointment)
    {
        $currentUser = Auth::user();

        // --- UPDATED AUTHORIZATION CHECK ---
        // Only the requester or an admin can delete the appointment.
        if (
            $currentUser->id !== $appointment->requester_id &&
            $currentUser->user_type !== 'admin'
        ) // Assuming 'admin' is the user_type for administrators
        {
            Session::flash('error', translate('You are not authorized to delete this appointment. Only the requester or an administrator can delete it.'));
            return back(); // Redirect back with an error message
        }

        // Prepare details for email notification
        $requester_user = User::find($appointment->requester_id);
        $recipient_user = User::find($appointment->recipient_id);

        $appointmentDetailsForEmail = [
            'appointment' => $appointment,
            'requester' => $requester_user,
            'recipient' => $recipient_user,
            'status' => 'deleted', // Custom status for email context
        ];

        // --- Notification Logic based on who is deleting ---
        if ($currentUser->id === $appointment->requester_id) {
            // Requester is deleting: Notify the recipient
            if ($recipient_user) {
                Mail::send(
                    'frontend.member.appointment.emails.appointment_status_changed',
                    array_merge($appointmentDetailsForEmail, ['user' => $recipient_user]),
                    function ($message) use ($recipient_user) {
                        $message->to($recipient_user->email)->subject('Appointment Deleted by Requester');
                    }
                );
            }
            Session::flash('success', translate('Your appointment request has been successfully deleted. The recipient has been notified.'));
        } elseif ($currentUser->user_type === 'admin') {
            // Admin is deleting: Notify BOTH requester and recipient

            // Notify Requester
            if ($requester_user) {
                Mail::send(
                    'frontend.member.appointment.emails.appointment_status_changed',
                    array_merge($appointmentDetailsForEmail, ['user' => $requester_user]),
                    function ($message) use ($requester_user) {
                        $message->to($requester_user->email)->subject('Appointment Deleted by Administrator');
                    }
                );
            }

            // Notify Recipient
            if ($recipient_user) {
                Mail::send(
                    'frontend.member.appointment.emails.appointment_status_changed',
                    array_merge($appointmentDetailsForEmail, ['user' => $recipient_user]),
                    function ($message) use ($recipient_user) {
                        $message->to($recipient_user->email)->subject('Appointment Deleted by Administrator');
                    }
                );
            }
            Session::flash('success', translate('The appointment has been successfully deleted by an administrator.'));
        }

        // Perform the deletion
        $appointment->delete();

        return redirect()->route('appointments.admin.index');
    }

    /**
     * Reject the appointment (Recipient action). Renamed from 'decline'.
     */
    public function reject(Appointment $appointment)
    {
        // Only the recipient can reject
        if (Auth::id() !== $appointment->recipient_id) {
            return back()->with('error', 'You are not authorized to reject this appointment.');
        }

        if ($appointment->status !== 'pending') {
            return back()->with('error', 'Only pending appointments can be rejected.');
        }

        $appointment->update([
            'status' => 'declined', // Keep 'declined' in DB as per schema
            'declined_at' => now(),
        ]);

        // Notify parties about rejection: Requester
        $this->notifyStatusChange($appointment, 'declined'); // Pass 'declined' for email subject

        return back()->with('success', 'Appointment rejected.');
    }

    /**
     * Cancel the appointment (Requester, Recipient, or Admin).
     */
    public function cancel(Appointment $appointment)
    {
        $user = Auth::user();

        // Check if the current user is the requester, recipient, or an admin
        if (!in_array($user->id, [$appointment->requester_id, $appointment->recipient_id]) && !$user->user_type == "admin") {   //
            return back()->with('error', 'You are not authorized to cancel this appointment.');
        }

        // Prevent cancellation of already finalized appointments
        if (in_array($appointment->status, ['cancelled', 'declined', 'completed'])) {
            return back()->with('error', 'This appointment cannot be cancelled as it\'s already finalized.');
        }

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Notify parties about cancellation: Requester, Recipient, and Admin
        $this->notifyStatusChange($appointment, 'cancelled');

        return back()->with('success', 'Appointment cancelled.');
    }

    /**
     * Complete the appointment (Admin).
     */

    public function complete(Appointment $appointment)
    {
        // Only admin can complete
        if (!Auth::user()->user_type == "admin") {
            return back()->with('error', 'You are not authorized to mark this appointment as completed.');
        }

        if ($appointment->status !== 'accepted') {
            return back()->with('error', 'Only accepted appointments can be marked as completed.');
        }

        $appointment->update([
            'status' => 'completed',
        ]);

        // Notify parties about completion: Requester and Recipient
        $this->notifyStatusChange($appointment, 'completed');

        return back()->with('success', 'Appointment marked as completed.');
    }
    /**
     * Helper method to notify relevant parties about status changes.
     */
    protected function notifyStatusChange(Appointment $appointment, string $newStatus)
    {
        $requester = $appointment->requester;
        $recipient = $appointment->recipient;
        // Note: User::where('user_type', 'admin')->first() retrieves only ONE admin.
        // If you have multiple admins and want to notify all, use ->get() and loop.
        // For this example, we'll stick with notifying just the first admin found.
        $admin = User::where('user_type', 'admin')->first();

        // Base details that apply to all emails
        $baseDetails = [
            'appointment' => $appointment,
            'status'      => $newStatus,
            'requester'   => $requester,
            'recipient'   => $recipient,
        ];

        $subject = "Appointment Update: Your Appointment is " . ucfirst($newStatus);

        // Logic to send emails based on the new status
        switch ($newStatus) {
            case 'accepted':
                // Notify Requester
                $requesterDetails = $baseDetails;
                $requesterDetails['user'] = $requester; // Add the current email recipient ($user)
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $requesterDetails, function ($message) use ($requester, $subject) {
                    $message->to($requester->email)->subject($subject);
                });

                // Notify Admin
                if ($admin) {
                    $adminDetails = $baseDetails;
                    $adminDetails['user'] = $admin; // Add the current email recipient ($user)
                    Mail::send('frontend.member.appointment.emails.appointment_status_changed', $adminDetails, function ($message) use ($admin, $subject, $appointment) {
                        $message->to($admin->email)->subject("Action Required: Appointment Accepted - " . $appointment->id); // More specific for admin
                    });
                }
                break;

            case 'declined':
                // Notify Requester
                $requesterDetails = $baseDetails;
                $requesterDetails['user'] = $requester;
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $requesterDetails, function ($message) use ($requester, $subject) {
                    $message->to($requester->email)->subject($subject);
                });
                break;

            case 'cancelled':
                // Notify Requester
                $requesterDetails = $baseDetails;
                $requesterDetails['user'] = $requester;
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $requesterDetails, function ($message) use ($requester, $subject) {
                    $message->to($requester->email)->subject($subject);
                });

                // Notify Recipient
                $recipientDetails = $baseDetails;
                $recipientDetails['user'] = $recipient;
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $recipientDetails, function ($message) use ($recipient, $subject) {
                    $message->to($recipient->email)->subject($subject);
                });

                // Notify Admin (optional, uncomment if needed)
                if ($admin) {
                    $adminDetails = $baseDetails;
                    $adminDetails['user'] = $admin;
                    Mail::send('frontend.member.appointment.emails.appointment_status_changed', $adminDetails, function ($message) use ($admin, $appointment, $subject) {
                        $message->to($admin->email)->subject("Appointment Notification: Cancellation - " . $appointment->id);
                    });
                }
                break;

            case 'completed':
                // Notify Requester
                $requesterDetails = $baseDetails;
                $requesterDetails['user'] = $requester;
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $requesterDetails, function ($message) use ($requester, $subject) {
                    $message->to($requester->email)->subject($subject);
                });

                // Notify Recipient
                $recipientDetails = $baseDetails;
                $recipientDetails['user'] = $recipient;
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $recipientDetails, function ($message) use ($recipient, $subject) {
                    $message->to($recipient->email)->subject($subject);
                });
                break;

            // You might have other cases like 'updated' as well
            case 'updated':
                // Notify Requester
                $requesterDetails = $baseDetails;
                $requesterDetails['user'] = $requester;
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $requesterDetails, function ($message) use ($requester, $subject) {
                    $message->to($requester->email)->subject($subject);
                });

                // Notify Recipient
                $recipientDetails = $baseDetails;
                $recipientDetails['user'] = $recipient;
                Mail::send('frontend.member.appointment.emails.appointment_status_changed', $recipientDetails, function ($message) use ($recipient, $subject) {
                    $message->to($recipient->email)->subject($subject);
                });
                break;
        }
    }

    /**
     * Helper method to display all appointment to the Admin.
     */
}
