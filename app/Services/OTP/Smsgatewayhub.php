<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;

class Smsgatewayhub implements SendSms {
    
    public function send($to, $from, $text, $template_id)
    {
        $to = str_replace('+', '', $to);
        $api_key = env('SMSGHUB_API_KEY');
        $sender_id = env('SMSGHUB_SENDER');
        
        $ch = curl_init('https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey='.$api_key.'&senderid='.$sender_id.'&channel=2&DCS=0&flashsms=0&number='.$to.'&text='.rawurlencode($text).'&route=1');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,"");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,2);
        $response = curl_exec($ch);

        return $response;
    }
}