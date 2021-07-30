<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Users\EmailController;
use App\DataTables\Admin\WithdrawalsDataTable;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Withdrawal,
    EmailTemplate,
    Transaction,
    Wallet
};
use Illuminate\Support\Facades\Session;
use App;

class WithdrawalController extends Controller
{
    protected $helper;
    protected $withdrawal;
    protected $email;

    public function __construct()
    {
        $this->helper     = new Common();
        $this->withdrawal = new Withdrawal();
        $this->email      = new EmailController();
    }

    public function index(WithdrawalsDataTable $dataTable)
    {
        $data['menu'] = 'withdrawals';

        $data['w_status']     = $w_status     = $this->withdrawal->select('status')->groupBy('status')->get();
        $data['w_currencies'] = $w_currencies = $this->withdrawal->select('currency_id')->groupBy('currency_id')->get();
        $data['w_pm']         = $w_pm         = $this->withdrawal->select('payment_method_id')->whereNotNull('payment_method_id')->groupBy('payment_method_id')->get();

        $data['from']     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['status']   = isset(request()->status) ? request()->status : 'all';
        $data['currency'] = isset(request()->currency) ? request()->currency : 'all';
        $data['pm']       = isset(request()->payment_methods) ? request()->payment_methods : 'all';
        $data['user']     = $user    = isset(request()->user_id) ? request()->user_id : null;
        $data['getName']  = $getName = $this->withdrawal->getWithdrawalsUserName($user);

        return $dataTable->render('admin.withdrawals.list', $data);
    }

    public function withdrawalCsv()
    {
        $from     = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to       = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status   = isset(request()->status) ? request()->status : null;

        $pm       = isset(request()->payment_methods) ? request()->payment_methods : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user     = isset(request()->user_id) ? request()->user_id : null;

        $data['withdrawals'] = $withdrawals = $this->withdrawal->getWithdrawalsList($from, $to, $status, $currency, $pm, $user)->orderBy('withdrawals.id', 'desc')->get();

        $datas = [];
        if (!empty($withdrawals))
        {
            foreach ($withdrawals as $key => $value)
            {
                $datas[$key]['Date'] = dateFormat($value->created_at);

                $datas[$key]['User'] = isset($value->user) ? $value->user->first_name . ' ' . $value->user->last_name : "-";

                $datas[$key]['Amount'] = formatNumber($value->amount);

                $datas[$key]['Fees'] = ($value->charge_percentage == 0) && ($value->charge_fixed == 0) ? '-' : formatNumber($value->charge_percentage + $value->charge_fixed);

                $datas[$key]['Total'] = '-'.formatNumber($value->amount + ($value->charge_percentage + $value->charge_fixed));

                $datas[$key]['Currency'] = $value->currency->code;

                $datas[$key]['Payment Method'] = ($value->payment_method->name == "Mts") ? getCompanyName() : $value->payment_method->name;

                if ($value->payment_method->name != "Bank")
                {
                    $payment_method_info_withdrawal =  !empty($value->payment_method_info) ? $value->payment_method_info : '-';
                }
                else
                {
                    $payment_method_info_withdrawal = !empty($value->withdrawal_detail) ?
                    $value->withdrawal_detail->account_name.' '.'('.('*****'.substr($value->withdrawal_detail->account_number,-4)).')'.' '.$value->withdrawal_detail->bank_name : '-';
                }
                $datas[$key]['Method Info'] = $payment_method_info_withdrawal;

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
            $datas[0]['Method Info']    = '';
            $datas[0]['Status']         = '';
        }

        return Excel::create('payout_list_' . time() . '', function ($excel) use ($datas)
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

    public function withdrawalPdf()
    {
        $data['company_logo'] = getCompanyLogoWithoutSession();

        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $pm = isset(request()->payment_methods) ? request()->payment_methods : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $data['withdrawals'] = $withdrawals = $this->withdrawal->getWithdrawalsList($from, $to, $status, $currency, $pm, $user)->orderBy('withdrawals.id', 'desc')->get();

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

        $mpdf->WriteHTML(view('admin.withdrawals.withdrawals_report_pdf', $data));

        $mpdf->Output('payouts_report_' . time() . '.pdf', 'D');
    }

    public function withdrawalsUserSearch(Request $request)
    {
        $search = $request->search;
        $user   = $this->withdrawal->getWithdrawalsUsersResponse($search);

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

    public function edit($id)
    {
        $data['menu']       = 'withdrawals';
        $data['withdrawal'] = $withdrawal = Withdrawal::find($id);

        $data['transaction'] = $transaction = Transaction::select('transaction_type_id', 'status', 'percentage', 'transaction_reference_id')
            ->where(['transaction_reference_id' => $withdrawal->id, 'status' => $withdrawal->status, 'transaction_type_id' => Withdrawal])
            ->first();

        return view('admin.withdrawals.edit', $data);
    }

    public function update(Request $request)
    {
        //if other language's subject and body not set, get en sub and body for mail
        $english_withdrawal_email_temp = EmailTemplate::where(['temp_id' => 10, 'lang' => 'en', 'type' => 'email'])->select('subject', 'body')->first();

        /**
         * Email Template
         */
        $withdrawal_email_temp = EmailTemplate::where([
            'temp_id'     => 10,
            'language_id' => Session::get('default_language'),
            'type' => 'email',
        ])->select('subject', 'body')->first();

        /**
         * SMS Template
         */
        $withdrawal_status_en_sms_info = EmailTemplate::where(['temp_id' => 10, 'lang' => 'en', 'type' => 'sms'])->select('subject', 'body')->first();
        $withdrawal_status_sms_info    = EmailTemplate::where(['temp_id' => 10, 'language_id' => Session::get('default_language'), 'type' => 'sms'])->select('subject', 'body')->first();

        //Withdrawal
        if ($request->transaction_type == 'Withdrawal')
        {
            if ($request->status == 'Pending') //requested status
            {
                if ($request->transaction_status == 'Pending')
                {
                    $this->helper->one_time_message('success', 'Payout is already Pending!');
                }
                elseif ($request->transaction_status == 'Success')
                {
                    $withdrawal         = Withdrawal::find($request->id);
                    $withdrawal->status = $request->status;
                    $withdrawal->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //Mail when withdrawal is 'Success'
                    if (!empty($withdrawal_email_temp->subject) && !empty($withdrawal_email_temp->body))
                    {
                        // subject
                        $withdrawal_pending_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_email_temp->subject);
                        // body
                        $withdrawal_pending_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_email_temp->body);
                    }
                    else
                    {
                        // subject
                        $withdrawal_pending_sub = str_replace('{uuid}', $withdrawal->uuid, $english_withdrawal_email_temp->subject);
                        // body
                        $withdrawal_pending_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $english_withdrawal_email_temp->body);
                    }
                    //1
                    $withdrawal_pending_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{status}', $withdrawal->status, $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{amount}', 'No Amount', $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{added/subtracted}', 'added/subtracted', $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{from/to}', 'from', $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{soft_name}', Session::get('name'), $withdrawal_pending_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($withdrawal->user->email, $withdrawal_pending_sub, $withdrawal_pending_msg);
                    }

                    //sms
                    if (!empty($withdrawal->user->carrierCode) && !empty($withdrawal->user->phone))
                    {
                        if (!empty($withdrawal_status_sms_info->subject) && !empty($withdrawal_status_sms_info->body))
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_sms_info->body);
                        }
                        else
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_en_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_en_sms_info->body);
                        }
                        //2 - fixed
                        $withdrawal_status_sms_info_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info_msg);
                        //
                        $withdrawal_status_sms_info_msg = str_replace('{status}', $withdrawal->status, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{amount}', 'No Amount', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{added/subtracted}', 'added/subtracted', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{from/to}', 'from', $withdrawal_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($withdrawal->user->carrierCode . $withdrawal->user->phone, $withdrawal_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Payout Updated Successfully!');
                }
                elseif ($request->transaction_status == 'Blocked')
                {
                    $withdrawal         = Withdrawal::find($request->id);
                    $withdrawal->status = $request->status;
                    $withdrawal->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    $update_wallet_for_deposit = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $current_balance->balance - ($request->amount + $request->feesTotal),
                    ]);

                    if (!empty($withdrawal_email_temp->subject) && !empty($withdrawal_email_temp->body))
                    {
                        $withdrawal_pending_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_email_temp->subject);
                        $withdrawal_pending_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_email_temp->body);
                    }
                    else
                    {
                        $withdrawal_pending_sub = str_replace('{uuid}', $withdrawal->uuid, $english_withdrawal_email_temp->subject);
                        $withdrawal_pending_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $english_withdrawal_email_temp->body);
                    }
                    //3
                    $withdrawal_pending_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{status}', $withdrawal->status, $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{added/subtracted}', 'subtracted', $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{from/to}', 'from', $withdrawal_pending_msg);
                    $withdrawal_pending_msg = str_replace('{soft_name}', Session::get('name'), $withdrawal_pending_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($withdrawal->user->email, $withdrawal_pending_sub, $withdrawal_pending_msg);
                    }

                    //sms
                    if (!empty($withdrawal->user->carrierCode) && !empty($withdrawal->user->phone))
                    {
                        if (!empty($withdrawal_status_sms_info->subject) && !empty($withdrawal_status_sms_info->body))
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_sms_info->body);
                        }
                        else
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_en_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_en_sms_info->body);
                        }
                        //4
                        $withdrawal_status_sms_info_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{status}', $withdrawal->status, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{from/to}', 'from', $withdrawal_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($withdrawal->user->carrierCode . $withdrawal->user->phone, $withdrawal_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Payout Updated Successfully!');
                }
            }
            elseif ($request->status == 'Success')
            {
                if ($request->transaction_status == 'Success') //current status
                {
                    $this->helper->one_time_message('success', 'Payout is already Successfull!');
                }
                elseif ($request->transaction_status == 'Pending')
                {
                    $withdrawal         = Withdrawal::find($request->id);
                    $withdrawal->status = $request->status;
                    $withdrawal->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //Mail when withdrawal is 'Success'
                    if (!empty($withdrawal_email_temp->subject) && !empty($withdrawal_email_temp->body))
                    {
                        // subject
                        $withdrawal_success_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_email_temp->subject);
                        // body
                        $withdrawal_success_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_email_temp->body);
                    }
                    else
                    {
                        // subject
                        $withdrawal_success_sub = str_replace('{uuid}', $withdrawal->uuid, $english_withdrawal_email_temp->subject);
                        // body
                        $withdrawal_success_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $english_withdrawal_email_temp->body);
                    }
                    //5
                    $withdrawal_success_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{status}', $withdrawal->status, $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{amount}', 'No Amount', $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{added/subtracted}', 'added/subtracted', $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{from/to}', 'from', $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{soft_name}', Session::get('name'), $withdrawal_success_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($withdrawal->user->email, $withdrawal_success_sub, $withdrawal_success_msg);
                    }

                    //sms
                    if (!empty($withdrawal->user->carrierCode) && !empty($withdrawal->user->phone))
                    {
                        if (!empty($withdrawal_status_sms_info->subject) && !empty($withdrawal_status_sms_info->body))
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_sms_info->body);
                        }
                        else
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_en_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_en_sms_info->body);
                        }
                        //6
                        $withdrawal_status_sms_info_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{status}', $withdrawal->status, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{amount}', 'No Amount', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{added/subtracted}', 'added/subtracted', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{from/to}', 'from', $withdrawal_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($withdrawal->user->carrierCode . $withdrawal->user->phone, $withdrawal_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Payout Updated Successfully!');
                }
                elseif ($request->transaction_status == 'Blocked') //current status
                {
                    $withdrawal         = Withdrawal::find($request->id);
                    $withdrawal->status = $request->status;
                    $withdrawal->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $current_balance->balance - ($request->amount + $request->feesTotal),
                    ]);

                    if (!empty($withdrawal_email_temp->subject) && !empty($withdrawal_email_temp->body))
                    {
                        $withdrawal_success_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_email_temp->subject);
                        $withdrawal_success_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_email_temp->body);
                    }
                    else
                    {
                        $withdrawal_success_sub = str_replace('{uuid}', $withdrawal->uuid, $english_withdrawal_email_temp->subject);
                        $withdrawal_success_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $english_withdrawal_email_temp->body);
                    }
                    //7
                    $withdrawal_success_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{status}', $withdrawal->status, $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{added/subtracted}', 'subtracted', $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{from/to}', 'from', $withdrawal_success_msg);
                    $withdrawal_success_msg = str_replace('{soft_name}', Session::get('name'), $withdrawal_success_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($withdrawal->user->email, $withdrawal_success_sub, $withdrawal_success_msg);
                    }

                    //sms
                    if (!empty($withdrawal->user->carrierCode) && !empty($withdrawal->user->phone))
                    {
                        if (!empty($withdrawal_status_sms_info->subject) && !empty($withdrawal_status_sms_info->body))
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_sms_info->body);
                        }
                        else
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_en_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_en_sms_info->body);
                        }
                        //8
                        $withdrawal_status_sms_info_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{status}', $withdrawal->status, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{from/to}', 'from', $withdrawal_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($withdrawal->user->carrierCode . $withdrawal->user->phone, $withdrawal_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Payout Updated Successfully!');
                }
            }
            elseif ($request->status == 'Blocked')
            {
                if ($request->transaction_status == 'Blocked') //current status
                {
                    $this->helper->one_time_message('success', 'Payout is already Blocked!');
                }
                elseif ($request->transaction_status == 'Pending') //current status
                {
                    $withdrawal         = Withdrawal::find($request->id);
                    $withdrawal->status = $request->status;
                    $withdrawal->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    $update_wallet_for_deposit = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $current_balance->balance + ($request->amount + $request->feesTotal),
                    ]);

                    //Mail when withdrawal is 'Success'
                    if (!empty($withdrawal_email_temp->subject) && !empty($withdrawal_email_temp->body))
                    {
                        // subject
                        $withdrawal_cancel_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_email_temp->subject);
                        // body
                        $withdrawal_cancel_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_email_temp->body);
                    }
                    else
                    {
                        // subject
                        $withdrawal_cancel_sub = str_replace('{uuid}', $withdrawal->uuid, $english_withdrawal_email_temp->subject);
                        // body
                        $withdrawal_cancel_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $english_withdrawal_email_temp->body);
                    }
                    //9
                    $withdrawal_cancel_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{status}', ($withdrawal->status == 'Blocked') ? 'Cancelled' : $withdrawal->status, $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{added/subtracted}', 'added', $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{from/to}', 'to', $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{soft_name}', Session::get('name'), $withdrawal_cancel_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($withdrawal->user->email, $withdrawal_cancel_sub, $withdrawal_cancel_msg);
                    }

                    //sms
                    if (!empty($withdrawal->user->carrierCode) && !empty($withdrawal->user->phone))
                    {
                        if (!empty($withdrawal_status_sms_info->subject) && !empty($withdrawal_status_sms_info->body))
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_sms_info->body);
                        }
                        else
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_en_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_en_sms_info->body);
                        }
                        //10
                        $withdrawal_status_sms_info_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{status}', ($withdrawal->status == 'Blocked') ? 'Cancelled' : $withdrawal->status, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{from/to}', 'to', $withdrawal_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($withdrawal->user->carrierCode . $withdrawal->user->phone, $withdrawal_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Payout Updated Successfully!');
                }
                elseif ($request->transaction_status == 'Success') //current status
                {
                    $withdrawal         = Withdrawal::find($request->id);
                    $withdrawal->status = $request->status;
                    $withdrawal->save();

                    Transaction::where([
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    $current_balance = Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->user_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $current_balance->balance + $request->amount + $request->feesTotal,
                    ]);

                    if (!empty($withdrawal_email_temp->subject) && !empty($withdrawal_email_temp->body))
                    {
                        $withdrawal_cancel_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_email_temp->subject);
                        $withdrawal_cancel_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_email_temp->body);
                    }
                    else
                    {
                        $withdrawal_cancel_sub = str_replace('{uuid}', $withdrawal->uuid, $english_withdrawal_email_temp->subject);
                        $withdrawal_cancel_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $english_withdrawal_email_temp->body);
                    }
                    //11
                    $withdrawal_cancel_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{status}', ($withdrawal->status == 'Blocked') ? 'Cancelled' : $withdrawal->status, $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{added/subtracted}', 'added', $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{from/to}', 'to', $withdrawal_cancel_msg);
                    $withdrawal_cancel_msg = str_replace('{soft_name}', Session::get('name'), $withdrawal_cancel_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($withdrawal->user->email, $withdrawal_cancel_sub, $withdrawal_cancel_msg);
                    }

                    //sms
                    if (!empty($withdrawal->user->carrierCode) && !empty($withdrawal->user->phone))
                    {
                        if (!empty($withdrawal_status_sms_info->subject) && !empty($withdrawal_status_sms_info->body))
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_sms_info->body);
                        }
                        else
                        {
                            $withdrawal_status_sms_info_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_en_sms_info->subject);
                            $withdrawal_status_sms_info_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_status_en_sms_info->body);
                        }
                        //12
                        $withdrawal_status_sms_info_msg = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{status}', ($withdrawal->status == 'Blocked') ? 'Cancelled' : $withdrawal->status, $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $withdrawal_status_sms_info_msg);
                        $withdrawal_status_sms_info_msg = str_replace('{from/to}', 'to', $withdrawal_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($withdrawal->user->carrierCode . $withdrawal->user->phone, $withdrawal_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Payout Updated Successfully!');
                }
            }
        }
        return redirect('admin/withdrawals');
    }
}
