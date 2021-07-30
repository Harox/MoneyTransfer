<?php

namespace App\DataTables\Admin;

use App\Http\Helpers\Common;
use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Support\Str;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class TransactionsDataTable extends DataTable
{

    public function ajax()
    {
        $columns = request()->columns;
        return datatables()
            ->eloquent($this->query())
            ->editColumn('txid', function ($transaction)
            {
                if (!empty($transaction->cryptoapi_log->payload))
                {
                    $payloadJson = json_decode($transaction->cryptoapi_log->payload, true);
                    return $payloadJson['txid'];
                }
            })
            ->editColumn('created_at', function ($transaction)
            {
                return dateFormat($transaction->created_at);
            })
            ->addColumn('sender', function ($transaction)
            {
                $senderWithLink = '-';
                switch ($transaction->transaction_type_id)
                {
                    case Deposit:
                    case Transferred:
                    case Exchange_From:
                    case Exchange_To:
                    case Request_From:
                    case Withdrawal:
                    case Payment_Sent:
                    case Crypto_Sent:
                        if (!empty($transaction->user))
                        {
                            $sender = $transaction->user->first_name.' '.$transaction->user->last_name;
                            $senderWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->user_id) . '">'.$sender.'</a>' : $sender;
                        }
                        else
                        {
                            $sender = '-';
                        }
                        break;
                    case Received:
                    case Request_To:
                    case Payment_Received:
                    case Crypto_Received:
                        if (!empty($transaction->end_user))
                        {
                            $sender = $transaction->end_user->first_name.' '.$transaction->end_user->last_name;
                            $senderWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->end_user_id) . '">'.$sender.'</a>' : $sender;
                        }
                        else
                        {
                            $sender = '-';
                        }
                        break;
                }
                return $senderWithLink;
            })
           ->addColumn('receiver', function ($transaction)
            {
                $receiverWithLink = '-';
                switch ($transaction->transaction_type_id)
                {
                    case Deposit:
                    case Exchange_From:
                    case Exchange_To:
                    case Withdrawal:
                    case Payment_Sent:
                    case Crypto_Sent:
                        if (!empty($transaction->end_user))
                        {
                            $receiver = $transaction->end_user->first_name.' '.$transaction->end_user->last_name;
                            $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->end_user_id) . '">'.$receiver.'</a>' : $receiver;
                        }
                        else
                        {
                            $receiver = '-';
                        }
                        break;
                    case Transferred:
                        if (!empty($transaction->end_user))
                        {
                            $receiver = $transaction->end_user->first_name.' '.$transaction->end_user->last_name;
                            $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->end_user_id) . '">'.$receiver.'</a>' : $receiver;
                        }
                        else
                        {
                            if (!empty($transaction->transfer->email))
                            {
                                $receiver = $transaction->transfer->email;
                                $receiverWithLink = $receiver;
                            }
                            elseif (!empty($transaction->transfer->phone))
                            {
                                $receiver         = $transaction->transfer->phone;
                                $receiverWithLink = $receiver;
                            }
                            else
                            {
                                $receiver = '-';
                            }
                        }
                        break;
                    case Received:
                        if (!empty($transaction->user))
                        {
                            $receiver = $transaction->user->first_name.' '.$transaction->user->last_name;
                            $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->user_id) . '">'.$receiver.'</a>' : $receiver;
                        }
                        else
                        {
                            if (!empty($transaction->transfer->email))
                            {
                                $receiver = $transaction->transfer->email;
                                $receiverWithLink = $receiver;
                            }
                            elseif (!empty($transaction->transfer->phone))
                            {
                                $receiver         = $transaction->transfer->phone;
                                $receiverWithLink = $receiver;
                            }
                            else
                            {
                                $receiver = '-';
                            }
                        }
                        break;
                    case Request_From:
                        if (!empty($transaction->end_user))
                        {
                            $receiver = $transaction->end_user->first_name.' '.$transaction->end_user->last_name;
                            $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->end_user_id) . '">'.$receiver.'</a>' : $receiver;
                        }
                        else
                        {
                            if (!empty($transaction->request_payment->email))
                            {
                                $receiver = $transaction->request_payment->email;
                                $receiverWithLink = $receiver;
                            }
                            elseif (!empty($transaction->request_payment->phone))
                            {
                                $receiver         = $transaction->request_payment->phone;
                                $receiverWithLink = $receiver;
                            }
                            else
                            {
                                $receiver         = '-';
                            }
                        }
                        break;
                    case Request_To:
                        if (!empty($transaction->user))
                        {
                            $receiver = $transaction->user->first_name.' '.$transaction->user->last_name;
                            $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->user_id) . '">'.$receiver.'</a>' : $receiver;
                        }
                        else
                        {
                            if (!empty($transaction->request_payment->email))
                            {
                                $receiver = $transaction->request_payment->email;
                                $receiverWithLink = $receiver;
                            }
                            elseif (!empty($transaction->request_payment->phone))
                            {
                                $receiver         = $transaction->request_payment->phone;
                                $receiverWithLink = $receiver;
                            }
                            else
                            {
                                $receiver         = '-';
                            }
                        }
                        break;
                    case Payment_Received:
                    case Crypto_Received:
                        if (!empty($transaction->user))
                        {
                            $receiver = $transaction->user->first_name.' '.$transaction->user->last_name;
                            $receiverWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $transaction->user_id) . '">'.$receiver.'</a>' : $receiver;
                        }
                        else
                        {
                            $receiver = '-';
                        }
                        break;
                }
                return $receiverWithLink;
            })
            ->editColumn('transaction_type_id', function ($transaction)
            {
                return ($transaction->transaction_type->name == "Withdrawal") ? "Payout" : str_replace('_', ' ', $transaction->transaction_type->name);
            })
            ->editColumn('subtotal', function ($transaction)
            {
                return $transaction->currency->type != 'fiat' ? $transaction->subtotal : formatNumber($transaction->subtotal);
            })
            ->addColumn('fees', function ($transaction)
            {
                return (($transaction->charge_percentage == 0) && ($transaction->charge_fixed == 0) ? '-' : ($transaction->currency->type != 'fiat' ? $transaction->charge_fixed : formatNumber($transaction->charge_percentage + $transaction->charge_fixed)));
            })
            ->editColumn('total', function ($transaction)
            {
                if ($transaction->total > 0)
                {
                    if ($transaction->currency->type != 'fiat')
                    {
                        $total = '<td><span class="text-green">+' . ($transaction->total) . '</span></td>';
                    }
                    else
                    {
                        $total = '<td><span class="text-green">+' . formatNumber($transaction->total) . '</span></td>';
                    }
                }
                else
                {
                    if ($transaction->currency->type != 'fiat')
                    {
                        $total = '<td><span class="text-red">' . ($transaction->total) . '</span></td>';
                    }
                    else
                    {
                        $total = '<td><span class="text-red">' . formatNumber($transaction->total) . '</span></td>';
                    }
                }
                return $total;
            })
            ->editColumn('currency_id', function ($transaction)
            {
                return $transaction->currency->code;
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
                elseif ($transaction->status == 'Refund')
                {
                    $status = '<span class="label label-warning">Refunded</span>';
                }
                elseif ($transaction->status == 'Blocked')
                {
                    $status = '<span class="label label-danger">Cancelled</span>';
                }
                return $status;
            })
            ->addColumn('action', function ($transaction)
            {
                $edit = '';
                $edit = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_transaction')) ?
                '<a href="' . url('admin/transactions/edit/' . $transaction->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';
                return $edit;
            })
            ->rawColumns(['sender','receiver','total', 'status', 'action'])
            ->make(true);
    }

    public function query()
    {
        $status   = isset(request()->status) ? request()->status : 'all';
        $currency = isset(request()->currency) ? request()->currency : 'all';
        $user     = isset(request()->user_id) ? request()->user_id : null;
        $type     = isset(request()->type) ? request()->type : 'all';
        $from     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $to       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $query    = (new Transaction())->getTransactionsList($from, $to, $status, $currency, $type, $user);

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
            ->addColumn(['data' => 'sender', 'name' => 'user.last_name', 'title' => 'User', 'visible' => false])//relation
            ->addColumn(['data' => 'sender', 'name' => 'user.first_name', 'title' => 'User'])//relation

            //transaction_type
            ->addColumn(['data' => 'transaction_type_id', 'name' => 'transaction_type.name', 'title' => 'Type'])//relation

            ->addColumn(['data' => 'subtotal', 'name' => 'transactions.subtotal', 'title' => 'Amount'])
            ->addColumn(['data' => 'fees', 'name' => 'fees', 'title' => 'Fees']) //custom
            ->addColumn(['data' => 'total', 'name' => 'transactions.total', 'title' => 'Total'])

            //currency
            ->addColumn(['data' => 'currency_id', 'name' => 'currency.code', 'title' => 'Currency'])//relation

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
