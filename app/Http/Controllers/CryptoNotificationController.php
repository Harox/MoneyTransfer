<?php

namespace App\Http\Controllers;

use App\Repositories\CryptoCurrencyRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\{CryptoapiLog,
	CryptoCurrenciesSetting,
	Transaction,
	Currency,
	User
};

class CryptoNotificationController extends Controller
{
	protected $cryptoCurrency;

	public function __construct()
    {
        $this->cryptoCurrency          = new CryptoCurrencyRepository();
    }

    public function sendCryptoNotification(Request $request)
    {
    	\Log::info($request->all());

    	$notifArray = $request->all();

    	if ($notifArray['type'] == 'address')
    	{
    		$responseData           = $notifArray['data'];
	        $responseNetwork        = $notifArray['data']['network'];
	        $responseAddress        = $notifArray['data']['address'];
	        $responseBalanceChange  = $notifArray['data']['balance_change'];
	        $responseAmountReceived = $notifArray['data']['amount_received'];
	        $responseAmountSent     = $notifArray['data']['amount_sent'];
	        $responseTxid           = $notifArray['data']['txid'];
	        $responseConfirmations  = $notifArray['data']['confirmations'];
	        $responseNotification   = $notifArray['notification_id'];

	        $cryptoCurrenciesSetting =  DB::select(DB::raw("SELECT network_credentials, status FROM crypto_currencies_settings"));

	        foreach ($cryptoCurrenciesSetting as $cryptoCurrenciesSettings)
	        {
	            $cryptoCurrenciesSettingApiKey = json_decode($cryptoCurrenciesSettings->network_credentials)->api_key;
	            $cryptoCurrenciesSettingStatus = $cryptoCurrenciesSettings->status;
	        }

	        $getActiveCryptoCurrencySettingsCredentialsSql = DB::select(DB::raw("SELECT network_credentials FROM crypto_currencies_settings WHERE network = '$responseNetwork' AND payment_method_id = 9 AND status = 'Active' "));

	        if (count($cryptoCurrenciesSetting) > 0 )
	        {
	            $cryptoCurrenciesSettingNetworkCreds = $cryptoCurrenciesSetting[0]->network_credentials;
	            $BLOCKIO_API_KEY                     = json_decode($cryptoCurrenciesSettingNetworkCreds)->api_key;
	            $BLOCKIO_SECRET_PIN                  = json_decode($cryptoCurrenciesSettingNetworkCreds)->pin;

	            if ($responseConfirmations == 1)
	            {
	            	if ($responseBalanceChange > 0 && $responseAmountSent == 0)
	            	{
                   		$getCryptoSentApiLogSql = $getCryptoSentApiLog = CryptoapiLog::with(['transaction:id,user_id,end_user_id,currency_id'])->where(['network' => $responseNetwork, 'object_type' => 'crypto_sent'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $responseTxid . '[[:>:]]'])->get();

	                    if (count($getCryptoSentApiLog) > 0)
	                    {
	                        $getCryptoSentApiLogNetwork         = json_decode($getCryptoSentApiLog[0]->payload)->network;
	                        $getCryptoSentApiLogTxid            = json_decode($getCryptoSentApiLog[0]->payload)->txid;
	                        $getCryptoSentApiLogReceiverAddress = json_decode($getCryptoSentApiLog[0]->payload)->receiverAddress;

                        	if ($responseNetwork == $getCryptoSentApiLogNetwork && $responseTxid == $getCryptoSentApiLogTxid && $responseAddress == $getCryptoSentApiLogReceiverAddress)
                        	{

                        		$getCryptoSentApiLogObjectId        = $getCryptoSentApiLog[0]->object_id;
	                            $getCryptoSentTransactionUserId     = $getCryptoSentApiLog[0]->transaction->user_id;
	                            $getCryptoSentTransactionEndUserId  = $getCryptoSentApiLog[0]->transaction->end_user_id;
	                            $getCryptoSentTransactionCurrencyId = $getCryptoSentApiLog[0]->transaction->currency_id;
	                            $getCryptoSentApiLogSenderAddress   = json_decode($getCryptoSentApiLog[0]->payload)->senderAddress;

	                            $responseData["senderAddress"]   = $getCryptoSentApiLogSenderAddress;
	                            $responseData["receiverAddress"] = $responseAddress;

	                            $updateCryptoApiLogsConfirmationsSql = DB::update(DB::raw("UPDATE cryptoapi_logs SET confirmations = '$responseConfirmations' WHERE object_id = '$getCryptoSentApiLogObjectId' "));

    	                        $transaction = new Transaction();
								$transaction->user_id             = $getCryptoSentTransactionEndUserId;
								$transaction->end_user_id         = $getCryptoSentTransactionUserId;
								$transaction->currency_id         = $getCryptoSentTransactionCurrencyId;
								$transaction->payment_method_id   = 9;
								$transaction->uuid                = unique_code();
								$transaction->transaction_type_id = 14;
								$transaction->subtotal            = $responseAmountReceived;
								$transaction->total               = $responseAmountReceived;
								$transaction->status              = 'Pending';
                                $transaction->save();

	                            $enCodedResponseData = json_encode($responseData);

                                $cryptoReceivedApiLogSql = new CryptoapiLog();
                                $cryptoReceivedApiLogSql->payment_method_id = 9;
                                $cryptoReceivedApiLogSql->object_id         = $transaction->id;
                                $cryptoReceivedApiLogSql->object_type       = 'crypto_received';
                                $cryptoReceivedApiLogSql->network           = $responseNetwork;
                                $cryptoReceivedApiLogSql->payload           = $enCodedResponseData;
                                $cryptoReceivedApiLogSql->confirmations     = $responseConfirmations;
                                $cryptoReceivedApiLogSql->save();

	                            $getAddressBalance = $this->cryptoCurrency->getUserCryptoAddressBalance($responseNetwork, $responseAddress);

	                            $getWalletAddressCryptoApiLogSql = $getWalletAddressCryptoApiLog = CryptoapiLog::with(['wallet:id,user_id'])->where(['network' => $responseNetwork, 'object_type' => 'wallet_address'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $responseAddress . '[[:>:]]'])->get();


                                if (!$getWalletAddressCryptoApiLogSql->isEmpty())
                                {
                                   $walletId = $getWalletAddressCryptoApiLog[0]->wallet->id;

                                    if (count($getWalletAddressCryptoApiLog) > 0)
                                    {
                                         $updateReceiverWalletBalanceSql = DB::update(DB::raw("UPDATE wallets SET balance = '$getAddressBalance' WHERE id = '$walletId'"));
                                    }
                                }
	                        }
                        }
                        else
                        {
                        	\Log::info('Crypto Received from External');
                        	\Log::info('Crypto Received from External');
                        	\Log::info('Crypto Received from External');
                        	\Log::info('Crypto Received from External');
                        	\Log::info($request->all());

                        	$getCryptoSentApiLogSql = $getCryptoSentApiLog = CryptoapiLog::with('wallet:id,user_id')->where(['network' => $responseNetwork, 'object_type' => 'wallet_address'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $responseAddress . '[[:>:]]'])->get();

                        	// user
                        	if (count($getCryptoSentApiLogSql) > 0)
	                        {
	                        	\Log::info('Users');
	                        	\Log::info('Users');
	                        	\Log::info('Users');
	                        	\Log::info('Users');

	                            $getNetworkCurrency = Currency::where(['code' => $responseNetwork])->first(['id']);

	                            $cryptoReceiverFromUnknown = User::where(['id' => $getCryptoSentApiLogSql[0]->wallet->user_id])->first();

	                            $transaction = new Transaction();
	                            $transaction->user_id             = $cryptoReceiverFromUnknown->id;
	                            $transaction->end_user_id         = NULL;
	                            $transaction->currency_id         = $getNetworkCurrency->id;
	                            $transaction->payment_method_id   = 9;
	                            $transaction->uuid                = unique_code();
	                            $transaction->transaction_type_id = Crypto_Received;
	                            $transaction->subtotal            = $responseAmountReceived;
	                            $transaction->total               = $responseAmountReceived;
	                            $transaction->status              = 'Pending';
	                            $transaction->save();

	                            $responseData['receiverAddress'] = json_decode($getCryptoSentApiLog[0]->payload)->address;
	                            $enCodedResponseData             = json_encode($responseData);

	                            $cryptoReceivedApiLogSql = new CryptoapiLog();
	                            $cryptoReceivedApiLogSql->payment_method_id    = 9;
	                            $cryptoReceivedApiLogSql->object_id            = $transaction->id;
	                            $cryptoReceivedApiLogSql->object_type          = 'crypto_received';
	                            $cryptoReceivedApiLogSql->network              = $responseNetwork;
	                            $cryptoReceivedApiLogSql->payload              = $enCodedResponseData;
	                            $cryptoReceivedApiLogSql->confirmations        = $responseConfirmations;
	                            $cryptoReceivedApiLogSql->save();
		                    }
		                    else
		                    {
		                    	\Log::info('Admin');
	                        	\Log::info('Admin');
	                        	\Log::info('Admin');
	                        	\Log::info('Admin');
		                    }
	                    }
	                }
                    else
                    {
                        $getCryptoSentApiLogSql = $getCryptoSentApiLog = CryptoapiLog::where(['network' => $responseNetwork, 'object_type' => 'crypto_sent'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $responseTxid . '[[:>:]]'])->get();

                        if (count($getCryptoSentApiLog) > 0)
                        {
                            $getCryptoSentApiLogObjectId        = $getCryptoSentApiLog[0]->object_id;
                            $getCryptoSentApiLogNetwork         = json_decode($getCryptoSentApiLog[0]->payload)->network;
                            $getCryptoSentApiLogTxid            = json_decode($getCryptoSentApiLog[0]->payload)->txid;
                            $getCryptoSentApiLogReceiverAddress = json_decode($getCryptoSentApiLog[0]->payload)->receiverAddress;
                            $getCryptoSentApiLogSenderAddress   = json_decode($getCryptoSentApiLog[0]->payload)->senderAddress;

                            if ($responseNetwork == $getCryptoSentApiLogNetwork && $responseTxid == $getCryptoSentApiLogTxid && $responseAddress == $getCryptoSentApiLogSenderAddress)
                            {
                                $receiverAddressCheck = CryptoapiLog::where(['network' => $responseNetwork, 'object_type' => 'wallet_address'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $getCryptoSentApiLogReceiverAddress . '[[:>:]]'])->get();

                                $cryptoCurrencySettings = CryptoCurrenciesSetting::where(['network' => $responseNetwork])->first(['network_credentials']);
                                $merchantAddress        = json_decode($cryptoCurrencySettings->network_credentials)->address;

                                if (count($receiverAddressCheck) == 0 && ($getCryptoSentApiLogReceiverAddress !=  $merchantAddress)) {
                                    $updateCryptoApiLogsConfirmationsSql = DB::update(DB::raw("UPDATE cryptoapi_logs SET confirmations = '$responseConfirmations' WHERE object_id = '$getCryptoSentApiLogObjectId' "));
                                }
                            }
                        }

                    }

	            }
	            else
	            {
	            	$getCryptoSentReceivedApiLogsSql = $getCryptoSentReceivedApiLogs = CryptoapiLog::where(['confirmations' => 1, 'network' => $responseNetwork])->whereIn('object_type', ['crypto_sent', 'crypto_received'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $responseTxid . '[[:>:]]'])->get();

	            	if (count($getCryptoSentReceivedApiLogsSql) > 0)
               		{
               			foreach ($getCryptoSentReceivedApiLogs as $getCryptoSentReceivedApiLog)
               			{
               				$getCryptoSentReceivedApiLogNetwork         = json_decode($getCryptoSentReceivedApiLog->payload)->network;
                        	$getCryptoSentReceivedApiLogTxid            = json_decode($getCryptoSentReceivedApiLog->payload)->txid;
                        	$getCryptoSentReceivedApiLogReceiverAddress = json_decode($getCryptoSentReceivedApiLog->payload)->receiverAddress;
                        	$getCryptoSentReceivedApiLogSenderAddress   = isset(json_decode($getCryptoSentReceivedApiLog->payload)->senderAddress) ? json_decode($getCryptoSentReceivedApiLog->payload)->senderAddress : '';

                        	if ($responseNetwork == $getCryptoSentReceivedApiLogNetwork && $responseTxid == $getCryptoSentReceivedApiLogTxid)
                        	{
                        		if (!empty($getCryptoSentReceivedApiLogSenderAddress) && ($responseAddress == $getCryptoSentReceivedApiLogReceiverAddress || $responseAddress == $getCryptoSentReceivedApiLogSenderAddress))
                        		{
                        			$getCryptoSentReceivedApiLogObjectId = $getCryptoSentReceivedApiLog->object_id;

	                        		$updateCryptoApiLogsConfirmationsSql = DB::raw(DB::update("UPDATE cryptoapi_logs SET confirmations = '$responseConfirmations' WHERE object_id = '$getCryptoSentReceivedApiLogObjectId' AND confirmations = 1 "));

	                        		$updateCryptoApiLogsConfirmations = DB::raw(DB::update("UPDATE transactions SET status = 'success' WHERE id = '$getCryptoSentReceivedApiLogObjectId'"));

	                        		$getAddressBalance = $this->cryptoCurrency->getUserCryptoAddressBalance($responseNetwork, $responseAddress);

	                        		$getWalletAddressCryptoApiLogSql = $getWalletAddressCryptoApiLog = CryptoapiLog::with(['wallet:id,user_id'])->where(['network' => $responseNetwork, 'object_type' => 'wallet_address'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $responseAddress . '[[:>:]]'])->get();

	                        		if (count($getWalletAddressCryptoApiLog) > 0)
	                        		{
	                        			$getWalletAddressCryptoApiLogWalletId = $getWalletAddressCryptoApiLog[0]->wallet->id;
	                        			$updateReceiverWalletBalanceSql = DB::raw(DB::update("UPDATE wallets SET balance = '$getAddressBalance' WHERE id = '$getWalletAddressCryptoApiLogWalletId' "));
	                        		}
                        		}
                        		else
                        		{
                        			$getCryptoSentReceivedApiLogObjectId = $getCryptoSentReceivedApiLog->object_id;

	                        		$updateCryptoApiLogsConfirmationsSql = DB::raw(DB::update("UPDATE cryptoapi_logs SET confirmations = '$responseConfirmations' WHERE object_id = '$getCryptoSentReceivedApiLogObjectId' AND confirmations = 1 "));

	                        		$updateCryptoApiLogsConfirmations = DB::raw(DB::update("UPDATE transactions SET status = 'success' WHERE id = '$getCryptoSentReceivedApiLogObjectId'"));

	                        		$getAddressBalance = $this->cryptoCurrency->getUserCryptoAddressBalance($responseNetwork, $responseAddress);

	                        		$getWalletAddressCryptoApiLogSql = $getWalletAddressCryptoApiLog = CryptoapiLog::with(['wallet:id,user_id'])->where(['network' => $responseNetwork, 'object_type' => 'wallet_address'])->whereRaw('payload REGEXP ?', ['[[:<:]]' . $responseAddress . '[[:>:]]'])->get();

	                        		if (count($getWalletAddressCryptoApiLog) > 0)
	                        		{
	                        			$getWalletAddressCryptoApiLogWalletId = $getWalletAddressCryptoApiLog[0]->wallet->id;
	                        			$updateReceiverWalletBalanceSql = DB::raw(DB::update("UPDATE wallets SET balance = '$getAddressBalance' WHERE id = '$getWalletAddressCryptoApiLogWalletId' "));
	                        		}
                        		}
                        	}
               			}
               		}
	            }
	        }
    	}
    }
}
