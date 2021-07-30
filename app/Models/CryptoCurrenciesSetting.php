<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoCurrenciesSetting extends Model
{
    protected $table = 'crypto_currencies_settings';

    protected $fillable = ['payment_method_id', 'network', 'network_credentials', 'status'];

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function getCryptoCurrencySetting($constraints, $selectOptions)
    {
        return $this->where($constraints)->first($selectOptions);
    }

    public function getAllCryptoCurrencySettings($constraints, $selectOptions)
    {
        return $this->where($constraints)->get($selectOptions);
    }
}
