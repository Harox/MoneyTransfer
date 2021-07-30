<?php

namespace App\DataTables\Admin;

use App\Http\Helpers\Common;
use App\Models\Transaction;
use App\Models\TransactionType;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class CryptoReceivedTransactionsDataTable extends DataTable
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
                if (!empty($transaction->end_user))
                {
                    $sender = $transaction->end_user->first_name.' '.$transaction->end_user->last_name;
                    $senderWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->end_user_id) . '">'.$sender.'</a>' : $sender;
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
                $subtotal = '<td><span class="text-green">+' . ($transaction->subtotal) . '</span></td>';
                return $subtotal;
            })
            ->editColumn('currency_id', function ($transaction)
            {
                return $transaction->currency->code;
            })
            ->addColumn('receiver', function ($transaction)
            {
                if (!empty($transaction->user))
                {
                    $receiver = !empty($transaction->user) ? $transaction->user->first_name . ' ' . $transaction->user->last_name : "-";
                    $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->user_id) . '">'.$receiver.'</a>' : $receiver;
                }
                else
                {
                    $receiverWithLink = '-';
                }
                return $receiverWithLink;
            })
            ->addColumn('action', function ($transaction)
            {
                $view = '';
                $view = (Common::has_permission(\Auth::guard('admin')->user()->id, 'view_crypto_transactions')) ?
                '<a href="' . url('admin/crypto-received-transactions/view/' . $transaction->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-eye-open" title="View"></i></a>&nbsp;' : '';
                return $view;
            })
            ->rawColumns(['sender','receiver','subtotal','action'])
            ->make(true);
    }

    public function query()
    {
        $from     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $to       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $currency = isset(request()->currency) ? request()->currency : 'all';
        $user     = isset(request()->user_id) ? request()->user_id : null;
        $query    = (new Transaction())->getCryptoReceivedTransactions($from, $to, $currency, $user);

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

            //currency
            ->addColumn(['data' => 'currency_id', 'name' => 'currency.code', 'title' => 'Crypto Currency'])//relation

            //receiver
            ->addColumn(['data' => 'receiver', 'name' => 'end_user.last_name', 'title' => 'Receiver', 'visible' => false])//relation
            ->addColumn(['data' => 'receiver', 'name' => 'end_user.first_name', 'title' => 'Receiver'])//relation

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
