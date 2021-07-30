<?php

namespace App\Models;

use App\Http\Controllers\Users\EmailController;
use App\Http\Helpers\Common;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class Transfer extends Model
{
    protected $table = 'transfers';

    protected $fillable = ['sender_id', 'rate','receiver_id', 'currency_id', 'bank_id', 'file_id', 'uuid', 'fee', 'amount', 'note', 'receivername', 'email', 'phone', 'status'];

    //
    protected $helper;
    protected $emailObject;
    public function __construct()
    {
        $this->helper      = new Common();
        $this->emailObject = new EmailController();
    }
    //

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'transaction_reference_id', 'id');
    }

    //new
    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    //new
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * [get users firstname and lastname for filtering]
     * @param  [integer] $user      [id]
     * @return [string]  [firstname and lastname]
     */
    public function getTransfersUserName($user)
    {
        $getUserEndUserTransfer = $this->where(function ($q) use ($user)
        {
            $q->where(['sender_id' => $user])->orWhere(['receiver_id' => $user]);
        })
        ->with(['sender:id,first_name,last_name','receiver:id,first_name,last_name'])
        ->first(['sender_id','receiver_id']);

        if (!empty($getUserEndUserTransfer))
        {
             if ($getUserEndUserTransfer->sender_id == $user)
            {
                return $getUserEndUserTransfer->sender;
            }

            if ($getUserEndUserTransfer->receiver_id == $user)
            {
                return $getUserEndUserTransfer->receiver;
            }
        }
    }

    /**
     * [ajax response for search results]
     * @param  [string] $search   [query string]
     * @return [string] [distinct firstname and lastname]
     */
    public function getTransfersUsersResponse($search)
    {
        $getTransfersUsers = $this->whereHas('sender', function ($query) use ($search)
        {
            $query->where('first_name', 'LIKE', '%' . $search . '%')->orWhere('last_name', 'LIKE', '%' . $search . '%');
        })
        ->distinct('sender_id')
        ->with(['sender:id,first_name,last_name'])
        ->get(['sender_id'])
        ->map(function ($transferA)
        {
            $arr['user_id']    = $transferA->sender_id;
            $arr['first_name'] = $transferA->sender->first_name;
            $arr['last_name']  = $transferA->sender->last_name;
            return $arr;
        });
        //
        $getTransfersEndUsers = $this->whereHas('receiver', function ($query) use ($search)
        {
            $query->where('first_name', 'LIKE', '%' . $search . '%')->orWhere('last_name', 'LIKE', '%' . $search . '%');
        })
        ->distinct('receiver_id')
        ->with(['receiver:id,first_name,last_name'])
        ->get(['receiver_id'])
        ->map(function ($transferB)
        {
            $arr['user_id']    = $transferB->receiver_id;
            $arr['first_name'] = $transferB->receiver->first_name;
            $arr['last_name']  = $transferB->receiver->last_name;
            return $arr;
        });

        //
        if ($getTransfersUsers->isNotEmpty())
        {
            return $getTransfersUsers->unique();
        }

        if ($getTransfersEndUsers->isNotEmpty())
        {
            return $getTransfersEndUsers->unique();
        }

        if ($getTransfersUsers->isNotEmpty() && $getTransfersEndUsers->isNotEmpty())
        {
            $getUniqueTransfersUsers = ($getTransfersUsers->merge($getTransfersEndUsers))->unique();
            return $getUniqueTransfersUsers;
        }
        //
    }

    /**
     * [Transfers Filtering Results]
     * @param  [null/date] $from   [start date]
     * @param  [null/date] $to     [end date]
     * @param  [string]    $status [Status]
     * @param  [null/id]   $user   [User ID]
     * @return [void]      [All Query Results]
     */
    public function getTransfersList($from, $to, $status, $currency, $user)
    {
        $conditions = [];

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

        if (!empty($status) && $status != 'all')
        {
            $conditions['transfers.status'] = $status;
        }
        if (!empty($currency) && $currency != 'all')
        {
            $conditions['transfers.currency_id'] = $currency;
        }

        //
        $transfers = $this->with([
            'sender:id,first_name,last_name',
            'receiver:id,first_name,last_name',
            'currency:id,code',
        ])->where($conditions);

        //if user is not empty, check both sender_id & receiver_id columns
        if (!empty($user))
        {
            $transfers->where(function ($q) use ($user)
            {
                $q->where(['transfers.sender_id' => $user])->orWhere(['transfers.receiver_id' => $user]);
            });
        }
        //

        if (!empty($date_range))
        {
            $transfers->where(function ($query) use ($from, $to)
            {
                $query->whereDate('transfers.created_at', '>=', $from)->whereDate('transfers.created_at', '<=', $to);
            })
                ->select('transfers.*');
        }
        else
        {
            $transfers->select('transfers.*');
        }
        //
        return $transfers;
    }

    //common functions - starts
    public function createTransfer($arr)
    {
        $transfer              = new self();
        $transfer->sender_id   = $arr['user_id'];
        $transfer->receiver_id = isset($arr['userInfo']) ? $arr['userInfo']->id : null;
        $transfer->currency_id = $arr['currency_id'];
        $transfer->uuid        = $arr['uuid'];
        $transfer->fee         = $arr['fee'];
        $transfer->amount      = $arr['amount'];
        $transfer->note        = $arr['note'];
        $transfer->receivername        = $arr['receivername'];
        if ($arr['emailFilterValidate'])
        {
            $transfer->email = $arr['receiver'];
        }
        elseif ($arr['phoneRegex'])
        {
            $transfer->phone = $arr['receiver'];
        }
        if (isset($transfer->receiver_id))
        {
            $transfer->status = 'Success';
        }
        else
        {
            $transfer->status = 'Pending';
        }
        $transfer->save();

        return $transfer;
    }

    public function createTransferredTransaction($arr)
    {
        $transaction                           = new Transaction();
        $transaction->user_id                  = $arr['user_id'];
        $transaction->end_user_id              = isset($arr['userInfo']) ? $arr['userInfo']->id : null;
        $transaction->currency_id              = $arr['currency_id'];
        $transaction->uuid                     = $arr['uuid'];
        $transaction->transaction_reference_id = $arr['transaction_reference_id'];
        $transaction->transaction_type_id      = Transferred;
        $transaction->user_type                = isset($arr['userInfo']) ? 'registered' : 'unregistered';
        if ($arr['emailFilterValidate'])
        {
            $transaction->email = $arr['receiver'];
        }
        elseif ($arr['phoneRegex'])
        {
            $transaction->phone = $arr['receiver'];
        }
        $transaction->subtotal          = $arr['amount'];
        $transaction->percentage        = @$arr['charge_percentage'] ? @$arr['charge_percentage'] : 0;
        $transaction->charge_percentage = @$arr['charge_percentage'] ? $arr['p_calc'] : 0;
        $transaction->charge_fixed      = @$arr['charge_fixed'] ? @$arr['charge_fixed'] : 0;
        $transaction->total             = '-' . ($arr['total']);
        $transaction->note              = $arr['note'];
        $transaction->status            = $arr['status'];
        $transaction->save();

        return $transaction->id;
    }

    public function createReceivedTransaction($arr)
    {
        $transaction                           = new Transaction();
        $transaction->user_id                  = isset($arr['userInfo']) ? $arr['userInfo']->id : null;
        $transaction->end_user_id              = $arr['user_id'];
        $transaction->currency_id              = $arr['currency_id'];
        $transaction->uuid                     = $arr['uuid'];
        $transaction->transaction_reference_id = $arr['transaction_reference_id'];
        $transaction->transaction_type_id      = Received;
        $transaction->user_type                = isset($arr['userInfo']) ? 'registered' : 'unregistered';
        if ($arr['emailFilterValidate'])
        {
            $transaction->email = $arr['receiver'];
        }
        elseif ($arr['phoneRegex'])
        {
            $transaction->phone = $arr['receiver'];
        }
        $transaction->subtotal          = $arr['amount'];
        $transaction->percentage        = 0;
        $transaction->charge_percentage = 0;
        $transaction->charge_fixed      = 0;
        $transaction->total             = $arr['amount'];
        $transaction->note              = $arr['note'];
        $transaction->status            = $arr['status'];
        $transaction->save();
    }

    public function updateSenderWallet($senderWallet, $totalWithFee)
    {
        $senderWallet->balance = $senderWallet->balance - $totalWithFee;
        $senderWallet->save();
    }

    public function createOrUpdateReceiverWallet($arr)
    {
        if (!empty($arr['transfer_receiver_id']) && isset($arr['userInfo']))
        {
            $receiverWallet = Wallet::where(['user_id' => $arr['userInfo']->id, 'currency_id' => $arr['currency_id']])->first(['id', 'balance']);
            if (empty($receiverWallet))
            {
                $wallet              = new Wallet();
                $wallet->user_id     = isset($arr['userInfo']) ? $arr['userInfo']->id : null;
                $wallet->currency_id = $arr['currency_id'];
                $wallet->is_default  = 'No';
                $wallet->balance     = $arr['amount'];
                $wallet->save();
            }
            else
            {
                $receiverWallet->balance = ($receiverWallet->balance + $arr['amount']);
                $receiverWallet->save();
            }
        }
    }

    public function mailToSender($transfer, $arr)
    {
        if (checkAppMailEnvironment())
        {
            //if other language's subject and body not set, get en sub and body for mail
            $englishSenderLanginfo = $this->helper->getEmailOrSmsTemplate(1, 'email');
            $sender_info           = $this->helper->getEmailOrSmsTemplate(1, 'email', getDefaultLanguage());
            if (!empty($sender_info->subject) && !empty($sender_info->body))
            {
                $sender_subject = $sender_info->subject;
                $sender_msg     = str_replace('{sender_id}', $transfer->sender->first_name . ' ' . $transfer->sender->last_name, $sender_info->body);
            }
            else
            {
                $sender_subject = $englishSenderLanginfo->subject;
                $sender_msg     = str_replace('{sender_id}', $transfer->sender->first_name . ' ' . $transfer->sender->last_name, $englishSenderLanginfo->body);
            }
            $sender_msg = str_replace('{amount}', moneyFormat($transfer->currency->symbol, formatNumber($arr['amount'])), $sender_msg);
            $sender_msg = str_replace('{uuid}', $arr['uuid'], $sender_msg);
            $sender_msg = str_replace('{receiver_id}', isset($arr['userInfo']) ? $arr['userInfo']->first_name . ' ' . $arr['userInfo']->last_name : $arr['receiver'], $sender_msg);
            $sender_msg = str_replace('{fee}', moneyFormat($transfer->currency->symbol, formatNumber($arr['p_calc'] + $arr['charge_fixed'])), $sender_msg);
            $sender_msg = str_replace('{created_at}', $this->helper->getCurrentDateTime(), $sender_msg);
            $sender_msg = str_replace('{soft_name}', getCompanyName(), $sender_msg);

            try {
                $this->emailObject->sendEmail($transfer->sender->email, $sender_subject, $sender_msg);
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

    public function mailToReceiver($transfer, $arr)
    {
        if (checkAppMailEnvironment())
        {
            //if other language's subject and body not set, get en sub and body for mail
            $englishLangReceiverinfo = $this->helper->getEmailOrSmsTemplate(2, 'email');
            $receiver_info           = $this->helper->getEmailOrSmsTemplate(2, 'email', getDefaultLanguage());
            if (isset($arr['userInfo']))
            {
                if (!empty($receiver_info->subject) && !empty($receiver_info->body))
                {
                    $receiver_subject = $receiver_info->subject;
                    $receiver_msg     = str_replace('{receiver_id}', $transfer->receiver->first_name . ' ' . $transfer->receiver->last_name, $receiver_info->body); //
                }
                else
                {
                    $receiver_subject = $englishLangReceiverinfo->subject;
                    $receiver_msg     = str_replace('{receiver_id}', $transfer->receiver->first_name . ' ' . $transfer->receiver->last_name, $englishLangReceiverinfo->body); //
                }
                $receiver_msg = str_replace('{amount}', moneyFormat($transfer->currency->symbol, formatNumber($arr['amount'])), $receiver_msg);
                $receiver_msg = str_replace('{sender_id}', $transfer->sender->first_name . ' ' . $transfer->sender->last_name, $receiver_msg);
                $receiver_msg = str_replace('{uuid}', $arr['uuid'], $receiver_msg);
                $receiver_msg = str_replace('{created_at}', $this->helper->getCurrentDateTime(), $receiver_msg);
                $receiver_msg = str_replace('{soft_name}', getCompanyName(), $receiver_msg);

                try {
                    $this->emailObject->sendEmail($transfer->receiver->email, $receiver_subject, $receiver_msg);
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
            else
            {
                $email_explode                 = explode("@", trim($arr['receiver']));
                $unregisteredUserNameFromEmail = $email_explode[0];
                $profileName                   = getCompanyName();
                $subject                       = 'Notice of Transfer!';
                $message                       = 'Hi ' . $unregisteredUserNameFromEmail . ',<br><br>';
                $message .= 'You have got ' . moneyFormat($transfer->currency->symbol, formatNumber($arr['amount'])) . ' money transfer from ' . $transfer->sender->email . '.<br>';
                $message .= 'To receive, please register on : ' . url('/register') . ' with current email.<br><br>';
                $message .= 'Regards,<br>';
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
    }

    public function smsToSender($transfer, $arr)
    {
        if (checkAppSmsEnvironment())
        {
            if (!empty($transfer->sender->carrierCode) && !empty($transfer->sender->phone))
            {
                //if other language's subject and body not set, get en sub and body for mail
                $englishSenderLangSMSinfo = $this->helper->getEmailOrSmsTemplate(1, 'sms');
                $senderSmsInfo            = $this->helper->getEmailOrSmsTemplate(1, 'sms', getDefaultLanguage());
                if (!empty($senderSmsInfo->subject) && !empty($senderSmsInfo->body))
                {
                    $sender_subject = $senderSmsInfo->subject;
                    $sender_msg     = str_replace('{sender_id}', $transfer->sender->first_name . ' ' . $transfer->sender->last_name, $senderSmsInfo->body);
                }
                else
                {
                    $sender_subject = $englishSenderLangSMSinfo->subject;
                    $sender_msg     = str_replace('{sender_id}', $transfer->sender->first_name . ' ' . $transfer->sender->last_name, $englishSenderLangSMSinfo->body);
                }
                $sender_msg = str_replace('{amount}', moneyFormat($transfer->currency->symbol, formatNumber($arr['amount'])), $sender_msg);

                try {
                    sendSMS($transfer->sender->carrierCode . $transfer->sender->phone, $sender_msg);
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

    public function smsToReceiver($transfer, $arr)
    {
        if (checkAppSmsEnvironment())
        {
            if (!empty($transfer->receiver->carrierCode) && !empty($transfer->receiver->phone))
            {
                //if other language's subject and body not set, get en sub and body for mail
                $englishLangReceiverSmsInfo = $this->helper->getEmailOrSmsTemplate(2, 'sms');
                $receiverSmsInfo            = $this->helper->getEmailOrSmsTemplate(2, 'sms', getDefaultLanguage());

                if (!empty($receiverSmsInfo->subject) && !empty($receiverSmsInfo->body))
                {
                    $receiver_subject = $receiverSmsInfo->subject;
                    $receiver_msg     = str_replace('{receiver_id}', $transfer->receiver->first_name . ' ' . $transfer->receiver->last_name, $receiverSmsInfo->body); //
                }
                else
                {
                    $receiver_subject = $englishLangReceiverSmsInfo->subject;
                    $receiver_msg     = str_replace('{receiver_id}', $transfer->receiver->first_name . ' ' . $transfer->receiver->last_name, $englishLangReceiverSmsInfo->body); //
                }
                $receiver_msg = str_replace('{amount}', moneyFormat($transfer->currency->symbol, formatNumber($arr['amount'])), $receiver_msg);
                $receiver_msg = str_replace('{sender_id}', $transfer->sender->first_name . ' ' . $transfer->sender->last_name, $receiver_msg);

                try {
                    sendSMS($transfer->receiver->carrierCode . $transfer->receiver->phone, $receiver_msg);
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

    public function smsToUnregisteredReceiver($transfer, $arr)
    {
        $message = 'You have received ' . moneyFormat($transfer->currency->symbol, formatNumber($arr['amount'])) . ' from ' . $transfer->sender->first_name . ' ' . $transfer->sender->last_name . '. <br><br>';
        try
        {
            sendSMS($transfer->phone, $message);
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

    public function sendTransferEmailNotification($transfer, $arr)
    {
        $data = [];

        //Mail To Sender
        $mailToSender = self::mailToSender($transfer, $arr);
        if (!empty($mailToSender['ex']))
        {
            $data = [
                'exFrom' => 'mailToSender',
                'ex'     => $mailToSender['ex'],
            ];
        }
        //Mail To Receiver
        $mailToReceiver = self::mailToReceiver($transfer, $arr);
        if (!empty($mailToReceiver['ex']))
        {
            $data = [
                'exFrom' => 'mailToReceiver',
                'ex'     => $mailToReceiver['ex'],
            ];
        }
        return $data;
    }

    public function sendTransferSmsNotification($transfer, $arr)
    {
        $data = [];

        //SMS To Sender
        $smsToSender = self::smsToSender($transfer, $arr);
        if (!empty($smsToSender['ex']))
        {
            $data = [
                'exFrom' => 'smsToSender',
                'ex'     => $smsToSender['ex'],
            ];
        }

        //SMS To Receiver
        if (isset($arr['userInfo']))
        {
            //sms To Registered Receiver
            $smsToReceiver = self::smsToReceiver($transfer, $arr);
            if (!empty($smsToReceiver['ex']))
            {
                $data = [
                    'exFrom' => 'smsToReceiver',
                    'ex'     => $smsToReceiver['ex'],
                ];
            }
        }
        else
        {
            //sms To Unregistered Receiver
            $smsToReceiver = self::smsToUnregisteredReceiver($transfer, $arr);
            if (!empty($smsToReceiver['ex']))
            {
                $data = [
                    'exFrom' => 'smsToReceiver',
                    'ex'     => $smsToReceiver['ex'],
                ];
            }
        }
        return $data;
    }

    /**
     * Process Send Money Confirm
     * param  array  $arr
     * param  string $clearSessionFrom
     * return object
     */
    public function processSendMoneyConfirmation($arr = [], $clearSessionFrom)
    {
        $response = ['status' => 401];

        try
        {
            //Backend Validation - Wallet Balance Again Amount Check - Starts here
            $checkWalletBalance = $this->helper->checkWalletBalanceAgainstAmount($arr['total'], $arr['currency_id'], $arr['user_id']);
            // if ($checkWalletBalance == true)
            // {
            //     $response['transactionOrTransferId'] = null;
            //     if ($clearSessionFrom == 'web')
            //     {
            //         $response['ex']['message'] = __("Not have enough balance !");
            //         return $response;
            //     }
            //     $response['ex']['message'] = "Sorry, not enough funds to perform the operation!";
            //     return $response;
            //     //Backend Validation - Wallet Balance Again Amount Check - Ends here
            // }
            // else
            // {
                DB::beginTransaction();

                //Create Transfer
                $transfer = self::createTransfer($arr);

                //Create Transferred Transaction
                $arr['transaction_reference_id'] = $transfer->id;
                $arr['status']                   = $transfer->status;
                $transferredTransactionId        = self::createTransferredTransaction($arr);

                //Create Received Transaction
                self::createReceivedTransaction($arr);

                //Update Sender Wallet
                self::updateSenderWallet($arr['senderWallet'], $arr['total']);

                //Create Or Update Receiver Wallet
                $arr['transfer_receiver_id'] = $transfer->receiver_id;
                self::createOrUpdateReceiverWallet($arr);

                DB::commit();

                $transactionOrTransferId             = ($clearSessionFrom == 'web') ? $transferredTransactionId : $transfer->id;
                $response['transactionOrTransferId'] = $transactionOrTransferId;

                //EMAIL & SMS - starts
                if ($arr['emailFilterValidate'] && $arr['processedBy'] == "email")
                {
                    $data = self::sendTransferEmailNotification($transfer, $arr);
                    if (!empty($data))
                    {
                        $response['ex']['source']  = $data['exFrom'];
                        $response['ex']['message'] = $data['ex']->getMessage();
                        return $response;
                    }
                }
                elseif ($arr['phoneRegex'] && $arr['processedBy'] == "phone")
                {
                    $data = self::sendTransferSmsNotification($transfer, $arr);
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
                        $data = self::sendTransferEmailNotification($transfer, $arr);
                        if (!empty($data))
                        {
                            $response['ex']['source']  = $data['exFrom'];
                            $response['ex']['message'] = $data['ex']->getMessage();
                            return $response;
                        }
                    }
                    elseif ($arr['phoneRegex'])
                    {
                        $data = self::sendTransferSmsNotification($transfer, $arr);
                        if (!empty($data))
                        {
                            $response['ex']['source']  = $data['exFrom'];
                            $response['ex']['message'] = $data['ex']->getMessage();
                            return $response;
                        }
                    }
                }

                //Admin Notification
                $notificationToAdmin = $this->helper->sendTransactionNotificationToAdmin('send', ['data' => $transfer]);
                if (!empty($notificationToAdmin['ex']))
                {
                    $response['ex']['source']  = $notificationToAdmin['exFrom'];
                    $response['ex']['message'] = $notificationToAdmin['ex']->getMessage();
                    return $response;
                }
                //EMAIL & SMS - ends

                $response['status'] = 200;
                return $response;
            // }
        }
        catch (Exception $e)
        {
            DB::rollBack();
            if ($clearSessionFrom == 'web')
            {
                $this->helper->clearSessionWithRedirect('transInfo', $e, 'moneytransfer');
            }
            $response['transactionOrTransferId'] = null;
            $response['ex']['message']           = $e->getMessage();
            return $response;
        }
    }
    //common functions - ends
}
