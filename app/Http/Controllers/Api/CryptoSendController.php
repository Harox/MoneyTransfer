<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Users\CryptoSendController as CryptoSend;
use App\Repositories\CryptoCurrencyRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Currency;
use Exception;

class CryptoSendController extends Controller
{
    public $successStatus      = 200;
    public $unauthorisedStatus = 401;
    protected $currency;
    /**
     * The CryptoCurrency repository instance.
     *
     * @var CryptoCurrencyRepository
     */
    protected $cryptoCurrency;

    public function __construct()
    {
        $this->cryptoCurrency = new CryptoCurrencyRepository();
        $this->currency       = new Currency();
        $this->cryptoSend     = new CryptoSend();
    }

    /**
     * Crypto Send Review
     * @param Request $request
     */
    public function cryptoSendReview(Request $request)
    {
        $cryptoCurrencyCode = $request->cryptoCurrencyCode;
        $senderAddress      = $request->senderAddress;
        $amount             = number_format($request->amount, 8, '.', ''); // formatted to 8 decimal places at back-end
        $receiverAddress    = $request->receiverAddress;
        $userId             = $request->user_id;
        $currency           = $this->currency->getCurrency(['code' => $cryptoCurrencyCode], ['id', 'symbol']);
        $uniqueCode         = unique_code();

        $rules = array(
            'receiverAddress' => 'required',
            'amount'          => 'required',
        );
        $fieldNames = array(
            'receiverAddress' => 'Address',
            'amount'          => 'Amount',
        );

        //Backend validation of receiver network address validity - starts
        $checkReceiverNetworkAddress = $this->cryptoCurrency->checkNetworkAddressValidity($cryptoCurrencyCode, $receiverAddress);
        if (!$checkReceiverNetworkAddress)
        {
            return [
                'status'  => $this->unauthorisedStatus,
                'reason'  => 'invalid-address',
                'message' => 'Invalid recipient ' . $cryptoCurrencyCode . ' address',
            ];
        }
        //Backend validation of receiver network address validity - ends

        //Backend validation of own network address with receiver network address - starts
        $getUserNetworkWallet = $this->cryptoCurrency->getUserNetworkWalletAddress($userId, $cryptoCurrencyCode);
        if ($receiverAddress == $getUserNetworkWallet->getData()->userAddress)
        {
            return [
                'status'  => $this->unauthorisedStatus,
                'reason'  => 'own-address',
                'message' => 'Cannot send ' . $cryptoCurrencyCode . ' to own address!',
            ];
        }
        //Backend validation of own network address with receiver network address - ends

        //Backend Validations of sender crypto wallet balance - starts
        $request['walletCurrencyCode']    = $cryptoCurrencyCode;
        $request['senderAddress']         = $senderAddress;
        $request['receiverAddress']       = $receiverAddress;
        $validateUserBalanceAgainstAmount = $this->cryptoSend->validateUserBalanceAgainstAmount($request);
        if ($validateUserBalanceAgainstAmount->getData()->status == 400)
        {
            return [
                'status'  => $this->unauthorisedStatus,
                'reason'  => 'insufficient-balance',
                'message' => $validateUserBalanceAgainstAmount->getData()->message,
            ];
        }
        //Backend Validations of sender crypto wallet balance - ends

        $validator = \Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails())
        {
            return [
                'status'  => $this->unauthorisedStatus,
                'reason'  => 'validation-error',
                'message' => $validator,
            ];
        } else {
            try {
                //Call network fee API of block io
                $networkFee = $this->cryptoCurrency->getNetworkFeeEstimate($cryptoCurrencyCode, $receiverAddress, $amount);

                //Get wallet id of receiver address from crypto api log
                $getReceiverAddressCryptoApiLog = $this->cryptoCurrency->getReceiverAddressWalletUserId($receiverAddress);
                if (!empty($getReceiverAddressCryptoApiLog)){
                    $endUserId = $getReceiverAddressCryptoApiLog->wallet->user_id;
                } else {
                    $endUserId = null;
                }

                return [
                    'status'          => $this->successStatus,
                    'amount'          => $amount,
                    'network-fee'     => $networkFee,
                    'total'           => number_format($amount + $networkFee, 8, '.', ''),
                    'currency-id'     => $currency->id,
                    'currency-symbol' => $currency->symbol,
                    'end-user-id'     => $endUserId,
                ];
            } catch (Exception $e) {
                return [
                    'status'  => $this->unauthorisedStatus,
                    'reason'  => 'exception-error',
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    public function cryptoSendConfirm(Request $request)
    {
        // .env - APP_DEMO - check
        if (checkDemoEnvironment() == true)
        {
            return [
                'status'  => $this->unauthorisedStatus,
                'reason'  => 'demo-site-error',
                'message' => 'Crypto Send is not possible on demo site.',
            ];
        }

        $userId             = $request->user_id;
        $cryptoCurrencyCode = $request->cryptoCurrencyCode;
        $senderAddress      = $request->senderAddress;
        $receiverAddress    = $request->receiverAddress;
        $amount             = $request->amount;
        $currencyId         = $request->currencyId;
        $currencySymbol     = $request->currencySymbol;
        $networkFee         = $request->networkFee;
        // Change localstorage "string" null to "object" null
        if ($request->endUserId == "null")
        {
            $endUserId = null;
        }
        else
        {
            $endUserId = $request->endUserId;
        }
        //
        $uniqueCode = unique_code();

        //Backend Validations of sender crypto wallet balance -- for multiple tab submit
        $request['walletCurrencyCode']    = $cryptoCurrencyCode;
        $request['senderAddress']         = $senderAddress;
        $request['receiverAddress']       = $receiverAddress;
        $request['amount']                = $amount;
        $validateUserBalanceAgainstAmount = $this->cryptoSend->validateUserBalanceAgainstAmount($request);
        if ($validateUserBalanceAgainstAmount->getData()->status == 400)
        {
            return [
                'status'  => $this->unauthorisedStatus,
                'reason'  => 'crypto-send-insufficient-balance',
                'message' => $validateUserBalanceAgainstAmount->getData()->message,
            ];
        } else {
            try {
                try {
                    //Call withdraw API of block io
                    $withdrawInfo = $this->cryptoCurrency->withdrawOrSendAmountToReceiverAddress($cryptoCurrencyCode, $senderAddress, $receiverAddress, $amount, $uniqueCode);
                } catch (Exception $e) {
                    return [
                        'status'  => $this->unauthorisedStatus,
                        'reason'  => 'withdrawal-error',
                        'message' => $e->getMessage(),
                    ];
                }
                //
                $cryptoSendArr = [
                    'receiverAddress' => $receiverAddress,
                    'amount'          => $amount,
                    'networkFee'      => $networkFee,
                    'senderAddress'   => $senderAddress,
                    'userId'          => $userId,
                    'endUserId'       => $endUserId,
                    'currencyId'      => $currencyId,
                    'uniqueCode'      => $uniqueCode,
                ];

                DB::beginTransaction();

                //Create Crypto Transaction
                $createCryptoTransactionId = $this->cryptoCurrency->createCryptoTransaction($cryptoSendArr);

                //Create new withdrawal/Send crypt api log
                $cryptoSendArr['transactionId']      = $createCryptoTransactionId;
                $cryptoSendArr['walletCurrencyCode'] = $cryptoCurrencyCode;
                $cryptoSendArr['withdrawInfoData']   = $withdrawInfo->data;

                //need this for showing send address against Crypto Receive Type Transaction in user/admin panel
                $cryptoSendArr['withdrawInfoData']->senderAddress = $senderAddress;

                //need this for nodejs websocket server
                $cryptoSendArr['withdrawInfoData']->receiverAddress = $receiverAddress;
                $this->cryptoCurrency->createWithdrawalOrSendCryptoApiLog($cryptoSendArr);

                //Update Sender Network Address Balance
                $this->cryptoCurrency->getUpdatedSendWalletBalance($cryptoSendArr);

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

                // Success Data
                $recommendedConfirmations = $cryptConfirmationsArr[$cryptoCurrencyCode];
                return [
                    'status'                    => $this->successStatus,
                    'recommended-confirmations' => $recommendedConfirmations,
                ];
            } catch (Exception $e) {
                DB::rollBack();
                return [
                    'status'  => $this->unauthorisedStatus,
                    'reason'  => 'exception-error',
                    'message' => $e->getMessage(),
                ];
            }
        }
    }
}
