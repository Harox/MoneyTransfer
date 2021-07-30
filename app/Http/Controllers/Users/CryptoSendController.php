<?php

namespace App\Http\Controllers\Users;

use App\Repositories\CryptoCurrencyRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{User,
    Currency
};
use Exception;

class CryptoSendController extends Controller
{
    protected $helper;
    protected $currency;
    /**
     * The CryptoCurrency repository instance.
     *
     * @var CryptoCurrencyRepository
     */
    protected $cryptoCurrency;

    public function __construct()
    {
        $this->helper         = new Common();
        $this->currency       = new Currency();
        $this->cryptoCurrency = new CryptoCurrencyRepository();
    }

    public function sendCryptoCreate($walletCurrencyCode, $walletId)
    {
        // destroying cryptoEncArr after loading create poge from reload of crypto success page
        if (!empty(session('cryptoEncArr')))
        {
            session()->forget('cryptoEncArr');
        }

        //set the session for validating the action
        setActionSession();

        $walletCurrencyCode         = decrypt($walletCurrencyCode);
        $walletId                   = decrypt($walletId);
        $data['walletCurrencyCode'] = strtoupper($walletCurrencyCode);
        $data['walletId']           = $walletId;

        // Check crypto currency status
        if ($data['walletCurrencyCode'] != '')
        {
            $getCryptoCurrencyStatus = $this->cryptoCurrency->getCryptoCurrencyStatus($data['walletCurrencyCode']);
            if ($getCryptoCurrencyStatus == 'Inactive')
            {
                $data['message'] = $data['walletCurrencyCode'] . __(' is Inactive!');
                return view('user_dashboard.users.check_crypto_currency_status', $data);
            }
            else
            {
                //get user's wallet address
                $data['senderAddress'] = $senderAddress = $this->cryptoCurrency->getUserCryptoAddress($walletId);
                return view('user_dashboard.crypto.send.create', $data);
            }
        }
        else
        {
            return redirect('dashboard');
        }
    }

    //Validators - starts
    public function validateCryptoAddress(Request $request)
    {
        try {
            $walletCurrencyCode      = $request->walletCurrencyCode;
            $receiverAddress         = $request->receiverAddress;
            $checkUserNetworkAddress = $this->cryptoCurrency->checkNetworkAddressValidity($walletCurrencyCode, $receiverAddress);
            if (!$checkUserNetworkAddress)
            {
                return response()->json([
                    'status'  => 400,
                    'message' => __("Invalid recipient") . ' ' . $walletCurrencyCode . ' ' . __("address") . '!',
                ]);
            }

            //Backend validation of own network address with receiver network address - starts
            $getUserNetworkWallet = $this->cryptoCurrency->getUserNetworkWalletAddress(auth()->user()->id, $walletCurrencyCode);
            if ($receiverAddress == $getUserNetworkWallet->getData()->userAddress)
            {
                return response()->json([
                    'status'  => 400,
                    'message' => __("Cannot send") . ' ' . ($walletCurrencyCode) . ' ' . __("to own address") . '!',
                ]);
            }
            //Backend validation of own network address with receiver network address - ends

            return response()->json([
                'status'  => 200,
                'isValid' => true,
            ]);
        }
        catch (Exception $e)
        {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function validateUserBalanceAgainstAmount(Request $request)
    {
        try {
            $validateNetworkAddressBalance = $this->cryptoCurrency->validateNetworkAddressBalance($request->walletCurrencyCode, $request->amount, $request->senderAddress, $request->receiverAddress);
            if (!$validateNetworkAddressBalance['status'])
            {
                return response()->json([
                    'status'  => 400,
                    'message' => __("Network fee") . ' ' . $validateNetworkAddressBalance['network-fee'] . ' ' . __("and amount") . ' ' . number_format($request->amount, 8, '.', '') . ' ' . __("exceeds your") . ' ' . ($request->walletCurrencyCode) . ' ' . __("balance"),
                ]);
            }
            else
            {
                return response()->json([
                    'status' => 200,
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }
    //Validators - ends

    public function sendCryptoConfirm(Request $request)
    {
        $walletCurrencyCode = decrypt($request->walletCurrencyCode);
        $walletId           = decrypt($request->walletId);
        $senderAddress      = decrypt($request->senderAddress);
        $amount             = $request->amount;
        $receiverAddress    = $request->receiverAddress;
        $userId             = auth()->user()->id;
        $currency           = $this->currency->getCurrency(['code' => $walletCurrencyCode], ['id', 'symbol']);
        $uniqueCode         = unique_code();

        $rules = array(
            'receiverAddress' => 'required',
            'amount'          => 'required',
        );
        $fieldNames = array(
            'receiverAddress' => __("Address"),
            'amount'          => __("Amount"),
        );

        //Backend validation of receiver network address validity - starts
        $checkReceiverNetworkAddress = $this->cryptoCurrency->checkNetworkAddressValidity($walletCurrencyCode, $receiverAddress);
        if (!$checkReceiverNetworkAddress)
        {
            //TODO: translation
            return back()->withErrors(__("Invalid recipient") . ' ' . $walletCurrencyCode . ' ' . __("address") . '!')->withInput();
        }
        //Backend validation of receiver network address validity - ends

        //Backend validation of own network address with receiver network address - starts
        $getUserNetworkWallet = $this->cryptoCurrency->getUserNetworkWalletAddress($userId, $walletCurrencyCode);
        if ($receiverAddress == $getUserNetworkWallet->getData()->userAddress)
        {
            return back()->withErrors(__("Cannot send") . ' ' . $walletCurrencyCode . ' ' . __("to own address") . '!')->withInput();
        }
        //Backend validation of own network address with receiver network address - ends

        //Backend Validations of wallet currency code & amount - starts
        if (($walletCurrencyCode == 'DOGE' || $walletCurrencyCode == 'DOGETEST') && $amount < 2)
        {
            //TODO: translation
            return back()->withErrors(__("The minimum amount must be") . ' 2 ' . $walletCurrencyCode)->withInput();
        }
        elseif (($walletCurrencyCode == 'BTC' || $walletCurrencyCode == 'BTCTEST') && $amount < 0.00002)
        {
            //TODO: translation
            return back()->withErrors(__("The minimum amount must be") . ' 0.00002 ' . $walletCurrencyCode)->withInput();
        }
        elseif (($walletCurrencyCode == 'LTC' || $walletCurrencyCode == 'LTCTEST') && $amount < 0.0002)
        {
            //TODO: translation
            return back()->withErrors(__("The minimum amount must be") . ' 0.0002 ' . $walletCurrencyCode)->withInput();
        }
        //Backend Validations of wallet currency code & amount - ends

        //Backend Validations of sender crypto wallet balance - starts
        $request['walletCurrencyCode']    = $walletCurrencyCode;
        $request['senderAddress']         = $senderAddress;
        $request['receiverAddress']       = $receiverAddress;
        $validateUserBalanceAgainstAmount = $this->validateUserBalanceAgainstAmount($request);
        if ($validateUserBalanceAgainstAmount->getData()->status == 400)
        {
            //TODO: translate balance message
            return back()->withErrors($validateUserBalanceAgainstAmount->getData()->message)->withInput();
        }
        //Backend Validations of sender crypto wallet balance - ends

        $validator = \Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }
        else
        {
            try
            {
                //Call network fee API of block io
                $getNetworkFeeEstimate = $this->cryptoCurrency->getNetworkFeeEstimate($walletCurrencyCode, $receiverAddress, $amount);
                $arr                   = [
                    'receiverAddress' => $receiverAddress,
                    'amount'          => $amount,
                    'networkFee'      => $getNetworkFeeEstimate,
                    'senderAddress'   => $senderAddress,
                    'userId'          => $userId,
                    'currencyId'      => $currency->id,
                    'currencySymbol'  => $currency->symbol,
                    'uniqueCode'      => $uniqueCode,
                ];
                //Get wallet id of receiver address from crypto api log
                $getReceiverAddressCryptoApiLog = $this->cryptoCurrency->getReceiverAddressWalletUserId($receiverAddress);
                if (!empty($getReceiverAddressCryptoApiLog))
                {
                    $arr['endUserId'] = $getReceiverAddressCryptoApiLog->wallet->user_id;
                }
                else
                {
                    $arr['endUserId'] = null;
                }

                //Put data in session for success page
                session(['cryptoTrx' => $arr]);

                //Put currency code and wallet into session id for create route & destroy it after loading create poge - starts
                $cryptoEncArr                       = [];
                $cryptoEncArr['walletCurrencyCode'] = $walletCurrencyCode;
                $cryptoEncArr['walletId']           = $walletId;
                session(['cryptoEncArr' => $cryptoEncArr]);
                //Put currency code and wallet into session id for create route & destroy it after loading create poge - ends

                //Data for confirm page - starts
                $data['cryptoTrx']          = $arr;
                $data['walletCurrencyCode'] = $walletCurrencyCode;
                $data['walletId']           = $walletId;
                //Data for confirm page - ends

                return view('user_dashboard.crypto.send.confirmation', $data);
            }
            catch (Exception $e)
            {
                return back()->withErrors(__($e->getMessage()))->withInput();
            }
        }
    }

    public function sendCryptoSuccess(Request $request)
    {
        $cryptoTrx = session('cryptoTrx');

        // .env - APP_DEMO - check
        if (checkDemoEnvironment() == true)
        {
            $this->helper->one_time_message('error', 'Crypto Send is not possible on demo site.');
            return redirect("crpto/send/" . encrypt(session('cryptoEncArr')['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
        }

        // Check session - cryptoTrx
        if (empty($cryptoTrx))
        {
            return redirect("crpto/send/" . encrypt(session('cryptoEncArr')['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
        }

        //initializing session
        actionSessionCheck();

        //Backend Validations of sender crypto wallet balance -- for multiple tab submit
        $request['walletCurrencyCode']    = session('cryptoEncArr')['walletCurrencyCode'];
        $request['senderAddress']         = $cryptoTrx['senderAddress'];
        $request['receiverAddress']       = $cryptoTrx['receiverAddress'];
        $request['amount']                = $cryptoTrx['amount'];
        $validateUserBalanceAgainstAmount = $this->validateUserBalanceAgainstAmount($request);
        if ($validateUserBalanceAgainstAmount->getData()->status == 400)
        {
            $this->helper->one_time_message('error', $validateUserBalanceAgainstAmount->getData()->message);
            return redirect("crpto/send/" . encrypt(session('cryptoEncArr')['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
        }
        else
        {
            try
            {
                $withdrawInfo = '';

                if ($request['walletCurrencyCode'] == 'BTC' || $request['walletCurrencyCode'] == 'LTC' || $request['walletCurrencyCode'] == 'DOGE') {
                    
                    try {

                        $notification = $this->cryptoCurrency->getNotificationStatus($request['walletCurrencyCode']);
                        if ($notification['status'] == false) {

                            $enableNotification = $this->cryptoCurrency->enableNotificationStatus($request['walletCurrencyCode'], $notification['notificationId']);
                            if ($enableNotification['status'] == true) {

                                try {
                                    $withdrawInfo = $this->cryptoCurrency->withdrawOrSendAmountToReceiverAddress($request['walletCurrencyCode'], $cryptoTrx['senderAddress'], $cryptoTrx['receiverAddress'], $cryptoTrx['amount'], $cryptoTrx['uniqueCode']);
                                } catch (Exception $e) {
                                    $this->helper->one_time_message('error', $e->getMessage());
                                    return redirect("crpto/send/" . encrypt($request['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
                                }
                            } else {
                                $this->helper->one_time_message('error', 'Admin\'s block.io Subscription on is Expired! Need to renew for properly work.');
                                return redirect("crpto/send/" . encrypt($request['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
                            }
                        } else {

                             try {
                                $withdrawInfo = $this->cryptoCurrency->withdrawOrSendAmountToReceiverAddress($request['walletCurrencyCode'], $cryptoTrx['senderAddress'], $cryptoTrx['receiverAddress'], $cryptoTrx['amount'], $cryptoTrx['uniqueCode']);
                            } catch (Exception $e) {
                                $this->helper->one_time_message('error', $e->getMessage());
                                return redirect("crpto/send/" . encrypt($request['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
                            }
                        }
                    } catch (Exception $e) {
                        $this->helper->one_time_message('error', $e->getMessage());
                        return redirect("crpto/send/" . encrypt($request['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));       
                    }
                } else {
                    
                    try {
                        $withdrawInfo = $this->cryptoCurrency->withdrawOrSendAmountToReceiverAddress($request['walletCurrencyCode'], $cryptoTrx['senderAddress'], $cryptoTrx['receiverAddress'], $cryptoTrx['amount'], $cryptoTrx['uniqueCode']);
                    } catch (Exception $e) {
                        $this->helper->one_time_message('error', $e->getMessage());
                        return redirect("crpto/send/" . encrypt($request['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
                    }
                }

                DB::beginTransaction();

                //Create Crypto Transaction
                $createCryptoTransactionId = $this->cryptoCurrency->createCryptoTransaction($cryptoTrx);

                //Create new withdrawal/Send crypt api log
                $cryptoTrx['transactionId']      = $createCryptoTransactionId;
                $cryptoTrx['walletCurrencyCode'] = $request['walletCurrencyCode'];
                $cryptoTrx['withdrawInfoData']   = $withdrawInfo->data;

                //need this for showing send address against Crypto Receive Type Transaction in user/admin panel
                $cryptoTrx['withdrawInfoData']->senderAddress = $cryptoTrx['senderAddress'];

                //need this for nodejs websocket server
                $cryptoTrx['withdrawInfoData']->receiverAddress = $cryptoTrx['receiverAddress'];
                $this->cryptoCurrency->createWithdrawalOrSendCryptoApiLog($cryptoTrx);

                //Update Sender Network Address Balance
                $this->cryptoCurrency->getUpdatedSendWalletBalance($cryptoTrx);

                DB::commit();

                // Initially after 1 confirmations of blockio response, websocket queries will be executed
                $cryptConfirmationsArr = [
                    'BTC'      => 1,
                    'BTCTEST'  => 1,
                    'DOGE'     => 1,
                    'DOGETEST' => 1,
                    'LTC'      => 1,
                    'LTCTEST'  => 1,
                ];
                $data['confirmations']      = $cryptConfirmationsArr[$request['walletCurrencyCode']];
                $data['walletCurrencyCode'] = $request['walletCurrencyCode'];
                $data['receiverAddress']    = $cryptoTrx['receiverAddress'];
                $data['currencySymbol']     = $cryptoTrx['currencySymbol'];
                $data['amount']             = $cryptoTrx['amount'];
                $data['transactionId']      = $cryptoTrx['transactionId'];
                $data['walletId']           = session('cryptoEncArr')['walletId'];

                //don't flush/forget cryptoEncArr from session as it will be cleared on create method
                session()->forget(['cryptoTrx']);
                clearActionSession();

                return view('user_dashboard.crypto.send.success', $data);
            }
            catch (Exception $e)
            {
                DB::rollBack();
                $this->helper->one_time_message('error', $e->getMessage());
                return redirect("crpto/send/" . encrypt($request['walletCurrencyCode']) . "/" . encrypt(session('cryptoEncArr')['walletId']));
            }
        }
    }

}
