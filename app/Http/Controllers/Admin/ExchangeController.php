<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\CurrencyExchangesDataTable;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\{Currency,
    CurrencyExchange,
    Transaction,
    Wallet
};

class ExchangeController extends Controller
{
    protected $helper;
    protected $exchange;

    public function __construct()
    {
        $this->helper   = new Common();
        $this->exchange = new CurrencyExchange();
    }

    public function index(CurrencyExchangesDataTable $dataTable)
    {
        $data['menu'] = 'exchanges';

        $data['exchanges_status'] = $exchanges_status = $this->exchange->select('status')->groupBy('status')->get();

        $data['exchanges_currency'] = $exchanges_currency = $this->exchange->join('wallets', function ($join)
        {
            $join->on('wallets.id', '=', 'currency_exchanges.from_wallet')->orOn('wallets.id', '=', 'currency_exchanges.to_wallet');
        })
            ->groupBy('wallets.currency_id')->select('wallets.currency_id', 'wallets.id as wallet_id')->get();

        $data['from']     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['status']   = isset(request()->status) ? request()->status : 'all';
        $data['currency'] = isset(request()->currency) ? request()->currency : 'all';
        $data['user']     = $user    = isset(request()->user_id) ? request()->user_id : null;
        $data['getName']  = $getName = $this->exchange->getExchangesUserName($user);

        return $dataTable->render('admin.exchange.list', $data);
    }

    public function exchangesUserSearch(Request $request)
    {
        $search = $request->search;
        $user   = $this->exchange->getExchangesUsersResponse($search);

        $res = [
            'status' => 'fail',
        ];
        if (count($user) > 0)
        {
            $res = [
                'status' => 'success',
                'data'   => $user,
            ];
        }
        return json_encode($res);
    }

    public function exchangeCsv()
    {
        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $exchanges = $this->exchange->getExchangesList($from, $to, $status, $currency, $user);
        $data['exchanges'] = $exchanges = collect($exchanges)->sortByDesc('id');

        $datas = [];
        if (!empty($exchanges))
        {
            foreach ($exchanges as $key => $value)
            {
                $datas[$key]['Date'] = dateFormat($value->created_at);

                $datas[$key]['User'] = $value->first_name . ' ' . $value->last_name;

                if ($value->type == 'Out')
                {
                    if ($value->amount > 0)
                    {
                        $datas[$key]['Amount'] = formatNumber($value->amount);
                    }
                }
                elseif ($value->type == 'In')
                {
                    if ($value->amount > 0)
                    {
                        $datas[$key]['Amount'] = formatNumber($value->amount);
                    }
                }
                $datas[$key]['Fees'] = ($value->fee == 0) ? "-" : formatNumber($value->fee);

                //Total start
                if ($value->type == 'Out')
                {
                    if (($value->fee + $value->amount) > 0)
                    {
                        $datas[$key]['Total'] = '-'.formatNumber($value->fee + $value->amount);
                    }
                    else
                    {
                        $datas[$key]['Total'] = '-';
                    }
                }
                elseif ($value->type == 'In')
                {
                    if (($value->fee + $value->amount) > 0)
                    {
                        $datas[$key]['Total'] = '-'.formatNumber($value->fee + $value->amount);
                    }
                    else
                    {
                        $datas[$key]['Total'] = '-';
                    }
                }
                //Total end

                // $datas[$key]['Rate'] = moneyFormat($value->tc_symbol, formatNumber($value->exchange_rate));
                $datas[$key]['Rate'] = moneyFormat($value->tc_symbol, (float)($value->exchange_rate));

                if ($value->type == 'Out')
                {
                    $datas[$key]['From'] = $value->fc_code;
                }
                else
                {
                    $datas[$key]['From'] = $value->fc_code;
                }

                if ($value->type == 'In')
                {
                    $datas[$key]['To'] = $value->tc_code;
                }
                else
                {
                    $datas[$key]['To'] = $value->tc_code;
                }
                $datas[$key]['Status'] = ($value->status == 'Blocked') ? 'Cancelled' : $value->status;
            }
        }
        else
        {
            $datas[0]['Date']   = '';
            $datas[0]['User']   = '';
            $datas[0]['Amount'] = '';
            $datas[0]['Fees']   = '';
            $datas[0]['Total']   = '';
            $datas[0]['Rate']   = '';
            $datas[0]['From']   = '';
            $datas[0]['To']     = '';
            $datas[0]['Status'] = '';
        }
        return Excel::create('exchanges_list_' . time() . '', function ($excel) use ($datas)
        {
            $excel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $excel->sheet('mySheet', function ($sheet) use ($datas)
            {
                $sheet->cells('A1:I1', function ($cells)
                {
                    $cells->setFontWeight('bold');
                });
                $sheet->fromArray($datas);
            });
        })->download();
    }

    public function exchangePdf()
    {
        $data['company_logo'] = getCompanyLogoWithoutSession();

        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $exchanges = $this->exchange->getExchangesList($from, $to, $status, $currency, $user);
        $data['exchanges'] = $exchanges = collect($exchanges)->sortByDesc('id');

        if (isset($from) && isset($to))
        {
            $data['date_range'] = $from . ' To ' . $to;
        }
        else
        {
            $data['date_range'] = 'N/A';
        }

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);

        $mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A3',
            'orientation' => 'P',
        ]);

        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;

        $mpdf->WriteHTML(view('admin.exchange.exchanges_report_pdf', $data));

        $mpdf->Output('exchanges_report_' . time() . '.pdf', 'D');
    }

    public function edit($id)
    {
        $data['menu']     = 'exchanges';
        $data['exchange'] = $exchange = CurrencyExchange::find($id);

        $data['transaction'] = $transaction = Transaction::select('transaction_type_id', 'status', 'transaction_reference_id', 'percentage', 'charge_percentage', 'charge_fixed', 'uuid')
            ->where(['transaction_reference_id' => $exchange->id, 'uuid' => $exchange->uuid])
            ->whereIn('transaction_type_id', [Exchange_From, Exchange_To])
            ->first();

        return view('admin.exchange.edit', $data);
    }

    public function update(Request $request)
    {
        if ($request->type == "Out")
        {
            if ($request->status == 'Success')
            {
                if ($request->transaction_status == 'Success') //current status
                {
                    $this->helper->one_time_message('success', 'Exchange is already Successfull!');
                    return redirect('admin/exchanges');
                }
                elseif ($request->transaction_status == 'Blocked')
                {
                    $exchange         = CurrencyExchange::find($request->id);
                    $exchange->status = $request->status;
                    $exchange->save();

                    // Exchange_From
                    Transaction::where([
                        'user_id'                  => $request->user_id,
                        'currency_id'              => $exchange->fromWallet->currency->id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    // Exchange_To
                    Transaction::where([
                        'user_id'                  => $request->user_id,
                        'currency_id'              => $exchange->toWallet->currency->id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Exchange_To,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sender wallet entry update
                    $from_wallet = Wallet::where([
                        'id'          => $exchange->from_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->fromWallet->currency->id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'id'          => $exchange->from_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->fromWallet->currency->id,
                    ])->update([
                        'balance' => $from_wallet->balance - $request->total,
                    ]);

                    //receiver wallet entry update
                    $to_wallet = Wallet::where([
                        'id'          => $exchange->to_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->toWallet->currency->id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'id'          => $exchange->to_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->toWallet->currency->id,
                    ])->update([
                        'balance' => $to_wallet->balance + ($request->amount * $exchange->exchange_rate),
                    ]);
                    $this->helper->one_time_message('success', 'Exchange Updated Successfully!');
                    return redirect('admin/exchanges');
                }
            }
            elseif ($request->status == 'Blocked')
            {
                if ($request->transaction_status == 'Blocked') //current status
                {
                    $this->helper->one_time_message('success', 'Exchange is already Blocked!');
                    return redirect('admin/exchanges');
                }
                elseif ($request->transaction_status == 'Success') //current status
                {
                    $exchange         = CurrencyExchange::find($request->id);
                    $exchange->status = $request->status;
                    $exchange->save();

                    // // Exchange_From
                    Transaction::where([
                        'user_id'                  => $request->user_id,
                        'currency_id'              => $exchange->fromWallet->currency->id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    // // Exchange_To
                    Transaction::where([
                        'user_id'                  => $request->user_id,
                        'currency_id'              => $exchange->toWallet->currency->id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Exchange_To,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sent wallet entry update
                    $from_wallet = Wallet::where([
                        'id'          => $exchange->from_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->fromWallet->currency->id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'id'          => $exchange->from_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->fromWallet->currency->id,
                    ])->update([
                        'balance' => $from_wallet->balance + $request->total,
                    ]);

                    //received wallet entry update
                    $to_wallet = Wallet::where([
                        'id'          => $exchange->to_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->toWallet->currency->id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'id'          => $exchange->to_wallet,
                        'user_id'     => $request->user_id,
                        'currency_id' => $exchange->toWallet->currency->id,
                    ])->update([
                        'balance' => $to_wallet->balance - ($request->amount * $exchange->exchange_rate),
                    ]);
                    $this->helper->one_time_message('success', 'Exchange Updated Successfully!');
                    return redirect('admin/exchanges');
                }
            }
        }
    }
}
