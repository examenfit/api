<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $stream;
    public $exam;
    public $question;
    public $part;
    public $topic;
    public $email;
    public $first_name;
    public $last_name;
    public $collection;
    public $creator;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->feedback = $data['feedback'];
        $this->collection = $data['collection'];
        $this->stream = $data['stream'];
        $this->exam = $data['exam'];
        $this->question = $data['question'];
        $this->part = $data['part'];
        $this->creator = $data['creator'];
        $this->topic = $data['topic'];
        $this->email = $data['email'];
        $this->first_name = $data['first_name'];
        $this->last_name = $data['last_name'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@examenfit.nl')
                    ->bcc('examenfit@hotmail.com', 'Examenfit')
                    ->subject('Feedback')
                    ->view('mail.feedback');
    }
}
