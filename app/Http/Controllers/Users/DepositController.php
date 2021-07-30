<?php

namespace App\Http\Controllers\Users;

use Hexters\CoinPayment\Entities\CointpaymentLogTrx;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Auth\OAuthTokenCredential;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\{DB, 
    Validator, 
    Session,
    Auth
};
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use PayPal\Rest\ApiContext;
use App\Models\{CryptoapiLog,
    CurrencyPaymentMethod,
    PaymentMethod,
    Transaction,
    FeesLimit,
    Currency,
    Setting,
    Deposit,
    Wallet,
    Bank,
    User,
    File
};
use PayPal\Api\{Amount,
    PaymentExecution,
    RedirectUrls,
    Payment,
    Payer
};
use Omnipay\Omnipay;
use CoinPayment;
use Exception;

class DepositController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper  = new Common();
        $this->deposit = new Deposit();
    }

    public function create(Request $request)
    {
        setActionSession(); //set the session for validate the action

        $data['menu']          = 'deposit';
        $data['content_title'] = 'Deposit';
        $data['icon']          = 'university';

        $activeCurrency             = Currency::where(['status' => 'Active'])->get(['id', 'code', 'status']);
        $feesLimitCurrency          = FeesLimit::where(['transaction_type_id' => Deposit, 'has_transaction' => 'Yes'])->get(['currency_id', 'has_transaction']);
        $data['activeCurrencyList'] = $this->currencyList($activeCurrency, $feesLimitCurrency);
        $data['defaultWallet']      = $defaultWallet      = Wallet::where(['user_id' => auth()->user()->id, 'is_default' => 'Yes'])->first(['currency_id']);

        //check Decimal Thousand Money Format Preference
        $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);

        // if (!empty($request->all()))
        if ($request->isMethod('post'))
        {
            //backend validation starts

            $rules = array(
                'amount'         => 'required',
                'currency_id'    => 'required',
                'payment_method' => 'required',
            );
            $fieldNames = array(
                'amount'         => __("Amount"),
                'currency_id'    => __("Currency"),
                'payment_method' => __("Payment Method"),
            );

            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($fieldNames);
            if ($validator->fails())
            {
                return back()->withErrors($validator)->withInput();
            }

            //backend validation ends
            $currency_id       = $request->currency_id;
            $user_id           = auth()->user()->id;
            $amount            = $request->amount;
            $coinpaymentAmount = $amount;
            Session::put('coinpaymentAmount', $coinpaymentAmount);

            $data['active_currency']    = $activeCurrency    = Currency::where(['status' => 'Active'])->get(['id', 'code', 'status']);
            $feesLimitCurrency          = FeesLimit::where(['transaction_type_id' => Deposit, 'has_transaction' => 'Yes'])->get(['currency_id', 'has_transaction']);
            $data['activeCurrencyList'] = $this->currencyList($activeCurrency, $feesLimitCurrency);
            $data['walletList']         = $activeCurrency;
            $data['payment_met']        = PaymentMethod::where(['status' => 'Active'])->get(['id', 'name']);
            $currency                   = Currency::where(['id' => $currency_id, 'status' => 'Active'])->first(['symbol']);
            $request['currSymbol']      = $currency->symbol;
            $data['payMtd']             = $payMtd             = PaymentMethod::where(['id' => $request->payment_method, 'status' => 'Active'])->first(['name']);
            $request['payment_name']    = $payMtd->name . '.' . 'jpg';
            $request['totalAmount']     = $request['amount'] + $request['fee'];
            session(['transInfo' => $request->all()]);
            $data['transInfo']           = $transInfo           = $request->all();
            $data['transInfo']['wallet'] = $request->currency_id;
            Session::put('payment_method_id', $request->payment_method);
            Session::put('wallet_currency_id', $request->currency_id);

            //Code for FeesLimit starts here
            $feesDetails = $this->helper->getFeesLimitObject([], Deposit, $currency_id, $transInfo['payment_method'], 'Yes', ['min_limit', 'max_limit']);
            if (@$feesDetails->max_limit == null)
            {
                if ((@$amount < @$feesDetails->min_limit))
                {
                    $data['error'] = __('Minimum amount ') . formatNumber($feesDetails->min_limit);
                    return view('user_dashboard.deposit.create', $data);
                }
            }
            else
            {
                if ((@$amount < @$feesDetails->min_limit) || (@$amount > @$feesDetails->max_limit))
                {
                    $data['error'] = __('Minimum amount ') . formatNumber($feesDetails->min_limit) . __(' and Maximum amount ') . formatNumber($feesDetails->max_limit);
                    return view('user_dashboard.deposit.create', $data);
                }
            }
            //Code for FeesLimit ends here

            if ($payMtd->name == 'Bank')
            {
                $banks                  = Bank::where(['currency_id' => $currency_id])->get(['id', 'bank_name', 'is_default', 'account_name', 'account_number']);
                $currencyPaymentMethods = CurrencyPaymentMethod::where('currency_id', $request->currency_id)->where('activated_for', 'like', "%deposit%")->where('method_data', 'like', "%bank_id%")->get(['method_data']);
                $data['banks']          = $bankList          = $this->bankList($banks, $currencyPaymentMethods);
                if (empty($bankList))
                {
                    $this->helper->one_time_message('error', __('Banks Does Not Exist For Selected Currency!'));
                    return redirect('deposit');
                }
                return view('user_dashboard.deposit.bank_confirmation', $data);
            }
            return view('user_dashboard.deposit.confirmation', $data);
        }
        return view('user_dashboard.deposit.create', $data);
    }

    /**
     * [Extended Function] - starts
     */
    public function currencyList($activeCurrency, $feesLimitCurrency)
    {
        $selectedCurrency = [];
        foreach ($activeCurrency as $aCurrency)
        {
            foreach ($feesLimitCurrency as $flCurrency)
            {
                if ($aCurrency->id == $flCurrency->currency_id && $aCurrency->status == 'Active' && $flCurrency->has_transaction == 'Yes')
                {
                    $selectedCurrency[$aCurrency->id]['id']   = $aCurrency->id;
                    $selectedCurrency[$aCurrency->id]['code'] = $aCurrency->code;
                }
            }
        }
        return $selectedCurrency;
    }
    /**
     * [Extended Function] - ends
     */

    public function bankList($banks, $currencyPaymentMethods)
    {
        $selectedBanks = [];
        $i             = 0;
        foreach ($banks as $bank)
        {
            foreach ($currencyPaymentMethods as $cpm)
            {
                if ($bank->id == json_decode($cpm->method_data)->bank_id)
                {
                    $selectedBanks[$i]['id']             = $bank->id;
                    $selectedBanks[$i]['bank_name']      = $bank->bank_name;
                    $selectedBanks[$i]['is_default']     = $bank->is_default;
                    $selectedBanks[$i]['account_name']   = $bank->account_name;
                    $selectedBanks[$i]['account_number'] = $bank->account_number;
                    $i++;
                }
            }
        }
        return $selectedBanks;
    }

    public function getBankDetailOnChange(Request $request)
    {
        $bank = Bank::with('file:id,filename')->where(['id' => $request->bank])->first(['bank_name', 'account_name', 'account_number', 'file_id']);
        if ($bank)
        {
            $data['status'] = true;
            $data['bank']   = $bank;

            if (!empty($bank->file_id))
            {
                $data['bank_logo'] = $bank->file->filename;
            }
        }
        else
        {
            $data['status'] = false;
            $data['bank']   = "Bank Not FOund!";
        }
        return $data;
    }

    //getMatchedFeesLimitsCurrencyPaymentMethodsSettingsPaymentMethods
    public function getDepositMatchedFeesLimitsCurrencyPaymentMethodsSettingsPaymentMethods(Request $request)
    {
        $feesLimits = FeesLimit::with([
            'currency'       => function ($query)
            {
                $query->where(['status' => 'Active']);
            },
            'payment_method' => function ($q)
            {
                $q->where(['status' => 'Active']);
            },
        ])
            ->where(['transaction_type_id' => $request->transaction_type_id, 'has_transaction' => 'Yes', 'currency_id' => $request->currency_id])
            ->get(['payment_method_id']);

        $currencyPaymentMethods                       = CurrencyPaymentMethod::where('currency_id', $request->currency_id)->where('activated_for', 'like', "%deposit%")->get(['method_id']);
        $currencyPaymentMethodFeesLimitCurrenciesList = $this->currencyPaymentMethodFeesLimitCurrencies($feesLimits, $currencyPaymentMethods);
        $success['paymentMethods']                    = $currencyPaymentMethodFeesLimitCurrenciesList;

        return response()->json(['success' => $success]);
    }

    public function currencyPaymentMethodFeesLimitCurrencies($feesLimits, $currencyPaymentMethods)
    {
        $selectedCurrencies = [];
        foreach ($feesLimits as $feesLimit)
        {
            foreach ($currencyPaymentMethods as $currencyPaymentMethod)
            {
                if ($feesLimit->payment_method_id == $currencyPaymentMethod->method_id)
                {
                    $selectedCurrencies[$feesLimit->payment_method_id]['id']   = $feesLimit->payment_method_id;
                    $selectedCurrencies[$feesLimit->payment_method_id]['name'] = $feesLimit->payment_method->name;
                }
            }
        }
        return $selectedCurrencies;
    }

    //getDepositFeesLimit
    public function getDepositFeesLimit(Request $request)
    {
        $amount  = $request->amount;
        $user_id = auth()->user()->id;

        $feesDetails = $this->helper->getFeesLimitObject([], Deposit, $request->currency_id, $request->payment_method_id, null, ['min_limit', 'max_limit', 'charge_percentage', 'charge_fixed']);

        if (@$feesDetails->max_limit == null)
        {
            if ((@$amount < @$feesDetails->min_limit))
            {
                $success['message'] = __('Minimum amount ') . formatNumber($feesDetails->min_limit);
                $success['status']  = '401';
            }
            else
            {
                $success['status'] = 200;
            }
        }
        else
        {
            if ((@$amount < @$feesDetails->min_limit) || (@$amount > @$feesDetails->max_limit))
            {
                $success['message'] = __('Minimum amount ') . formatNumber($feesDetails->min_limit) . __(' and Maximum amount ') . formatNumber($feesDetails->max_limit);
                $success['status']  = '401';
            }
            else
            {
                $success['status'] = 200;
            }
        }
        //Code for Amount Limit ends here

        //Code for Fees Limit Starts here
        if (empty($feesDetails))
        {
            $feesPercentage            = 0;
            $feesFixed                 = 0;
            $totalFess                 = $feesPercentage + $feesFixed;
            $totalAmount               = $amount + $totalFess;
            $success['feesPercentage'] = $feesPercentage;
            $success['feesFixed']      = $feesFixed;
            $success['totalFees']      = $totalFess;
            $success['totalFeesHtml']  = formatNumber($totalFess);
            $success['totalAmount']    = $totalAmount;
            $success['pFees']          = $feesPercentage;
            $success['fFees']          = $feesFixed;
            $success['pFeesHtml']      = formatNumber($feesPercentage); //2.3
            $success['fFeesHtml']      = formatNumber($feesFixed);      //2.3
            $success['min']            = 0;
            $success['max']            = 0;
            $success['balance']        = 0;
        }
        else
        {
            $feesPercentage            = $amount * ($feesDetails->charge_percentage / 100);
            $feesFixed                 = $feesDetails->charge_fixed;
            $totalFess                 = $feesPercentage + $feesFixed;
            $totalAmount               = $amount + $totalFess;
            $success['feesPercentage'] = $feesPercentage;
            $success['feesFixed']      = $feesFixed;
            $success['totalFees']      = $totalFess;
            $success['totalFeesHtml']  = formatNumber($totalFess);
            $success['totalAmount']    = $totalAmount;
            $success['pFeesHtml']      = formatNumber($feesDetails->charge_percentage); //2.3
            $success['fFeesHtml']      = formatNumber($feesDetails->charge_fixed);      //2.3
            $success['min']            = $feesDetails->min_limit;
            $success['max']            = $feesDetails->max_limit;
            $wallet                    = Wallet::where(['currency_id' => $request->currency_id, 'user_id' => $user_id])->first(['balance']);
            $success['balance']        = @$wallet->balance ? @$wallet->balance : 0;
        }
        return response()->json(['success' => $success]);
    }

    public function store(Request $request)
    {
        //to check action whether action is valid or not
        actionSessionCheck();

        $userid = auth()->user()->id;
        $rules  = array(
            'amount' => 'required|numeric',
        );
        $fieldNames = array(
            'amount' => 'Amount',
        );
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }
        else
        {
            $methodId              = $request->method;
            $amount                = $request->amount;
            $PaymentMethod         = PaymentMethod::find($methodId, ['id', 'name']);
            $method                = ucfirst(strtolower($PaymentMethod->name));
            $currencyPaymentMethod = CurrencyPaymentMethod::where(['currency_id' => session('wallet_currency_id'), 'method_id' => $methodId])->where('activated_for', 'like', "%deposit%")->first(['method_data']);
            $methodData            = json_decode($currencyPaymentMethod->method_data);
            if (empty($methodData))
            {
                $this->helper->one_time_message('error', __('Payment gateway credentials not found!'));
                return back();
            }
            Session::put('method', $method);
            Session::put('payment_method_id', $methodId);
            Session::put('amount', $amount);
            Session::save();

            $currencyId = session('wallet_currency_id');
            $currency   = Currency::find($currencyId, ['id', 'code']);
            if ($method == 'Paypal')
            {
                if ($currency)
                {
                    $currencyCode = $currency->code;
                }
                else
                {
                    $currencyCode = "USD";
                }

                //paypal setup is a custom function to setup paypal api credentials
                $apiContext = $this->paypalSetup($methodData->client_id, $methodData->client_secret, $methodData->mode);
                $payer      = new Payer();
                $payer->setPaymentMethod('paypal');

                $amount = new Amount();
                $amount->setTotal(round($request->amount, 3));
                $amount->setCurrency($currencyCode);

                $transaction = new \PayPal\Api\Transaction();
                $transaction->setAmount($amount);

                $redirectUrls = new RedirectUrls();
                $redirectUrls->setReturnUrl(url("deposit/payment_success"))
                    ->setCancelUrl(url("deposit/payment_cancel"));

                $payment = new Payment();
                $payment->setIntent('sale')
                    ->setPayer($payer)
                    ->setTransactions(array($transaction))
                    ->setRedirectUrls($redirectUrls);

                try {
                    $payment->create($apiContext);
                    return redirect()->to($payment->getApprovalLink());
                }
                catch (PayPalConnectionException $ex)
                {
                    session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount']);
                    $this->helper->one_time_message('error', $ex->getData());
                    return redirect('deposit');
                }
            }
            else if ($method == 'Stripe')
            {
                $publishable = $methodData->publishable_key;
                Session::put('publishable', $publishable);
                return redirect('deposit/stripe_payment');
            }
            else if ($method == '2checkout')
            {
                $transInfo             = Session::get('transInfo');
                $currencyId            = $transInfo['currency_id'];
                $currencyPaymentMethod = CurrencyPaymentMethod::where(['currency_id' => $currencyId, 'method_id' => $methodId])->where('activated_for', 'like', "%deposit%")->first(['method_data']);
                $methodData            = json_decode($currencyPaymentMethod->method_data);

                $seller_id = $methodData->seller_id;
                Session::put('seller_id', $seller_id);
                Session::put('wallet_currency_id', $currencyId);
                Session::put('2Checkout_mode', $methodData->mode);
                return redirect('deposit/checkout/payment');
            }
            else if ($method == 'Payumoney')
            {
                $transInfo = Session::get('transInfo');
                $currencyId            = $transInfo['currency_id'];
                $currencyPaymentMethod = CurrencyPaymentMethod::where(['currency_id' => $currencyId, 'method_id' => $methodId])->where('activated_for', 'like', "%deposit%")->first(['method_data']);
                $methodData            = json_decode($currencyPaymentMethod->method_data);
                Session::put('mode', $methodData->mode);
                Session::put('key', $methodData->key);
                Session::put('salt', $methodData->salt);
                return redirect('deposit/payumoney_payment');
            }
            else if ($method == 'Coinpayments')
            {
                $trx['amountTotal'] = $amount;
                $trx['payload']     = [
                    'type'     => 'deposit',
                    'currency' => $currency->code,
                ];
                changeEnvironmentVariable('coinpayment_currency', $currency->code);
                $link_transaction = CoinPayment::url_payload($trx);
                Session::put('link_transaction', $link_transaction);
                return redirect($link_transaction);
            }
            else if ($method == 'Payeer')
            {
                $transInfo             = Session::get('transInfo');
                $currencyId            = $transInfo['currency_id'];
                $currencyPaymentMethod = CurrencyPaymentMethod::where(['currency_id' => $currencyId, 'method_id' => $methodId])->where('activated_for', 'like', "%deposit%")->first(['method_data']);
                $payeer                = json_decode($currencyPaymentMethod->method_data);
                Session::put('payeer_merchant_id', $payeer->merchant_id);
                Session::put('payeer_secret_key', $payeer->secret_key);
                Session::put('payeer_encryption_key', $payeer->encryption_key);
                Session::put('payeer_merchant_domain', $payeer->merchant_domain);
                return redirect('deposit/payeer/payment');
            }
            else
            {
                $this->helper->one_time_message('error', __('Please check your payment method!'));
            }
            return back();
        }
    }

    /* Start of Stripe */
    public function stripePayment()
    {
        $data['menu']              = 'deposit';
        $data['amount']            = Session::get('amount');
        $data['payment_method_id'] = $method_id = Session::get('payment_method_id');
        $data['content_title']     = 'Deposit';
        $data['icon']              = 'university';
        $sessionValue              = session('transInfo');
        $currencyId                = $sessionValue['currency_id'];
        $currencyPaymentMethod     = CurrencyPaymentMethod::where(['currency_id' => $currencyId, 'method_id' => $method_id])->where('activated_for', 'like', "%deposit%")->first(['method_data']);
        $methodData                = json_decode($currencyPaymentMethod->method_data);
        $data['publishable']       = $methodData->publishable_key;
        return view('user_dashboard.deposit.stripe', $data);
    }

    public function stripePaymentStore(Request $request)
    {
        //Backend validation - starts
        $validation = Validator::make($request->all(), [
            'stripeToken' => 'required',
        ]);
        if ($validation->fails())
        {
            return redirect()->back()->withErrors($validation->errors());
        }
        //Backend validation - ends

        actionSessionCheck();

        $sessionValue      = session('transInfo');
        $amount            = $sessionValue['totalAmount'];
        $payment_method_id = $sessionValue['payment_method'];
        $user_id           = auth()->user()->id;
        $wallet            = Wallet::where(['currency_id' => $sessionValue['currency_id'], 'user_id' => $user_id])->first(['id', 'currency_id']);

        if (empty($wallet))
        {
            $walletInstance              = new Wallet();
            $walletInstance->user_id     = $user_id;
            $walletInstance->currency_id = $sessionValue['currency_id'];
            $walletInstance->balance     = 0.00000000;
            $walletInstance->is_default  = 'No';
            $walletInstance->save();
        }
        $currencyId = isset($wallet->currency_id) ? $wallet->currency_id : $walletInstance->currency_id;
        $currency   = Currency::find($currencyId, ['id', 'code']);
        if ($request->isMethod('post'))
        {
            if (isset($request->stripeToken))
            {
                $currencyPaymentMethod = CurrencyPaymentMethod::where(['currency_id' => $currencyId, 'method_id' => $payment_method_id])->where('activated_for', 'like', "%deposit%")->first(['method_data']);

                $methodData            = json_decode($currencyPaymentMethod->method_data);
                $gateway               = Omnipay::create('Stripe');
                $gateway->setApiKey($methodData->secret_key);
                $response = $gateway->purchase([
                    'amount'   => number_format((float) $amount, 2, '.', ''),
                    'currency' => $currency->code,
                    'token'    => $request->stripeToken,
                ])->send();

                if ($response->isSuccessful())
                {
                    $token = $response->getTransactionReference();
                    if ($token)
                    {
                        $uuid       = unique_code();
                        $feeInfo    = $this->helper->getFeesLimitObject([], Deposit, $currencyId, $payment_method_id, null, ['charge_percentage', 'charge_fixed']);
                        $p_calc     = $sessionValue['amount'] * (@$feeInfo->charge_percentage / 100); //correct calc
                        $total_fees = $p_calc+@$feeInfo->charge_fixed;

                        try
                        {
                            DB::beginTransaction();
                            //Deposit
                            $deposit                    = new Deposit();
                            $deposit->uuid              = $uuid;
                            $deposit->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
                            $deposit->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
                            $deposit->amount            = $present_amount            = ($amount - $total_fees);
                            $deposit->status            = 'Success';
                            $deposit->user_id           = $user_id;
                            $deposit->currency_id       = $currencyId;
                            $deposit->payment_method_id = $payment_method_id;
                            $deposit->save();

                            //Transaction
                            $transaction                           = new Transaction();
                            $transaction->user_id                  = $user_id;
                            $transaction->currency_id              = $currencyId;
                            $transaction->payment_method_id        = $payment_method_id;
                            $transaction->transaction_reference_id = $deposit->id;
                            $transaction->transaction_type_id      = Deposit;
                            $transaction->uuid                     = $uuid;
                            $transaction->subtotal                 = $present_amount;
                            $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
                            $transaction->charge_percentage        = @$feeInfo->charge_percentage ? $p_calc : 0;
                            $transaction->charge_fixed             = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
                            $transaction->total                    = $sessionValue['amount'] + $total_fees;
                            $transaction->status                   = 'Success';
                            $transaction->save();

                            //Wallet
                            $wallet          = Wallet::where(['user_id' => $user_id, 'currency_id' => $currencyId])->first(['id', 'balance']);
                            $wallet->balance = ($wallet->balance + $present_amount);
                            $wallet->save();

                            DB::commit();

                            // Send notification to admin
                            $response = $this->helper->sendTransactionNotificationToAdmin('deposit', ['data' => $deposit]);

                            $data['transaction'] = $transaction;
                            return \Redirect::route('deposit.stripe.success')->with(['data' => $data]);
                        }
                        catch (Exception $e)
                        {
                            DB::rollBack();
                            Session::forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'publishable', 'transInfo']);
                            clearActionSession();
                            $this->helper->one_time_message('error', $e->getMessage());
                            return back();
                        }
                    }
                    else
                    {
                        DB::rollBack();
                        Session::forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'publishable', 'transInfo']);
                        clearActionSession();
                        $this->helper->one_time_message('error', __('Token is missing!'));
                        return back();
                    }
                }
                else
                {
                    DB::rollBack();
                    Session::forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'publishable', 'transInfo']);
                    clearActionSession();
                    $message = $response->getMessage();
                    $this->helper->one_time_message('error', $message);
                    return back();
                }
            }
            else
            {
                DB::rollBack();
                Session::forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'publishable', 'transInfo']);
                clearActionSession();
                $this->helper->one_time_message('error', __('Please try again later!'));
                return back();
            }
        }
    }

    public function stripePaymentSuccess()
    {
        if (empty(session('data'))) {
            return redirect('deposit');
        } else {
            $data['transaction'] = session('data')['transaction'];
            //clearing session
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'publishable', 'transInfo', 'data']);
            clearActionSession();
            return view('user_dashboard.deposit.success', $data);
        }
    }

    /* End of Stripe */

    /* Start of PayPal */
    public function paypalSetup()
    {
        $numarr = func_num_args();
        if ($numarr > 0)
        {
            $clientID   = func_get_arg(0);
            $secret     = func_get_arg(1);
            $mode       = func_get_arg(2);
            $apicontext = new ApiContext(new OAuthTokenCredential($clientID, $secret));
            $apicontext->setConfig([
                'mode' => $mode,
            ]);
        }
        else
        {
            $credentials = Setting::where(['type' => 'PayPal'])->get();
            $clientID    = $credentials[0]->value;
            $secret      = $credentials[1]->value;
            $apicontext  = new ApiContext(new OAuthTokenCredential($clientID, $secret));
            $apicontext->setConfig([
                'mode' => $credentials[3]->value,
            ]);
        }
        return $apicontext;
    }

    public function paypalDepositPaymentConfirm(Request $request)
    {
        actionSessionCheck();

        $sessionValue = session('transInfo');
        $method            = session('method');
        $amount            = $sessionValue['totalAmount'];
        $payment_method_id = $sessionValue['payment_method'];
        $user_id           = auth()->user()->id;
        $wallet            = Wallet::where(['currency_id' => $sessionValue['currency_id'], 'user_id' => $user_id])->first(['id', 'currency_id']);
        if (empty($wallet))
        {
            $walletInstance              = new Wallet();
            $walletInstance->user_id     = $user_id;
            $walletInstance->currency_id = $sessionValue['currency_id'];
            $walletInstance->balance     = 0;
            $walletInstance->is_default  = 'No';
            $walletInstance->save();
        }
        $currencyId = isset($wallet->currency_id) ? $wallet->currency_id : $walletInstance->currency_id;
        $currency   = Currency::find($currencyId, ['id', 'code']);
        if ($currency)
        {
            $currencyCode = $currency->code;
        }
        else
        {
            $currencyCode = "USD";
        }

        if (isset($request->paymentId) && $request->paymentId != null)
        {
            $currencyPaymentMethod = CurrencyPaymentMethod::where(['currency_id' => $currencyId, 'method_id' => $payment_method_id])->where('activated_for', 'like', "%deposit%")->first(['method_data']);
            $methodData            = json_decode($currencyPaymentMethod->method_data);
            $apiContext            = $this->paypalSetup($methodData->client_id, $methodData->client_secret, $methodData->mode);

            $paymentId = $request->paymentId;
            $payment   = Payment::get($paymentId, $apiContext);

            $execution = new PaymentExecution();
            $execution->setPayerId($request->PayerID);

            $transaction = new \PayPal\Api\Transaction();
            $amountO     = new Amount();
            $amountO->setCurrency($currencyCode);
            $amountO->setTotal(number_format((float) $amount, 2, '.', '')); //PayPal accepts 2 decimal places only - if not rounded to 2 decimal places, PayPal will throw error.
            $transaction->setAmount($amountO);

            try {
                $result = $payment->execute($execution, $apiContext);
                try {
                    $payment = Payment::get($paymentId, $apiContext);
                }
                catch (Exception $ex)
                {
                    // Log::error($ex);
                    $this->helper->one_time_message('error', $ex);
                    session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
                    clearActionSession();
                    return redirect('deposit');
                }
            }
            catch (Exception $ex)
            {
                // Log::error($ex->getMessage());
                $this->helper->one_time_message('error', $ex->getMessage());
                session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
                clearActionSession();
                return redirect('deposit');
            }
        }
        else
        {
            // Log::error("User Cancelled the transaction");
            $this->helper->one_time_message('error', __('User Cancelled the transaction!'));
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
            clearActionSession();
            return redirect('deposit');
        }
        $uuid    = unique_code();
        $feeInfo = $this->helper->getFeesLimitObject([], Deposit, $currencyId, $payment_method_id, null, ['charge_percentage', 'charge_fixed']);
        $p_calc  = $sessionValue['amount'] * (@$feeInfo->charge_percentage / 100); //correct calc

        try
        {
            DB::beginTransaction();

            //Deposit
            $deposit                    = new Deposit();
            $deposit->uuid              = $uuid;
            $deposit->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
            $deposit->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
            $deposit->status            = 'Success';
            $deposit->user_id           = $user_id;
            $deposit->currency_id       = $currencyId;
            $deposit->payment_method_id = $payment_method_id;
            $deposit->amount            = $present_amount            = ($amount - ($p_calc+@$feeInfo->charge_fixed));
            $deposit->save();

            //Transaction
            $transaction                           = new Transaction();
            $transaction->user_id                  = $user_id;
            $transaction->currency_id              = $currencyId;
            $transaction->payment_method_id        = $payment_method_id;
            $transaction->transaction_reference_id = $deposit->id;
            $transaction->transaction_type_id      = Deposit;
            $transaction->uuid                     = $uuid;
            $transaction->subtotal                 = $present_amount;
            $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
            $transaction->charge_percentage        = $deposit->charge_percentage;
            $transaction->charge_fixed             = $deposit->charge_fixed;
            $total_fees                            = $deposit->charge_percentage + $deposit->charge_fixed;
            $transaction->total                    = $sessionValue['amount'] + $total_fees;
            $transaction->status                   = 'Success';
            $transaction->save();

            //Wallet
            $wallet          = Wallet::where(['user_id' => $user_id, 'currency_id' => $currencyId])->first(['id', 'balance']);
            $wallet->balance = ($wallet->balance + $present_amount);
            $wallet->save();

            DB::commit();

            // Send notification to admin
            $response = $this->helper->sendTransactionNotificationToAdmin('deposit', ['data' => $deposit]);

            $data['transaction'] = $transaction;
            return \Redirect::route('deposit.paypal.success')->with(['data' => $data]);
        }
        catch (Exception $e)
        {
            DB::rollBack();
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
            clearActionSession();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect('deposit');
        }
    }

    public function paypalDepositPaymentSuccess()
    {
        if (empty(session('data'))) {
            return redirect('deposit');
        } else {
            $data['transaction'] = session('data')['transaction'];
            //clearing session
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo', 'data']);
            clearActionSession();
            return view('user_dashboard.deposit.success', $data);
        }
    }

    public function paymentCancel()
    {
        clearActionSession();
        $this->helper->one_time_message('error', __('You have cancelled your payment'));
        return back();
    }
    /* End of PayPal */

    /* Start of 2Checkout */
    public function checkoutPayment()
    {
        $data['menu']              = 'deposit';
        $amount                    = Session::get('amount');
        $data['amount']            = number_format((float) $amount, 2, '.', ''); //2Checkout accepts 2 decimal places only - if not rounded to 2 decimal places, 2Checkout will throw ERROR CODE:PE103.
        $data['payment_method_id'] = Session::get('payment_method_id');
        $data['seller_id']         = Session::get('seller_id');
        $currencyId                = Session::get('wallet_currency_id');
        $data['currency']          = Currency::find($currencyId, ['id', 'code']);
        $data['mode']              = Session::get('2Checkout_mode');
        return view('user_dashboard.deposit.2checkout', $data);
    }

    public function checkoutPaymentConfirm(Request $request)
    {
        actionSessionCheck();

        $payment_method_id = Session::get('payment_method_id');
        $sessionValue      = session('transInfo');
        $user_id           = auth()->user()->id;
        $wallet            = Wallet::where(['currency_id' => $sessionValue['currency_id'], 'user_id' => $user_id])->first(['id', 'currency_id']);
        if (empty($wallet))
        {
            $walletInstance              = new Wallet();
            $walletInstance->user_id     = $user_id;
            $walletInstance->currency_id = $sessionValue['currency_id'];
            $walletInstance->balance     = 0;
            $walletInstance->is_default  = 'No';
            $walletInstance->save();
        }
        $currencyId = isset($wallet->currency_id) ? $wallet->currency_id : $walletInstance->currency_id;
        if ($request->all())
        {
            $amount     = Session::get('amount');
            $uuid       = unique_code();
            $feeInfo    = $this->helper->getFeesLimitObject([], Deposit, $currencyId, $payment_method_id, null, ['charge_percentage', 'charge_fixed']);
            $p_calc     = $sessionValue['amount'] * (@$feeInfo->charge_percentage / 100);
            $total_fees = $p_calc+@$feeInfo->charge_fixed;

            try
            {
                DB::beginTransaction();
                //Deposit
                $deposit                    = new Deposit();
                $deposit->user_id           = $user_id;
                $deposit->currency_id       = $currencyId;
                $deposit->payment_method_id = $payment_method_id;
                $deposit->uuid              = $uuid;
                $deposit->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
                $deposit->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
                $deposit->amount            = $present_amount            = $amount - $total_fees;
                $deposit->status            = 'Success';
                $deposit->save();

                //Transaction
                $transaction                           = new Transaction();
                $transaction->user_id                  = $user_id;
                $transaction->currency_id              = $currencyId;
                $transaction->payment_method_id        = $payment_method_id;
                $transaction->transaction_reference_id = $deposit->id;
                $transaction->transaction_type_id      = Deposit;
                $transaction->uuid                     = $uuid;
                $transaction->subtotal                 = $present_amount;
                $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
                $transaction->charge_percentage        = $deposit->charge_percentage;
                $transaction->charge_fixed             = $deposit->charge_fixed;
                $transaction->total                    = $sessionValue['amount'] + $total_fees;
                $transaction->status                   = 'Success';
                $transaction->save();

                //Wallet
                $wallet          = Wallet::where(['user_id' => $user_id, 'currency_id' => $currencyId])->first(['id', 'balance']);
                $wallet->balance = ($wallet->balance + $present_amount);
                $wallet->save();

                DB::commit();

                // Send mail to admin
                $response = $this->helper->sendTransactionNotificationToAdmin('deposit', ['data' => $deposit]);

                $data['transaction'] = $transaction;

                return \Redirect::route('deposit.checkout.success')->with(['data' => $data]);
            }
            catch (Exception $e)
            {
                DB::rollBack();
                session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
                clearActionSession();
                $this->helper->one_time_message('error', $e->getMessage());
                return redirect('deposit');
            }
        }
        else
        {
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
            clearActionSession();
            $this->helper->one_time_message('error', __('Please try again later!'));
            return back();
        }
    }

    public function checkoutPaymentSuccess()
    {
        if (empty(session('data')))
        {
            return redirect('deposit');
        }
        else
        {
            $data['transaction'] = session('data')['transaction'];

            //clearing session
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo', 'data']);
            clearActionSession();
            return view('user_dashboard.deposit.success', $data);
        }
    }
    /* End of 2Checkout */

    /* Start of Payumoney */
    public function payumoneyPayment()
    {
        $data['menu'] = 'deposit';

        //Check Currency Code - starts - pm_v2.3
        $currency_id  = session('transInfo')['currency_id'];
        $currencyCode = Currency::where(['id' => $currency_id])->first(['code'])->code;
        if ($currencyCode !== 'INR')
        {
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
            clearActionSession();
            $this->helper->one_time_message('error', __('PayUMoney only supports Indian Rupee(INR)'));
            return redirect('deposit');
        }
        $amount            = session('transInfo')['amount'];             //fixed - was getting total - should get amount
        $data['amount']    = number_format((float) $amount, 2, '.', ''); //Payumoney accepts 2 decimal places only - if not rounded to 2 decimal places, Payumoney will throw.
        $data['mode']      = Session::get('mode');
        $data['key']       = Session::get('key');
        $data['salt']      = Session::get('salt');
        $data['email']     = auth()->user()->email;
        $data['txnid']     = unique_code();
        $data['firstname'] = auth()->user()->first_name;
        return view('user_dashboard.deposit.payumoney', $data);
    }

    public function payumoneyPaymentConfirm()
    {
        actionSessionCheck();

        $sessionValue = session('transInfo');
        $user_id      = auth()->user()->id;
        $amount       = Session::get('amount');
        $uuid         = unique_code();

        if ($_POST['status'] == 'success')
        {
            $feeInfo    = $this->helper->getFeesLimitObject([], Deposit, $sessionValue['currency_id'], $sessionValue['payment_method'], null, ['charge_percentage', 'charge_fixed']);
            $p_calc     = $sessionValue['amount'] * (@$feeInfo->charge_percentage / 100);
            $total_fees = $p_calc+@$feeInfo->charge_fixed;

            try
            {
                DB::beginTransaction();

                //Deposit
                $deposit                    = new Deposit();
                $deposit->user_id           = $user_id;
                $deposit->currency_id       = $sessionValue['currency_id'];
                $deposit->payment_method_id = Session::get('payment_method_id');
                $deposit->uuid              = $uuid;
                $deposit->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
                $deposit->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
                $deposit->amount            = $present_amount            = $amount - $total_fees;
                $deposit->status            = 'Success';
                $deposit->save();

                //Transaction
                $transaction                           = new Transaction();
                $transaction->user_id                  = $user_id;
                $transaction->currency_id              = $sessionValue['currency_id'];
                $transaction->payment_method_id        = Session::get('payment_method_id');
                $transaction->transaction_reference_id = $deposit->id;
                $transaction->transaction_type_id      = Deposit;
                $transaction->uuid                     = $uuid;
                $transaction->subtotal                 = $present_amount;
                $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
                $transaction->charge_percentage        = $deposit->charge_percentage;
                $transaction->charge_fixed             = $deposit->charge_fixed;
                $transaction->total                    = $sessionValue['amount'] + $total_fees;
                $transaction->status                   = 'Success';
                $transaction->save();

                //Wallet
                $chkWallet = Wallet::where(['user_id' => $user_id, 'currency_id' => $sessionValue['currency_id']])->first(['id', 'balance']);
                if (empty($chkWallet))
                {
                    $wallet              = new Wallet();
                    $wallet->user_id     = $user_id;
                    $wallet->currency_id = $sessionValue['currency_id'];
                    $wallet->balance     = $present_amount;
                    $wallet->is_default  = 'No';
                    $wallet->save();
                }
                else
                {
                    $chkWallet->balance = ($chkWallet->balance + $present_amount);
                    $chkWallet->save();
                }
                DB::commit();

                // Send mail to admin
                $response = $this->helper->sendTransactionNotificationToAdmin('deposit', ['data' => $deposit]);

                $data['transaction'] = $transaction;

                return \Redirect::route('deposit.payumoney.success')->with(['data' => $data]);
            }
            catch (Exception $e)
            {
                DB::rollBack();
                session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'mode', 'key', 'salt', 'transInfo']);
                clearActionSession();
                $this->helper->one_time_message('error', $e->getMessage());
                return redirect('deposit');
            }
        }
    }

    public function payumoneyPaymentSuccess()
    {
        if (empty(session('data')))
        {
            return redirect('deposit');
        }
        else
        {
            $data['transaction'] = session('data')['transaction'];

            //Transaction
            $transaction                           = new Transaction();
            $transaction->user_id                  = auth()->user()->id;
            $transaction->currency_id              = $sessionValue['currency_id'];
            $transaction->payment_method_id        = $sessionValue['payment_method'];
            $transaction->bank_id                  = $request->bank;
            $transaction->file_id                  = $file->id;
            $transaction->uuid                     = $uuid;
            $transaction->transaction_reference_id = $deposit->id;
            $transaction->transaction_type_id      = Deposit;
            $transaction->subtotal                 = $deposit->amount;
            $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
            $transaction->charge_percentage        = $deposit->charge_percentage;
            $transaction->charge_fixed             = $deposit->charge_fixed;
            $transaction->total                    = $sessionValue['amount'] + $deposit->charge_percentage + $deposit->charge_fixed;
            $transaction->status                   = 'Pending'; //in bank deposit, status will be pending
            $transaction->save();

            //Wallet
            $wallet = Wallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sessionValue['currency_id']])->first(['id']);
            if (empty($wallet))
            {
                $wallet              = new Wallet();
                $wallet->user_id     = auth()->user()->id;
                $wallet->currency_id = $sessionValue['currency_id'];
                $wallet->balance     = 0; // as initially, transaction status will be pending
                $wallet->is_default  = 'No';
                $wallet->save();
            }
            DB::commit();

            // Send mail to admin
            $response = $this->helper->sendTransactionNotificationToAdmin('payout', ['data' => $deposit]);

            //For print
            $data['transaction'] = $transaction;

            //clearing session
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'mode', 'key', 'salt', 'transInfo', 'data']);
            clearActionSession();
            return view('user_dashboard.deposit.success', $data);
        }
    }

    public function payumoneyPaymentFail(Request $request)
    {
        if ($_POST['status'] == 'failure')
        {
            clearActionSession();
            $this->helper->one_time_message('error', __('You have cancelled your payment'));
            return redirect('deposit');
        }
    }
    /* End of Payumoney */

    /* Start of CoinPayment */
    public function coinpaymentsCheckStatus()
    {
        $coinLog = CointpaymentLogTrx::where('status', 0)->get(['id', 'payload', 'payment_id', 'status_text', 'status', 'confirmation_at']);
        foreach ($coinLog as $data)
        {
            $obj = json_decode($data->payload);
            if (isset($obj->type) && $obj->type == "deposit" && isset($obj->deposit_id))
            {

                $deposit                   = Deposit::find($obj->deposit_id, ['id', 'status', 'user_id', 'currency_id', 'payment_method_id', 'amount']);
                $session['payment_method'] = $deposit->payment_method_id;
                $session['currency_id']    = $deposit->currency_id;
                session(['transInfo' => $session]);
                //

                $payment = CoinPayment::api_call('get_tx_info', [
                    'txid' => $data->payment_id,
                ]);

                if ($payment['error'] == "ok")
                {
                    $result = $payment['result'];

                    $data->status_text     = $result['status_text'];
                    $data->status          = $result['status'];
                    $data->confirmation_at = ((INT) $result['status'] === 100) ? date('Y-m-d H:i:s', $result['time_completed']) : null;
                    $data->save();

                    if ($result['status'] == 100 || $result['status'] == 2)
                    {
                        try
                        {
                            DB::beginTransaction();

                            // payment is complete or queued for nightly payout, success
                            if (!empty($deposit))
                            {
                                $deposit->status = "Success";
                                $deposit->save();
                            }

                            $trans = Transaction::where("transaction_reference_id", $deposit->id)->where('transaction_type_id', Deposit)->first(['id', 'status']);
                            if (!empty($trans))
                            {
                                $trans->status = "Success";
                                $trans->save();
                            }

                            $wallet = Wallet::where(['user_id' => $deposit->user_id, 'currency_id' => $deposit->currency_id])->first(['id', 'balance']);
                            if (!empty($wallet))
                            {
                                $wallet->balance = ($wallet->balance + $deposit->amount);
                                $wallet->save();
                            }
                            DB::commit();

                            // Send mail to admin
                            $response = $this->helper->sendTransactionNotificationToAdmin('deposit', ['data' => $deposit]);
                        }
                        catch (Exception $e)
                        {
                            DB::rollBack();
                            $this->helper->one_time_message('error', $e->getMessage());
                            return redirect('deposit');
                        }
                    }
                    else if ($result['status'] == 0)
                    {
                        echo "<pre>";
                        echo "Waiting for CoinPayments buyer funds for Payment ID - " . $data->payment_id;
                        echo "<br>";
                    }
                    else if ($result['status'] < 0)
                    {
                        //payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent
                        echo "<pre>";
                        echo "Payment Error for Payment ID - " . $data->payment_id;
                        echo "<br>";
                    }
                }
            }
        }
    }

    public function coinpaymentsCancel()
    {
        clearActionSession();
        $this->helper->one_time_message('error', __('You have cancelled your payment'));
        return redirect('deposit');
    }
    /* End of CoinPayment */

    /* Start of Payeer */
    public function payeerPayement()
    {
        $data['menu']       = 'deposit';
        $amount             = Session::get('amount');
        $transInfo          = Session::get('transInfo');
        $currency           = Currency::where(['id' => $transInfo['currency_id']])->first(['code']);
        $payeer_merchant_id = Session::get('payeer_merchant_id');
        $data['m_shop']     = $m_shop     = $payeer_merchant_id;
        $data['m_orderid']  = $m_orderid  = six_digit_random_number();
        $data['m_amount'] = $m_amount = number_format((float) $amount, 2, '.', ''); //Payeer might throw error, if 2 decimal place amount is not sent to Payeer server

        // $data['m_amount'] = $m_amount = "0.01"; // for test purpose

        $data['m_curr']             = $m_curr             = $currency->code;
        $data['form_currency_code'] = $form_currency_code = $currency->code;
        $data['m_desc']             = $m_desc             = base64_encode('Deposit');
        $payeer_secret_key          = Session::get('payeer_secret_key');
        $m_key                      = $payeer_secret_key;
        $arHash                     = array(
            $m_shop,
            $m_orderid,
            $m_amount,
            $m_curr,
            $m_desc,
        );
        $merchantDomain = Session::get('payeer_merchant_domain');
        $arParams       = array(
            'success_url' => url('/') . '/deposit/payeer/payment/confirm',
            'status_url'  => url('/') . '/deposit/payeer/payment/status',
            'fail_url'    => url('/') . '/deposit/payeer/payment/fail',
            'reference'   => array(
                'email' => auth()->user()->email,
                'name'  => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            ),
            'submerchant' => $merchantDomain,
        );
        $cipher                = 'AES-256-CBC';
        $merchantEncryptionKey = Session::get('payeer_encryption_key');
        $key                   = md5($merchantEncryptionKey . $m_orderid);                                                            //key from (payeer.com->merchant settings->Key for encryption additional parameters)
        $m_params              = @urlencode(base64_encode(openssl_encrypt(json_encode($arParams), $cipher, $key, OPENSSL_RAW_DATA))); // this throws error if '@' symbol is not used
        $arHash[]              = $data['m_params']              = $m_params;
        $arHash[]              = $m_key;
        $data['sign']          = strtoupper(hash('sha256', implode(":", $arHash)));
        return view('user_dashboard.deposit.payeer', $data);

        // return redirect('deposit/payeer/payment/confirm');
    }

    public function payeerPayementConfirm(Request $request)
    {
        if (isset($request['m_operation_id']) && isset($request['m_sign']))
        {
            $payeer_secret_key = Session::get('payeer_secret_key');

            $m_key  = $payeer_secret_key;
            $arHash = array(
                $request['m_operation_id'],
                $request['m_operation_ps'],
                $request['m_operation_date'],
                $request['m_operation_pay_date'],
                $request['m_shop'],
                $request['m_orderid'],
                $request['m_amount'],
                $request['m_curr'],
                $request['m_desc'],
                $request['m_status'],
            );

            //additional parameters
            if (isset($request['m_params']))
            {
                $arHash[] = $request['m_params'];
            }

            $arHash[]  = $m_key;
            $sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));

            if ($request['m_sign'] == $sign_hash && $request['m_status'] == 'success')
            {
                actionSessionCheck();
                $sessionValue = session('transInfo');

                $user_id           = auth()->user()->id;
                $uuid              = unique_code();
                $feeInfo           = $this->helper->getFeesLimitObject([], Deposit, $sessionValue['currency_id'], $sessionValue['payment_method'], null, ['charge_percentage', 'charge_fixed']);
                $p_calc            = $sessionValue['amount'] * (@$feeInfo->charge_percentage / 100);
                $total_fees        = $p_calc+@$feeInfo->charge_fixed;
                $payment_method_id = $sessionValue['payment_method'];
                $sessionAmount     = Session::get('amount');
                $amount            = $sessionAmount;

                try
                {
                    DB::beginTransaction();
                    //Deposit
                    $deposit                    = new Deposit();
                    $deposit->user_id           = auth()->user()->id;
                    $deposit->currency_id       = $sessionValue['currency_id'];
                    $deposit->payment_method_id = $payment_method_id;
                    $deposit->uuid              = $uuid;
                    $deposit->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
                    $deposit->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
                    $deposit->amount            = $present_amount            = ($amount - ($p_calc + (@$feeInfo->charge_fixed)));
                    $deposit->status            = 'Success';
                    $deposit->save();

                    //Transaction
                    $transaction                           = new Transaction();
                    $transaction->user_id                  = auth()->user()->id;
                    $transaction->currency_id              = $sessionValue['currency_id'];
                    $transaction->payment_method_id        = $payment_method_id;
                    $transaction->transaction_reference_id = $deposit->id;
                    $transaction->transaction_type_id      = Deposit;
                    $transaction->uuid                     = $uuid;
                    $transaction->subtotal                 = $present_amount;
                    $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
                    $transaction->charge_percentage        = $deposit->charge_percentage;
                    $transaction->charge_fixed             = $deposit->charge_fixed;
                    $transaction->total                    = $sessionValue['amount'] + $total_fees;
                    $transaction->status                   = 'Success';
                    $transaction->save();

                    //Wallet
                    $chkWallet = Wallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sessionValue['currency_id']])->first(['id', 'balance']);
                    if (empty($chkWallet))
                    {
                        //if wallet does not exist, create it
                        $wallet              = new Wallet();
                        $wallet->user_id     = auth()->user()->id;
                        $wallet->currency_id = $sessionValue['currency_id'];
                        $wallet->balance     = $deposit->amount;
                        $wallet->is_default  = 'No';
                        $wallet->save();
                    }
                    else
                    {
                        //add deposit amount to existing wallet
                        $chkWallet->balance = ($chkWallet->balance + $deposit->amount);
                        $chkWallet->save();
                    }
                    DB::commit();

                    // Send mail to admin
                    $response = $this->helper->sendTransactionNotificationToAdmin('deposit', ['data' => $deposit]);

                    $data['transaction'] = $transaction;

                    return \Redirect::route('deposit.payeer.success')->with(['data' => $data]);
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                    session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'payeer_merchant_id', 'payeer_secret_key',
                    'payeer_encryption_key', 'payeer_merchant_domain','transInfo']);
                    $this->helper->one_time_message('error', $e->getMessage());
                    return redirect('deposit');
                }
            }
            else
            {
                session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'payeer_merchant_id', 'payeer_secret_key',
                'payeer_encryption_key', 'payeer_merchant_domain','transInfo']);
                clearActionSession();
                $this->helper->one_time_message('error', __('Please try again later!'));
                return back();
            }
        }
    }

    public function payeerPayementSuccess()
    {
        if (empty(session('data')))
        {
            return redirect('deposit');
        }
        else
        {
            $data['transaction'] = session('data')['transaction'];

            //clearing session
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'payeer_merchant_id', 'payeer_secret_key',
                'payeer_encryption_key', 'payeer_merchant_domain','transInfo', 'data']);
            clearActionSession();
            return view('user_dashboard.deposit.success', $data);
        }
    }

    public function payeerPayementStatus(Request $request)
    {
        return 'Payeer Status Page =>'.$request->all();
    }

    public function payeerPayementFail()
    {
        $this->helper->one_time_message('error', __('You have cancelled your payment'));
        return redirect('deposit');
    }
    /* End of Payeer */

    /* Start of Bank Payment Method */
    public function bankPaymentConfirm(Request $request)
    {
        actionSessionCheck();

        $sessionValue = session('transInfo');
        $feeInfo      = $this->helper->getFeesLimitObject([], Deposit, $sessionValue['currency_id'], $sessionValue['payment_method'], null, ['charge_percentage', 'charge_fixed']);
        $uuid         = unique_code();
        $p_calc       = $sessionValue['amount'] * (@$feeInfo->charge_percentage / 100);

        try
        {
            DB::beginTransaction();

            //File
            if ($request->hasFile('attached_file'))
            {
                $fileName     = $request->file('attached_file');
                $originalName = $fileName->getClientOriginalName();
                $uniqueName   = strtolower(time() . '.' . $fileName->getClientOriginalExtension());
                $file_extn    = strtolower($fileName->getClientOriginalExtension());
                $path         = 'uploads/files/bank_attached_files';
                $uploadPath   = public_path($path);
                $fileName->move($uploadPath, $uniqueName);

                //File
                $file               = new File();
                $file->user_id      = auth()->user()->id;
                $file->filename     = $uniqueName;
                $file->originalname = $originalName;
                $file->type         = $file_extn;
                $file->save();
            }

            //Deposit
            $deposit                    = new Deposit();
            $deposit->user_id           = auth()->user()->id;
            $deposit->currency_id       = $sessionValue['currency_id'];
            $deposit->payment_method_id = $sessionValue['payment_method'];
            $deposit->bank_id           = $request->bank;
            $deposit->file_id           = $file->id;
            $deposit->uuid              = $uuid;
            $deposit->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
            $deposit->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
            $deposit->amount            = $sessionValue['amount'];
            $deposit->status            = 'Pending'; //in bank deposit, status will be pending
            $deposit->save();

            //Transaction
            $transaction                           = new Transaction();
            $transaction->user_id                  = auth()->user()->id;
            $transaction->currency_id              = $sessionValue['currency_id'];
            $transaction->payment_method_id        = $sessionValue['payment_method'];
            $transaction->bank_id                  = $request->bank;
            $transaction->file_id                  = $file->id;
            $transaction->uuid                     = $uuid;
            $transaction->transaction_reference_id = $deposit->id;
            $transaction->transaction_type_id      = Deposit;
            $transaction->subtotal                 = $deposit->amount;
            $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
            $transaction->charge_percentage        = $deposit->charge_percentage;
            $transaction->charge_fixed             = $deposit->charge_fixed;
            $transaction->total                    = $sessionValue['amount'] + $deposit->charge_percentage + $deposit->charge_fixed;
            $transaction->status                   = 'Pending'; //in bank deposit, status will be pending
            $transaction->save();

            //Wallet
            $wallet = Wallet::where(['user_id' => auth()->user()->id, 'currency_id' => $sessionValue['currency_id']])->first(['id']);
            if (empty($wallet))
            {
                $wallet              = new Wallet();
                $wallet->user_id     = auth()->user()->id;
                $wallet->currency_id = $sessionValue['currency_id'];
                $wallet->balance     = 0; // as initially, transaction status will be pending
                $wallet->is_default  = 'No';
                $wallet->save();
            }
            DB::commit();

            // Send mail to admin
            $response = $this->helper->sendTransactionNotificationToAdmin('deposit', ['data' => $deposit]);

            //For print
            $data['transaction'] = $transaction;

            return \Redirect::route('deposit.bank.success')->with(['data' => $data]);
        }
        catch (Exception $e)
        {
            DB::rollBack();
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo']);
            clearActionSession();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect('deposit');
        }
    }

    public function bankPaymentSuccess()
    {
        if (empty(session('data')))
        {
            return redirect('deposit');
        }
        else
        {
            $data['transaction'] = session('data')['transaction'];

            //clearing session
            session()->forget(['coinpaymentAmount', 'wallet_currency_id', 'method', 'payment_method_id', 'amount', 'transInfo', 'data']);
            clearActionSession();
            return view('user_dashboard.deposit.success', $data);
        }
    }
    /* End of Bank Payment Method */

    public function depositPrintPdf($trans_id)
    {
        $data['companyInfo'] = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);

        $data['transactionDetails'] = Transaction::with(['payment_method:id,name', 'currency:id,symbol,code'])
            ->where(['id' => $trans_id])
            ->first(['uuid', 'created_at', 'status', 'currency_id', 'payment_method_id', 'subtotal', 'charge_percentage', 'charge_fixed', 'total']);

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A3',
            'orientation' => 'P',
        ]);
        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('user_dashboard.deposit.depositPaymentPdf', $data));
        $mpdf->Output('sendMoney_' . time() . '.pdf', 'I'); //
    }
}
