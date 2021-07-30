<?php

namespace App\Models;

use App\Models\Currency;
use DB;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table    = 'wallets';
    protected $fillable = ['user_id', 'currency_id', 'balance', 'is_default'];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function active_currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id')->where('status', 'Active');
    }

    public function currency_exchanges()
    {
        return $this->hasMany(CurrencyExchange::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function walletBalance()
    {
        $data = $this->leftJoin('currencies', 'currencies.id', '=', 'wallets.currency_id')
            ->select(DB::raw('SUM(wallets.balance) as amount,wallets.currency_id,currencies.type, currencies.code, currencies.symbol'))
            ->groupBy('wallets.currency_id')
            ->get();

        $array_data = [];
        foreach ($data as $row)
        {
            $array_data[$row->code] = $row->type != 'fiat' ? $row->amount : formatNumber($row->amount);
        }
        return $array_data;
    }

    //new
    public function cryptoapi_log()
    {
        return $this->hasOne(CryptoapiLog::class, 'object_id')->whereIn('object_type', ["wallet_address"]);
    }

    //Query for Mobile Application - starts
    public function getAvailableBalance($user_id)
    {
        $wallets = $this->with(['currency:id,type,code'])->where(['user_id' => $user_id])
            ->orderBy('balance', 'ASC')
            ->get(['currency_id', 'is_default', 'balance'])
            ->map(function ($wallet)
        {
                $arr['balance']    = $wallet->currency->type != 'fiat' ? $wallet->balance : formatNumber($wallet->balance);
                $arr['is_default'] = $wallet->is_default;
                $arr['curr_code']  = $wallet->currency->code;
                return $arr;
            });
        return $wallets;
    }
    //Query for Mobile Application - ends
}
