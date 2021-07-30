<?php

namespace App\Http\Controllers\Users;

use Illuminate\Support\Facades\{Validator,
    Auth
};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Currency,
    Transaction,
    FeesLimit,
    Transfer,
    Setting,
    Wallet,
    User,
    Fee 
};
use App;

class MoneyTransferController extends Controller
{
    protected $helper;
    protected $transfer;

    public function __construct()
    {
        $this->helper   = new Common();
        $this->transfer = new Transfer();
    }

    //Send Money - Email/Phone validation
    public function transferUserEmailPhoneReceiverStatusValidate(Request $request)
    {
        $phoneRegex = $this->helper->validatePhoneInput(trim($request->receiver));
        if ($phoneRegex)
        {
            //Check phone number exists or not
            $user = User::where(['id' => auth()->user()->id])->first(['formattedPhone']);
            if (empty($user->formattedPhone))
            {
                return response()->json([
                    'status'  => 404,
                    'message' => __("Please set your phone number first!"),
                ]);
            }

            //Check own phone number
            if ($request->receiver == auth()->user()->formattedPhone)
            {
                return response()->json([
                    'status'  => true,
                    'message' => __("You Cannot Send Money To Yourself!"),
                ]);
            }

            //Check Receiver/Recipient is suspended/inactive - if entered phone number
            $receiver = User::where(['formattedPhone' => $request->receiver])->first(['status']);
            if (!empty($receiver))
            {
                if ($receiver->status == 'Suspended')
                {
                    return response()->json([
                        'status'  => true,
                        'message' => __("The recipient is suspended!"),
                    ]);
                }
                elseif ($receiver->status == 'Inactive')
                {
                    return response()->json([
                        'status'  => true,
                        'message' => __("The recipient is inactive!"),
                    ]);
                }
            }
        }
        else
        {
            //Check own phone email
            if ($request->receiver == auth()->user()->email)
            {
                return response()->json([
                    'status'  => true,
                    'message' => __("You Cannot Send Money To Yourself!"),
                ]);
            }

            //Check Receiver/Recipient is suspended/inactive - if entered email
            $receiver = User::where(['email' => trim($request->receiver)])->first(['status']);
            if (!empty($receiver))
            {
                if ($receiver->status == 'Suspended')
                {
                    return response()->json([
                        'status'  => true,
                        'message' => __("The recipient is suspended!"),
                    ]);
                }
                elseif ($receiver->status == 'Inactive')
                {
                    return response()->json([
                        'status'  => true,
                        'message' => __("The recipient is inactive!"),
                    ]);
                }
            }
        }
    }

    public function create(Request $request)
    {
        //set the session for validating the action
        setActionSession();

        $data['menu']    = 'send_receive';
        $data['submenu'] = 'send';

        if (!$request->isMethod('post'))
        {
            /*Check Whether Currency is Activated in feesLimit*/
            $data['walletList'] = Wallet::where(['user_id' => auth()->user()->id])
                ->whereHas('active_currency', function ($q)
            {
                    $q->whereHas('fees_limit', function ($query)
                {
                        $query->where('transaction_type_id', Transferred)->where('has_transaction', 'Yes')->select('currency_id', 'has_transaction');
                    });
                })
                ->with(['active_currency:id,code', 'active_currency.fees_limit:id,currency_id']) //Optimized by parvez - for pm v2.3
                ->get(['id', 'currency_id', 'is_default']);

            //check Decimal Thousand Money Format Preference
            $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);
            $data['currencies'] = Currency:: all();
            return view('user_dashboard.moneytransfer.create', $data);
        }
        else if ($request->isMethod('post'))
        {
            $rules = array(
                'amount'   => 'required|numeric',
                'receiver' => 'required',
                'note'     => 'required',
                'receivername' => 'required',
            );

            $fieldNames = array(
                'amount'   => __("Amount"),
                'receiver' => __("Recipient"),
                'note'     => __("Note"),
                'receivername'     => __("Receiver Name"),
            );

            //instantiating message array
            $messages = [
                //
            ];

            // backend Validation - starts
            if ($request->sendMoneyProcessedBy == 'email')
            {
                //check if valid email
                $rules['receiver'] = 'required|email';
            }
            elseif ($request->sendMoneyProcessedBy == 'phone')
            {
                //check if valid phone
                $myStr = explode('+', $request->receiver);
                if ($request->receiver[0] != "+" || !is_numeric($myStr[1]))
                {
                    return back()->withErrors(__("Please enter valid phone (ex: +12015550123)"))->withInput();
                }
            }
            elseif ($request->sendMoneyProcessedBy == 'email_or_phone')
            {
                $myStr = explode('+', $request->receiver);
                //valid number is not entered
                if ($request->receiver[0] != "+" || !is_numeric($myStr[1]))
                {
                    //check if valid email or phone
                    $rules['receiver'] = 'required|email';
                    $messages          = [
                        'email' => __("Please enter valid email (ex: user@gmail.com) or phone (ex: +12015550123)"),
                    ];
                }
            }

            //Own Email or phone validation + Receiver/Recipient is suspended/Inactive validation
            $transferUserEmailPhoneReceiverStatusValidate = $this->transferUserEmailPhoneReceiverStatusValidate($request);
            if ($transferUserEmailPhoneReceiverStatusValidate)
            {
                if ($transferUserEmailPhoneReceiverStatusValidate->getData()->status == true || $transferUserEmailPhoneReceiverStatusValidate->getData()->status == 404)
                {
                    return back()->withErrors(__($transferUserEmailPhoneReceiverStatusValidate->getData()->message))->withInput();
                }
            }

            //Amount Limit Check validation
            $request['wallet_id']           = $request->wallet;
            $request['transaction_type_id'] = Transferred;
            $amountLimitCheck               = $this->amountLimitCheck($request);
            // if ($amountLimitCheck->getData()->success->status == 200)
            // {
            //     if ($amountLimitCheck->getData()->success->totalAmount > $amountLimitCheck->getData()->success->balance)
            //     {
            //         // return back()->withErrors(__("Not have enough balance !"))->withInput();
            //         return back();
            //     }
            // }
            // else
            // {
            //     // return back()->withErrors(__($amountLimitCheck->getData()->success->message))->withInput();
            //     return back();
            // }
            //backend validation ends

            // $validator = Validator::make($request->all(), $rules, $messages);
            // $validator->setAttributeNames($fieldNames);
            // if ($validator->fails())
            // {
            //     return back()->withErrors($validator)->withInput();
            // }
            // else
            // {
                //Validation passed
                $wallet                          = Wallet::with(['currency:id,symbol'])->where(['id' => $request->wallet, 'user_id' => auth()->user()->id])->first(['currency_id', 'balance']);
                
                // $request['currency_id']          = $wallet->currency->id;
                $request['currency_id']          = $request->currency_id;
                $request['currSymbol']           = $wallet->currency->symbol;
                $request['totalAmount']          = $request->amount + $request->fee;
                $request['sendMoneyProcessedBy'] = $request->sendMoneyProcessedBy;
                session(['transInfo' => $request->all()]);
                $data['transInfo'] = $request->all();
            // }

            return view('user_dashboard.moneytransfer.confirmation', $data);
        }
    }

    //Send Money - Amount Limit Check
    public function amountLimitCheck(Request $request)
    {
        $amount      = $request->amount;
        $wallet_id   = $request->wallet_id;
        $user_id     = Auth::user()->id;
        $wallet      = Wallet::where(['id' => $wallet_id, 'user_id' => $user_id])->first(['currency_id', 'balance']);
        $currency_id = $wallet->currency_id;
        $feesDetails = FeesLimit::where(['transaction_type_id' => $request->transaction_type_id, 'currency_id' => $currency_id])->first(['max_limit', 'min_limit', 'charge_percentage', 'charge_fixed']);

        //Code for Amount Limit starts here
        // if (@$feesDetails->max_limit == null)
        // {
        //     if ((@$amount < @$feesDetails->min_limit))
        //     {
        //         $success['message'] = __('Minimum amount ') . formatNumber($feesDetails->min_limit);
        //         $success['status']  = '401';
        //     }
        //     else
        //     {
        //         $success['status'] = 200;
        //     }
        // }
        // else
        // {
        //     if ((@$amount < @$feesDetails->min_limit) || (@$amount > @$feesDetails->max_limit))
        //     {
        //         $success['message'] = __('Minimum amount ') . formatNumber($feesDetails->min_limit) . __(' and Maximum amount ') . formatNumber($feesDetails->max_limit);
        //         $success['status']  = '401';
        //     }
        //     else
        //     {
        //         $success['status'] = 200;
        //     }
        // }
        //Code for Amount Limit ends here

        //Code for Fees Limit Starts here
        if (empty($feesDetails))
        {
            $feesPercentage            = 0;
            $feesFixed                 = 0;
            $totalFess                 = 0;
            $totalAmount               = $amount;
            $success['feesPercentage'] = $feesPercentage;
            $success['feesFixed']      = $feesFixed;
            $success['totalFees']      = $totalFess;
            $success['totalFeesHtml']  = formatNumber($totalFess);
            $success['totalAmount']    = $totalAmount;
            $success['pFees']          = $feesPercentage;
            $success['fFees']          = $feesFixed;
            $success['pFeesHtml']      = formatNumber($feesPercentage);
            $success['fFeesHtml']      = formatNumber($feesFixed);
            $success['min']            = 0;
            $success['max']            = 0;
            $success['balance']        = $wallet->balance;
        }
        else
        {
            $feesPercentage            = 0;
            $feesFixed                 = 0;
            $totalFess                 = 0;
            $totalAmount               = $amount;
            $success['feesPercentage'] = $feesPercentage;
            $success['feesFixed']      = $feesFixed;
            $success['totalFees']      = $totalFess;
            $success['totalFeesHtml']  = formatNumber($totalFess);
            $success['totalAmount']    = $totalAmount;
            $success['pFees']          = $feesDetails->charge_percentage;
            $success['fFees']          = $feesDetails->charge_fixed;
            $success['pFeesHtml']      = formatNumber($feesDetails->charge_percentage);
            $success['fFeesHtml']      = formatNumber($feesDetails->charge_fixed);
            $success['min']            = $feesDetails->min_limit;
            $success['max']            = $feesDetails->max_limit;
            $success['balance']        = $wallet->balance;
        }
        //Code for Fees Limit Ends here
        return response()->json(['success' => $success]);
    }

    //Send Money - Confirm
    public function sendMoneyConfirm(Request $request)
    {
        $data['menu']    = 'send_receive';
        $data['submenu'] = 'send';

        $sessionValue = session('transInfo');
        if (empty($sessionValue))
        {
            return redirect('moneytransfer');
        }

        //initializing session
        actionSessionCheck();

        //Data - Wallet Balance Again Amount Check
        $total_with_fee          = $sessionValue['amount'] + $sessionValue['fee'];
        $currency_id             = session('transInfo')['currency_id'];
        $user_id                 = auth()->user()->id;
        $feesDetails             = $this->helper->getFeesLimitObject([], Transferred, $sessionValue['currency_id'], null, null, ['charge_percentage', 'charge_fixed']);
        $senderWallet            = $this->helper->getUserWallet([], ['user_id' => $user_id, 'currency_id' => $currency_id], ['id', 'balance']);
        $p_calc                  = $sessionValue['amount'] * (@$feesDetails->charge_percentage / 100);
        $processedBy             = $sessionValue['sendMoneyProcessedBy'];
        $request_wallet_currency = $sessionValue['currency_id'];
        $unique_code             = unique_code();
        $emailFilterValidate     = $this->helper->validateEmailInput(trim($sessionValue['receiver']));
        $phoneRegex              = $this->helper->validatePhoneInput(trim($sessionValue['receiver']));
        $userInfo                = $this->helper->getEmailPhoneValidatedUserInfo($emailFilterValidate, $phoneRegex, trim($sessionValue['receiver']));
        $arr                     = [
            'emailFilterValidate' => $emailFilterValidate,
            'phoneRegex'          => $phoneRegex,
            'processedBy'         => $processedBy,
            'user_id'             => $user_id,
            'userInfo'            => $userInfo,
            'currency_id'         => $request_wallet_currency,
            'uuid'                => $unique_code,
            'fee'                 => $sessionValue['fee'],
            'amount'              => $sessionValue['amount'],
            'note'                => trim($sessionValue['note']),
            'receivername'                => trim($sessionValue['receivername']),
            'receiver'            => trim($sessionValue['receiver']),
            'charge_percentage'   => $feesDetails->charge_percentage,
            'charge_fixed'        => $feesDetails->charge_fixed,
            'p_calc'              => $p_calc,
            'total'               => $total_with_fee,
            'senderWallet'        => $senderWallet,
        ];
        $data['transInfo']['receiver']   = $sessionValue['receiver'];
        $data['transInfo']['currSymbol'] = $sessionValue['currSymbol'];
        $data['transInfo']['amount']     = $sessionValue['amount'];
        $data['userPic']                 = isset($userInfo) ? $userInfo->picture : '';
        $data['receiverName']            = isset($userInfo) ? $userInfo->first_name . ' ' . $userInfo->last_name : '';

        //Get response
        $response = $this->transfer->processSendMoneyConfirmation($arr, 'web');
        if ($response['status'] != 200)
        {
            if (empty($response['transactionOrTransferId']))
            {
                session()->forget('transInfo');
                $this->helper->one_time_message('error', $response['ex']['message']);
                return redirect('moneytransfer');
            }
            $data['errorMessage'] = $response['ex']['message'];
        }
        $data['transInfo']['trans_id'] = $response['transactionOrTransferId'];

        //clearing session
        session()->forget('transInfo');
        clearActionSession();
        return view('user_dashboard.moneytransfer.success', $data);
    }

    //Send Money - Generate pdf for print
    public function transferPrintPdf($trans_id)
    {
        $data['companyInfo']        = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);
        $data['transactionDetails'] = Transaction::with(['end_user:id,first_name,last_name', 'currency:id,symbol,code'])
            ->where(['id' => $trans_id])
            ->first(['transaction_type_id', 'end_user_id', 'currency_id', 'uuid', 'created_at', 'status', 'subtotal', 'charge_percentage', 'charge_fixed', 'total', 'note']);

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf = new \Mpdf\Mpdf([
            'mode'                 => 'utf-8',
            'format'               => 'A3',
            'orientation'          => 'P',
            'shrink_tables_to_fit' => 0,
        ]);
        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('user_dashboard.moneytransfer.transferPaymentPdf', $data));
        $mpdf->Output('sendMoney_' . time() . '.pdf', 'I'); // this will output data
    }
}
