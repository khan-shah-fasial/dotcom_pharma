<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;

class Twofactor implements SendSms {
    
    public function send($to, $from, $text, $template_id)
    {
        // dd($to, $from, $text, $template_id);
        $api_key = env("TWOFACTOR_KEY");
        $sender  = env("TWOFACTOR_SENDER");
	    $url = 'https://2factor.in/API/R1/?module=TRANS_SMS&apikey='.$api_key.'&to='.$to.'&from='.$from.'&templatename='.$template_id.'&var1='.$data['var1'].'&var2='.$data['var2'];
	    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
            "content-type: application/x-www-form-urlencoded"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);	    
	    return $response;
    }
}