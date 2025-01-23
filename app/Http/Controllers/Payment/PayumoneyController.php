<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Api\V2\Seller\SellerPackageController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerPackageController;
use App\Http\Controllers\WalletController;
use GuzzleHttp\Client;
use App\Models\BusinessSetting;
use Session;
use DB;
use Illuminate\Http\Request;
use Schema;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

class PayumoneyController extends Controller
{
    public function pay()
    {   
        $paymentType = Session::get('payment_type');
        //$paymentData = Session::get('payment_data');
        //dd($paymentData);

        if ($paymentType == 'cart_payment') {
            return view('frontend.payumoney.cart_payment');
        } elseif ($paymentType == 'order_re_payment') {
            return view('frontend.payumoney.order_re_payment');
        } elseif ($paymentType == 'wallet_payment') {
            return view('frontend.payumoney.wallet_payment');
        }elseif ($paymentType == 'customer_package_payment') {
            return view('frontend.payumoney.customer_package_payment_vogue');
        } elseif ($paymentType == 'seller_package_payment') {
            return view('frontend.payumoney.seller_package_payment_vogue');
        }
    }

    public function paymentSuccess(Request $request)
    {
        if($this->validateHash($request->all()) && $request->status == 'success'){

            // Get data from response
            $payment_detalis = json_encode($request->all());
            $user_id = $request->udf1;
            $paymentType = $request->udf2;
            $combined_order_id = $request->udf3;
            $paymentData = ['order_id' => $request->udf4, 'payment_method' => $request->udf5, 'amount' => $request->amount];
            
            //Login user if session loosed
            $user = auth()->user();
            if (!$user) {
                $user = User::find($user_id);
                auth()->login($user);
            }         
            
            if ($paymentType == 'cart_payment') {
                return (new CheckoutController)->checkout_done($combined_order_id, $payment_detalis);
            } elseif ($paymentType == 'order_re_payment') {
                return (new CheckoutController)->orderRePaymentDone($paymentData, $payment_detalis);
            } elseif ($paymentType == 'wallet_payment') {
                return (new WalletController)->wallet_payment_done($paymentData, $payment_detalis);
            }elseif ($paymentType == 'customer_package_payment') { //payment intergartion pending
                return (new CustomerPackageController)->purchase_payment_done($paymentData, $payment_detalis);
            } elseif ($paymentType == 'seller_package_payment') { //payment intergartion pending
                return (new SellerPackageController)->purchase_payment_done($paymentData, $payment_detalis);
            }            
        } else {
            flash(translate('Payment Failed'))->error();
            return redirect()->route('home');
        }
    }

    public function paymentFailure(Request $request)
    {
        //dd($request->all());
        flash(translate('Payment Failed'))->error();
        return redirect()->route('home');
    }

    public function paymentWebhook(Request $request)
    {
        //create file
        $fileContent = [
            'headers' => $request->headers->all(),
            'postData' => $request->all(),
        ];        
        Storage::disk('payu')->put(time().'-webhook.txt', json_encode($fileContent));

        return true;

        if($this->validateHash($request->all()) && $request->status == 'success') {
            // Get data from response
            $payment_detalis = json_encode($request->all());
            $user_id = $request->udf1;
            $paymentType = $request->udf2;
            $combined_order_id = $request->udf3;
            $paymentData = ['order_id' => $request->udf4, 'payment_method' => $request->udf5, 'amount' => $request->amount];
            
            if ($paymentType == 'cart_payment') {
                $order = Order::where('combined_order_id', $combined_order_id)->first();
                if(isset($order) && $order->payment_status == 'unpaid'){
                    return (new CheckoutController)->checkout_done($combined_order_id, $payment_detalis);
                }    
            } elseif ($paymentType == 'order_re_payment') {
                $order = Order::findOrFail($paymentData['order_id']);
                if(isset($order) && $order->payment_status == 'unpaid'){
                    return (new CheckoutController)->orderRePaymentDone($paymentData, $payment_detalis);
                }
            } elseif ($paymentType == 'wallet_payment') {
                //return (new WalletController)->wallet_payment_done($paymentData, $payment_detalis);
            } elseif ($paymentType == 'customer_package_payment') { //payment intergartion pending
                //return (new CustomerPackageController)->purchase_payment_done($paymentData, $payment_detalis);
            } elseif ($paymentType == 'seller_package_payment') { //payment intergartion pending
                //return (new SellerPackageController)->purchase_payment_done($paymentData, $payment_detalis);
            }            
        }
    } 

    public function validateHash(array $data)
    {
        // Get the values from .env
        $salt = env('PAYUMONEY_SALT');
        $key = env('PAYUMONEY_KEY');  
        
        // Get the values from the response data
        $status = $data['status'];
        $txnid = $data['txnid'];
        $amount = $data['amount'];
        $productinfo = $data['productinfo'];
        $firstname = $data['firstname'];
        $email = $data['email'];
        $udf1 = $data['udf1'];
        $udf2 = $data['udf2'];
        $udf3 = $data['udf3'];
        $udf4 = $data['udf4'];
        $udf5 = $data['udf5'];

        // Generate the hash sequence
        //sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key) -- this is hash making mechanism in response by docs 
        $retHashSeq = $salt.'|'.$status.'||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
    
        // Generate hash using SHA512
        $generatedHash = hash("sha512", $retHashSeq);

        // Compare the generated hash with the received hash
        if (strtolower($generatedHash) === strtolower($data['hash'])) {
            return true;
        } else {
            return false;
        }
    }    
    

}