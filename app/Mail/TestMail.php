<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function build()
    {
        return $this->subject('Test email from '.setting('site_name', 'Laradoc'))->view('mail.test');
    }
}
