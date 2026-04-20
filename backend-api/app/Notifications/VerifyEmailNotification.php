<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public $url;

    /**
     * Kita terima URL verifikasi yang sudah di-generate oleh AppServiceProvider.
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Tentukan channel pengiriman (Email).
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Representasi email.
     */
   public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Aktivasi Akun - PINDEV Lab')
        ->view('emails.dynamic_mail', [
            'title'       => 'Aktivasi Akun',
            'name'        => $notifiable->name,
            'body'        => 'Terima kasih telah bergabung di PINDEV. Silakan klik tombol di bawah ini untuk memverifikasi email dan mengaktifkan akun Anda:',
            'button_text' => 'Aktivasi Akun Sekarang',
            'url'         => $this->url
        ]);
}

    public function toArray(object $notifiable): array
    {
        return [];
    }
}