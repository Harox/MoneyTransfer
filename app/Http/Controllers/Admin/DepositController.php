<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\DataTable;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Deposit,
    Transaction,
    Wallet
};

class DepositController extends Controller
{
    protected $helper;
    protected $deposit;

    public function __construct()
    {
        $this->helper  = new Common();
        $this->deposit = new Deposit();
    }

    public function index(DataTable $dataTable)
    {
        $data['menu'] = '';

        $data['d_status']     = $d_status     = $this->deposit->select('status')->groupBy('status')->get();
        $data['d_currencies'] = $d_currencies = $this->deposit->with('currency:id,code')->select('currency_id')->groupBy('currency_id')->get();
        $data['d_pm']         = $d_pm         = $this->deposit->with('payment_method:id,name')->select('payment_method_id')->whereNotNull('payment_method_id')->groupBy('payment_method_id')->get();

        $data['from']     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['status']   = isset(request()->status) ? request()->status : 'all';
        $data['currency'] = isset(request()->currency) ? request()->currency : 'all';
        $data['pm']       = isset(request()->payment_methods) ? request()->payment_methods : 'all';
        $data['user']     = $user    = isset(request()->user_id) ? request()->user_id : null;
        $data['getName']  = $getName = $this->deposit->getUsersName($user);

        return $dataTable->render('admin..list', $data);
    }

    public function UserSearch(Request $request)
    {
        $search = $request->search;
        $user   = $this->deposit->getUsersResponse($search);

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

    public function depositCsv()
    {
        $from     = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to       = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status   = isset(request()->status) ? request()->status : null;

        $pm       = isset(request()->payment_methods) ? request()->payment_methods : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user     = isset(request()->user_id) ? request()->user_id : null;

        $data[''] = $ = $this->deposit->getList($from, $to, $status, $currency, $pm, $user)->orderBy('id', 'desc')->get();

        $datas = [];
        if (!empty($))
        {
            foreach ($ as $key => $value)
            {
                $datas[$key]['Date'] = dateFormat($value->created_at);

                $datas[$key]['User'] = isset($value->user) ? $value->user->first_name . ' ' . $value->user->last_name : "-";

                $datas[$key]['Amount'] = formatNumber($value->amount);

                $datas[$key]['Fees'] = ($value->charge_percentage == 0) && ($value->charge_fixed == 0) ? '-' : formatNumber($value->charge_percentage + $value->charge_fixed);

                $datas[$key]['Total'] = '+'.formatNumber($value->amount + ($value->charge_percentage + $value->charge_fixed));

                $datas[$key]['Currency'] = $value->currency->code;

                $datas[$key]['Payment Method'] = ($value->payment_method->name == 'Mts' ? getCompanyName() : $value->payment_method->name);

                $datas[$key]['Status'] = ($value->status == 'Blocked') ? 'Cancelled' : $value->status;
            }
        }
        else
        {
            $datas[0]['Date']           = '';
            $datas[0]['User']           = '';
            $datas[0]['Amount']         = '';
            $datas[0]['Fees']           = '';
            $datas[0]['Total']          = '';
            $datas[0]['Currency']       = '';
            $datas[0]['Payment Method'] = '';
            $datas[0]['Status']         = '';
        }

        return Excel::create('deposit_list_' . time() . '', function ($excel) use ($datas)
        {
            $excel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $excel->sheet('mySheet', function ($sheet) use ($datas)
            {
                $sheet->cells('A1:H1', function ($cells)
                {
                    $cells->setFontWeight('bold');
                });
                $sheet->fromArray($datas);
            });
        })->download();
    }

    public function depositPdf()
    {
        $data['company_logo'] = getCompanyLogoWithoutSession();

        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $pm = isset(request()->payment_methods) ? request()->payment_methods : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $data[''] = $ = $this->deposit->getList($from, $to, $status, $currency, $pm, $user)->orderBy('id', 'desc')->get();

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

        $mpdf->WriteHTML(view('admin.._report_pdf', $data));

        $mpdf->Output('_report_' . time() . '.pdf', 'D');
    }

    public function edit($id)
    {
        $data['menu']    = '';
        $data['deposit'] = $deposit = Deposit::find($id);

        $data['transaction'] = $transaction = Transaction::select('transaction_type_id', 'status', 'transaction_reference_id', 'percentage')
            ->where(['transaction_reference_id' => $deposit->id, 'status' => $deposit->status, 'transaction_type_id' => Deposit])
            ->first();

        return view('admin..edit', $data);
    }

    public function update(Request $request)
    {
        //Deposit
        if ($request->transaction_type == 'Deposit')
        {
            if ($request->status == 'Pending') //requested status
            {
                if ($request->transaction_status == 'Pending')
                {
                    $this->helper->one_time_message('success', 'Deposit is already Pending!');
                    return redirect('admin/');
                }
                elseif ($request->transaction_status == 'Success')
                {
                    $         = Deposit::find($request->id);
                    $->status = $request->status;
                    $->save();

                    $tt = Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->update([
                        'balance' => $current_balance->balance - $request->amount,
                    ]);
                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/');
                }
                elseif ($request->transaction_status == 'Blocked')
                {
                    $         = Deposit::find($request->id);
                    $->status = $request->status;
                    $->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);
                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/');
                }
            }
            elseif ($request->status == 'Success')
            {
                if ($request->transaction_status == 'Success') //current status
                {
                    $this->helper->one_time_message('success', 'Deposit is already Successfull!');
                    return redirect('admin/');
                }
                elseif ($request->transaction_status == 'Blocked') //current status
                {
                    $         = Deposit::find($request->id);
                    $->status = $request->status;
                    $->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->select('balance')->first();

                    $update_wallet_for_deposit = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->update([
                        'balance' => $current_balance->balance + $request->amount,
                    ]);
                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/');
                }
                elseif ($request->transaction_status == 'Pending')
                {
                    $         = Deposit::find($request->id);
                    $->status = $request->status;
                    $->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->update([
                        'balance' => $current_balance->balance + $request->amount,
                    ]);
                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/');
                }
            }
            elseif ($request->status == 'Blocked')
            {
                if ($request->transaction_status == 'Blocked') //current status
                {
                    $this->helper->one_time_message('success', 'Deposit is already Blocked!');
                    return redirect('admin/');
                }
                elseif ($request->transaction_status == 'Pending') //current status
                {
                    $         = Deposit::find($request->id);
                    $->status = $request->status;
                    $->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);
                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/');
                }
                elseif ($request->transaction_status == 'Success') //current status
                {
                    $         = Deposit::find($request->id);
                    $->status = $request->status;
                    $->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                        // 'is_default'  => 'Yes',
                    ])->update([
                        'balance' => $current_balance->balance - $request->amount,
                    ]);
                    $this->helper->one_time_message('success', 'Deposit Updated Successfully!');
                    return redirect('admin/');
                }
            }
        }
    }
}
