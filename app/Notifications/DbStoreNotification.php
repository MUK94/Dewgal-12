<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
// These are not needed if you only use 'database' channel,
// but good practice to keep if you *might* add email later.
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Notifications\Messages\MailMessage;


class DbStoreNotification extends Notification
{
    use Queueable;

    /**
     * The type of notification (e.g., 'appointment_request', 'message_received').
     * @var string
     */
    public $notify_type;

    /**
     * The ID of the primary entity related to the notification (e.g., Appointment ID).
     * @var int
     */
    public $id;

    /**
     * The user ID who initiated the notification (e.g., the requester of an appointment).
     * @var int
     */
    public $notify_by;

    /**
     * An additional info ID if needed (e.g., a specific message ID, not always necessary).
     * @var int|null
     */
    public $info_id;

    /**
     * The display message for the notification.
     * @var string
     */
    public $message;

    /**
     * The route name or URL associated with the notification (where to redirect on click).
     * @var string
     */
    public $route;

    /**
     * Create a new notification instance.
     *
     * @param string $notify_type The type of notification.
     * @param int $id The ID of the primary related entity.
     * @param int $notify_by The ID of the user who caused the notification.
     * @param int|null $info_id Optional additional info ID.
     * @param string $message The notification message to display.
     * @param string $route The route name or URL for redirection.
     */
    public function __construct(string $notify_type, int $id, int $notify_by, ?int $info_id, string $message, string $route)
    {
        $this->notify_type = $notify_type;
        $this->id = $id;
        $this->notify_by = $notify_by;
        $this->info_id = $info_id;
        $this->message = $message;
        $this->route = $route;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        // This notification is specifically designed for database storage.
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'      => $this->notify_type,
            'id'        => $this->id, // Consider renaming this to 'entity_id' for clarity if 'id' is ambiguous
            'notify_by' => $this->notify_by,
            'info_id'   => $this->info_id,
            'message'   => $this->message,
            'route'     => $this->route,
        ];
    }

    /**
     * This notification is not intended for email delivery.
     *
     * @param  mixed  $notifiable
     * @return void
     */
    public function toMail(object $notifiable)
    {
        // This method is empty because 'mail' is not specified in the 'via' method.
        // If you enable 'mail' in 'via', you *must* implement this method.
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        //  For general array representation, you can delegate to toDatabase()
        return $this->toDatabase($notifiable);
    }
}
