<?php

namespace App\Http\Controllers\Admin;

use App\Repositories\CryptoCurrencyRepository;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\Common;
use Illuminate\Http\Request;
use blockIoPHP\BlockIo;
use App\Models\{CryptoCurrenciesSetting, 
    Currency
};
use Exception;

class CryptoCurrenciesSettingController extends Controller
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

    /**
     * Shows blockIo Crypto Currencies Settings based on network type
     * param  string $network [btc/ltc/doge]
     */
    public function viewBlockIoSettings($network = 'BTC')
    {
        $data['menu'] = 'blockio-settings';

        $cryptoCurrenciesSetting = $this->cryptoCurrency->getCryptoCurrenciesSetting($network, 'All', ['*']);
        if (!empty($cryptoCurrenciesSetting))
        {
            $data['cryptoCurrenciesSetting'] = $cryptoCurrenciesSetting;
            $data['network']                 = $cryptoCurrenciesSetting->network;
        }
        else
        {
            $data['network'] = $network;
        }
        return view('admin.settings.crypto_currencies_settings.list', $data);
    }

    /**
     * Updates blockIo Crypto Currencies Settings based on network type
     * param  Request $request
     */
    public function updateBlockIoSettings(Request $request)
    {
        $network = $request->network;
        $api_key = $request->api_key;
        $pin     = $request->pin;
        $address = $request->address;

        // .env - APP_DEMO - check
        if (checkDemoEnvironment() == true)
        {
            $this->helper->one_time_message('error', 'CryptoCurrency Settings cannot be updated on demo site.');
            return redirect("admin/settings/crypto-currencies-settings/$network");
        }

        $rules = array(
            'api_key' => 'required',
            'pin'     => 'required',
            'address' => 'required',
            'status'  => 'required',
        );
        $fieldNames = array(
            'api_key' => 'Api Key',
            'pin'     => 'Pin',
            'address' => 'Address',
            'status'  => 'Status',
        );

        $validator = \Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }
        else
        {
            //Backend merchant address balance validation
            $checkMerchantNetworkAddress = $this->cryptoCurrency->checkMerchantNetworkAddressValidity($api_key, $pin, $address);
            if (!$checkMerchantNetworkAddress['status'])
            {
                $this->helper->one_time_message('error', 'Invalid merchant address');
                return redirect("admin/settings/crypto-currencies-settings/$network");
            }
            else
            {
                try
                {
                    DB::beginTransaction();

                    $getCryptoCurrencySetting = $this->cryptoCurrency->getCryptoCurrenciesSetting($network, 'All', ['*']);
                    $cryptoCurrenciesSetting                      = !empty($getCryptoCurrencySetting) ? $getCryptoCurrencySetting : new cryptoCurrenciesSetting();
                    $cryptoCurrenciesSetting->payment_method_id   = 9;
                    $cryptoCurrenciesSetting->network             = $this->cryptoCurrency->getCryptoCurrencyNetwork($api_key, $pin);
                    $blockIoBtcArr                                = [];
                    $blockIoBtcArr['api_key']                     = $api_key;
                    $blockIoBtcArr['pin']                         = $pin;
                    $blockIoBtcArr['address']                     = $address;
                    $cryptoCurrenciesSetting->network_credentials = json_encode($blockIoBtcArr);
                    $cryptoCurrenciesSetting->status              = $request->status;
                    $cryptoCurrenciesSetting->save();

                    // Update Crypto Currency (both normal & testnets) Status (if exists)
                    $currency = $this->currency->where(function ($q) use ($network)
                    {
                        $network = str_replace("TEST", "", $network);
                        $q->where(['code' => $network])->orWhere(['code' => $network . 'TEST']);
                    })
                        ->where(['type' => 'crypto'])
                        ->first(['id', 'name', 'code', 'status']);

                    if (!empty($currency))
                    {
                        // Update existing currency name, according to crypto currency setting network
                        switch ($network)
                        {
                            case 'BTC':
                                $currency->name = "Bitcoin";
                                break;
                            case 'BTCTEST':
                                $currency->name = 'Bitcoin (TESTNET!)';
                                break;
                            case 'LTC':
                                $currency->name = 'Litecoin';
                                break;
                            case 'LTCTEST':
                                $currency->name = 'Litecoin (TESTNET!)';
                                break;
                            case 'DOGE':
                                $currency->name = 'Dogecoin';
                                break;
                            case 'DOGETEST':
                                $currency->name = 'Dogecoin (TESTNET!)';
                                break;
                        }

                        // Update existing currency code, according to crypto currency setting network
                        $currency->code = $network;

                        // Update existing currency status, according to crypto currency setting network
                        $currency->status = $request->status;
                        $currency->save();
                    }

                    DB::commit();



                    $this->helper->one_time_message('success', 'CryptoCurrency Setting Updated Successfully!');
                    return redirect("admin/settings/crypto-currencies-settings/$network");
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                    $this->helper->one_time_message('error', $e->getMessage());
                    return redirect("admin/settings/crypto-currencies-settings/$network");
                }
            }
        }
    }

    //Check Merchant Network Address Validity
    public function checkMerchantNetworkAddress(Request $request)
    {
        try {
            $api_key                     = $request->api_key;
            $pin                         = $request->pin;
            $address                     = $request->address;
            $checkMerchantNetworkAddress = $this->cryptoCurrency->checkMerchantNetworkAddressValidity($api_key, $pin, $address);
            if (!$checkMerchantNetworkAddress['status'])
            {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Invalid merchant address',
                ]);
            }
            return response()->json([
                'status'  => 200,
                'network' => $checkMerchantNetworkAddress['network'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
