<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WalletController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Session;


class PhonepeController extends Controller
{

    public function pay()
    {
        $user_id = auth()->user()->id;
        $amount = Session::get('payment_data')['amount'];
        $merchantUserId = $user_id;

        if (Session::has('payment_type')) {
            $payment_type = Session::get('payment_type');
            if (Session::get('payment_type') == 'package_payment') {
                $merchantTransactionId = $payment_type. '-' . Session::get('payment_data')['package_id'] . '-' . $user_id . '-' . rand(0, 100000);
            }
            elseif (Session::get('payment_type') == 'wallet_payment') {
                $merchantTransactionId = $payment_type . '-' . $user_id . '-' . $user_id . '-' . rand(0, 100000);
            } 
        }

        $merchantId = env('PHONEPE_MERCHANT_ID');
        $salt_key = env('PHONEPE_SALT_KEY');
        $salt_index = env('PHONEPE_SALT_INDEX');


        $base_url = (get_setting('phonepe_sandbox') == 1) ? "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay" : "https://api.phonepe.com/apis/hermes/pg/v1/pay";

        $post_field = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => $merchantUserId,
            'amount' => $amount * 100,
            'redirectUrl' => route('phonepe.redirecturl'),
            'redirectMode' => 'POST',
            'callbackUrl' =>  route('phonepe.callbackUrl'),
            'mobileNumber' =>  "9999999999",
            "paymentInstrument" => [
                "type" => "PAY_PAGE"
            ],
        ];
        
        $payload = base64_encode(json_encode($post_field));

        $hashedkey =  hash('sha256', $payload . "/pg/v1/pay" . $salt_key) . '###' . $salt_index;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-VERIFY: ' . $hashedkey . '',
            'accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "\n{\n  \"request\": \"$payload\"\n}\n");

        $response = curl_exec($ch);
        $res = (json_decode($response));
    
        return Redirect::to($res->data->instrumentResponse->redirectInfo->url);
    }

   
    public function phonepe_redirecturl(Request $request)
    {
        $payment_type = explode("-", $request['transactionId']);
        auth()->login(User::findOrFail($payment_type[2]));
        // dd($payment_type[0], $payment_type[1], $request['merchantId'], $request['transactionId'], $request->all());

        if ($request['code'] == 'PAYMENT_SUCCESS') {

            if ($payment_type[0] == 'package_payment') {
                flash(translate('Payment process completed'))->success();
                return redirect()->route('package_purchase_history');
            }
            elseif ($payment_type[0] == 'wallet_payment') {
                flash(translate('Payment process completed'))->success();
                return redirect()->route('wallet.index');
            } 
        }
        flash(translate('Payment failed'))->success();
        return redirect()->back();
    }

    public function phonepe_callbackUrl(Request $request)
    {
        
        $res = $request->all();
        $response = $res['response'];
        $decodded_response = json_decode(base64_decode($response));
        $payment_type = explode("-", $decodded_response->data->merchantTransactionId);
        auth()->login(User::findOrFail($payment_type[2]));
        // dd($payment_type[0], $payment_type[1], $request['merchantId'], $request['transactionId'], $request->all());

        if ($decodded_response->code  == 'PAYMENT_SUCCESS') {
		
            if ($payment_type[0] == 'package_payment') {
                $payment_data = array();
                $payment_data['package_id'] = $payment_type[1];
                $payment_data['amount'] = $decodded_response->data->amount / 100;
                $payment_data['payment_method'] = 'phonepe';
                return (new PackagePaymentController)->package_payment_done($payment_data, json_encode($decodded_response->data));
            }
            elseif ($payment_type[0] == 'wallet_payment') {
                $payment_data = array();
                $payment_data['amount'] = $decodded_response->data->amount / 100;
                $payment_data['payment_method'] = 'phonepe';
                return (new WalletController)->wallet_payment_done($payment_data, json_encode($decodded_response->data));
            }
        }
    }
}
