<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductEnquiryMailManager extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $array;
    public function __construct($array)
    {
        $this->array = $array;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.product_enquiry')
                    ->from($this->array['from'], env('MAIL_FROM_NAME'))
                    ->subject($this->array['subject'])
                    ->with([
                        'name' => $this->array['name'],
                        'email' => $this->array['email'],
                        'phone' => $this->array['phone'],
                        'url' => $this->array['url'],
                        'content' =>  $this->array['content'],
                        'product_img' =>  $this->array['product_img'],
                        'subject' =>  $this->array['subject'],
                        // 'pincode_data' =>  $this->array['pincode_data']
                    ]);
    }
}
