<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{AppToken,
    AppTransactionsInfo,
    MerchantPayment,
    Transaction,
    MerchantApp,
    Preference,
    Currency,
    Setting,
    Wallet,
    User
};
use Illuminate\Support\Facades\{DB,
    Session,
    Auth
};
use Exception;

class MerchantApiPayment extends Controller
{
    protected $helper;
    public function __construct()
    {
        $this->helper = new Common();
    }

    public function verifyClient(Request $request)
    {
        $app      = $this->verifyClientIdAndClientSecret($request->client_id, $request->client_secret);
        $response = $this->createAccessToken($app); //will expire in one hour
        return json_encode($response);
    }

    protected function verifyClientIdAndClientSecret($client_id, $client_secret)
    {
        $app = MerchantApp::where(['client_id' => $client_id, 'client_secret' => $client_secret])->first();
        if (!$app)
        {
            $res = [
                'status'  => 'error',
                'message' => 'Can not verify the client. Please check client Id and Client Secret',
                'data'    => [],
            ];
            return json_encode($res);
        }
        return $app;
    }

    protected function createAccessToken($app)
    {
        $token = $app->accessToken()->create(['token' => str_random(26), 'expires_in' => time() + 3600]);
        $res   = [
            'status'  => 'success',
            'message' => 'Client Verified',
            'data'    => [
                'access_token' => $token->token,
            ],
        ];
        return $res;
    }

    /**
     * [Generat URL]
     * @param  Request $request  [email, password]
     * @return [view]  [redirect to merchant confirm page or redirect back]
     */
    public function generatedUrl(Request $request)
    {
        if (!auth()->check())
        {
            if ($_POST)
            {
                $credentials = $request->only('email', 'password');
                if (Auth::attempt($credentials))
                {
                    $this->setDefaultSessionValues();

                    $credentialsForConfirmPageLogin = [
                        'email'    => $request->email,
                        'password' => $request->password,
                    ];
                    Session::put('credentials', $credentialsForConfirmPageLogin);

                    //
                    $transInfo = $this->getTransactionData($request->grant_id,$request->token);

                    //Abort if logged in user is same as merchant
                    if ($transInfo->app->merchant->user->id == auth()->user()->id)
                    {
                        auth()->logout();
                        $this->helper->one_time_message('error', __('Merchant cannot make payment to himself!'));
                        return redirect()->back();
                    }
                    else
                    {
                        //Check whether user is suspended - starts
                        $checkPaidByUser = $this->helper->getUserStatus(auth()->user()->status);
                        if ($checkPaidByUser == 'Suspended')
                        {
                            $data['message'] = __('You are suspended to do any kind of transaction!');
                            return view('merchantPayment.user_suspended', $data);
                        }
                        //Check whether user is suspended - ends

                        //Check whether user is inactive - starts
                        elseif ($checkPaidByUser == 'Inactive')
                        {
                            auth()->logout();
                            $this->helper->one_time_message('danger', __('Your account is inactivated. Please try again later!'));
                            return redirect('/login');
                        }
                        //Check whether user is inactive - ends

                        //Check whether merchant is suspended - starts
                        $data = $this->checkoutToPaymentConfirmPage($transInfo);
                        if ($data['status'] == 'Suspended')
                        {
                            return view('merchantPayment.user_suspended', $data);
                        }
                        //Check whether merchant is suspended - ends

                        //Check whether merchant is Inactive - starts
                        elseif ($data['status'] == 'Inactive')
                        {
                            return view('merchantPayment.user_inactive', $data);
                        }
                        //Check whether merchant is Inactive - ends

                        return view('merchantPayment.confirm', $data);
                    }
                    //
                }
                else
                {
                    $this->helper->one_time_message('error', __('Unable to login with provided credentials!'));
                    return redirect()->back();
                }
            }
            else
            {
                $general         = Setting::where(['type' => 'general'])->get(['value', 'name'])->toArray();
                $data['setting'] = $setting = $this->helper->key_value('name', 'value', $general);
                return view('merchantPayment.login', $data);
            }
        }
        else
        {
            $transInfo = $this->getTransactionData($request->grant_id,$request->token);

            //Abort if logged in user is same as merchant
            if ($transInfo->app->merchant->user->id == auth()->user()->id)
            {
                auth()->logout();
                $this->helper->one_time_message('error', __('Merchant cannot make payment to himself!'));
                return redirect()->back();
            }
            else
            {
                //Check whether user is suspended - starts
                $checkPaidByUser = $this->helper->getUserStatus(auth()->user()->status);
                if ($checkPaidByUser == 'Suspended')
                {
                    $data['message'] = __('You are suspended to do any kind of transaction!');
                    return view('merchantPayment.user_suspended', $data);
                }
                //Check whether user is suspended - ends

                //Check whether user is inactive - starts
                elseif ($checkPaidByUser == 'Inactive')
                {
                    auth()->logout();
                    $this->helper->one_time_message('danger', __('Your account is inactivated. Please try again later!'));
                    return redirect('/login');
                }
                //Check whether user is inactive - ends

                //Check whether merchant is suspended - starts
                $data = $this->checkoutToPaymentConfirmPage($transInfo);
                if ($data['status'] == 'Suspended')
                {
                    return view('merchantPayment.user_suspended', $data);
                }
                //Check whether merchant is suspended - ends

                //Check whether merchant is Inactive - starts
                elseif ($data['status'] == 'Inactive')
                {
                    return view('merchantPayment.user_inactive', $data);
                }
                //Check whether merchant is Inactive - ends

                return view('merchantPayment.confirm', $data);
            }
            //
        }
    }

    protected function checkoutToPaymentConfirmPage($transInfo)
    {
        //check expired or not
        if (!$transInfo)
        {
            abort(403, 'Url has been deleted or expired.');
        }
        //Check whether merchant is suspended
        $checkExpressMerchantUser = $this->helper->getUserStatus($transInfo->app->merchant->user->status);
        if ($checkExpressMerchantUser == 'Suspended')
        {
            $data['message'] = __('Merchant is suspended!');
            $data['status'] = $checkExpressMerchantUser;
            return $data;
        }
        //Check whether merchant is Inactive
        elseif ($checkExpressMerchantUser == 'Inactive')
        {
            $data['message'] = __('Merchant is inactive!');
            $data['status'] = $checkExpressMerchantUser;
            return $data;
        }
        else
        {
            $data['status'] = 'Active'; //used to eliminate errors if paid by user is active
        }
        //check if currency exists in wallets
        $availableCurrency = [];
        $wallets           = Wallet::with(['currency:id,code'])->where(['user_id' => $transInfo->app->merchant->user->id])->get(['currency_id']); //2.3
        foreach ($wallets as $wallet)
        {
            $availableCurrency[] = $wallet->currency->code;
        }
        if (!in_array($transInfo->currency, $availableCurrency))
        {
            $this->helper->one_time_message('error', "You don't have the payment wallet. Please create wallet for currency - {$transInfo->currency} !");
            return redirect()->to('payment/fail');
        }

        $data['currSymbol'] = $currSymbol = Currency::where('code', $transInfo->currency)->first(['symbol'])->symbol;
        $data['transInfo']  = $transInfo;

        //Put transaction informations to Session
        Session::put('transInfo', $transInfo);
        return $data;
    }

    public function storeTransactionInfo(Request $request)
    {
        $paymentMethod = $request->payer;
        $amount        = $request->amount;
        $currency      = $request->currency;
        $successUrl    = $request->successUrl;
        $cancelUrl     = $request->cancelUrl;

        # check token missing
        $hasHeaderAuthorization = $request->hasHeader('Authorization');
        if (!$hasHeaderAuthorization)
        {
            $res = [
                'status'  => 'error',
                'message' => 'Access token is missing',
                'data'    => [],
            ];
            return json_encode($res);
        }

        # check token authorization
        $headerAuthorization = $request->header('Authorization');
        $token               = $this->checkTokenAuthorization($headerAuthorization);

        # Currency Validation
        $res = $this->currencyValidaation($token, $currency);
        if (!empty($res['status']))
        {
            return json_encode($res);
        }

        # Amount Validation
        $res = $this->amountValidaation($amount);
        if (!empty($res['status']))
        {
            return json_encode($res);
        }

        if (false)
        {
            $res = [
                'status'  => 'error',
                'message' => 'Validation error',
                'data'    => [],
            ];
            return json_encode($res);
        }

        # Update/Create AppTransactionsInfo and return response
        $res = $this->updateOrAppTransactionsInfoAndReturnResponse($token->app_id, $paymentMethod, $amount, $currency, $successUrl, $cancelUrl);
        return json_encode($res);
    }

    /**
     * [Set Necessary Values To Session]
     */
    protected function setDefaultSessionValues()
    {
        $preferences = Preference::where('field', '!=', 'dflt_lang')->get();
        if (!empty($preferences))
        {
            foreach ($preferences as $pref)
            {
                $pref_arr[$pref->field] = $pref->value;
            }
        }
        if (!empty($preferences))
        {
            Session::put($pref_arr);
        }

        // default_currency
        $default_currency = Setting::where('name', 'default_currency')->first();
        if (!empty($default_currency))
        {
            Session::put('default_currency', $default_currency->value);
        }

        //default_timezone
        $default_timezone = auth()->user()->user_detail->timezone;
        if (!$default_timezone)
        {
            Session::put('dflt_timezone_user', session('dflt_timezone'));
        }
        else
        {
            Session::put('dflt_timezone_user', $default_timezone);
        }

        // default_language
        $default_language = Setting::where('name', 'default_language')->first();
        if (!empty($default_language))
        {
            Session::put('default_language', $default_language->value);
        }

        // company_name
        $company_name = Setting::where('name', 'name')->first();
        if (!empty($company_name))
        {
            Session::put('name', $company_name->value);
        }

        // company_logo
        $company_logo = Setting::where(['name' => 'logo', 'type' => 'general'])->first();
        if (!empty($company_logo))
        {
            Session::put('company_logo', $company_logo->value);
        }
    }

    /**
     * [check Token Authorization]
     * @param  [request] $headerAuthorization [header authorization request]
     * @return [string]  [token]
     */
    protected function checkTokenAuthorization($headerAuthorization)
    {
        $accessToken = $headerAuthorization;
        $tokenType   = '';
        $actualToken = '';
        if (preg_match('/\bBearer\b/', $accessToken))
        {
            $tokenType   = 'bearer';
            $t           = explode(' ', $accessToken);
            $key         = array_keys($t);
            $last        = end($key);
            $actualToken = $t[$last];
        }
        $token = AppToken::where('token', $actualToken)->where('expires_in', '>=', time())->first();
        if (!$token)
        {
            $res = [
                'status'  => 'error',
                'message' => 'Unauthorized token or token has been expired',
                'data'    => [],
            ];
            return json_encode($res);
        }
        return $token;
    }

    protected function currencyValidaation($token, $currency)
    {
        $acceptedCurrency = [];
        $wallets          = $token->app->merchant->user->wallets;
        foreach ($wallets as $wallet)
        {
            $acceptedCurrency[] = $wallet->currency->code;
        }
        //TODO:: Accepted currency will come from database or from merchant currency

        $res = ['status' => ''];
        if (!in_array($currency, $acceptedCurrency))
        {
            $res = [
                'status'  => 'error',
                'message' => 'Currency ' . $currency . ' is not supported by this merchant!',
                'data'    => [],
            ];
        }
        return $res;
    }

    protected function amountValidaation($amount)
    {
        $res = ['status' => ''];
        if ($amount <= 0)
        {
            $res = [
                'status'  => 'error',
                'message' => 'Amount cannot be 0 or less than 0.',
                'data'    => [],
            ];
        }
        return $res;
    }

    protected function updateOrAppTransactionsInfoAndReturnResponse($tokenAppId, $paymentMethod, $amount, $currency, $successUrl, $cancelUrl)
    {
        try
        {
            $grandId  = random_int(10000000, 99999999);
            $urlToken = str_random(20);

            AppTransactionsInfo::updateOrCreate([
                'app_id'         => $tokenAppId,
                'payment_method' => $paymentMethod,
                'amount'         => $amount,
                'currency'       => $currency,
                'success_url'    => $successUrl,
                'cancel_url'     => $cancelUrl,
                'grant_id'       => $grandId,
                'token'          => $urlToken,
                'expires_in'     => time() + (60 * 60 * 5), // url will expire in 5 hours after generation
            ]);

            $url = url("merchant/payment?grant_id=$grandId&token=$urlToken");
            $res = [
                'status'  => 'success',
                'message' => '',
                'data'    => [
                    'approvedUrl' => $url,
                ],
            ];
            return $res;
        }
        catch (Exception $e)
        {
            print $e;
            exit;
        }
    }

    public function confirmPayment()
    {
        if (!auth()->check())
        {
            $getLoggedInCredentials = Session::get('credentials');
            if (Auth::attempt($getLoggedInCredentials))
            {
                $this->setDefaultSessionValues();
                $successPath = $this->storePaymentInformations();
                return redirect()->to($successPath);
            }
            else
            {
                $this->helper->one_time_message('error', __('Unable to login with provided credentials!'));
                return redirect()->back();
            }
        }
        $this->setDefaultSessionValues();
        $data = $this->storePaymentInformations();
        if ($data['status'] == 200)
        {
            return redirect()->to($data['successPath']);
        }
        else
        {
            if ($data['status'] == 401)
            {
                $this->helper->one_time_message('error', 'Currency does not exist in the system!');
            }
            elseif ($data['status'] == 402)
            {
                $this->helper->one_time_message('error', "User doesn't have the wallet - {$data['currency']}. Please exchange to wallet - {$data['currency']}!");
            }
            elseif ($data['status'] == 403)
            {
                $this->helper->one_time_message('error', "User doesn't have sufficient balance!");
            }
            return redirect()->to('payment/fail');
        }
        Session::forget('transInfo');
    }

    protected function storePaymentInformations()
    {
        $transInfo = Session::get('transInfo');
        $unique_code = unique_code();
        $amount      = $transInfo->amount;
        $currency    = $transInfo->currency;
        $p_calc      = ($transInfo->app->merchant->fee / 100) * $amount;

        //Check currency exists in system or not
        $curr = Currency::where('code', $currency)->first(['id']);
        if (!$curr)
        {
            DB::rollBack();
            $data['status'] = 401;
            return $data;
        }

        $senderWallet = Wallet::where(['user_id' => auth()->user()->id, 'currency_id' => $curr->id])->first(['id', 'balance']);
        if (!$senderWallet)
        {
            DB::rollBack();
            $data['status']   = 402;
            $data['currency'] = $transInfo->currency;
            return $data;
        }

        if ($senderWallet->balance < $amount)
        {
            DB::rollBack();
            $data['status'] = 403;
            return $data;
        }

        try
        {
            DB::beginTransaction();

            $data = [];

            //Check User has the wallet or not
            $senderWallet->balance = $senderWallet->balance - $amount;
            $senderWallet->save();

            // Add on merchant
            $merchantPayment                    = new MerchantPayment();
            $merchantPayment->merchant_id       = $transInfo->app->merchant_id;
            $merchantPayment->currency_id       = $curr->id;
            $merchantPayment->payment_method_id = 1;
            $merchantPayment->user_id           = auth()->user()->id;
            $merchantPayment->gateway_reference = $unique_code;
            $merchantPayment->order_no          = '';
            $merchantPayment->item_name         = '';
            $merchantPayment->uuid              = $unique_code;
            $merchantPayment->charge_percentage = $p_calc;
            $merchantPayment->charge_fixed      = 0;
            $merchantPayment->amount            = $amount - $p_calc;
            $merchantPayment->total             = $amount;
            $merchantPayment->status            = 'Success';
            $merchantPayment->save();

            $transaction_A                           = new Transaction();
            $transaction_A->user_id                  = auth()->user()->id;
            $transaction_A->end_user_id              = $transInfo->app->merchant->user_id;
            $transaction_A->merchant_id              = $transInfo->app->merchant_id;
            $transaction_A->currency_id              = $curr->id;
            $transaction_A->payment_method_id        = 1;
            $transaction_A->uuid                     = $unique_code;
            $transaction_A->transaction_reference_id = $merchantPayment->id;
            $transaction_A->transaction_type_id      = Payment_Sent;
            $transaction_A->subtotal                 = $amount;
            $transaction_A->percentage               = $transInfo->app->merchant->fee;
            $transaction_A->charge_percentage        = 0;
            $transaction_A->charge_fixed             = 0;
            $transaction_A->total                    = '-' . ($transaction_A->subtotal);
            $transaction_A->status                   = 'Success';
            $transaction_A->save();

            $transaction_B                           = new Transaction();
            $transaction_B->user_id                  = $transInfo->app->merchant->user_id;
            $transaction_B->end_user_id              = auth()->user()->id;
            $transaction_B->merchant_id              = $transInfo->app->merchant_id;
            $transaction_B->currency_id              = $curr->id;
            $transaction_B->payment_method_id        = 1;
            $transaction_B->uuid                     = $unique_code;
            $transaction_B->transaction_reference_id = $merchantPayment->id;
            $transaction_B->transaction_type_id      = Payment_Received;
            $transaction_B->subtotal                 = $amount - ($p_calc);
            $transaction_B->percentage               = $transInfo->app->merchant->fee; //fixed
            $transaction_B->charge_percentage        = $p_calc;
            $transaction_B->charge_fixed             = 0;
            $transaction_B->total                    = $transaction_B->charge_percentage + $transaction_B->subtotal;
            $transaction_B->status                   = 'Success';
            $transaction_B->save();


            $transInfo->status = 'success';
            $transInfo->save();

            //updating/Creating merchant wallet
            $merchantWallet          = Wallet::where(['user_id' => $transInfo->app->merchant->user_id, 'currency_id' => $curr->id])->first(['id', 'balance']);
            if (empty($merchantWallet))
            {
                $wallet              = new Wallet();
                $wallet->user_id     = $transInfo->app->merchant->user_id;
                $wallet->currency_id = $curr->id;
                $wallet->balance     = ($amount - $p_calc);
                $wallet->is_default  = 'No';
                $wallet->save();
            }
            else
            {
                $merchantWallet->balance = $merchantWallet->balance + ($amount - $p_calc); //fixed -- not amount with fee(total); only amount)
                $merchantWallet->save();
            }

            DB::commit();

            // Send mail to admin
            $this->helper->sendTransactionNotificationToAdmin('payment', ['data' => $merchantPayment]);

            //pass the response to success url
            $response = [
                'status'         => 200,
                'transaction_id' => $merchantPayment->uuid,
                'merchant'       => $merchantPayment->merchant->user->first_name . ' ' . $merchantPayment->merchant->user->last_name,
                'currency'       => $merchantPayment->currency->code,
                'fee'            => $merchantPayment->charge_percentage,
                'amount'         => $merchantPayment->amount,
                'total'          => $merchantPayment->total,
            ];
            $response            = json_encode($response);
            $encodedResponse     = base64_encode($response);
            $successPath         = $transInfo->success_url . '?' . $encodedResponse;
            $data['status']      = 200;
            $data['successPath'] = $successPath;
            return $data;
        }
        catch (Exception $e)
        {
            DB::rollBack();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect()->to('payment/fail');
        }
    }

    public function cancelPayment()
    {
        $transInfo     = Session::get('transInfo');
        $trans         = AppTransactionsInfo::find($transInfo->id, ['id', 'status', 'cancel_url']);
        $trans->status = 'cancel';
        $trans->save();
        Session::forget('transInfo');
        return redirect()->to($trans->cancel_url);
    }

    protected function getTransactionData($grant_id,$token)
    {
        return AppTransactionsInfo::with([
            'app:id,merchant_id',
            'app.merchant:id,user_id,business_name,fee',
            'app.merchant.user:id,first_name,last_name,status',
        ])
        ->where(['grant_id' => $grant_id, 'token' => $token, 'status' => 'pending'])->where('expires_in', '>=', time())
        ->first(['id', 'app_id', 'payment_method', 'currency', 'amount', 'success_url']);
    }
}
