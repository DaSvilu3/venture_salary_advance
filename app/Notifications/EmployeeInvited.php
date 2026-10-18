<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a roster email address so the employee can create their account.
 */
class EmployeeInvited extends Notification
{
    use Queueable;

    public function __construct(public Employee $employee) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':employer invited you to salary advances', ['employer' => $this->employee->employer->name]))
            ->greeting(__('Hello :name,', ['name' => $this->employee->name]))
            ->line(__(':employer now offers small advances against your salary, repaid automatically on payday.', [
                'employer' => $this->employee->employer->name,
            ]))
            ->action(__('Accept invitation'), route('invitation.show', $this->employee->invitation_token))
            ->line(__('If you were not expecting this, you can ignore this email.'));
    }
}
