<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\RevenuesDataTable;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Models\Transaction;

class RevenueController extends Controller
{
    protected $revenue;
    public function __construct()
    {
        $this->revenue = new Transaction();
    }

    public function revenues_list(RevenuesDataTable $dataTable)
    {
        $data['menu'] = 'revenues';

        $revenueDatas = $this->revenue
        ->where(function($query)
        {
            $query->where('charge_percentage', '>', 0);
            $query->orWhere('charge_fixed', '!=', 0);
        })
        ->where('status', 'Success')
        ->whereIn('transaction_type_id', [Deposit, Withdrawal, Exchange_From, Transferred, Request_To, Payment_Received, Crypto_Sent]);

        $data['revenues_currency'] = $revenueDatas->groupBy('currency_id')->select('currency_id')->get();
        $data['revenues_type']     = $revenueDatas->groupBy('transaction_type_id')->select('transaction_type_id')->get();

        $data['from']     = $from    = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = $to      = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['type']     = $type    = isset(request()->type) ? request()->type : null;
        $data['currency'] = $currency= isset(request()->currency) ? request()->currency : 'all';

        $getRevenuesListForCurrencyIfo = $this->revenue->getRevenuesList($from, $to, $currency, $type)->orderBy('transactions.id', 'desc')->get();

        $toal_revenue = 0;
        $array_map    = [];
        $array_final  = [];
        $counter      = 0;

        if ($getRevenuesListForCurrencyIfo->count() > 0)
        {
            foreach ($getRevenuesListForCurrencyIfo as $value)
            {
                $toal_revenue                                = ($value->charge_percentage + $value->charge_fixed);
                $array_map[$value->currency->code][$counter] = $toal_revenue;
                $counter++;
            }

            if ($array_map)
            {
                foreach ($array_map as $key => $res)
                {
                    $array_final[$key] = array_sum($res);
                }
            }
            $data['currency_info'] = $array_final;
        }
        else
        {
            $data['currency_info'] = [];
        }
        return $dataTable->render('admin.revenues.list', $data);
    }

    public function revenueCsv()
    {
        $from     = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to       = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $type     = isset(request()->type) ? request()->type : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $data['revenues'] = $revenues = $this->revenue->getRevenuesList($from, $to, $currency, $type)->orderBy('transactions.id', 'desc')->get();

        $datas = [];
        if (!empty($revenues))
        {
            foreach ($revenues as $key => $value)
            {
                $datas[$key]['Date']              = dateFormat($value->created_at);
                $datas[$key]['Transaction Type']  = ($value->transaction_type->name == "Withdrawal") ? "Payout" : str_replace('_', ' ', $value->transaction_type->name);
                $datas[$key]['Percentage Charge'] = ($value->charge_percentage == 0) ? '-' : formatNumber($value->charge_percentage);
                $datas[$key]['Fixed Charge']      = ($value->charge_fixed == 0) ? '-' : formatNumber($value->charge_fixed);
                $datas[$key]['Total']             = ($value->charge_percentage == 0) && ($value->charge_fixed == 0) ? '-' : '+'.formatNumber($value->charge_percentage + $value->charge_fixed);
                $datas[$key]['Currency']          = $value->currency->code;
            }
        }
        else
        {
            $datas[0]['Date']              = '';
            $datas[0]['Transaction Type']  = '';
            $datas[0]['Percentage Charge'] = '';
            $datas[0]['Fixed Charge']      = '';
            $datas[0]['Total']             = '';
            $datas[0]['Currency']          = '';
        }

        return Excel::create('revenues_list_' . time() . '', function ($excel) use ($datas)
        {
            $excel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $excel->sheet('mySheet', function ($sheet) use ($datas)
            {
                $sheet->cells('A1:F1', function ($cells)
                {
                    $cells->setFontWeight('bold');
                });
                $sheet->fromArray($datas);
            });
        })->download();
    }

    public function revenuePdf()
    {
        $data['company_logo'] = getCompanyLogoWithoutSession();
        $from     = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to       = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $type     = isset(request()->type) ? request()->type : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $data['revenues'] = $revenues = $this->revenue->getRevenuesList($from, $to, $currency, $type)->orderBy('transactions.id', 'desc')->get();
        if (isset($from) && isset($to))
        {
            $data['date_range'] = $from. ' To ' . $to;
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
        $mpdf->WriteHTML(view('admin.revenues.revenues_report_pdf', $data));
        $mpdf->Output('revenues_report_' . time() . '.pdf', 'D');
    }

}
