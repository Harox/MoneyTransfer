<?php

namespace App\DataTables\Admin;

use App\Http\Helpers\Common;
use App\Models\Transaction;
use App\Models\TransactionType;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class CryptoSentTransactionsDataTable extends DataTable
{

    public function ajax()
    {
        $columns = request()->columns;
        return datatables()
            ->eloquent($this->query())
            ->editColumn('txid', function ($transaction)
            {
                $payloadJson = json_decode($transaction->cryptoapi_log->payload, true);
                return $payloadJson['txid'];
            })
            ->editColumn('created_at', function ($transaction)
            {
                return dateFormat($transaction->created_at);
            })
            ->addColumn('sender', function ($transaction)
            {
                if (!empty($transaction->user))
                {
                    $sender = $transaction->user->first_name.' '.$transaction->user->last_name;
                    $senderWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->user_id) . '">'.$sender.'</a>' : $sender;
                }
                else
                {
                    $senderWithLink = '-';
                }
                return $senderWithLink;
            })
            // ->editColumn('transaction_type_id', function ($transaction)
            // {
            //     return $transaction->transaction_type->name;
            // })
            ->editColumn('subtotal', function ($transaction)
            {
                return $transaction->subtotal;
            })
            ->addColumn('fees', function ($transaction)
            {
                return $transaction->charge_fixed;
            })
            ->editColumn('total', function ($transaction)
            {
                if ($transaction->total > 0)
                {
                    $total = '<td><span class="text-green">+' . ($transaction->total) . '</span></td>';
                }
                else
                {
                    $total = '<td><span class="text-red">' . ($transaction->total) . '</span></td>';
                }
                return $total;
            })
            ->editColumn('currency_id', function ($transaction)
            {
                return $transaction->currency->code;
            })
            ->addColumn('receiver', function ($transaction)
            {
                if (!empty($transaction->end_user))
                {
                    $receiver = !empty($transaction->end_user) ? $transaction->end_user->first_name . ' ' . $transaction->end_user->last_name : "-";
                    $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->end_user_id) . '">'.$receiver.'</a>' : $receiver;
                }
                else
                {
                    $receiverWithLink = '-';
                }
                return $receiverWithLink;
            })
            ->editColumn('status', function ($transaction)
            {
                if ($transaction->status == 'Success')
                {
                    $status = '<span class="label label-success">Success</span>';
                }
                elseif ($transaction->status == 'Pending')
                {
                    $status = '<span class="label label-primary">Pending</span>';
                }
                return $status;
            })
            ->addColumn('action', function ($transaction)
            {
                $view = '';
                $view = (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_crypto_transactions')) ?
                '<a href="' . url('admin/crypto-sent-transactions/view/' . $transaction->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-eye-open" title="View"></i></a>&nbsp;' : '';
                return $view;
            })
            ->rawColumns(['sender','receiver','total', 'status', 'action'])
            ->make(true);
    }

    public function query()
    {
        $from     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $to       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $status   = isset(request()->status) ? request()->status : 'all';
        $currency = isset(request()->currency) ? request()->currency : 'all';
        $user     = isset(request()->user_id) ? request()->user_id : null;
        $query    = (new Transaction())->getCryptoSentTransactions($from, $to, $status, $currency, $user);

        return $this->applyScopes($query);
    }

    public function html()
    {
        return $this->builder()
            ->addColumn(['data' => 'id', 'name' => 'transactions.id', 'title' => 'ID', 'searchable' => false, 'visible' => false])

            // For searching with txid
            ->addColumn(['data' => 'txid', 'name' => 'cryptoapi_log.payload', 'title' => 'Txid', 'visible' => false])//relation

            ->addColumn(['data' => 'uuid', 'name' => 'transactions.uuid', 'title' => 'UUID', 'visible' => false])

            ->addColumn(['data' => 'created_at', 'name' => 'transactions.created_at', 'title' => 'Date'])

            //sender
            ->addColumn(['data' => 'sender', 'name' => 'user.last_name', 'title' => 'Sender', 'visible' => false])//relation
            ->addColumn(['data' => 'sender', 'name' => 'user.first_name', 'title' => 'Sender'])//relation

            //transaction_type
            // ->addColumn(['data' => 'transaction_type_id', 'name' => 'transaction_type.name', 'title' => 'Type'])//relation

            ->addColumn(['data' => 'subtotal', 'name' => 'transactions.subtotal', 'title' => 'Amount'])
            ->addColumn(['data' => 'fees', 'name' => 'fees', 'title' => 'Fees']) //custom
            ->addColumn(['data' => 'total', 'name' => 'transactions.total', 'title' => 'Total'])

            //currency
            ->addColumn(['data' => 'currency_id', 'name' => 'currency.code', 'title' => 'Crypto Currency'])//relation

            //receiver
            ->addColumn(['data' => 'receiver', 'name' => 'end_user.last_name', 'title' => 'Receiver', 'visible' => false])//relation
            ->addColumn(['data' => 'receiver', 'name' => 'end_user.first_name', 'title' => 'Receiver'])//relation

            ->addColumn(['data' => 'status', 'name' => 'transactions.status', 'title' => 'Status'])
            ->addColumn(['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false])

            ->parameters([
                'order'      => [[0, 'desc']],
                //centering all texts in columns
                "columnDefs" => [
                    [
                        "className" => "dt-center",
                        "targets" => "_all"
                    ]
                ],
                'pageLength' => Session::get('row_per_page'),
                'language'   => Session::get('language'),
            ]);
    }

}
