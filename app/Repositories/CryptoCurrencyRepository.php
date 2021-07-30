<?php
namespace App\Repositories;

use App\Http\Helpers\Common;
use App\Models\CryptoapiLog;
use App\Models\CryptoCurrenciesSetting;
use App\Models\Currency;
use App\Models\Preference;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use BlockIo;
use Exception;

class CryptoCurrencyRepository
{
    protected $cryptoCurrenciesSetting;
    protected $cryptoapiLog;

    public function __construct()
    {
        $this->cryptoCurrenciesSetting = new CryptoCurrenciesSetting();
        $this->cryptoapiLog            = new CryptoapiLog();
    }

    /**
     * Initialize Block Io
     * @param  string $network
     * @return object blockIo instance
     */
    public function getBlockIoData($network)
    {
        //get crypto Currencies Settings by network
        $cryptoCurrenciesSettings = $this->cryptoCurrenciesSetting->getCryptoCurrencySetting(['payment_method_id' => 9, 'network' => $network], ['network_credentials']);
        $networkCredentials       = json_decode($cryptoCurrenciesSettings->network_credentials, true);

        //initialize BlockIo
        $apiKey  = $networkCredentials['api_key'];
        $pin     = $networkCredentials['pin'];
        $version = BLOCKIO_API_VERSION; //see - app/Providers/AppServiceProvider.php

        $blockIo = new BlockIo($apiKey, $pin, $version);
        return $blockIo;
    }

    /**
     * Get Crypto Api Log Of Wallet
     * @param  string $walletId
     * @param  string $network
     * @param  object $user
     * @return void
     */
    public function getCryptoApiLogOfWallet($walletId, $network, $user)
    {
        $getCryptoapiLog = $this->cryptoapiLog->getCryptoapiLog(['payment_method_id' => 9, 'object_id' => $walletId, 'object_type' => 'wallet_address', 'network' => $network], ['id']);
        if (empty($getCryptoapiLog))
        {
            //create new crypt api log if empty
            $cryptoapiLog                    = new CryptoapiLog();
            $cryptoapiLog->payment_method_id = 9; //BlockIo
            $cryptoapiLog->object_id         = $walletId;
            $cryptoapiLog->object_type       = 'wallet_address';
            $cryptoapiLog->network           = $network;

            $getNewAddressInfo = "";

            //initialize BlockIo
            $blockIo = $this->getBlockIoData($network);

            try {
                // For Production
                // Call get_new_address BlockIo API
                $getNewAddressInfo = $blockIo->get_new_address(array('label' => $user->id . '_' . $user->email));

                // For Development
                // $getNewAddressInfo = $blockIo->get_balance();

                // Add BlockIo API response to CryptoapiLog payload
                $cryptoapiLog->payload = json_encode($getNewAddressInfo->data);

                // Save cryptoapiLog
                $cryptoapiLog->save();

                return [
                    'status' => 200,
                ];
            } catch (Exception $e) {
                return [
                    'status'  => 401,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Get User Crypt Address Balance
     * @param  string $network
     * @param  string $address
     * @return object available_balance
     */
    public function getUserCryptoAddressBalance($network, $address)
    {
        //Initialize BlockIo
        $blockIo = $this->getBlockIoData($network);

        //Call get address balance blockio API
        $getAddressBalance = $blockIo->get_address_balance(array('addresses' => $address));

        //Check address available balance
        foreach ($getAddressBalance->data->balances as $balanceObject)
        {
            return $balanceObject->available_balance;
        }
    }

    /**
     * Get User's Crypto Address
     * @param  string $walletId
     * @return object address
     */
    public function getUserCryptoAddress($walletId)
    {
        //get user's wallet address
        $cryptoapiLog = $this->cryptoapiLog->getCryptoapiLog(['payment_method_id' => 9, 'object_id' => $walletId, 'object_type' => 'wallet_address'], ['payload']);
        $payload      = json_decode($cryptoapiLog->payload, true);
        $address      = $payload['address'];
        return $address;
    }

    /**
     * Get Minimum Network fee
     * @param  string $network
     * @param  string $receiverAddress
     * @param  string $amount
     * @return object estimated min network fee
     */
    public function getNetworkFeeEstimate($network, $receiverAddress, $amount)
    {
        //Initialize BlockIo
        $blockIo = $this->getBlockIoData($network);

        //Call network fee API of block io
        $networkFeeInfo = $blockIo->get_network_fee_estimate(array('to_address' => $receiverAddress, 'amount' => $amount));
        return $networkFeeInfo->data->estimated_network_fee; //this is NOT minimum

        // return $networkFeeInfo->data->estimated_min_custom_network_fee;//this is minimum
    }

    /**
     * Get Network from crypto currency API
     * @param  $apiKey
     * @param  $pin
     * @return $network
     */
    public function getCryptoCurrencyNetwork($apiKey, $pin)
    {
        //see - app/Providers/AppServiceProvider.php
        $version = BLOCKIO_API_VERSION;
        $blockIo = new BlockIo($apiKey, $pin, $version);
        //called to get only network using api key
        $blockIoObj = $blockIo->get_balance();
        $network    = $blockIoObj->data->network;
        return $network;
    }

    /**
     * Validate network address balance (both merchant/admin & user network address)
     * @param  $walletCurrencyCode/$network
     * @param  $amount
     * @param  $senderAddress/$merchantAddress
     * @param  $receiverAddress/$userAddress
     * @return status,                           network-fee
     */
    public function validateNetworkAddressBalance($walletCurrencyCode, $amount, $senderAddress, $receiverAddress)
    {
        $getUserCryptoAddressBalance = $this->getUserCryptoAddressBalance($walletCurrencyCode, $senderAddress);

        //Network fee for receiver Address
        $getNetworkFeeEstimate = $this->getNetworkFeeEstimate($walletCurrencyCode, $receiverAddress, $amount);

        if ($getUserCryptoAddressBalance < 0 || ($getUserCryptoAddressBalance < ($amount + $getNetworkFeeEstimate)))
        {
            return [
                'status'      => false,
                'network-fee' => $getNetworkFeeEstimate,
            ];
        }
        else
        {
            return [
                'status' => true,
            ];
        }
    }

    /**
     * Check Network Address (both user & merchant)
     * @param  $network
     * @param  $address
     * @return true/false
     */
    public function checkNetworkAddressValidity($network, $address)
    {
        $blockIo                     = $this->getBlockIoData($network);
        $checkMerchantNetworkAddress = $blockIo->is_valid_address(array('address' => $address));
        if ($checkMerchantNetworkAddress->data->is_valid != true)
        {
            return false;
        }
        return true;
    }

    /**
     * Get Crypto Api log Payload Details & Confirmations for Crypto_Sent & Crypto_Received
     * @param  $transaction_type_id
     * @param  $payload
     * @param  $confirmations
     * @return array
     */
    public function getCryptoPayloadConfirmationsDetails($transaction_type_id, $payload, $confirmations)
    {
        $arr = [];
        if (!empty($payload))
        {
            if ($transaction_type_id == Crypto_Sent || $transaction_type_id == Crypto_Received)
            {
                $payloadJson = json_decode($payload, true);
                if (isset($payloadJson['senderAddress']))
                {
                    $arr['senderAddress'] = $payloadJson['senderAddress'];
                }
                if (isset($payloadJson['receiverAddress']))
                {
                    $arr['receiverAddress'] = ($transaction_type_id == Crypto_Sent) ? $payloadJson['receiverAddress'] : $payloadJson['address'];
                }
                $arr['txId']            = $payloadJson['txid'];
                $arr['confirmations']   = $confirmations;
            }
        }
        return $arr;
    }

    /**
     * Get active crypto currencies settings
     * @param  $network
     * @param  $status
     * @param  $selectOptions
     * @return object
     */
    public function getCryptoCurrenciesSetting($network, $status, $selectOptions)
    {
        $cryptoCurrenciesSetting = $this->cryptoCurrenciesSetting->where(function ($q) use ($network)
        {
            $network = str_replace("TEST", "", $network);
            $q->where(['network' => $network])->orWhere(['network' => $network . 'TEST']);
        })
            ->where(['payment_method_id' => 9]);

        if ($status == 'Active')
        {
            return $cryptoCurrenciesSetting->where(['status' => 'Active'])->first($selectOptions);
        }
        elseif ($status == 'Inactive') //maybe needed later
        {
            return $cryptoCurrenciesSetting->where(['status' => 'Inactive'])->first($selectOptions);
        }
        elseif ($status == 'All')
        {
            return $cryptoCurrenciesSetting->first($selectOptions);
        }
    }

    /**
     * Get active crypto currencies settings networks
     * @return networks
     */
    public function getActiveCryptoCurrenciesSettingsNetwork()
    {
        return $this->cryptoCurrenciesSetting->getAllCryptoCurrencySettings(['payment_method_id' => 9, 'status' => 'Active'], ['id', 'network']);
    }

    /**
     * Check whether user has any crypto wallet address
     * @param  $userWallets
     * @return crypto         api log object
     */
    public function getUserWalletCryptoapiLogs($userWallets)
    {
        $walletArr = [];
        foreach ($userWallets as $wallet)
        {
            $walletArr[] = $wallet->id;
        }
        return $this->cryptoapiLog->where(['payment_method_id' => 9, 'object_type' => 'wallet_address'])->whereIn('object_id', $walletArr);
    }

    /**
     * Get user wallet address user id
     * @param  $receiverAddress
     * @return user               id
     */
    public function getReceiverAddressWalletUserId($receiverAddress)
    {
        return $this->cryptoapiLog->with(['wallet:id,user_id'])->where(['payment_method_id' => 9, 'object_type' => 'wallet_address'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $receiverAddress . '[[:>:]]'])
        ->first(['object_id']);
    }

    /**
     * Get User Wallet Address
     * @param  $user_id
     * @param  $network
     * @return user       address
     */
    public function getUserNetworkWalletAddress($user_id, $network)
    {
        try
        {
            $user                = User::find($user_id, ['id']);
            $getUserCryptoapiLog = $this->getUserWalletCryptoapiLogs($user->wallets)->where(['network' => $network])->first(['payload']);
            if (!empty($getUserCryptoapiLog))
            {
                $payload     = json_decode($getUserCryptoapiLog->payload, true);
                $userAddress = $payload['address'];
                return response()->json([
                    'status'      => 200,
                    'userAddress' => $userAddress,
                ]);
            }
            else
            {
                return response()->json([
                    'status'  => 400,
                    'message' => 'User ' . $network . ' address not found!',
                ]);
            }
        }
        catch (Exception $e)
        {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get Merchant Network Address
     * @param  $network
     * @return merchant   address
     */
    public function getMerchantNetworkAddress($network)
    {
        $cryptoCurrenciesSetting = $this->getCryptoCurrenciesSetting($network, 'Active', ['network_credentials']);
        $payload                 = json_decode($cryptoCurrenciesSetting->network_credentials, true);
        $merchantAddress         = $payload['address'];
        return $merchantAddress;
    }

    /**
     * Get crypto currency status
     * @param  $walletCurrencyCode
     * @return status
     */
    public function getCryptoCurrencyStatus($walletCurrencyCode)
    {
        $currency = (new Currency())->where(function ($q) use ($walletCurrencyCode)
        {
            $walletCurrencyCode = str_replace("TEST", "", $walletCurrencyCode);
            $q->where(['code' => $walletCurrencyCode])->orWhere(['code' => $walletCurrencyCode . 'TEST']);
        })
            ->where(['type' => 'crypto'])
            ->first(['status']);
        return $currency->status;
    }

    /**
     * Check Merchant Network Address Validity
     * @param  $network
     * @param  $address
     * @return true/false
     */
    public function checkMerchantNetworkAddressValidity($api_key, $pin, $address)
    {
        $version                     = BLOCKIO_API_VERSION; //see - app/Providers/AppServiceProvider.php
        $blockIo                     = new BlockIo($api_key, $pin, $version);
        $checkMerchantNetworkAddress = $blockIo->is_valid_address(array('address' => $address));
        if ($checkMerchantNetworkAddress->data->is_valid != true)
        {
            return [
                'status' => false,
            ];
        }

        $createNotification = $blockIo->list_notifications();

        if (empty($createNotification->data->notifications)) {
            $blockIo->create_notification(array('type' => 'account', 'url' => (\URL::to('/') . '/send/crypto-notification') ));
        }

        return [
            'status'  => true,
            'network' => $checkMerchantNetworkAddress->data->network,
        ];
    }

    /**
     * Get active crypto currencies
     * @return array
     */
    public function getActiveCryptoCurrencies()
    {
        return (new Common())->key_value('id', 'code', Currency::where(['type' => 'crypto', 'status' => 'Active'])->get(['id', 'code'])->toArray());
    }

    /**
     * Get currencies preference
     * @return object
     */
    public function getCurrenciesPreference()
    {
        return Preference::where(['category' => 'preference', 'field' => 'enable_currencies'])->first(['value']);
    }

    /**
     * Get allowed crypto currencies setting
     * @return object
     */
    public function getAllowedCryptoCurrenciesSetting()
    {
        return Setting::where(['name' => 'default_crypto_currencies', 'type' => 'general'])->first(['value']);
    }

    /**
     * Get crypto send and crypto received api logs of provided user wallet address
     * @param  $userWallets
     * @return crypto         api log object
     */
    public function deleteWalletAddressCryptoSentCryptoReceivedApiLogs($userWallets)
    {
        $walletArr = [];
        foreach ($userWallets as $wallet)
        {
            $walletArr[] = $wallet->id;
        }
        $getUserWalletAddressCryptoApiLogs = $this->cryptoapiLog->where(['payment_method_id' => 9, 'object_type' => 'wallet_address'])->whereIn('object_id', $walletArr)->get(['id','payload']);
        if ($getUserWalletAddressCryptoApiLogs->isNotEmpty()) {
            foreach ($getUserWalletAddressCryptoApiLogs as $cryptoapiLog) {
                $payload                   = json_decode($cryptoapiLog->payload, true);
                $address                   = $payload['address'];
                $getWalletAddressCryptoSentReceivedApiLogs = $this->cryptoapiLog->where(['payment_method_id' => 9])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $address . '[[:>:]]'])->get(['id']);
                if ($getWalletAddressCryptoSentReceivedApiLogs->isNotEmpty()) {
                    foreach ($getWalletAddressCryptoSentReceivedApiLogs as $getWalletAddressCryptoSentReceivedApiLog) {
                        $getWalletAddressCryptoSentReceivedApiLog->delete();
                    }
                }
            }
        }
    }

//Send & Receive - starts

    /**
     * Withdraw/Send from/to a crypto address
     * @param  string $network
     * @param  string $receiverAddress
     * @param  string $amount
     * @return object withdraw Api response
     */
    public function withdrawOrSendAmountToReceiverAddress($network, $senderAddress, $receiverAddress, $amount, $nonce)
    {
        //Initialize BlockIo
        $blockIo = $this->getBlockIoData($network);

        //Call withdraw API of block io, without priority
        $withdrawInfo = $blockIo->withdraw_from_addresses(array('amounts' => $amount, 'from_addresses' => $senderAddress, 'to_addresses' => $receiverAddress, 'nonce' => $nonce));

        //Call withdraw API of block io, with priority
        // $withdrawInfo = $blockIo->withdraw_from_addresses(array('amounts' => $amount, 'from_addresses' => $senderAddress, 'to_addresses' => $receiverAddress, 'nonce' => $nonce, 'priority' => 'high'));
        return $withdrawInfo;
    }

    public function createCryptoTransaction($arr)
    {
        $transaction                      = new Transaction();
        $transaction->user_id             = $arr['userId'];
        $transaction->end_user_id         = $arr['endUserId'];
        $transaction->currency_id         = $arr['currencyId'];
        $transaction->payment_method_id   = 9; //BlockIo
        $transaction->uuid                = $arr['uniqueCode'];
        $transaction->transaction_type_id = Crypto_Sent;
        $transaction->subtotal            = $arr['amount'];
        $transaction->charge_fixed        = $arr['networkFee'];
        $transaction->total               = "-" . ($arr['amount'] + $arr['networkFee']);
        $transaction->status              = 'Pending';
        $transaction->save();

        return $transaction->id;
    }

    public function createWithdrawalOrSendCryptoApiLog($arr)
    {
        $cryptoapiLog                    = new CryptoapiLog();
        $cryptoapiLog->payment_method_id = 9; //BlockIo
        $cryptoapiLog->object_id         = $arr['transactionId'];
        $cryptoapiLog->object_type       = 'crypto_sent';
        $cryptoapiLog->network           = $arr['walletCurrencyCode'];
        $cryptoapiLog->payload           = json_encode($arr['withdrawInfoData']); //add BlockIo API withdrawal/Send response to CryptoapiLog payload
        $cryptoapiLog->save();
    }

    public function getUpdatedSendWalletBalance($arr)
    {
        // updating of merchant network address balance will NOT be done in the system

        // update user network address balance
        $getUserCryptoAddressBalance = self::getUserCryptoAddressBalance($arr['walletCurrencyCode'], $arr['senderAddress']);
        $senderWallet                = (new Common())->getUserWallet([], ['user_id' => $arr['userId'], 'currency_id' => $arr['currencyId']], ['id', 'balance']);
        $senderWallet->balance       = $getUserCryptoAddressBalance;
        $senderWallet->save();
    }
//Send & Receive - starts

    public function getNotificationStatus($network)
    {
        $blockIo = $this->getBlockIoData($network);
        $notification = $blockIo->list_notifications();

        return [
            'status'         => $notification->data->notifications[0]->enabled,
            'notificationId' => $notification->data->notifications[0]->notification_id,
        ];
    }

    public function enableNotificationStatus($network, $notificationId)
    {
        $blockIo = $this->getBlockIoData($network);
        $notification = $blockIo->enable_notification(array('notification_id' => $notificationId));

        return [
            'status' => $notification->data->notifications[0]->enabled,
        ];
    }

}
