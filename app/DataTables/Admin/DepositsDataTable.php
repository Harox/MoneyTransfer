<?php

namespace App\DataTables\Admin;

use App\Http\Helpers\Common;
use App\Models\Deposit;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class DepositsDataTable extends DataTable
{

    public function ajax()
    {
        return datatables()
            ->eloquent($this->query())
            ->editColumn('created_at', function ($deposit)
            {
                return dateFormat($deposit->created_at);
            })
            ->addColumn('user_id', function ($deposit)
            {
                $sender = isset($deposit->user) ? $deposit->user->first_name.' '.$deposit->user->last_name :"-";

                $senderWithLink = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_user')) ? '<a href="' . url('admin/users/edit/' . $deposit->user->id) . '">'.$sender.'</a>' : $sender;
                return $senderWithLink;
            })
            ->editColumn('amount', function ($deposit)
            {
                return formatNumber($deposit->amount);
            })
            ->addColumn('fees', function ($deposit)
            {
                return ($deposit->charge_percentage == 0) && ($deposit->charge_fixed == 0) ? '-' : formatNumber($deposit->charge_percentage + $deposit->charge_fixed);
            })
            ->addColumn('total', function ($deposit)
            {
                if (($deposit->charge_percentage + $deposit->charge_fixed + $deposit->amount) > 0)
                {
                    $total = '<td><span class="text-green">+' . formatNumber($deposit->charge_percentage + $deposit->charge_fixed + $deposit->amount) . '</span></td>';
                }
                else
                {
                    $total = '<td><span class="text-red">' . formatNumber($deposit->charge_percentage + $deposit->charge_fixed + $deposit->amount) . '</span></td>';
                }
                return $total;
            })
            ->editColumn('currency_id', function ($deposit)
            {
                return $deposit->currency->code;
            })
            ->editColumn('payment_method_id', function ($deposit)
            {
                if (isset($deposit->payment_method))
                {
                    if ($deposit->payment_method->name == "Mts")
                    {
                        // $pm = "Pay Money";
                        $pm = getCompanyName();
                    }
                    else
                    {
                        $pm = $deposit->payment_method->name;
                    }
                }
                else
                {
                    $pm = "-";
                }
                return $pm;
            })
            ->editColumn('status', function ($deposit)
            {
                if ($deposit->status == 'Success')
                {
                    $status = '<span class="label label-success">Success</span>';
                }
                elseif ($deposit->status == 'Pending')
                {
                    $status = '<span class="label label-primary">Pending</span>';
                }
                elseif ($deposit->status == 'Blocked')
                {
                    $status = '<span class="label label-danger">Cancelled</span>';
                }
                return $status;
            })
            ->addColumn('action', function ($deposit)
            {
                $edit = '';
                $edit = (Common::has_permission(\Auth::guard('admin')->user()->id, 'edit_deposit')) ?
                '<a href="' . url('admin/deposits/edit/' . $deposit->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';
                return $edit;
            })
            ->rawColumns(['user_id','total', 'status', 'action'])
            ->make(true);
    }

    public function query()
    {
        $status   = isset(request()->status) ? request()->status : 'all';
        $currency = isset(request()->currency) ? request()->currency : 'all';
        $pm       = isset(request()->payment_methods) ? request()->payment_methods : 'all';
        $user     = isset(request()->user_id) ? request()->user_id : null;
        $from     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $to       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $query    = (new Deposit())->getDepositsList($from, $to, $status, $currency, $pm, $user);
        
        return $this->applyScopes($query);
    }

    public function html()
    {
        return $this->builder()
            ->addColumn(['data' => 'id', 'name' => 'deposits.id', 'title' => 'ID', 'searchable' => false, 'visible' => false])

            ->addColumn(['data' => 'created_at', 'name' => 'deposits.created_at', 'title' => 'Date'])

            ->addColumn(['data' => 'uuid', 'name' => 'deposits.uuid', 'title' => 'UUID', 'visible' => false])

            ->addColumn(['data' => 'user_id', 'name' => 'user.last_name', 'title' => 'User','visible' => false])//relation
            ->addColumn(['data' => 'user_id', 'name' => 'user.first_name', 'title' => 'User'])//relation

            ->addColumn(['data' => 'amount', 'name' => 'deposits.amount', 'title' => 'Amount'])

            ->addColumn(['data' => 'fees', 'name' => 'fees', 'title' => 'Fees']) //custom
            ->addColumn(['data' => 'total', 'name' => 'total', 'title' => 'Total']) //custom

            ->addColumn(['data' => 'currency_id', 'name' => 'currency.code', 'title' => 'Currency'])//relation
            ->addColumn(['data' => 'payment_method_id', 'name' => 'payment_method.name', 'title' => 'Payment Method'])//relation

            ->addColumn(['data' => 'status', 'name' => 'deposits.status', 'title' => 'Status'])
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
