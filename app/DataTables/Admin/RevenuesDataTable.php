<?php

namespace App\DataTables\Admin;

use App\Models\Transaction;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Session;

class RevenuesDataTable extends DataTable
{

    public function ajax()
    {
        return datatables()
            ->eloquent($this->query())
            ->editColumn('created_at', function ($revenue)
            {
                return dateFormat($revenue->created_at);
            })
            ->editColumn('transaction_type_id', function ($revenue)
            {
                return ($revenue->transaction_type->name == "Withdrawal") ? "Payout" : str_replace('_', ' ', $revenue->transaction_type->name);
            })
            ->editColumn('charge_percentage', function ($revenue)
            {
                return ($revenue->charge_percentage == 0) ?  '-' : formatNumber($revenue->charge_percentage);
            })
            ->editColumn('charge_fixed', function ($revenue)
            {
                return ($revenue->charge_fixed == 0) ?  '-' : formatNumber($revenue->charge_fixed);
            })
            ->addColumn('total', function ($revenue)
            {
                $total = ($revenue->charge_percentage == 0) && ($revenue->charge_fixed == 0) ? '-' : $revenue->charge_percentage + $revenue->charge_fixed;
                if ($total > 0)
                {
                    $total = '<td><span class="text-green">+' . formatNumber($total) . '</span></td>';
                }
                else
                {
                    $total = '<td><span class="text-red">' . ($total) . '</span></td>';
                }
                return $total;
            })
            ->editColumn('currency_id', function ($revenue)
            {
                return $revenue->currency->code;
            })
            ->rawColumns(['total'])
            ->make(true);
    }

    public function query()
    {
        $currency = isset(request()->currency) ? request()->currency : 'all';
        $type     = isset(request()->type) ? request()->type : 'all';
        $from     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $to       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $query    = (new Transaction())->getRevenuesList($from, $to, $currency, $type);

        return $this->applyScopes($query);
    }

    public function html()
    {
        return $this->builder()
            ->addColumn(['data' => 'id', 'name' => 'transactions.id', 'title' => 'ID', 'searchable' => false, 'visible' => false])

            ->addColumn(['data' => 'created_at', 'name' => 'transactions.created_at', 'title' => 'Date'])

            ->addColumn(['data' => 'transaction_type_id', 'name' => 'transaction_type.name', 'title' => 'Transaction Type']) //relation

            ->addColumn(['data' => 'charge_percentage', 'name' => 'transactions.charge_percentage', 'title' => 'Percentage Charge'])

            ->addColumn(['data' => 'charge_fixed', 'name' => 'transactions.charge_fixed', 'title' => 'Fixed Charge'])

            ->addColumn(['data' => 'total', 'name' => 'total', 'title' => 'Total']) //custom

            ->addColumn(['data' => 'currency_id', 'name' => 'currency.code', 'title' => 'Currency']) //relation

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
