<?php

namespace App\Http\Controllers\Users;

use App\Repositories\CryptoCurrencyRepository;
use App\Http\Controllers\Controller;

class CryptoReceiveController extends Controller
{
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

    public function receiveCryptoCreate($walletCurrencyCode, $walletId)
    {
        //set the session for validating the action
        setActionSession();

        $walletCurrencyCode         = decrypt($walletCurrencyCode);
        $walletId                   = decrypt($walletId);
        $data['walletCurrencyCode'] = strtoupper($walletCurrencyCode);

        // Check crypto currency status
        $getCryptoCurrencyStatus = $this->cryptoCurrency->getCryptoCurrencyStatus($data['walletCurrencyCode']);
        if ($getCryptoCurrencyStatus == 'Inactive')
        {
            $data['message'] = $data['walletCurrencyCode'] . __(' is Inactive!');
            return view('user_dashboard.users.check_crypto_currency_status', $data);
        }
        else
        {
            //get user's wallet address
            $address         = $this->cryptoCurrency->getUserCryptoAddress($walletId);
            $data['address'] = encrypt($address);
            return view('user_dashboard.crypto.receive.create', $data);
        }
    }
}
