<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\{Session,
    App
};
use App\DataTables\Admin\MoneyTransfersDataTable;
use App\Http\Controllers\Users\EmailController;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Transaction,
    Transfer,
    Wallet,
    User
};
class MoneyTransferController extends Controller
{
    protected $helper;
    protected $email;
    protected $transfer;

    public function __construct()
    {
        $this->helper   = new Common();
        $this->email    = new EmailController();
        $this->transfer = new Transfer();
    }

    public function index(MoneyTransfersDataTable $dataTable)
    {
        $data['menu'] = 'transfers';

        $data['transfer_status']     = $transfer_status     = $this->transfer->select('status')->groupBy('status')->get();
        $data['transfer_currencies'] = $transfer_currencies = $this->transfer->select('currency_id')->groupBy('currency_id')->get();

        $data['from']     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['status']   = isset(request()->status) ? request()->status : 'all';
        $data['currency'] = isset(request()->currency) ? request()->currency : 'all';
        $data['user']     = $user    = isset(request()->user_id) ? request()->user_id : null;
        $data['getName']  = $getName = $this->transfer->getTransfersUserName($user);
        //$data['rate']  = $getName = $this->transfer->getTransfersUserName($user);

        return $dataTable->render('admin.transfers.list', $data);
    }

    public function transferCsv()
    {
        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $data['transfers'] = $transfers = $this->transfer->getTransfersList($from, $to, $status, $currency, $user)->orderBy('transfers.id', 'desc')->get();

        $datas = [];
        if (!empty($transfers)) {
            foreach ($transfers as $key => $value) {
                if ($value->status == 'Blocked') {
                    $value->status = 'Cancelled';
                } elseif ($value->status == 'Refund') {
                    $value->status = 'Refunded';
                }

                $datas[$key]['Date'] = dateFormat($value->created_at);

                $datas[$key]['Sender'] = isset($value->sender) ? $value->sender->first_name . ' ' . $value->sender->last_name : "-";

                $datas[$key]['Amount'] = formatNumber($value->amount);

                $datas[$key]['Fees'] = ($value->fee == 0) ? '-' : formatNumber($value->fee);

                $datas[$key]['Total'] = '-' . formatNumber($value->amount + $value->fee);

                $datas[$key]['Rate'] = formatNumber($value->amount / $value->fee);

                $datas[$key]['Currency'] = $value->currency->code;

                if ($value->receiver) {
                    $datas[$key]['Receiver'] = $value->receiver->first_name . ' ' . $value->receiver->last_name;
                } elseif ($value->email) {
                    $datas[$key]['Receiver'] = $value->email;
                } elseif ($value->phone) {
                    $datas[$key]['Receiver'] = $value->phone;
                } else {
                    $datas[$key]['Receiver'] = '-';
                }
                $datas[$key]['Status'] = $value->status;
            }
        } else {
            $datas[0]['Date']     = '';
            $datas[0]['Sender']     = '';
            $datas[0]['Amount']   = '';
            $datas[0]['Fees']     = '';
            $datas[0]['Total']    = '';
            $datas[0]['Rate'] = '';
            $datas[0]['Currency'] = '';
            $datas[0]['Receiver'] = '';
            $datas[0]['Status']   = '';
        }

        return Excel::create('transfer_list_' . time() . '', function ($excel) use ($datas)
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

    public function transferPdf()
    {
        $data['company_logo'] = getCompanyLogoWithoutSession();

        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $data['transfers'] = $transfers = $this->transfer->getTransfersList($from, $to, $status, $currency, $user)->orderBy('transfers.id', 'desc')->get();

        if (isset($from) && isset($to)) {
            $data['date_range'] = $from. ' To ' . $to;
        } else {
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

        $mpdf->WriteHTML(view('admin.transfers.transfers_report_pdf', $data));

        $mpdf->Output('transfers_report_' . time() . '.pdf', 'D');
    }

    public function transfersUserSearch(Request $request)
    {
        $search = $request->search;
        $user   = $this->transfer->getTransfersUsersResponse($search);

        $res = [
            'status' => 'fail',
        ];
        if (count($user) > 0) {
            $res = [
                'status' => 'success',
                'data'   => $user,
            ];
        }
        return json_encode($res);
    }

    public function edit($id)
    {
        $data['menu']     = 'transfers';
        $data['transfer'] = $transfer = Transfer::find($id);

        $data['transactionOfRefunded'] = $transactionOfRefunded = Transaction::where(['uuid' => $transfer->uuid])->first(['refund_reference']);

        $data['transferOfRefunded'] = $transferOfRefunded = Transfer::where(['uuid' => $transactionOfRefunded->refund_reference])->first(['id']);

        $data['transaction'] = $transaction = Transaction::select('refund_reference', 'transaction_type_id', 'status', 'transaction_reference_id', 'percentage', 'charge_percentage', 'charge_fixed')
            ->where(['transaction_reference_id' => $transfer->id, 'status' => $transfer->status])
            ->whereIn('transaction_type_id', [Transferred, Received])
            ->first();

        return view('admin.transfers.edit', $data);
    }

    public function update(Request $request)
    {
        if (!empty(trim($request->email))) {
            $userInfo = User::where(['email' => trim($request->email)])->first();
        } else {
            $userInfo = User::where(['formattedPhone' => $request->phone])->first();
        }

        $englishSenderLanginfo = EmailTemplate::where(['temp_id' => 6, 'lang' => 'en', 'type' => 'email'])->select('subject', 'body')->first(); //if other language's subject and body not set, get en sub and body for mail

        /*
        Email
         */
        $transferred_email_template = EmailTemplate::where([
            'temp_id'     => 6,
            'language_id' => Session::get('default_language'),
            'type'        => 'email',
        ])->select('subject', 'body')->first();
        /**/

        /**
         * SMS
         */
        $transfer_status_en_sms_info = EmailTemplate::where(['temp_id' => 6, 'lang' => 'en', 'type' => 'sms'])->select('subject', 'body')->first();
        $transfer_status_sms_info    = EmailTemplate::where(['temp_id' => 6, 'language_id' => Session::get('default_language'), 'type' => 'sms'])->select('subject', 'body')->first();

        //using Transferred transaction_type to update both Transferred and Received entries
        if ($request->transaction_type == 'Transferred') {
            if ($request->status == 'Pending') {
                if ($request->transaction_status == 'Pending') {
                    $this->helper->one_time_message('success', 'Transfer is already Pending!');
                    return redirect('admin/transfers');
                } elseif ($request->transaction_status == 'Success') {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    if (isset($userInfo)) {
                        //Received entry update
                        Transaction::where([
                            'user_id'                  => $userInfo->id,
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => Received,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        //deduct amount from receiver wallet only
                        $receiver_wallet = Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->update([
                            'balance' => $receiver_wallet->balance - $request->amount,
                        ]);

                        // Mail when, [ request: Pending, status: Success ]
                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body)) {
                            $t_pending_sub = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            $t_pending_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        } else {
                            $t_pending_sub = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            $t_pending_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_pending_msg = str_replace('{uuid}', $transfers->uuid, $t_pending_msg);
                        $t_pending_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_pending_msg);
                        $t_pending_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $t_pending_msg);
                        $t_pending_msg = str_replace('{added/subtracted}', 'subtracted', $t_pending_msg);
                        $t_pending_msg = str_replace('{from/to}', 'from', $t_pending_msg);
                        $t_pending_msg = str_replace('{soft_name}', Session::get('name'), $t_pending_msg);

                        if (checkAppMailEnvironment()) {
                            $this->email->sendEmail($userInfo->email, $t_pending_sub, $t_pending_msg);
                        }

                        //SMS when, [ request: Pending, status: Success ]
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                } elseif ($request->transaction_status == 'Refund') {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //Received entry update
                    Transaction::where([
                        'user_id'                  => isset($userInfo) ? $userInfo->id : null,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Received,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sender wallet entry update
                    $sender_wallet = Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $sender_wallet->balance - ($request->amount + $request->feesTotal),
                    ]);

                    // Mail when, [ request: Pending, status: Refund ]
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_pending_sub = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_pending_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_pending_sub = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_pending_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_pending_msg = str_replace('{uuid}', $transfers->uuid, $t_pending_msg);
                    $t_pending_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_pending_msg);
                    $t_pending_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $t_pending_msg);
                    $t_pending_msg = str_replace('{added/subtracted}', 'subtracted', $t_pending_msg);
                    $t_pending_msg = str_replace('{from/to}', 'from', $t_pending_msg);
                    $t_pending_msg = str_replace('{soft_name}', Session::get('name'), $t_pending_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_pending_sub, $t_pending_msg);
                    }

                    //sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Blocked')
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //Received entry update
                    Transaction::where([
                        'user_id'                  => isset($userInfo) ? $userInfo->id : null,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Received,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sender wallet entry update
                    $sender_wallet = Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $sender_wallet->balance - ($request->amount + $request->feesTotal),
                    ]);

                    // Mail when, [ request: Pending, status: Blocked ]
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_pending_sub = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_pending_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_pending_sub = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_pending_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_pending_msg = str_replace('{uuid}', $transfers->uuid, $t_pending_msg);
                    $t_pending_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_pending_msg);
                    $t_pending_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $t_pending_msg);
                    $t_pending_msg = str_replace('{added/subtracted}', 'subtracted', $t_pending_msg);
                    $t_pending_msg = str_replace('{from/to}', 'from', $t_pending_msg);
                    $t_pending_msg = str_replace('{soft_name}', Session::get('name'), $t_pending_msg);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_pending_sub, $t_pending_msg);
                    }

                    //sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
            }
            elseif ($request->status == 'Success')
            {
                if ($request->transaction_status == 'Success')
                {
                    $this->helper->one_time_message('success', 'Transfer is already Successfull!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Pending') //current status
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    Transaction::where([
                        'user_id'                  => isset($userInfo) ? $userInfo->id : null,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Received,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    if (isset($userInfo))
                    {
                        $receiver_wallet = Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->update([
                            'balance' => $receiver_wallet->balance + $request->amount,
                        ]);
                    }

                    // Sent Mail when status is 'Success'
                    if (isset($userInfo))
                    {

                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                        {
                            // subject
                            $t_success_sub = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            // body
                            $t_success_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        }
                        else
                        {
                            // subject
                            $t_success_sub = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            // body
                            $t_success_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_success_msg = str_replace('{uuid}', $transfers->uuid, $t_success_msg);
                        $t_success_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_success_msg);
                        $t_success_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $t_success_msg);
                        $t_success_msg = str_replace('{added/subtracted}', 'added', $t_success_msg);
                        $t_success_msg = str_replace('{from/to}', 'to', $t_success_msg);
                        $t_success_msg = str_replace('{soft_name}', Session::get('name'), $t_success_msg);

                        if (checkAppMailEnvironment())
                        {
                            $this->email->sendEmail($userInfo->email, $t_success_sub, $t_success_msg);
                        }

                        //sms
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Blocked')
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sender wallet entry update
                    $sender_wallet = Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $sender_wallet->balance - ($request->amount + $request->feesTotal),
                    ]);

                    // Sender Mail
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_success_sub_1 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_success_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_success_sub_1 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_success_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_success_msg_1 = str_replace('{uuid}', $transfers->uuid, $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $t_success_msg_1);

                    $t_success_msg_1 = str_replace('{added/subtracted}', 'subtracted', $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{from/to}', 'from', $t_success_msg_1);

                    $t_success_msg_1 = str_replace('{soft_name}', Session::get('name'), $t_success_msg_1);
                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_success_sub_1, $t_success_msg_1);
                    }

                    //Sender sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $transfer_status_sms_info_msg);

                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }

                    //Received entry update
                    if (isset($userInfo))
                    {
                        Transaction::where([
                            'user_id'                  => $userInfo->id,
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => Received,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        $receiver_wallet = Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->update([
                            'balance' => $receiver_wallet->balance + $request->amount,
                        ]);

                        // Receiver Mail
                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                        {
                            // subject
                            $t_success_sub_2 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            // body
                            $t_success_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        }
                        else
                        {
                            // subject
                            $t_success_sub_2 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            // body
                            $t_success_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_success_msg_2 = str_replace('{uuid}', $transfers->uuid, $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{added/subtracted}', 'added', $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{from/to}', 'to', $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{soft_name}', Session::get('name'), $t_success_msg_2);

                        if (checkAppMailEnvironment())
                        {
                            $this->email->sendEmail($userInfo->email, $t_success_sub_2, $t_success_msg_2);
                        }

                        // Receiver SMS
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }

                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Refund')
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //Received entry update
                    Transaction::where([
                        'user_id'                  => isset($userInfo) ? $userInfo->id : null,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Received,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sender wallet entry update
                    $sender_wallet = Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $sender_wallet->balance - ($request->amount + $request->feesTotal),
                    ]);

                    if (isset($userInfo))
                    {
                        //receiver wallet entry update
                        $receiver_wallet = Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->update([
                            'balance' => $receiver_wallet->balance + $request->amount,
                        ]);
                    }

                    // Sent Mail when request is 'Success'
                    // Sender Mail
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_success_sub_1 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_success_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_success_sub_1 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_success_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_success_msg_1 = str_replace('{uuid}', $transfers->uuid, $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{added/subtracted}', 'subtracted', $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{from/to}', 'from', $t_success_msg_1);
                    $t_success_msg_1 = str_replace('{soft_name}', Session::get('name'), $t_success_msg_1);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_success_sub_1, $t_success_msg_1);
                    }

                    //sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }

                    if (isset($userInfo))
                    {
                        // Receiver Mail
                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                        {
                            // subject
                            $t_success_sub_2 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            // body
                            $t_success_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        }
                        else
                        {
                            // subject
                            $t_success_sub_2 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            // body
                            $t_success_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_success_msg_2 = str_replace('{uuid}', $transfers->uuid, $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{added/subtracted}', 'added', $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{from/to}', 'to', $t_success_msg_2);
                        $t_success_msg_2 = str_replace('{soft_name}', Session::get('name'), $t_success_msg_2);

                        if (checkAppMailEnvironment())
                        {
                            $this->email->sendEmail($userInfo->email, $t_success_sub_2, $t_success_msg_2);
                        }

                        //sms
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
            }
            elseif ($request->status == 'Blocked')
            {
                if ($request->transaction_status == 'Blocked') // done
                {
                    $this->helper->one_time_message('success', 'Transfer is already Blocked!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Success') // done
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sender wallet entry update
                    $sender_wallet = Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $sender_wallet->balance + ($request->amount + $request->feesTotal),
                    ]);

                    // Sent Mail when status is 'blocked'
                    // Transfer Mail
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_block_sub_1 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        $t_block_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body); // body
                    }
                    else
                    {
                        // subject
                        $t_block_sub_1 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        $t_block_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body); // body
                    }
                    $t_block_msg_1 = str_replace('{uuid}', $transfers->uuid, $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{added/subtracted}', 'added', $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{from/to}', 'to', $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{soft_name}', Session::get('name'), $t_block_msg_1);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_block_sub_1, $t_block_msg_1);
                    }

                    //sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }

                    if (isset($userInfo))
                    {
                        Transaction::where([
                            'user_id'                  => $userInfo->id,
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => Received,
                        ])->update([
                            'status' => $request->status,
                        ]);

                        //receiver wallet entry update
                        $receiver_wallet = Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->update([
                            'balance' => $receiver_wallet->balance - $request->amount,
                        ]);

                        // Receiver Mail
                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                        {
                            // subject
                            $t_block_sub_2 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            // body
                            $t_block_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        }
                        else
                        {
                            // subject
                            $t_block_sub_2 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            // body
                            $t_block_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_block_msg_2 = str_replace('{uuid}', $transfers->uuid, $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{added/subtracted}', 'subtracted', $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{from/to}', 'from', $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{soft_name}', Session::get('name'), $t_block_msg_2);

                        if (checkAppMailEnvironment())
                        {
                            $this->email->sendEmail($userInfo->email, $t_block_sub_2, $t_block_msg_2);
                        }

                        //sms
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }

                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Pending') // done
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //sender wallet entry update
                    $sender_wallet = Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $sender_wallet->balance + ($request->amount + $request->feesTotal),
                    ]);

                    // Cancel Mail - only to sender
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_block_sub = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_block_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_block_sub = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_block_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_block_msg = str_replace('{uuid}', $transfers->uuid, $t_block_msg);
                    $t_block_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_block_msg);
                    $t_block_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $t_block_msg);
                    $t_block_msg = str_replace('{added/subtracted}', 'added', $t_block_msg);
                    $t_block_msg = str_replace('{from/to}', 'to', $t_block_msg);
                    $t_block_msg = str_replace('{soft_name}', Session::get('name'), $t_block_msg);
                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_block_sub, $t_block_msg);
                    }

                    //sms - only to sender
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }

                    //Received transactions entry update only
                    if (isset($userInfo))
                    {
                        Transaction::where([
                            'user_id'                  => $userInfo->id,
                            'transaction_reference_id' => $request->transaction_reference_id,
                            'transaction_type_id'      => Received,
                        ])->update([
                            'status' => $request->status,
                        ]);
                    }

                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Refund')
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //Received entry update
                    Transaction::where([
                        'user_id'                  => isset($userInfo) ? $userInfo->id : null,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Received,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    // Sent Mail when status is 'blocked'
                    // Sender Mail
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_block_sub_1 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_block_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_block_sub_1 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_block_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_block_msg_1 = str_replace('{uuid}', $transfers->uuid, $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{amount}', 'No Amount', $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{added/subtracted}', 'added/subtracted', $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{from/to}', 'from', $t_block_msg_1);
                    $t_block_msg_1 = str_replace('{soft_name}', Session::get('name'), $t_block_msg_1);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_block_sub_1, $t_block_msg_1);
                    }

                    //sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', 'No Amount', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added/subtracted', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }

                    if (isset($userInfo))
                    {
                        // Receiver Mail
                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                        {
                            // subject
                            $t_block_sub_2 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            // body
                            $t_block_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        }
                        else
                        {
                            // subject
                            $t_block_sub_2 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            // body
                            $t_block_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_block_msg_2 = str_replace('{uuid}', $transfers->uuid, $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{amount}', 'No Amount', $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{added/subtracted}', 'added/subtracted', $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{from/to}', 'from', $t_block_msg_2);
                        $t_block_msg_2 = str_replace('{soft_name}', Session::get('name'), $t_block_msg_2);

                        if (checkAppMailEnvironment())
                        {
                            $this->email->sendEmail($userInfo->email, $t_block_sub_2, $t_block_msg_2);
                        }

                        //sms
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', 'No Amount', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added/subtracted', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
            }
            elseif ($request->status == 'Refund')
            {
                if ($request->transaction_status == 'Refund') //current status
                {
                    $this->helper->one_time_message('success', 'Transfer is already Refund!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Success') //done
                {
                    $unique_code = unique_code();

                    $transfers              = new Transfer();
                    $transfers->sender_id   = $request->sender_id;
                    $transfers->receiver_id = isset($userInfo) ? $userInfo->id : null;
                    $transfers->currency_id = $request->currency_id;
                    $transfers->uuid        = $unique_code;
                    $transfers->fee         = $request->feesTotal;
                    $transfers->amount      = $request->amount;
                    $transfers->note        = $request->note;
                    $transfers->email       = $request->email;
                    $transfers->status      = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'refund_reference' => $unique_code,
                    ]);

                    //Received entry update
                    Transaction::where([
                        'user_id'                  => $request->receiver_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Received,
                    ])->update([
                        'refund_reference' => $unique_code,
                    ]);

                    //New Transferred entry
                    $refund_t_A                           = new Transaction();
                    $refund_t_A->user_id                  = $transfers->sender_id;
                    $refund_t_A->end_user_id              = $transfers->receiver_id;
                    $refund_t_A->currency_id              = $request->currency_id;
                    $refund_t_A->uuid                     = $unique_code;
                    $refund_t_A->refund_reference         = $request->uuid;
                    $refund_t_A->transaction_reference_id = $transfers->id;
                    $refund_t_A->transaction_type_id      = $request->transaction_type_id; //Transferred
                    $refund_t_A->user_type                = isset($userInfo) ? 'registered' : 'unregistered';
                    $refund_t_A->email                    = $request->email;
                    $refund_t_A->subtotal                 = $request->amount;
                    $refund_t_A->percentage               = $request->percentage;
                    $refund_t_A->charge_percentage        = $request->charge_percentage;
                    $refund_t_A->charge_fixed             = $request->charge_fixed;
                    $refund_t_A->total                    = $request->charge_percentage + $request->charge_fixed + $request->amount;
                    $refund_t_A->note                     = $request->note;
                    $refund_t_A->status                   = $request->status;
                    $refund_t_A->save();

                    //New Received entry
                    $refund_t_B                           = new Transaction();
                    $refund_t_B->user_id                  = $transfers->receiver_id;
                    $refund_t_B->end_user_id              = $transfers->sender_id;
                    $refund_t_B->currency_id              = $request->currency_id;
                    $refund_t_B->uuid                     = $unique_code;
                    $refund_t_B->refund_reference         = $request->uuid;
                    $refund_t_B->transaction_reference_id = $transfers->id;
                    $refund_t_B->transaction_type_id      = Received; //Received
                    $refund_t_B->user_type                = isset($userInfo) ? 'registered' : 'unregistered';
                    $refund_t_B->email                    = $request->email;
                    $refund_t_B->subtotal                 = $request->amount;
                    $refund_t_B->percentage               = 0;
                    $refund_t_B->charge_percentage        = 0;
                    $refund_t_B->charge_fixed             = 0;
                    $refund_t_B->total                    = '-' . $request->amount;
                    $refund_t_B->note                     = $request->note;
                    $refund_t_B->status                   = $request->status;
                    $refund_t_B->save();

                    //sender wallet entry update
                    $sender_wallet = Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->select('balance')->first();

                    Wallet::where([
                        'user_id'     => $request->sender_id,
                        'currency_id' => $request->currency_id,
                    ])->update([
                        'balance' => $sender_wallet->balance + ($request->amount + $request->feesTotal),
                    ]);

                    // Mail when refunded
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_refund_sub_1 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_refund_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_refund_sub_1 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_refund_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_refund_msg_1 = str_replace('{uuid}', $transfers->uuid, $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{added/subtracted}', 'added', $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{from/to}', 'to', $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{soft_name}', Session::get('name'), $t_refund_msg_1);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_refund_sub_1, $t_refund_msg_1);
                    }

                    //sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount + $request->feesTotal)), $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'to', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }

                    if (isset($userInfo))
                    {
                        //receiver wallet entry update
                        $receiver_wallet = Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->select('balance')->first();

                        Wallet::where([
                            'user_id'     => $userInfo->id,
                            'currency_id' => $request->currency_id,
                        ])->update([
                            'balance' => $receiver_wallet->balance - $request->amount,
                        ]);

                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                        {
                            // subject
                            $t_refund_sub_2 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            // body
                            $t_refund_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        }
                        else
                        {
                            // subject
                            $t_refund_sub_2 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            // body
                            $t_refund_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_refund_msg_2 = str_replace('{uuid}', $transfers->uuid, $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{added/subtracted}', 'subtracted', $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{from/to}', 'from', $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{soft_name}', Session::get('name'), $t_refund_msg_2);

                        if (checkAppMailEnvironment())
                        {
                            $this->email->sendEmail($userInfo->email, $t_refund_sub_2, $t_refund_msg_2);
                        }

                        //sms
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', moneyFormat($transfers->currency->symbol, formatNumber($request->amount)), $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'subtracted', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
                elseif ($request->transaction_status == 'Blocked')
                {
                    $transfers         = Transfer::find($request->id);
                    $transfers->status = $request->status;
                    $transfers->save();

                    //Transferred entry update
                    Transaction::where([
                        'user_id'                  => $request->sender_id,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => $request->transaction_type_id,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    //Received entry update
                    Transaction::where([
                        'user_id'                  => isset($userInfo) ? $userInfo->id : null,
                        'transaction_reference_id' => $request->transaction_reference_id,
                        'transaction_type_id'      => Received,
                    ])->update([
                        'status' => $request->status,
                    ]);

                    // Sent Mail when request is 'Refund'
                    // Sender Mail
                    if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                    {
                        // subject
                        $t_refund_sub_1 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                        // body
                        $t_refund_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transferred_email_template->body);
                    }
                    else
                    {
                        // subject
                        $t_refund_sub_1 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                        // body
                        $t_refund_msg_1 = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $englishSenderLanginfo->body);
                    }
                    $t_refund_msg_1 = str_replace('{uuid}', $transfers->uuid, $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{status}', $transfers->status, $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{amount}', 'No Amount', $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{added/subtracted}', 'added/subtracted', $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{from/to}', 'from', $t_refund_msg_1);
                    $t_refund_msg_1 = str_replace('{soft_name}', Session::get('name'), $t_refund_msg_1);

                    if (checkAppMailEnvironment())
                    {
                        $this->email->sendEmail($transfers->sender->email, $t_refund_sub_1, $t_refund_msg_1);
                    }

                    //sms
                    if (!empty($transfers->sender->carrierCode) && !empty($transfers->sender->phone))
                    {
                        if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_sms_info->body);
                        }
                        else
                        {
                            $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                            $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $transfers->sender->first_name . ' ' . $transfers->sender->last_name, $transfer_status_en_sms_info->body);
                        }
                        $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                            $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{amount}', 'No Amount', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added/subtracted', $transfer_status_sms_info_msg);
                        $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                        if (checkAppSmsEnvironment())
                        {
                            sendSMS($transfers->sender->carrierCode . $transfers->sender->phone, $transfer_status_sms_info_msg);
                        }
                    }

                    if (isset($userInfo))
                    {
                        // Receiver Mail
                        if (!empty($transferred_email_template->subject) && !empty($transferred_email_template->body))
                        {
                            // subject
                            $t_refund_sub_2 = str_replace('{uuid}', $transfers->uuid, $transferred_email_template->subject);
                            // body
                            $t_refund_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transferred_email_template->body);
                        }
                        else
                        {
                            // subject
                            $t_refund_sub_2 = str_replace('{uuid}', $transfers->uuid, $englishSenderLanginfo->subject);
                            // body
                            $t_refund_msg_2 = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $englishSenderLanginfo->body);
                        }
                        $t_refund_msg_2 = str_replace('{uuid}', $transfers->uuid, $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status), $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{amount}', 'No Amount', $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{added/subtracted}', 'added/subtracted', $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{from/to}', 'from', $t_refund_msg_2);
                        $t_refund_msg_2 = str_replace('{soft_name}', Session::get('name'), $t_refund_msg_2);

                        if (checkAppMailEnvironment())
                        {
                            $this->email->sendEmail($userInfo->email, $t_refund_sub_2, $t_refund_msg_2);
                        }

                        //sms
                        if (!empty($userInfo->carrierCode) && !empty($userInfo->phone))
                        {
                            if (!empty($transfer_status_sms_info->subject) && !empty($transfer_status_sms_info->body))
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_sms_info->body);
                            }
                            else
                            {
                                $transfer_status_sms_info_sub = str_replace('{uuid}', $transfers->uuid, $transfer_status_en_sms_info->subject);
                                $transfer_status_sms_info_msg = str_replace('{sender_id/receiver_id}', $userInfo->first_name . ' ' . $userInfo->last_name, $transfer_status_en_sms_info->body);
                            }
                            $transfer_status_sms_info_msg = str_replace('{uuid}', $transfers->uuid, $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{status}', ($transfers->status == 'Blocked') ? "Cancelled" : (($transfers->status == 'Refund') ? "Refunded" : $transfers->status),
                                $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{amount}', 'No Amount', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{added/subtracted}', 'added/subtracted', $transfer_status_sms_info_msg);
                            $transfer_status_sms_info_msg = str_replace('{from/to}', 'from', $transfer_status_sms_info_msg);

                            if (checkAppSmsEnvironment())
                            {
                                sendSMS($userInfo->carrierCode . $userInfo->phone, $transfer_status_sms_info_msg);
                            }
                        }
                    }
                    $this->helper->one_time_message('success', 'Transfer Updated Successfully!');
                    return redirect('admin/transfers');
                }
            }
        }
    }
}
