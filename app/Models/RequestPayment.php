<?php

namespace App\Models;

use App\Http\Controllers\Users\EmailController;
use App\Http\Helpers\Common;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class RequestPayment extends Model
{
    protected $table    = 'request_payments';
    protected $fillable = [
        'user_id',
        'receiver_id',
        'currency_id',
        'uuid',
        'amount',
        'accept_amount',
        'email',
        'phone',
        'purpose',
        'note',
        'status',
    ];

    // public $timestamps = true;

    //
    protected $helper;
    protected $emailObject;
    public function __construct()
    {
        $this->helper = new Common();
        $this->emailObject  = new EmailController();
    }
    //

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'transaction_reference_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * [get users firstname and lastname for filtering]
     * @param  [integer] $user      [id]
     * @return [string]  [firstname and lastname]
     */
    public function getRequestPaymentsUserName($user)
    {
        $getUserEndUserRequestPayments = $this->where(function ($q) use ($user)
        {
            $q->where(['user_id' => $user])->orWhere(['receiver_id' => $user]);
        })
        ->with(['user:id,first_name,last_name','receiver:id,first_name,last_name'])
        ->first(['user_id','receiver_id']);

        if (!empty($getUserEndUserRequestPayments))
        {
             if ($getUserEndUserRequestPayments->user_id == $user)
            {
                return $getUserEndUserRequestPayments->user;
            }

            if ($getUserEndUserRequestPayments->receiver_id == $user)
            {
                return $getUserEndUserRequestPayments->receiver;
            }
        }
    }

    /**
     * [ajax response for search results]
     * @param  [string] $search   [query string]
     * @return [string] [distinct firstname and lastname]
     */
    public function getRequestPaymentsUsersResponse($search)
    {
        $getRequestPaymentsUsers = $this->whereHas('user', function ($query) use ($search)
        {
            $query->where('first_name', 'LIKE', '%' . $search . '%')->orWhere('last_name', 'LIKE', '%' . $search . '%');
        })
        ->distinct('user_id')
        ->with(['user:id,first_name,last_name'])
        ->get(['user_id'])
        ->map(function ($requestPaymentA)
        {
            $arr['user_id']    = $requestPaymentA->user_id;
            $arr['first_name'] = $requestPaymentA->user->first_name;
            $arr['last_name']  = $requestPaymentA->user->last_name;
            return $arr;
        });
        //
        $getRequestPaymentsEndUsers = $this->whereHas('receiver', function ($query) use ($search)
        {
            $query->where('first_name', 'LIKE', '%' . $search . '%')->orWhere('last_name', 'LIKE', '%' . $search . '%');
        })
        ->distinct('receiver_id')
        ->with(['receiver:id,first_name,last_name'])
        ->get(['receiver_id'])
        ->map(function ($requestPaymentB)
        {
            $arr['user_id']    = $requestPaymentB->receiver_id;
            $arr['first_name'] = $requestPaymentB->receiver->first_name;
            $arr['last_name']  = $requestPaymentB->receiver->last_name;
            return $arr;
        });

        //
        if ($getRequestPaymentsUsers->isNotEmpty())
        {
            return $getRequestPaymentsUsers->unique();
        }

        if ($getRequestPaymentsEndUsers->isNotEmpty())
        {
            return $getRequestPaymentsEndUsers->unique();
        }

        if ($getRequestPaymentsUsers->isNotEmpty() && $getRequestPaymentsEndUsers->isNotEmpty())
        {
            $getUniqueRequestPaymentsUsers = ($getRequestPaymentsUsers->merge($getRequestPaymentsEndUsers))->unique();
            return $getUniqueRequestPaymentsUsers;
        }
        //
    }

    /**
     * [Exchanges Filtering Results]
     * @param  [null/date] $from   [start date]
     * @param  [null/date] $to     [end date]
     * @param  [string]    $status [Status]
     * @param  [null/id]   $user   [User ID]
     * @return [query]     [All Query Results]
     */

    public function getRequestPaymentsList($from, $to, $status, $currency, $user)
    {
        $conditions = [];

        //start date conditions
        if (empty($from) || empty($to))
        {
            $date_range = null;
        }
        else if (empty($from))
        {
            $date_range = null;
        }
        else if (empty($to))
        {
            $date_range = null;
        }
        else
        {
            $date_range = 'Available';
        }
        //end date conditions

        if (!empty($status) && $status != 'all')
        {
            $conditions['request_payments.status'] = $status;
        }
        if (!empty($currency) && $currency != 'all')
        {
            $conditions['request_payments.currency_id'] = $currency;
        }
        if (!empty($type) && $type != 'all')
        {
            $conditions['request_payments.type'] = $type;
        }
        //
        $request_payments = $this->with([
            'user:id,first_name,last_name',
            'receiver:id,first_name,last_name',
            'currency:id,code',
        ])->where($conditions);

        //if user is not empty, check both user_id & receiver_id columns
        if (!empty($user))
        {
            $request_payments->where(function ($q) use ($user)
            {
                $q->where(['request_payments.user_id' => $user])->orWhere(['request_payments.receiver_id' => $user]);
            });
        }
        //

        if (!empty($date_range))
        {
            $request_payments->where(function ($query) use ($from, $to)
            {
                $query->whereDate('request_payments.created_at', '>=', $from)->whereDate('request_payments.created_at', '<=', $to);
            })
            ->select('request_payments.*');
        }
        else
        {
            $request_payments->select('request_payments.*');
        }
        //
        return $request_payments;
    }

    //common functions - starts
    public function createRequestPayment($arr)
    {
        $requestPayment              = new self();
        $requestPayment->user_id     = $arr['user_id'];
        $requestPayment->receiver_id = isset($arr['userInfo']) ? $arr['userInfo']->id : null;
        $requestPayment->currency_id = $arr['currency_id'];
        $requestPayment->uuid        = $arr['uuid'];
        $requestPayment->amount      = $arr['amount'];
        if ($arr['emailFilterValidate'])
        {
            $requestPayment->email = $arr['receiver'];
        }
        elseif ($arr['phoneRegex'])
        {
            $requestPayment->phone = $arr['receiver'];
        }
        $requestPayment->status = 'Pending';
        $requestPayment->note = $arr['note'];
        $requestPayment->save();
        return $requestPayment;
    }

    public function createRequestFromTransaction($arr)
    {
        $transaction                           = new Transaction();
        $transaction->user_id                  = $arr['user_id'];
        $transaction->currency_id              = $arr['currency_id'];
        $transaction->uuid                     = $arr['uuid'];
        $transaction->transaction_reference_id = $arr['transaction_reference_id'];
        $transaction->transaction_type_id      = Request_From;
        if (!empty($arr['userInfo']))
        {
            $transaction->end_user_id = $arr['userInfo']->id;
            $transaction->user_type   = 'registered';
        }
        else
        {
            $transaction->user_type = 'unregistered';
        }
        if ($arr['emailFilterValidate'])
        {
            $transaction->email = $arr['receiver'];
        }
        elseif ($arr['phoneRegex'])
        {
            $transaction->phone = $arr['receiver'];
        }
        $transaction->subtotal = $arr['amount'];
        $transaction->total    = $arr['amount'];
        $transaction->note     = $arr['note'];
        $transaction->status   = $arr['status'];
        $transaction->save();
        return $transaction->id;
    }

    public function createRequestToTransaction($arr)
    {
        $transaction                           = new Transaction();
        $transaction->user_id                  = isset($arr['userInfo']) ? $arr['userInfo']->id : null;
        $transaction->end_user_id              = $arr['user_id'];
        $transaction->currency_id              = $arr['currency_id'];
        $transaction->uuid                     = $arr['uuid'];
        $transaction->transaction_reference_id = $arr['transaction_reference_id'];
        $transaction->transaction_type_id      = Request_To;
        if (!empty($arr['userInfo']))
        {
            $transaction->user_type = 'registered';
        }
        else
        {
            $transaction->user_type = 'unregistered';
        }
        if ($arr['emailFilterValidate'])
        {
            $transaction->email = $arr['receiver'];
        }
        elseif ($arr['phoneRegex'])
        {
            $transaction->phone = $arr['receiver'];
        }
        $transaction->subtotal = $arr['amount'];
        $transaction->total    = '-' . $arr['amount'];
        $transaction->note     = $arr['note'];
        $transaction->status   = $arr['status'];
        $transaction->save();
    }

    public function createRequestCreatorWallet($user_id, $currency_id)
    {
        $createWalletIfNotExist = Wallet::where(['user_id' => $user_id, 'currency_id' => $currency_id])->first(['id']);
        if (empty($createWalletIfNotExist))
        {
            $wallet              = new Wallet();
            $wallet->user_id     = $user_id;
            $wallet->currency_id = $currency_id;
            $wallet->balance     = 0.00;
            $wallet->is_default  = 'No';
            $wallet->save();
        }
    }

    public function mailToRegisteredRequestAcceptor($requestPayment, $arr)
    {
        /**
         * Mail when request created
         */
        if (checkAppMailEnvironment())
        {
            $englishSenderLanginfo = $this->helper->getEmailOrSmsTemplate(4, 'email');
            $req_create_temp       = $this->helper->getEmailOrSmsTemplate(4, 'email', getDefaultLanguage());

            if (!empty($req_create_temp->subject) && !empty($req_create_temp->body))
            {
                $req_create_sub = $req_create_temp->subject;
                $req_create_msg = str_replace('{acceptor}', $arr['receiverName'], $req_create_temp->body);
            }
            else
            {
                $req_create_sub = $englishSenderLanginfo->subject;
                $req_create_msg = str_replace('{acceptor}', $arr['receiverName'], $englishSenderLanginfo->body);
            }
            $req_create_msg = str_replace('{creator}', $requestPayment->user->first_name . ' ' . $requestPayment->user->last_name, $req_create_msg);
            $req_create_msg = str_replace('{amount}', moneyFormat($requestPayment->currency->symbol, formatNumber($arr['amount'])), $req_create_msg);
            $req_create_msg = str_replace('{uuid}', $arr['uuid'], $req_create_msg);
            $req_create_msg = str_replace('{created_at}', $this->helper->getCurrentDateTime(), $req_create_msg);
            $req_create_msg = str_replace('{note}', $arr['note'], $req_create_msg);
            $req_create_msg = str_replace('{soft_name}', getCompanyName(), $req_create_msg);
            try {
                $this->emailObject->sendEmail($arr['receiver'], $req_create_sub, $req_create_msg);
                return [
                    'ex' => null,
                ];
            }
            catch (Exception $e)
            {
                return [
                    'ex' => $e,
                ];
            }
        }
    }

    public function mailToUnRegisteredRequestAcceptor($requestPayment, $arr)
    {
        /**
         * Mail to unregistered user when request created
         */
        if (checkAppMailEnvironment())
        {
            $email_explode                 = explode("@", trim($arr['receiver']));
            $unregisteredUserNameFromEmail = $email_explode[0];
            $profileName                   = getCompanyName();
            $subject                       = 'Notice of Request Creation!';
            $message                       = 'Hi ' . $unregisteredUserNameFromEmail . ',<br><br>';
            $message .= 'You have got ' . moneyFormat($requestPayment->currency->symbol, formatNumber($arr['amount'])) . ' payment request from ' . $arr['senderEmail'] . '.<br>';
            $message .= 'To accept the request, please register on : ' . url('/register') . ' with current email.<br><br>';
            $message .= 'Regards, <br>';
            $message .= $profileName;

            try {
                $this->emailObject->sendEmail($arr['receiver'], $subject, $message);
                return [
                    'ex' => null,
                ];
            }
            catch (Exception $e)
            {
                return [
                    'ex' => $e,
                ];
            }
        }
    }

    public function smsToRequestAcceptor($requestPayment, $arr)
    {
        if (checkAppSmsEnvironment())
        {
            if (isset($arr['userInfo']))
            {
                //sms To registered request acceptor
                $enRpSmsTempInfo = $this->helper->getEmailOrSmsTemplate(4, 'sms');
                $reqSmsTempInfo  = $this->helper->getEmailOrSmsTemplate(4, 'sms', getDefaultLanguage());
                if (!empty($reqSmsTempInfo->subject) && !empty($reqSmsTempInfo->body))
                {
                    $reqSmsTempInfo_sub = $reqSmsTempInfo->subject;
                    $reqSmsTempInfo_msg = str_replace('{acceptor}', $arr['receiverName'], $reqSmsTempInfo->body);
                }
                else
                {
                    $reqSmsTempInfo_sub = $enRpSmsTempInfo->subject;
                    $reqSmsTempInfo_msg = str_replace('{acceptor}', $arr['receiverName'], $enRpSmsTempInfo->body);
                }
                $reqSmsTempInfo_msg = str_replace('{amount}', moneyFormat($requestPayment->currency->symbol, formatNumber($arr['amount'])), $reqSmsTempInfo_msg);
                $reqSmsTempInfo_msg = str_replace('{creator}', $requestPayment->user->first_name . ' ' . $requestPayment->user->last_name, $reqSmsTempInfo_msg);

                if (!empty($arr['userInfo']->carrierCode) && !empty($arr['userInfo']->phone))
                {
                    try {
                        sendSMS($arr['userInfo']->carrierCode . $arr['userInfo']->phone, $reqSmsTempInfo_msg);
                        return [
                            'ex' => null,
                        ];
                    }
                    catch (Exception $e)
                    {
                        return [
                            'ex' => $e,
                        ];
                    }
                }
            }
            else
            {
                //sms To Unregistered request acceptor
                $message = 'Amount ' . moneyFormat($requestPayment->currency->symbol, formatNumber($arr['amount'])) . ' has been requested by ' . $requestPayment->user->first_name . ' ' . $requestPayment->user->last_name. '. <br><br>';
                try
                {
                    sendSMS($requestPayment->phone, $message);
                    return [
                        'ex' => null,
                    ];
                }
                catch (Exception $e)
                {
                    return [
                        'ex' => $e,
                    ];
                }
            }
        }
    }

    public function sendReqCreateEmailINotifnIfUserExists($requestPayment, $arr)
    {
        $data = [];
        if (isset($arr['userInfo']))
        {
            $mailToRegistUser = self::mailToRegisteredRequestAcceptor($requestPayment, $arr);
            if (!empty($mailToRegistUser['ex']))
            {
                $data = [
                    'exFrom' => 'mailToRegistUser',
                    'ex'     => $mailToRegistUser['ex'],
                ];
            }
        }
        else
        {
            $mailToUnRegistUser = self::mailToUnRegisteredRequestAcceptor($requestPayment, $arr);
            if (!empty($mailToUnRegistUser['ex']))
            {
                $data = [
                    'exFrom' => 'mailToUnRegistUser',
                    'ex'     => $mailToUnRegistUser['ex'],
                ];
            }
        }
        return $data;
    }

    public function sendReqCreateSmsNotificationToReqAcceptor($requestPayment, $arr)
    {
        $data            = [];
        $smsToRegistUser = self::smsToRequestAcceptor($requestPayment, $arr);
        if (!empty($smsToRegistUser['ex']))
        {
            $data = [
                'exFrom' => 'smsToRegistUser',
                'ex'     => $smsToRegistUser['ex'],
            ];
        }
        return $data;
    }

    /**
     * Rquest Create Money Confirm
     * param  array  $arr
     * param  string $clearSessionFrom
     * return object
     */
    public function processRequestCreateConfirmation($arr = [], $clearSessionFrom)
    {
        $response = ['status' => 401];
        try {
            DB::beginTransaction();

            //Create Request Payment
            $requestPayment = self::createRequestPayment($arr);

            //Create Request From Transaction
            $arr['transaction_reference_id'] = $requestPayment->id;
            $arr['status']                   = $requestPayment->status;

            $requestFromTransactionId = self::createRequestFromTransaction($arr);

            //Create RequestTo Transaction
            self::createRequestToTransaction($arr);

            //Create Request Creator Wallet - If it does not exist
            self::createRequestCreatorWallet($arr['user_id'], $arr['currency_id']);

            DB::commit();

            $transactionOrReqPaymentId             = ($clearSessionFrom == 'web') ? $requestFromTransactionId : $requestPayment->id;
            $response['transactionOrReqPaymentId'] = $transactionOrReqPaymentId;

            // Mail & SMS to registered request acceptor
            if ($arr['emailFilterValidate'] && $arr['processedBy'] == "email")
            {
                $data = self::sendReqCreateEmailINotifnIfUserExists($requestPayment, $arr);
                if (!empty($data))
                {
                    $response['ex']['source']  = $data['exFrom'];
                    $response['ex']['message'] = $data['ex']->getMessage();
                    return $response;
                }
            }
            elseif ($arr['phoneRegex'] && $arr['processedBy'] == "phone")
            {
                $data = self::sendReqCreateSmsNotificationToReqAcceptor($requestPayment, $arr);
                if (!empty($data))
                {
                    $response['ex']['source']  = $data['exFrom'];
                    $response['ex']['message'] = $data['ex']->getMessage();
                    return $response;
                }
            }
            elseif ($arr['processedBy'] == "email_or_phone")
            {
                if ($arr['emailFilterValidate'])
                {
                    $data = self::sendReqCreateEmailINotifnIfUserExists($requestPayment, $arr);
                    if (!empty($data))
                    {
                        $response['ex']['source']  = $data['exFrom'];
                        $response['ex']['message'] = $data['ex']->getMessage();
                        return $response;
                    }
                }
                elseif ($arr['phoneRegex'])
                {
                    $data = self::sendReqCreateSmsNotificationToReqAcceptor($requestPayment, $arr);
                    if (!empty($data))
                    {
                        $response['ex']['source']  = $data['exFrom'];
                        $response['ex']['message'] = $data['ex']->getMessage();
                        return $response;
                    }
                }
            }
            $response['status'] = 200;
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            if ($clearSessionFrom == 'web') {
                $this->helper->clearSessionWithRedirect('transInfo', $e, 'request_payment/add');
            }
            $response['transactionOrReqPaymentId'] = null;
            $response['ex']['message']             = $e->getMessage();
            return $response;
        }
    }

    public function updateRequestPayment($arr)
    {
        $requestPayment                = $this->with(['user:id,first_name,last_name,phone,carrierCode,email', 'receiver:id,first_name,last_name', 'currency:id,symbol,code'])->find($arr['requestPaymentId']);
        $requestPayment->accept_amount = $arr['accept_amount'];
        $requestPayment->status        = "Success";
        $requestPayment->save();
        return $requestPayment;
    }

    public function udpateRequestCreatorTransaction($arr, $requestPayment)
    {
        //Update Request Creator Transaction Information
        $transaction                    = Transaction::where(['user_id' => $requestPayment->user_id, 'currency_id' => $arr['currency_id'], 'transaction_reference_id' => $arr['requestPaymentId'], 'transaction_type_id' => Request_From])->first(['id', 'percentage', 'charge_percentage', 'charge_percentage', 'subtotal', 'total', 'status']);
        $transaction->percentage        = 0;
        $transaction->charge_percentage = 0;
        $transaction->charge_fixed      = 0;
        $transaction->subtotal          = $arr['accept_amount'];
        $transaction->total             = $arr['accept_amount'];
        $transaction->status            = 'Success';
        $transaction->save();
    }

    public function udpateRequestAcceptorTransaction($arr, $requestPayment)
    {

        $transaction = Transaction::where(['user_id' => $requestPayment->receiver_id, 'currency_id' => $arr['currency_id'], 'transaction_reference_id' => $arr['requestPaymentId'], 'transaction_type_id' => Request_To])->first(['id', 'percentage', 'charge_percentage', 'charge_percentage', 'subtotal', 'total', 'status']);

        $transaction->percentage        = @$arr['charge_percentage'] ? @$arr['charge_percentage'] : 0;
        $transaction->charge_percentage = $arr['percentage_fee'];
        $transaction->charge_fixed      = $arr['fixed_fee'];
        $transaction->subtotal          = $arr['accept_amount'];
        $t_total                        = $transaction->subtotal + ($transaction->charge_percentage + $transaction->charge_fixed);
        $transaction->total             = '-' . $t_total;
        $transaction->status            = 'Success';
        $transaction->save();
        return $transaction->id;
    }

    public function updateRequestCreatorWallet($arr, $requestPayment)
    {
        $requestCreatorWallet = Wallet::where(['user_id' => $requestPayment->user_id, 'currency_id' => $arr['currency_id']])->first(['id', 'balance']);
        if (!empty($requestCreatorWallet))
        {
            $requestCreatorWallet->balance = $requestCreatorWallet->balance + $arr['accept_amount'];
            $requestCreatorWallet->save();
        }
        else
        {
            $requestCreatorWallet              = new Wallet();
            $requestCreatorWallet->balance     = $arr['accept_amount'];
            $requestCreatorWallet->user_id     = $requestPayment->user_id;
            $requestCreatorWallet->currency_id = $arr['currency_id'];
            $requestCreatorWallet->is_default  = 'No';
            $requestCreatorWallet->save();
        }
    }

    public function updateRequestAcceptorWallet($arr)
    {
        $requestAcceptorWallet          = Wallet::where(['user_id' => $arr['user_id'], 'currency_id' => $arr['currency_id']])->first(['id', 'balance']);
        $requestAcceptorWallet->balance = $requestAcceptorWallet->balance - ($arr['accept_amount'] + $arr['fee']);
        $requestAcceptorWallet->save();
    }

    //SMS
    public function onlySmsToRequestCreatorOnRequestAccept($arr, $requestPayment)
    {
        if (checkAppSmsEnvironment())
        {
            if (!empty($requestPayment->user->carrierCode) && !empty($requestPayment->user->phone))
            {
                $enRpAcceptSmsTempInfo       = $this->helper->getEmailOrSmsTemplate(5, 'sms');
                $reqPaymentAcceptSmsTempInfo = $this->helper->getEmailOrSmsTemplate(5, 'sms', getDefaultLanguage());
                if (!empty($reqPaymentAcceptSmsTempInfo->subject) && !empty($reqPaymentAcceptSmsTempInfo->body))
                {
                    $reqPaymentAcceptSmsTempInfo_sub = $reqPaymentAcceptSmsTempInfo->subject;
                    $reqPaymentAcceptSmsTempInfo_msg = str_replace('{creator}', $requestPayment->user->first_name . ' ' . $requestPayment->user->last_name, $reqPaymentAcceptSmsTempInfo->body);
                }
                else
                {
                    $reqPaymentAcceptSmsTempInfo_sub = $enRpAcceptSmsTempInfo->subject;
                    $reqPaymentAcceptSmsTempInfo_msg = str_replace('{creator}', $requestPayment->user->first_name . ' ' . $requestPayment->user->last_name, $enRpAcceptSmsTempInfo->body);
                }
                $reqPaymentAcceptSmsTempInfo_msg = str_replace('{uuid}', $requestPayment->uuid, $reqPaymentAcceptSmsTempInfo_msg);
                $reqPaymentAcceptSmsTempInfo_msg = str_replace('{amount}', moneyFormat($requestPayment->currency->symbol, formatNumber($requestPayment->amount)), $reqPaymentAcceptSmsTempInfo_msg);
                $reqPaymentAcceptSmsTempInfo_msg = str_replace('{acceptor}', $requestPayment->receiver->first_name . ' ' . $requestPayment->receiver->last_name, $reqPaymentAcceptSmsTempInfo_msg);

                try {
                    sendSMS($requestPayment->user->carrierCode . $requestPayment->user->phone, $reqPaymentAcceptSmsTempInfo_msg);
                    return [
                        'ex' => null,
                    ];
                }
                catch (Exception $e)
                {
                    return [
                        'ex' => $e,
                    ];
                }
            }
        }
        //
    }

    //Email
    public function onlyEmailToRequestCreatorOnRequestAccept($arr, $requestPayment)
    {
        if (checkAppMailEnvironment())
        {
            //if other language's subject and body not set, get en sub and body for mail
            $englishSenderLanginfo = $this->helper->getEmailOrSmsTemplate(5, 'email');
            $rp_accept_temp        = $this->helper->getEmailOrSmsTemplate(5, 'email', getDefaultLanguage());
            if (!empty($rp_accept_temp->subject) && !empty($rp_accept_temp->body))
            {
                $rp_acc_sub = $rp_accept_temp->subject;
                $rp_msg     = str_replace('{creator}', $requestPayment->user->first_name . ' ' . $requestPayment->user->last_name, $rp_accept_temp->body);
            }
            else
            {
                $rp_acc_sub = $englishSenderLanginfo->subject;
                $rp_msg     = str_replace('{creator}', $requestPayment->user->first_name . ' ' . $requestPayment->user->last_name, $englishSenderLanginfo->body);
            }
            $rp_msg = str_replace('{uuid}', $requestPayment->uuid, $rp_msg);
            $rp_msg = str_replace('{acceptor}', $requestPayment->receiver->first_name . ' ' . $requestPayment->receiver->last_name, $rp_msg);
            $rp_msg = str_replace('{created_at}', $this->helper->getCurrentDateTime(), $rp_msg);
            $rp_msg = str_replace('{amount}', moneyFormat($requestPayment->currency->symbol, formatNumber($requestPayment->amount)), $rp_msg);
            $rp_msg = str_replace('{accept_amount}', moneyFormat($requestPayment->currency->symbol, formatNumber($requestPayment->accept_amount)), $rp_msg);
            $rp_msg = str_replace('{currency}', $requestPayment->currency->code, $rp_msg);
            $rp_msg = str_replace('{soft_name}', getCompanyName(), $rp_msg);

            try {
                $this->emailObject->sendEmail($requestPayment->user->email, $rp_acc_sub, $rp_msg);
                return [
                    'ex' => null,
                ];
            }
            catch (Exception $e)
            {
                return [
                    'ex' => $e,
                ];
            }
        }
    }

    /**
     * Process Request Accept Confirm
     * param  array  $arr
     * param  string $clearSessionFrom
     * return object
     */
    public function processRequestAcceptConfirmation($arr = [], $clearSessionFrom)
    {
        $response = ['status' => 401];

        try
        {
            //Backend Validation - Wallet Balance Again Amount Check (checked by giving hard-coded value - OK) - Starts here
            $checkWalletBalance = $this->helper->checkWalletBalanceAgainstAmount($arr['total'], $arr['currency_id'], $arr['user_id']);
            if ($checkWalletBalance == true)
            {
                $response['reqPayment'] = null;
                if ($clearSessionFrom == 'web')
                {
                    $response['ex']['message'] = __("Not have enough balance !");
                    return $response;
                }
                $response['ex']['message'] = "Sorry, not enough funds to perform the operation!";
                return $response;
                //Backend Validation - Wallet Balance Again Amount Check - Ends here
            }
            else
            {
                DB::beginTransaction();

                //Create Transfer
                $requestPayment = self::updateRequestPayment($arr);

                //Update Request Creator Transaction
                self::udpateRequestCreatorTransaction($arr, $requestPayment);

                //Update Request Acceptor Transaction
                $reqAcceptTransactionId = self::udpateRequestAcceptorTransaction($arr, $requestPayment);

                //Update Request Creator Wallet
                self::updateRequestCreatorWallet($arr, $requestPayment);

                //Update Request Acceptor Wallet
                self::updateRequestAcceptorWallet($arr);

                DB::commit();

                $resArray = [];
                $resArray = [
                    'transaction_id'    => $reqAcceptTransactionId,
                    'requestPaymentObj' => $requestPayment,
                ];

                $requestPaymentData     = ($clearSessionFrom == 'web') ? $resArray : $requestPayment->id;
                $response['reqPayment'] = $requestPaymentData;

                ///////////////////////////MAIL AND SMS - starts//////////////////////////////////
                if ($arr['emailFilterValidate'] && $arr['processedBy'] == "email")
                {
                    // Mail when request accepted
                    $mailToReqCrt = $this->onlyEmailToRequestCreatorOnRequestAccept($arr, $requestPayment);
                    if (!empty($mailToReqCrt['ex']))
                    {
                        $response['ex']['source']  = 'mailToReqCrt';
                        $response['ex']['message'] = $mailToReqCrt['ex']->getMessage();
                        return $response;
                    }
                }
                elseif ($arr['phoneRegex'] && $arr['processedBy'] == "phone")
                {
                    // SMS to Request Payment Creator
                    $smsToReqAcc = $this->onlySmsToRequestCreatorOnRequestAccept($arr, $requestPayment);
                    if (!empty($smsToReqAcc['ex']))
                    {
                        $response['ex']['source']  = 'smsToReqAcc';
                        $response['ex']['message'] = $smsToReqAcc['ex']->getMessage();
                        return $response;
                    }
                }
                elseif ($arr['processedBy'] == "email_or_phone")
                {
                    if ($arr['emailFilterValidate'])
                    {
                        // Mail when request accepted
                        $mailToReqCrt = $this->onlyEmailToRequestCreatorOnRequestAccept($arr, $requestPayment);
                        if (!empty($mailToReqCrt['ex']))
                        {
                            $response['ex']['source']  = 'mailToReqCrt';
                            $response['ex']['message'] = $mailToReqCrt['ex']->getMessage();
                            return $response;
                        }
                    }
                    elseif ($arr['phoneRegex'])
                    {
                        // SMS to Request Payment Creator
                        $smsToReqAcc = $this->onlySmsToRequestCreatorOnRequestAccept($arr, $requestPayment);
                        if (!empty($smsToReqAcc['ex']))
                        {
                            $response['ex']['source']  = 'smsToReqAcc';
                            $response['ex']['message'] = $smsToReqAcc['ex']->getMessage();
                            return $response;
                        }
                    }
                }
                //Admin Notification
                $requestPayment['charge_percentage'] = $arr['percentage_fee'];
                $requestPayment['charge_fixed']      = $arr['fixed_fee'];
                $notificationToAdmin                 = $this->helper->sendTransactionNotificationToAdmin('request', ['data' => $requestPayment]);
                if (!empty($notificationToAdmin['ex']))
                {
                    $response['ex']['source']  = $notificationToAdmin['exFrom'];
                    $response['ex']['message'] = $notificationToAdmin['ex']->getMessage();
                    return $response;
                }
                ////////////////////////////MAIL AND SMS - ends//////////////////////////////////

                $response['status'] = 200;
                return $response;
            }
        }
        catch (Exception $e)
        {
            DB::rollBack();
            if ($clearSessionFrom == 'web')
            {
                $requestPaymentId = $arr['requestPaymentId'];
                $this->helper->clearSessionWithRedirect('transInfo', $e, "request_payment/accept/$requestPaymentId");
            }
            $response['reqPayment']    = null;
            $response['ex']['message'] = $e->getMessage();
            return $response;
        }
    }
    //common functions - ends
}
