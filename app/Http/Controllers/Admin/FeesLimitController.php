<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Currency,
    TransactionType,
    PaymentMethod,
    FeesLimit
};

class FeesLimitController extends Controller
{
    protected $helper;
    protected $currency;

    public function __construct()
    {
        $this->helper   = new Common();
        $this->currency = new Currency();
    }

    public function limitList($tab, $id)
    {
        $data['menu']      = 'currency';
        $data['list_menu'] = $tab;

        if ($tab == 'transfer') {
            $tab = 'Transferred';
        } elseif ($tab == 'exchange') {
            $tab = 'Exchange_From';
        } elseif ($tab == 'request_payment') {
            $tab = 'Request_To';
        }

        $transaction_type         = TransactionType::where(['name' => ucfirst($tab)])->first(['id']);
        $transaction_type         = $transaction_type->id;
        $data['transaction_type'] = $transaction_type;
        $data['currency']         = $this->currency->getCurrency(['id' => $id], ['id', 'default', 'name']);
        $data['currencyList']     = $this->currency->getAllCurrencies(['status' => 'Active', 'type' => 'fiat'], ['id', 'default', 'name']);
        $currency_id              = $id;

        //check Decimal Thousand Money Format Preference
        $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);

        if ($tab == 'deposit') {
            $data['payment_methods'] = PaymentMethod::with(['fees_limit' => function ($q) use ($transaction_type, $currency_id)
            {
                $q->where('transaction_type_id', '=', $transaction_type)->where('currency_id', '=', $currency_id);
            }])
                ->whereNotIn('id', [9]) // BlockIo
                ->where(['status' => 'Active'])
                ->get(['id', 'name']);
            return view('admin.feeslimits.deposit_limit', $data);
        } else if ($tab == 'withdrawal') {
            $data['payment_methods'] = PaymentMethod::with(['fees_limit' => function ($q) use ($transaction_type, $currency_id)
            {
                $q->where('transaction_type_id', '=', $transaction_type)->where('currency_id', '=', $currency_id);
            }])
                ->whereNotIn('id', [2, 4, 5, 7, 8, 9]) // ['Stripe', '2Checkout', 'PayUMoney', 'Coinpayments', 'Payeer', 'BlockIo'] respectively
                ->where(['status' => 'Active'])
                ->get(['id', 'name']);
            return view('admin.feeslimits.deposit_limit', $data);
        } else {
            $data['feeslimit'] = $feeslimit = FeesLimit::where(['transaction_type_id' => $transaction_type, 'currency_id' => $currency_id])->first();
            
            return view('admin.feeslimits.deposit_limit_single', $data);
        }
    }

    public function updateDepositLimit(Request $request)
    {
        $payment_method_id = $request->payment_method_id;
        $min_limit         = $request->min_limit;
        $max_limit         = $request->max_limit;
        $charge_percentage = $request->charge_percentage;
        $charge_fixed      = $request->charge_fixed;
        $has_transaction   = $request->has_transaction;
        if ($request->transaction_type == 1 || $request->transaction_type == 2) {
            foreach ($payment_method_id as $key => $value) {
                $feeslimit = FeesLimit::where(['transaction_type_id' => $request->transaction_type, 'currency_id' => $request->currency_id, 'payment_method_id' => $value])->first();
                if (empty($feeslimit)) {
                    $feeslimit                      = new FeesLimit();
                    $feeslimit->currency_id         = $request->currency_id;
                    $feeslimit->transaction_type_id = $request->transaction_type;
                    $feeslimit->payment_method_id   = $value;
                    $feeslimit->charge_percentage   = $charge_percentage[$key];
                    $feeslimit->charge_fixed        = $charge_fixed[$key];
                    $feeslimit->min_limit           = ($min_limit[$key] == null) ? 1.00000000 : $min_limit[$key];
                    $feeslimit->max_limit           = $max_limit[$key];

                    if ($request->defaultCurrency) {
                        $feeslimit->has_transaction = 'Yes';
                    } else {
                        $feeslimit->has_transaction = isset($has_transaction[$value]) ? $has_transaction[$value] : 'No';
                    }
                    $feeslimit->save();
                } else {
                    $feeslimit                      = FeesLimit::where(['transaction_type_id' => $request->transaction_type, 'currency_id' => $request->currency_id, 'payment_method_id' => $value])->first();
                    $feeslimit->currency_id         = $request->currency_id;
                    $feeslimit->transaction_type_id = $request->transaction_type;
                    $feeslimit->payment_method_id   = $value;
                    $feeslimit->charge_percentage   = $charge_percentage[$key];
                    $feeslimit->charge_fixed        = $charge_fixed[$key];
                    $feeslimit->min_limit           = ($min_limit[$key] == null) ? 1.00000000 : $min_limit[$key];
                    $feeslimit->max_limit           = $max_limit[$key];
                    if ($request->defaultCurrency) {
                        $feeslimit->has_transaction = 'Yes';
                    } else {
                        $feeslimit->has_transaction = isset($has_transaction[$value]) ? $has_transaction[$value] : 'No';
                    }
                    $feeslimit->save();
                }
            }
        } else {
            $feeslimit = FeesLimit::where(['transaction_type_id' => $request->transaction_type, 'currency_id' => $request->currency_id])->first();
            if (empty($feeslimit)) {
                $feeslimit                      = new FeesLimit();
                $feeslimit->currency_id         = $request->currency_id;
                $feeslimit->transaction_type_id = $request->transaction_type;
                $feeslimit->charge_percentage   = $charge_percentage;
                $feeslimit->charge_fixed        = $charge_fixed;
                $feeslimit->min_limit           = ($min_limit == null) ? 1.00000000 : $min_limit;
                $feeslimit->max_limit           = $max_limit;

                if ($request->defaultCurrency) {
                    $feeslimit->has_transaction = 'Yes';
                } else {
                    $feeslimit->has_transaction = isset($request->has_transaction) ? $request->has_transaction : 'No';
                }
                $feeslimit->save();
            } else {
                $feeslimit                      = FeesLimit::find($request->id);
                $feeslimit->currency_id         = $request->currency_id;
                $feeslimit->transaction_type_id = $request->transaction_type;
                $feeslimit->charge_percentage   = $charge_percentage;
                $feeslimit->charge_fixed        = $charge_fixed;
                $feeslimit->min_limit           = ($min_limit == null) ? 1.00000000 : $min_limit;
                $feeslimit->max_limit           = $max_limit;
                if ($request->defaultCurrency) {
                    $feeslimit->has_transaction = 'Yes';
                } else {
                    $feeslimit->has_transaction = isset($request->has_transaction) ? $request->has_transaction : 'No';
                }
                $feeslimit->save();
            }
        }
        $this->helper->one_time_message('success', 'Currency Settings Updated Successfully!');

        return redirect('admin/settings/feeslimit/' . $request->tabText . '/' . $request->currency_id);
    }

    public function getFesslimitDetails(Request $request)
    {
        $data             = [];
        $transaction_type = $request->transaction_type;
        $currency_id      = $request->currency_id;
        if ($transaction_type == 1) {
            $feeslimit = PaymentMethod::with(['fees_limit' => function ($q) use ($transaction_type, $currency_id)
            {
                $q->where('transaction_type_id', '=', $transaction_type)->where('currency_id', '=', $currency_id);
            }])
                ->whereNotIn('id', [9]) // BlockIo
                ->where(['status' => 'Active'])->get(['id', 'name']);
        } else if ($transaction_type == 2) {
            $feeslimit = PaymentMethod::with(['fees_limit' => function ($q) use ($transaction_type, $currency_id)
            {
                $q->where('transaction_type_id', '=', $transaction_type)->where('currency_id', '=', $currency_id);
            }])
                ->whereNotIn('id', [2, 4, 5, 7, 8, 9]) // ['Stripe', '2Checkout', 'PayUMoney', 'Coinpayments', 'Payeer', 'BlockIo'] respectively
                ->where(['status' => 'Active'])->get(['id', 'name']);
        } else {
            $feeslimit = FeesLimit::where(['transaction_type_id' => $transaction_type, 'currency_id' => $currency_id])->first();
        }

        if (empty($feeslimit)) {
            $data['status'] = 401;
        } else {
            $data['status']    = 200;
            $data['feeslimit'] = $feeslimit;
        }
        return $data;
        exit();
    }

    public function getSpecificCurrencyDetails(Request $request)
    {
        $data             = [];
        $transaction_type = $request->transaction_type;
        $currency_id      = $request->currency_id;

        if ($transaction_type == 1) {
            $feeslimit = PaymentMethod::with(['fees_limit' => function ($q) use ($transaction_type, $currency_id)
            {
                $q->where('transaction_type_id', '=', $transaction_type)->where('currency_id', '=', $currency_id);
            }])
                ->whereNotIn('id', [9]) // BlockIo
                ->where(['status' => 'Active'])
                ->get(['id', 'name']);
        } else if ($transaction_type == 2) {
            $feeslimit = PaymentMethod::with(['fees_limit' => function ($q) use ($transaction_type, $currency_id)
            {
                $q->where('transaction_type_id', '=', $transaction_type)->where('currency_id', '=', $currency_id);
            }])
                ->whereNotIn('id', [2, 4, 5, 7, 8, 9]) // ['Stripe', '2Checkout', 'PayUMoney', 'Coinpayments', 'Payeer', 'BlockIo'] respectively
                ->where(['status' => 'Active'])
                ->get(['id', 'name']);
        } else {
            $feeslimit = FeesLimit::where(['transaction_type_id' => $transaction_type, 'currency_id' => $currency_id])->first();
        }

        $currency = $this->currency->getCurrency(['id' => $currency_id], ['id', 'name', 'symbol']);
        if ($currency && $feeslimit) {
            $data['status']    = 200;
            $data['currency']  = $currency;
            $data['feeslimit'] = $feeslimit;
        } else {
            $data['status']   = 401;
            $data['currency'] = $currency;
        }
        return $data;
        exit();
    }
}
