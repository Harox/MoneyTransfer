<?php

namespace App\Models;

use App\Http\Helpers\Common;
use App\Models\Transaction;
use App\Repositories\CryptoCurrencyRepository;
use Illuminate\Database\Eloquent\Model;

class CryptoapiLog extends Model
{
    protected $table    = 'cryptoapi_logs';
    protected $fillable = ['payment_method_id', 'object_id', 'object_type', 'network', 'payload', 'confirmations'];

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    //new
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'object_id');
    }

    //new
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'object_id');
    }

//common functions - starts
    public function getCryptoapiLog($constraints, $selectOptions)
    {
        return $this->where($constraints)->first($selectOptions);
    }
//common functions - ends

}
