<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Users\EmailController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\{DB,
    Validator
};
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Currency,
    RequestPayment,
    Transaction,
    FeesLimit,
    Setting,
    Wallet,
    User,
};
use App;
use Exception;

class RequestPaymentController extends Controller
{
    protected $helper;
    protected $email;
    protected $requestPayment;

    public function __construct()
    {
        $this->helper         = new Common();
        $this->email          = new EmailController();
        $this->requestPayment = new RequestPayment();
    }

    public function add()
    {
        //set the session for validating the action
        setActionSession();

        $data['menu']          = 'send_receive';
        $data['submenu']       = 'receive';
        $data['content_title'] = 'Request Payment';

        $activeCurrency       = Currency::where(['status' => 'Active'])->get(['id', 'status', 'code']);
        $feesLimitCurrency    = FeesLimit::where(['transaction_type_id' => Request_To, 'has_transaction' => 'Yes'])->get(['currency_id', 'has_transaction']);
        $data['currencyList'] = $this->currencyList($activeCurrency, $feesLimitCurrency);

        //pm_v2.3
        $data['defaultWallet'] = $defaultWallet = Wallet::where(['user_id' => auth()->user()->id, 'is_default' => 'Yes'])->first(['currency_id']);

        //check Decimal Thousand Money Format Preference
        $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);

        return view('user_dashboard.requestPayment.add', $data);
    }

    public function requestUserEmailPhoneReceiverStatusValidate(Request $request)
    {
        $phoneRegex = $this->helper->validatePhoneInput($request->requestCreatorEmailOrPhone);
        if ($phoneRegex)
        {
            $user = User::where(['id' => auth()->user()->id])->first(['formattedPhone']);
            if (empty($user->formattedPhone))
            {
                return response()->json([
                    'status'  => 404,
                    'message' => __("Please set your phone number first!"),
                ]);
            }

            //Check own phone number
            if ($request->requestCreatorEmailOrPhone == auth()->user()->formattedPhone)
            {
                return response()->json([
                    'status'  => true,
                    'message' => __("You Cannot Request Money To Yourself!"),
                ]);
            }

            //Check Request Acceptor/Recipient is suspended/inactive - if entered phone number
            $requestAcceptor = User::where(['formattedPhone' => $request->requestCreatorEmailOrPhone])->first(['status']);
            if (!empty($requestAcceptor))
            {
                if ($requestAcceptor->status == 'Suspended')
                {
                    return response()->json([
                        'status'  => true,
                        'message' => __("The recipient is suspended!"),
                    ]);
                }
                elseif ($requestAcceptor->status == 'Inactive')
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
            if ($request->requestCreatorEmailOrPhone == auth()->user()->email)
            {
                return response()->json([
                    'status'  => true,
                    'message' => __("You Cannot Request Money To Yourself!"),
                ]);
            }

            //Check Receiver/Recipient is suspended/inactive - if entered email
            $requestAcceptor = User::where(['email' => trim($request->requestCreatorEmailOrPhone)])->first(['status']);
            if (!empty($requestAcceptor))
            {
                if ($requestAcceptor->status == 'Suspended')
                {
                    return response()->json([
                        'status'  => true,
                        'message' => __("The recipient is suspended!"),
                    ]);
                }
                elseif ($requestAcceptor->status == 'Inactive')
                {
                    return response()->json([
                        'status'  => true,
                        'message' => __("The recipient is inactive!"),
                    ]);
                }
            }
        }
    }

    public function store(Request $request)
    {
        $data['menu']          = 'send_receive';
        $data['submenu']       = 'receive';
        $data['content_title'] = 'Request Payment';
        $rules                 = array(
            'amount' => 'required',
            'email'  => 'required',
            'note'   => 'required',
        );
        $fieldNames = array(
            'amount' => __("Amount"),
            'email'  => __("Email"),
            'note'   => __("Note"),
        );

        //new by arif v2.3
        // backend Validation - starts
        $messages = [
            //
        ];

        if ($request->requestMoneyProcessedBy == 'email')
        {
            $rules['email'] = 'required|email';
        }
        elseif ($request->requestMoneyProcessedBy == 'phone')
        {
            $myStr = explode('+', $request->email);
            if ($request->email[0] != "+" || !is_numeric($myStr[1]))
            {
                return back()->withErrors(__("Please enter valid phone (ex: +12015550123)"))->withInput();
            }
        }
        elseif ($request->requestMoneyProcessedBy == 'email_or_phone')
        {
            $myStr = explode('+', $request->email);
            //valid number is not entered
            if ($request->email[0] != "+" || !is_numeric($myStr[1]))
            {
                //check if valid email
                $rules['email'] = 'required|email';

                $messages = [
                    'email' => __("Please enter valid email (ex: user@gmail.com) or phone (ex: +12015550123)"),
                ];
            }
        }

        //Own Email or phone validation + Request Acceptor/Recipient is suspended/Inactive validation
        $request['requestCreatorEmailOrPhone']       = $request->email;
        $requestUserEmailPhoneReceiverStatusValidate = $this->requestUserEmailPhoneReceiverStatusValidate($request);
        if ($requestUserEmailPhoneReceiverStatusValidate)
        {
            if ($requestUserEmailPhoneReceiverStatusValidate->getData()->status == true || $requestUserEmailPhoneReceiverStatusValidate->getData()->status == 404)
            {
                return back()->withErrors(__($requestUserEmailPhoneReceiverStatusValidate->getData()->message))->withInput();
            }
        }

        // backend Validation - ends

        $validator = Validator::make($request->all(), $rules, $messages);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }
        else
        {
            $currency              = Currency::find($request->currency_id, ['id', 'symbol']);
            $request['currSymbol'] = $currency->symbol;
            $data['transInfo']     = $request->all();
            session(['transInfo' => $request->all()]);
            return view('user_dashboard.requestPayment.confirmation', $data);
        }
    }

    public function requestMoneyConfirm()
    {
        $data['menu']    = 'send_receive';
        $data['submenu'] = 'receive';

        $sessionValue = session('transInfo');
        if (empty($sessionValue))
        {
            return redirect('request_payment/add');
        }

        actionSessionCheck();

        $user_id             = auth()->user()->id;
        $processedBy         = $sessionValue['requestMoneyProcessedBy'];
        $uuid                = unique_code();
        $emailFilterValidate = $this->helper->validateEmailInput(trim($sessionValue['email']));
        $phoneRegex          = $this->helper->validatePhoneInput(trim($sessionValue['email']));
        $userInfo            = $this->helper->getEmailPhoneValidatedUserInfo($emailFilterValidate, $phoneRegex, trim($sessionValue['email']));
        $receiverName        = isset($userInfo) ? $userInfo->first_name . ' ' . $userInfo->last_name : '';
        $arr                 = [
            'unauthorisedStatus'  => null,
            'emailFilterValidate' => $emailFilterValidate,
            'phoneRegex'          => $phoneRegex,
            'processedBy'         => $processedBy,
            'user_id'             => $user_id,
            'userInfo'            => $userInfo,
            'currency_id'         => $sessionValue['currency_id'],
            'uuid'                => $uuid,
            'amount'              => $sessionValue['amount'],
            'receiver'            => $sessionValue['email'],
            'note'                => $sessionValue['note'],
            'receiverName'        => $receiverName,
            'senderEmail'         => auth()->user()->email,
            // 'status'              => 'Pending',
        ];
        $data['transInfo']['currSymbol'] = $sessionValue['currSymbol'];
        $data['transInfo']['amount']     = $sessionValue['amount'];
        $data['userPic']                 = isset($userInfo) ? $userInfo->picture : null;
        $data['receiverName']            = $receiverName;
        $data['transInfo']['email']      = $sessionValue['email'];

        //Get response
        $response = $this->requestPayment->processRequestCreateConfirmation($arr, 'web');
        if ($response['status'] != 200)
        {
            if (empty($response['transactionOrReqPaymentId']))
            {
                session()->forget('transInfo');
                $this->helper->one_time_message('error', $response['ex']['message']);
                return redirect('request_payment/add');
            }
            $data['errorMessage'] = $response['ex']['message'];
        }
        $data['transInfo']['trans_id'] = $response['transactionOrReqPaymentId'];

        //clearing session
        session()->forget('transInfo');
        clearActionSession();
        return view('user_dashboard.requestPayment.success', $data);
    }

    //Cancel from request acceptor
    public function cancel(Request $request)
    {
        $id = $request->id;
        try
        {
            DB::beginTransaction();
            $TransactionA         = Transaction::find($id); //TODO: query optimization
            $TransactionA->status = "Blocked";
            $TransactionA->save();

            $transaction_type_id = $TransactionA->transaction_type_id == Request_To ? Request_From : Request_To;
            $TransactionB        = Transaction::where([
                'transaction_reference_id' => $TransactionA->transaction_reference_id,
                'transaction_type_id'      => $transaction_type_id])->first(); //TODO: query optimization
            $TransactionB->status = "Blocked";
            $TransactionB->save();

            $RequestPayment         = RequestPayment::find($TransactionA->transaction_reference_id); //TODO: query optimization
            $RequestPayment->status = "Blocked";
            $RequestPayment->save();
            DB::commit();
            $data = $this->sendRequestCancelNotificationToAcceptorOrCreator($RequestPayment, $request->notificationType); //TODO: query optimization
            return json_encode($data);
        }
        catch (Exception $e)
        {
            DB::rollBack();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect('dashboard');
        }
    }

    //Cancel from request creator
    public function cancelfrom(Request $request)
    {
        $id = $request->id;
        try
        {
            DB::beginTransaction();
            if ($request->type == Request_From)
            {
                $TransactionA         = Transaction::find($id); //TODO: query optimization
                $TransactionA->status = "Blocked";
                $TransactionA->save();

                $TransactionB         = Transaction::where(['transaction_reference_id' => $TransactionA->transaction_reference_id, 'transaction_type_id' => Request_To])->first(); //TODO: query optimization
                $TransactionB->status = "Blocked";
                $TransactionB->save();

            }
            elseif ($request->type == Request_To)
            {
                $TransactionA         = Transaction::find($id); //TODO: query optimization
                $TransactionA->status = "Blocked";
                $TransactionA->save();

                $TransactionB         = Transaction::where(['transaction_reference_id' => $TransactionA->transaction_reference_id, 'transaction_type_id' => Request_From])->first(); //TODO: query optimization
                $TransactionB->status = "Blocked";
                $TransactionB->save();
            }
            $RequestPayment         = RequestPayment::find($TransactionA->transaction_reference_id); //TODO: query optimization
            $RequestPayment->status = "Blocked";
            $RequestPayment->save();
            DB::commit();

            $data = $this->sendRequestCancelNotificationToAcceptorOrCreator($RequestPayment, $request->notificationType); //TODO: query optimization
            return json_encode($data);
        }
        catch (Exception $e)
        {
            DB::rollBack();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect('dashboard');
        }
    }

    public function sendRequestCancelNotificationToAcceptorOrCreator($RequestPayment, $notificationType)
    {
        $processedBy         = $this->helper->getPrefProcessedBy();
        $emailFilterValidate = $this->helper->validateEmailInput($notificationType);
        $phoneRegex          = $this->helper->validatePhoneInput($notificationType);

        $soft_name = session('name');

        $messageFromCreatorToAcceptor = 'Your request payment #' . $RequestPayment->uuid . ' of ' . moneyFormat($RequestPayment->currency->symbol, formatNumber($RequestPayment->amount)) . ' has been cancelled by ' .
        $RequestPayment->user->first_name . ' ' . $RequestPayment->user->last_name . '.';

        //////////////////////////////////////////////////////////////////////////
        if ($emailFilterValidate && $processedBy == "email")
        {
            if (auth()->user()->id == $RequestPayment->user_id)
            {
                if (!empty($RequestPayment->receiver_id))
                {
                    //ok
                    $data = $this->onlyEmailToRegisteredRequestReceiver($messageFromCreatorToAcceptor,
                        $RequestPayment->receiver->first_name, $RequestPayment->receiver->last_name, $soft_name, $RequestPayment->receiver->email);
                    return $data;
                }
                else
                {
                    //ok
                    $data = $this->onlyEmailToUnregisteredRequestReceiver($messageFromCreatorToAcceptor, $soft_name, $RequestPayment->email);
                    return $data;
                }
            }
            elseif (!empty($RequestPayment->receiver_id) && auth()->user()->id == $RequestPayment->receiver_id)
            {
                //ok
                $messageFromAcceptorToCreator = 'Your request payment #' . $RequestPayment->uuid . ' of ' . moneyFormat($RequestPayment->currency->symbol, formatNumber($RequestPayment->amount)) .
                ' has been cancelled by ' . $RequestPayment->receiver->first_name . ' ' . $RequestPayment->receiver->last_name . '.';
                $data = $this->onlyEmailToRequestCreator($messageFromAcceptorToCreator, $RequestPayment->user->first_name, $RequestPayment->user->last_name, $soft_name, $RequestPayment->user->email);
                return $data;
            }
        }
        elseif ($phoneRegex && $processedBy == "phone")
        {
            if (auth()->user()->id == $RequestPayment->user_id)
            {
                if (!empty($RequestPayment->receiver_id))
                {
                    $data = $this->onlySmsToRegisteredRequestReceiver($messageFromCreatorToAcceptor,
                        $RequestPayment->receiver->first_name, $RequestPayment->receiver->last_name, $soft_name, $RequestPayment->receiver->carrierCode, $RequestPayment->receiver->phone);
                    return $data;
                }
                else
                {
                    $data = $this->onlySmsToUnregisteredRequestReceiver($messageFromCreatorToAcceptor, $soft_name, $RequestPayment->phone);
                    return $data;
                }
            }
            elseif (!empty($RequestPayment->receiver_id) && auth()->user()->id == $RequestPayment->receiver_id)
            {
                $messageFromAcceptorToCreator = 'Your request payment #' . $RequestPayment->uuid . ' of ' . moneyFormat($RequestPayment->currency->symbol, formatNumber($RequestPayment->amount)) .
                ' has been cancelled by ' . $RequestPayment->receiver->first_name . ' ' . $RequestPayment->receiver->last_name . '.';
                $data = $this->onlySmsToRequestCreator($messageFromAcceptorToCreator, $RequestPayment->user->first_name, $RequestPayment->user->last_name, $soft_name,
                    $RequestPayment->user->carrierCode, $RequestPayment->user->phone);
                return $data;
            }
        }
        elseif ($processedBy == "email_or_phone")
        {
            if ($emailFilterValidate)
            {
                if (auth()->user()->id == $RequestPayment->user_id)
                {
                    if (!empty($RequestPayment->receiver_id))
                    {
                        $data = $this->onlyEmailToRegisteredRequestReceiver($messageFromCreatorToAcceptor,
                            $RequestPayment->receiver->first_name, $RequestPayment->receiver->last_name, $soft_name, $RequestPayment->receiver->email);
                        return $data;
                    }
                    else
                    {
                        $data = $this->onlyEmailToUnregisteredRequestReceiver($messageFromCreatorToAcceptor, $soft_name, $RequestPayment->email);
                        return $data;
                    }
                }
                elseif (!empty($RequestPayment->receiver_id) && auth()->user()->id == $RequestPayment->receiver_id)
                {
                    $messageFromAcceptorToCreator = 'Your request payment #' . $RequestPayment->uuid . ' of ' . moneyFormat($RequestPayment->currency->symbol, formatNumber($RequestPayment->amount)) .
                    ' has been cancelled by ' . $RequestPayment->receiver->first_name . ' ' . $RequestPayment->receiver->last_name . '.';
                    $data = $this->onlyEmailToRequestCreator($messageFromAcceptorToCreator, $RequestPayment->user->first_name, $RequestPayment->user->last_name, $soft_name, $RequestPayment->user->email);
                    return $data;
                }
            }
            elseif ($phoneRegex)
            {
                if (auth()->user()->id == $RequestPayment->user_id)
                {
                    if (!empty($RequestPayment->receiver_id))
                    {
                        $data = $this->onlySmsToRegisteredRequestReceiver($messageFromCreatorToAcceptor,
                            $RequestPayment->receiver->first_name, $RequestPayment->receiver->last_name, $soft_name, $RequestPayment->receiver->carrierCode, $RequestPayment->receiver->phone);
                        return $data;
                    }
                    else
                    {
                        $data = $this->onlySmsToUnregisteredRequestReceiver($messageFromCreatorToAcceptor, $soft_name, $RequestPayment->phone);
                        return $data;
                    }
                }
                elseif (!empty($RequestPayment->receiver_id) && auth()->user()->id == $RequestPayment->receiver_id)
                {
                    $messageFromAcceptorToCreator = 'Your request payment #' . $RequestPayment->uuid . ' of ' . moneyFormat($RequestPayment->currency->symbol, formatNumber($RequestPayment->amount)) .
                    ' has been cancelled by ' . $RequestPayment->receiver->first_name . ' ' . $RequestPayment->receiver->last_name . '.';
                    $data = $this->onlySmsToRequestCreator($messageFromAcceptorToCreator, $RequestPayment->user->first_name, $RequestPayment->user->last_name, $soft_name,
                        $RequestPayment->user->carrierCode, $RequestPayment->user->phone);
                    return $data;
                }
            }
        }
        //////////////////////////////////////////////////////////////////////////
    }

    public function requestAccept($id)
    {
        //set the session for validating the action
        setActionSession();

        //check Decimal Thousand Money Format Preference
        $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);

        $data['requestPayment'] = $requestPayment = RequestPayment::with(['currency:id,symbol,code'])->where(['id' => $id])->first();
        $data['transfer_fee']   = $transfer_fee   = FeesLimit::where(['transaction_type_id' => Request_To, 'currency_id' => $requestPayment->currency_id])->first(['charge_percentage', 'charge_fixed']);
        return view('user_dashboard.requestPayment.accept', $data);
    }

    public function requestAccepted(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $rules = array(
                'amount' => 'required|numeric',
            );
            $fieldNames = array(
                'amount' => 'Amount',
            );

            // backend Validation - starts
            $request['amount']              = $request->amount;
            $request['currency_id']         = $request->currency_id;
            $request['transaction_type_id'] = Request_To;
            $amountLimitCheck               = $this->amountLimitCheck($request);
            if ($amountLimitCheck->getData()->success->status == 404 || $amountLimitCheck->getData()->success->status == 401)
            {
                return back()->withErrors(__($amountLimitCheck->getData()->success->message))->withInput();
            }
            //backend validation - ends

            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($fieldNames);
            if ($validator->fails())
            {
                return back()->withErrors($validator)->withInput();
            }
            else
            {
                $amount                   = $request->amount;
                $currency_id              = $request->currency_id;
                $data['requestPaymentId'] = $request->id;
                $request['currSymbol']    = $request->currencySymbol;
                $request['totalAmount']   = $request->amount + $request->fee;
                session(['transInfo' => $request->all()]); //needed for requestAcceptedConfirm
                $data['transInfo'] = $request->all();
            }
            return view('user_dashboard.requestPayment.acceptconfirmation', $data);
        }
    }

    //Amount Limit Check
    public function amountLimitCheck(Request $request)
    {
        $amount      = $request->amount;
        $currency_id = $request->currency_id;
        $user_id     = auth()->user()->id;

        $RequestAcceptorWallet = Wallet::where(['user_id' => $user_id, 'currency_id' => $currency_id])->first(['id']);
        if (empty($RequestAcceptorWallet))
        {
            $success['status']  = 404;
            $success['message'] = __("You don't have the requested currency!");
            return response()->json(['success' => $success]);
        }

        $wallet              = Wallet::where(['currency_id' => $currency_id, 'user_id' => $user_id])->first(['balance']);
        $feesDetails         = FeesLimit::where(['transaction_type_id' => $request->transaction_type_id, 'currency_id' => $currency_id])->first(['charge_fixed', 'charge_percentage', 'min_limit', 'max_limit']);
        $feesPercentage      = $amount * ($feesDetails->charge_percentage / 100);
        $checkAmountWithFees = $amount + $feesDetails->charge_fixed + $feesPercentage;
        if (@$wallet)
        {
            if ((@$checkAmountWithFees) > (@$wallet->balance) || (@$wallet->balance < 0))
            {
                $success['message'] = __("Not have enough balance !");
                $success['status']  = '401';
                return response()->json(['success' => $success]);
            }
        }

        //Code for Amount Limit starts here
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
            $success['pFeesHtml']      = formatNumber($feesPercentage);
            $success['fFeesHtml']      = formatNumber($feesFixed);
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
            $success['pFeesHtml']      = formatNumber($feesDetails->charge_percentage);
            $success['fFeesHtml']      = formatNumber($feesDetails->charge_fixed);
            $success['min']            = $feesDetails->min_limit;
            $success['max']            = $feesDetails->max_limit;
            $success['balance']        = isset($wallet) ? $wallet->balance : 0.00;
        }
        //Code for Fees Limit Ends here
        return response()->json(['success' => $success]);
    }

    public function requestAcceptedConfirm()
    {
        $sessionValue = session('transInfo');
        if (empty($sessionValue))
        {
            return redirect("dashboard");
        }
        actionSessionCheck();

        $requestPaymentId    = $sessionValue['id'];
        $user_id             = auth()->user()->id;
        $processedBy         = $this->helper->getPrefProcessedBy();
        $emailFilterValidate = $this->helper->validateEmailInput($sessionValue['emailOrPhone']);
        $phoneRegex          = $this->helper->validatePhoneInput($sessionValue['emailOrPhone']);
        $feesLimit           = $this->helper->getFeesLimitObject([], Request_To, $sessionValue['currency_id'], null, null, ['charge_percentage']);

        $arr = [
            'unauthorisedStatus'  => null,
            'emailFilterValidate' => $emailFilterValidate,
            'phoneRegex'          => $phoneRegex,
            'processedBy'         => $processedBy,
            'requestPaymentId'    => $requestPaymentId,
            'currency_id'         => $sessionValue['currency_id'],
            'user_id'             => $user_id,
            'accept_amount'       => $sessionValue['amount'],
            'charge_percentage'   => $feesLimit->charge_percentage,
            'percentage_fee'      => $sessionValue['percentage_fee'],
            'fixed_fee'           => $sessionValue['fixed_fee'],
            'fee'                 => $sessionValue['fee'],
            'total'               => $sessionValue['totalAmount'],
        ];
        $data['transInfo']['currSymbol'] = $sessionValue['currSymbol'];
        $data['transInfo']['amount']     = $sessionValue['amount'];

        //Get response
        $response = $this->requestPayment->processRequestAcceptConfirmation($arr, 'web');
        if ($response['status'] != 200)
        {
            if (empty($response['reqPayment']))
            {
                session()->forget('transInfo');
                $this->helper->one_time_message('error', $response['ex']['message']);
                return redirect("request_payment/accept/$requestPaymentId");
            }
            $data['errorMessage'] = $response['ex']['message'];
        }

        $data['requestCreator']['picture']    = $response['reqPayment']['requestPaymentObj']['user']->picture;
        $data['requestCreator']['first_name'] = $response['reqPayment']['requestPaymentObj']['user']->first_name;
        $data['requestCreator']['last_name']  = $response['reqPayment']['requestPaymentObj']['user']->last_name;
        $data['transInfo']['trans_id']        = $response['reqPayment']['transaction_id'];

        session()->forget('transInfo');
        clearActionSession();
        return view('user_dashboard.requestPayment.acceptsuccess', $data);
    }

    /**
     * Generate pdf for print
     */
    public function printPdf($trans_id)
    {
        $data['companyInfo']        = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);
        $data['transactionDetails'] = $transactionDetails = Transaction::with(['end_user:id,first_name,last_name', 'currency:id,symbol,code'])
            ->where(['id' => $trans_id])
            ->first(['transaction_type_id', 'end_user_id', 'currency_id', 'uuid', 'created_at', 'status', 'subtotal', 'charge_percentage', 'charge_fixed', 'total', 'note', 'user_type', 'email']);

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
        $mpdf->WriteHTML(view('user_dashboard.requestPayment.requestPaymentPrintPdf', $data));
        $mpdf->Output('requestPayment_' . time() . '.pdf', 'I'); // this will output data
    }

    //Extended functions - starts
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

    // Email to registered receiver
    public function onlyEmailToRegisteredRequestReceiver($messageFromAcceptorToCreator, $requestPaymentFirstName, $requestPaymentLastName, $softName, $requestPaymentEmail)
    {
        // Mail to request creator when a request is cancelled (both sides)
        $subject = 'Cancellation of Request Payment';
        $message = 'Hi ' . $requestPaymentFirstName . ' ' . $requestPaymentLastName . ',<br><br>'; //
        $message .= $messageFromAcceptorToCreator;
        $message .= '<br><br>';
        $message .= 'If you have any questions, please feel free to reply to this mail';
        $message .= '<br><br>';
        $message .= 'Regards,';
        $message .= '<br>';
        $message .= $softName;
        try {
            $this->email->sendEmail($requestPaymentEmail, $subject, $message);
            $data['status'] = 'Cancelled';
            return $data['status'];
        }
        catch (Exception $e)
        {
            DB::rollBack();
        }
    }

    // Email to unregistered receiver
    public function onlyEmailToUnregisteredRequestReceiver($messageFromCreatorToAcceptor, $softName, $requestPaymentEmail)
    {
        // Mail to request creator when a request is cancelled (both sides)
        $subject = 'Cancellation of Request Payment';
        $message = 'Hi ' . $requestPaymentEmail . ',<br><br>'; //
        $message .= $messageFromCreatorToAcceptor;
        $message .= '<br><br>';
        $message .= 'If you have any questions, please feel free to reply to this mail';
        $message .= '<br><br>';
        $message .= 'Regards,';
        $message .= '<br>';
        $message .= $softName;
        try {
            $this->email->sendEmail($requestPaymentEmail, $subject, $message);
            $data['status'] = 'Cancelled';
            return $data['status'];
        }
        catch (Exception $e)
        {
            DB::rollBack();
        }
    }

    // Email to registered creator
    public function onlyEmailToRequestCreator($messageFromAcceptorToCreator, $requestPaymentFirstName, $requestPaymentLastName, $softName, $requestPaymentEmail)
    {
        // Mail to request creator when a request is cancelled (both sides)
        $subject = 'Cancellation of Request Payment';
        $message = 'Hi ' . $requestPaymentFirstName . ' ' . $requestPaymentLastName . ',<br><br>'; //
        $message .= $messageFromAcceptorToCreator;
        $message .= '<br><br>';
        $message .= 'If you have any questions, please feel free to reply to this mail';
        $message .= '<br><br>';
        $message .= 'Regards,';
        $message .= '<br>';
        $message .= $softName;
        try {

            $this->email->sendEmail($requestPaymentEmail, $subject, $message);
            $data['status'] = 'Cancelled';
            return $data['status'];
        }
        catch (Exception $e)
        {
            DB::rollBack();
        }
    }

    // Sms to registered receiver
    public function onlySmsToRegisteredRequestReceiver($messageFromCreatorToAcceptor, $requestPaymentFirstName, $requestPaymentLastName, $softName, $RequestPaymentUserCarrierCode,
        $RequestPaymentUserPhone)
    {
        if (!empty($RequestPaymentUserCarrierCode) && !empty($RequestPaymentUserPhone))
        {
            if (checkAppSmsEnvironment())
            {
                try {
                    // Mail to request creator when a request is cancelled (both sides)
                    $message = 'Hi ' . $requestPaymentFirstName . ' ' . $requestPaymentLastName . ',<br><br>';
                    $message .= $messageFromCreatorToAcceptor;
                    // $message .= '<br><br>';
                    // $message .= 'Regards,';
                    // $message .= '<br>';
                    // $message .= $softName;
                    sendSMS($RequestPaymentUserCarrierCode . $RequestPaymentUserPhone, $message);
                    $data['status'] = 'Cancelled';
                    return $data['status'];
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                }
            }
        }
    }

    // Sms to unregistered receiver
    public function onlySmsToUnregisteredRequestReceiver($messageFromCreatorToAcceptor, $softName, $RequestPaymentUserPhone)
    {
        if (!empty($RequestPaymentUserPhone))
        {
            if (checkAppSmsEnvironment())
            {
                try {
                    // Mail to request creator when a request is cancelled (both sides)
                    $message = 'Hi ' . $RequestPaymentUserPhone . ',<br><br>';
                    $message .= $messageFromCreatorToAcceptor;
                    // $message .= '<br><br>';
                    // $message .= 'Regards,';
                    // $message .= '<br>';
                    // $message .= $softName;
                    sendSMS($RequestPaymentUserPhone, $message);
                    $data['status'] = 'Cancelled';
                    return $data['status'];
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                }
            }
        }
    }

    // Sms to registered creator
    public function onlySmsToRequestCreator($messageFromAcceptorToCreator, $requestPaymentFirstName, $requestPaymentLastName, $softName, $RequestPaymentUserCarrierCode,
        $RequestPaymentUserPhone)
    {
        if (!empty($RequestPaymentUserCarrierCode) && !empty($RequestPaymentUserPhone))
        {
            if (checkAppSmsEnvironment())
            {
                try {
                    // Mail to request creator when a request is cancelled (both sides)
                    $message = 'Hi ' . $requestPaymentFirstName . ' ' . $requestPaymentLastName . ',<br><br>';
                    $message .= $messageFromAcceptorToCreator;
                    // $message .= '<br><br>';
                    // $message .= 'Regards,';
                    // $message .= '<br>';
                    // $message .= $softName;
                    sendSMS($RequestPaymentUserCarrierCode . $RequestPaymentUserPhone, $message);
                    $data['status'] = 'Cancelled';
                    return $data['status'];
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                }
            }
        }
    }

    //Check Request Creator Status (for dashboard and transactions list - user panel)
    public function checkReqCreatorStatus(Request $request)
    {
        try
        {
            $transaction = Transaction::with(['end_user:id,status'])->find($request->trans_id, ['id', 'end_user_id']);
            return response()->json([
                'status' => $transaction->end_user->status,
            ]);
        }
        catch (Exception $e)
        {
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect('dashboard');
        }
    }
}
