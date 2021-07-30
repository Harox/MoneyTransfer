<?php

namespace App\Http\Controllers\Api;

use App\Repositories\CryptoCurrencyRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;

class CryptoCurrencyController extends Controller
{
    public $successStatus      = 200;
    public $unauthorisedStatus = 401;
    /**
     * The CryptoCurrency repository instance.
     *
     * @var CryptoCurrencyRepository
     */
    protected $cryptoCurrency;

    public function __construct()
    {
        $this->cryptoCurrency = new CryptoCurrencyRepository();
    }

    /**
     * Get user's crypto wallets
     * @return wallet balance
     */
    public function getUserCryptoWallets()
    {
        $wallets = Wallet::whereHas('currency', function ($q)
        {
            $q->where(['type' => 'crypto']);
        })
        ->with(['currency:id,code,status'])
        ->where(['user_id' => request('user_id')])
        ->orderBy('balance', 'ASC')
        ->get(['id', 'currency_id', 'is_default', 'balance'])
        ->map(function ($wallet)
        {
            $arr['wallet_id']   = $wallet->id;
            $arr['balance']     = $wallet->balance;
            $arr['is_default']  = $wallet->is_default;
            $arr['curr_code']   = $wallet->currency->code;
            $arr['curr_status'] = $wallet->currency->status;
            return $arr;
        });
        $success['status'] = $this->successStatus;
        return response()->json(['success' => $success, 'wallets' => $wallets], $this->successStatus);
    }

    /**
     * Get User Crypto Wallet Address
     * @return wallet-address
     */
    public function getUserCryptoWalletAddress()
    {
        //get user's wallet address
        $walletAddress = $this->cryptoCurrency->getUserCryptoAddress(request('wallet_id'));
        return response()->json([
            'status'         => $this->successStatus,
            'wallet-address' => $walletAddress,
        ]);
    }

    /**
     * Get Enabled Currencies Preference
     * @return status, message
     */
    public function getEnabledCurrenciesPreference()
    {
        $getCurrenciesPreference = $this->cryptoCurrency->getCurrenciesPreference();
        if ($getCurrenciesPreference->value == "fiat")
        {
            return response()->json(['status' => $this->successStatus, 'message' => 'The system adminstrator has disabled crypto currency!']);
        }
        else
        {
            return response()->json(['status' => $this->unauthorisedStatus]);
        }
    }

    public function getCryptoCurrencyStatus()
    {
        // Check crypto currency status
        $getCryptoCurrencyStatus = $this->cryptoCurrency->getCryptoCurrencyStatus(request('cryptoCurrencyCode'));
        if ($getCryptoCurrencyStatus == 'Inactive')
        {
            return response()->json(['status' => $this->successStatus]);
        }
        else
        {
            return response()->json(['status' => $this->unauthorisedStatus]);
        }
    }
}
