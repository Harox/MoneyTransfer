<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $table = 'deposits';

    protected $fillable = ['user_id', 'currency_id', 'payment_method_id', 'bank_id', 'file_id', 'uuid', 'charge_percentage', 'charge_fixed', 'amount', 'status'];

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'transaction_reference_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //new
    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    //new
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }


    /*
    end of relationships
     */

    /**
     * [get users firstname and lastname for filtering]
     * @param  [integer] $user      [id]
     * @return [string]  [firstname and lastname]
     */
    public function getDepositsUsersName($user)
    {
        return $this->leftJoin('users', 'users.id', '=', 'deposits.user_id')
            ->where(['user_id' => $user])
            ->select('users.first_name', 'users.last_name', 'users.id')
            ->first();
    }

    /**
     * [ajax response for search results]
     * @param  [string] $search   [query string]
     * @return [string] [distinct firstname and lastname]
     */
    public function getDepositsUsersResponse($search)
    {
        return $this->leftJoin('users', 'users.id', '=', 'deposits.user_id')
            ->where('users.first_name', 'LIKE', '%' . $search . '%')
            ->orWhere('users.last_name', 'LIKE', '%' . $search . '%')
            ->distinct('users.first_name')
            ->select('users.first_name', 'users.last_name', 'deposits.user_id')
            ->get();
    }

    /**
     * [Deposits Filtering Results]
     * @param  [null/date] $from     [start date]
     * @param  [null/date] $to       [end date]
     * @param  [string]    $status   [Status]
     * @param  [string]    $pm       [Payment Methods]
     * @param  [string]    $currency [Currency]
     * @param  [null/id]   $user     [User ID]
     * @return [query]     [All Query Results]
     */
    public function getDepositsList($from, $to, $status, $currency, $pm, $user)
    {
        $conditions = [];

        if (empty($from) || empty($to)) {
            $date_range = null;
        } else if (empty($from)) {
            $date_range = null;
        } else if (empty($to)) {
            $date_range = null;
        } else {
            $date_range = 'Available';
        }

        if (!empty($status) && $status != 'all') {
            $conditions['status'] = $status;
        }
        if (!empty($pm) && $pm != 'all') {
            $conditions['payment_method_id'] = $pm;
        }
        if (!empty($currency) && $currency != 'all') {
            $conditions['currency_id'] = $currency;
        }
        if (!empty($user)) {
            $conditions['user_id'] = $user;
        }

        //
        $deposits = $this->with([
            'user:id,first_name,last_name',
            'currency:id,code',
            'payment_method:id,name',
        ])->where($conditions);

        if (!empty($date_range)) {
            $deposits->where(function ($query) use ($from, $to)
            {
                $query->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
            })
            ->select('deposits.*');
        } else {
            $deposits->select('deposits.*');
        }
        return $deposits;
    }
}
