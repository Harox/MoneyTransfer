<?php

namespace App\Models;

use App\Http\Controllers\Users\EmailController;
use App\Http\Helpers\Common;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class CurrencyExchange extends Model
{
    protected $table    = 'currency_exchanges';
    public $timestamps  = true;
    protected $fillable = [
        'user_id',
        'from_wallet',
        'to_wallet',
        'currency_id',
        'uuid',
        'exchange_rate',
        'amount',
        'type',
        'status',
    ];

    //
    protected $helper;
    protected $email;
    public function __construct()
    {
        $this->helper = new Common();
        $this->email  = new EmailController();
    }
    //

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'transaction_reference_id', 'id');
    }

    /**
     * [all exchanges data]
     * @return [void] [query]
     */
    public function getAllExchanges()
    {
        return $this->leftJoin('currencies', 'currencies.id', '=', 'currency_exchanges.currency_id')
            ->select('currency_exchanges.*', 'currencies.code')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * [get users firstname and lastname for filtering]
     * @param  [integer] $user      [id]
     * @return [string]  [firstname and lastname]
     */
    public function getExchangesUserName($user)
    {
        return $this->leftJoin('users', 'users.id', '=', 'currency_exchanges.user_id')
            ->where(['currency_exchanges.user_id' => $user])
            ->select('users.first_name', 'users.last_name', 'users.id')
            ->first();
    }

    /**
     * [ajax response for search results]
     * @param  [string] $search   [query string]
     * @return [string] [distinct firstname and lastname]
     */
    public function getExchangesUsersResponse($search)
    {
        return $this->leftJoin('users', 'users.id', '=', 'currency_exchanges.user_id')
            ->where('users.first_name', 'LIKE', '%' . $search . '%')
            ->orWhere('users.last_name', 'LIKE', '%' . $search . '%')
            ->distinct('users.first_name')
            ->select('users.first_name', 'users.last_name', 'currency_exchanges.user_id')
            ->get();
    }

    public function getExchangesList($from, $to, $status, $currency, $user)
    {
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

        /*$condtionsWithoutDate start*/
        $condtionsWithoutDate = " detail.id != 0";
        if ($status != 'all')
        {
            $condtionsWithoutDate .= " AND detail.status = '$status'";
        }
        if ($currency != 'all')
        {
            if ($user)
            {
                $condtionsWithoutDate .= " AND detail.from_currecny_id = '$currency' AND detail.user_id = '$user' OR detail.to_currecny_id = '$currency' AND detail.user_id = '$user' ";
            }
            else
            {
                $condtionsWithoutDate .= " AND detail.from_currecny_id = '$currency' OR detail.to_currecny_id = '$currency'";
            }
        }
        else
        {
            if ($user)
            {
                $condtionsWithoutDate .= " AND detail.user_id = '$user' ";
            }
        }
        /*$condtionsWithoutDate end*/

        /*$condtionWithDate start*/
        $condtionWithDate = " detail.id != 0";
        if (!empty($from) || !empty($to))
        {
            $condtionWithDate .= " AND DATE(detail.created_at) BETWEEN '$from' AND '$to'";
        }
        if ($status != 'all')
        {
            $condtionWithDate .= " AND detail.status = '$status'";
        }
        if ($currency != 'all')
        {
            if ($user)
            {
                $condtionWithDate .= " AND detail.from_currecny_id = '$currency' AND detail.user_id = '$user' OR detail.to_currecny_id = '$currency' AND detail.user_id = '$user' ";
            }
            else
            {
                $condtionWithDate .= " AND detail.from_currecny_id = '$currency' OR detail.to_currecny_id = '$currency'";
            }
        }
        else
        {
            if ($user)
            {
                $condtionWithDate .= " AND detail.user_id = '$user' ";
            }
        }
        /*$condtionWithDate end*/

        if (!empty($date_range))
        {
            $data = DB::select("
                SELECT
                detail.*,
                fc.code as fc_code,
                tc.code as tc_code,
                fc.symbol as fc_symbol,
                tc.symbol as tc_symbol,
                users.first_name, users.last_name

                FROM(SELECT
                ce.*,
                from_wallet.currency_id as from_currecny_id,
                to_wallet.currency_id as to_currecny_id
                FROM currency_exchanges as ce
                LEFT JOIN wallets as from_wallet ON from_wallet.id = from_wallet
                LEFT JOIN wallets as to_wallet ON to_wallet.id = to_wallet)

                as detail

                LEFT JOIN currencies as fc ON fc.id = from_currecny_id
                LEFT JOIN currencies as tc ON tc.id = to_currecny_id
                LEFT JOIN users ON users.id = detail.user_id
                WHERE $condtionWithDate
            ");
        }
        else
        {
            $data = DB::select("SELECT
                detail.*,
                fc.code as fc_code,
                tc.code as tc_code,
                fc.symbol as fc_symbol,
                tc.symbol as tc_symbol,
                users.first_name, users.last_name

                FROM(SELECT
                ce.*,
                from_wallet.currency_id as from_currecny_id,
                to_wallet.currency_id as to_currecny_id
                FROM currency_exchanges as ce
                LEFT JOIN wallets as from_wallet ON from_wallet.id = from_wallet
                LEFT JOIN wallets as to_wallet ON to_wallet.id = to_wallet)

                as detail

                LEFT JOIN currencies as fc ON fc.id = from_currecny_id
                LEFT JOIN currencies as tc ON tc.id = to_currecny_id
                LEFT JOIN users ON users.id = detail.user_id
                WHERE $condtionsWithoutDate
            ");
        }
        return $data;
    }


    //common functions - starts
    public function createOrUpdateToWallet($arr)
    {
        if (empty($arr['toWallet']))
        {
            //Create To Wallet
            $toWallet              = new Wallet();
            $toWallet->user_id     = $arr['user_id'];
            $toWallet->currency_id = $arr['toWalletCurrencyId'];
            $toWallet->is_default  = 'No';
            $toWallet->balance     = $arr['finalAmount'];
            $toWallet->save();
            $toWallet = $toWallet->id;
        }
        else
        {
            //Update To Wallet
            $arr['toWallet']->balance = ($arr['toWallet']->balance + $arr['finalAmount']);
            $arr['toWallet']->save();
            $toWallet = $arr['toWallet']->id;
        }
        return $toWallet;
    }

    public function createCurrencyExchange($arr, $toWallet)
    {
        $currencyExchange                = new self;
        $currencyExchange->user_id       = $arr['user_id'];
        $currencyExchange->from_wallet   = $arr['fromWallet']->id;
        $currencyExchange->to_wallet     = $toWallet;
        $currencyExchange->currency_id   = $arr['toWalletCurrencyId'];
        $currencyExchange->uuid          = $arr['uuid'];
        $currencyExchange->exchange_rate = $arr['destinationCurrencyExRate'];
        $currencyExchange->amount        = $arr['amount'];
        $currencyExchange->fee           = $arr['fee'];
        $currencyExchange->type          = 'Out';
        $currencyExchange->status        = 'Success';
        $currencyExchange->save();
        return $currencyExchange;
    }

    public function createExchangeFromTransaction($arr, $currencyExchangeId)
    {
        $transaction                           = new Transaction();
        $transaction->user_id                  = $arr['user_id'];
        $transaction->currency_id              = $arr['fromWallet']->currency_id;
        $transaction->uuid                     = $arr['uuid'];
        $transaction->transaction_reference_id = $currencyExchangeId;
        $transaction->transaction_type_id      = Exchange_From;
        $transaction->subtotal                 = $arr['amount'];
        $transaction->percentage               = @$arr['charge_percentage'] ? @$arr['charge_percentage'] : 0;
        $transaction->charge_percentage        = @$arr['charge_percentage'] ? ($arr['formattedChargePercentage']) : 0;
        $transaction->charge_fixed             = @$arr['charge_fixed'] ? @$arr['charge_fixed'] : 0;
        $transaction->total                    = '-' . ($arr['amount'] + $arr['formattedChargePercentage'] + $arr['charge_fixed']);
        $transaction->status                   = 'Success';
        $transaction->save();
    }

    public function createExchangeToTransaction($arr, $currencyExchangeId)
    {
        $transaction                           = new Transaction();
        $transaction->user_id                  = $arr['user_id'];
        $transaction->currency_id              = $arr['toWalletCurrencyId'];
        $transaction->uuid                     = $arr['uuid'];
        $transaction->transaction_reference_id = $currencyExchangeId;
        $transaction->transaction_type_id      = Exchange_To;
        $transaction->subtotal                 = $arr['finalAmount'];
        $transaction->total                    = $arr['finalAmount'];
        $transaction->status                   = 'Success';
        $transaction->save();
    }

    public function updateFromWallet($arr)
    {
        $arr['fromWallet']->balance = ($arr['fromWallet']->balance - ($arr['amount'] + $arr['formattedChargePercentage'] + $arr['charge_fixed']));
        $arr['fromWallet']->save();
    }

    public function processExchangeMoneyConfirmation($arr = [], $clearSessionFrom)
    {
        $response = ['status' => 401];

        try {
            //Backend Validation - Wallet Balance Again Amount Check - Starts here
            $checkWalletBalance = $this->helper->checkWalletBalanceAgainstAmount($arr['amount'] + $arr['fee'], $arr['fromWallet']->currency_id, $arr['user_id']);
            if ($checkWalletBalance == true) {
                $response['exchangeCurrencyId'] = null;
                if ($clearSessionFrom == 'web') {
                    $response['ex']['message'] = __("Not have enough balance !");
                    return $response;
                }
                $response['ex']['message'] = "Sorry, not enough funds to perform the operation!";
                return $response;
                //Backend Validation - Wallet Balance Again Amount Check - Ends here
            } else {
                DB::beginTransaction();

                //Create or Update To Wallet
                $toWallet = self::createOrUpdateToWallet($arr);

                //Create Currency Exchange
                $currencyExchange = self::createCurrencyExchange($arr, $toWallet);

                //create Exchange From Transaction
                self::createExchangeFromTransaction($arr, $currencyExchange->id);

                //create Exchange To Transaction
                self::createExchangeToTransaction($arr, $currencyExchange->id);

                //Update From Wallet
                self::updateFromWallet($arr);

                DB::commit();

                $exchangeCurrencyId             = ($clearSessionFrom == 'web') ? $currencyExchange->id : $currencyExchange->id;
                $response['exchangeCurrencyId'] = $exchangeCurrencyId;

                //Admin Notification
                $notificationToAdmin = $this->helper->sendTransactionNotificationToAdmin('exchange', ['data' => $currencyExchange]);
                // for debugging only
                if (!empty($notificationToAdmin['ex'])) {
                    $response['ex']['source']  = $notificationToAdmin['exFrom'];
                    $response['ex']['message'] = $notificationToAdmin['ex']->getMessage();
                    return $response;
                }
                $response['status'] = 200;
                return $response;
            }
        } catch (Exception $e) {
            DB::rollBack();
            if ($clearSessionFrom == 'web') {
                $this->helper->clearSessionWithRedirect('transInfo', $e, 'exchange');
            }
            $response['exchangeCurrencyId'] = null;
            $response['ex']['message'] = $e->getMessage();
            return $response;
        }
    }
    //common functions - ends
}
