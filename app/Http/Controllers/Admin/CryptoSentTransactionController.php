<?php

namespace App\Http\Controllers\Admin;

use App;
use App\DataTables\Admin\CryptoSentTransactionsDataTable;
use App\Repositories\CryptoCurrencyRepository;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\{Currency,
    Transaction,
    User
};

class CryptoSentTransactionController extends Controller
{
    protected $transaction;
    /**
     * The CryptoCurrency repository instance.
     *
     * @var CryptoCurrencyRepository
     */
    protected $cryptoCurrency;

    public function __construct()
    {
        $this->transaction    = new Transaction();
        $this->cryptoCurrency = new CryptoCurrencyRepository();
    }

    public function index(CryptoSentTransactionsDataTable $dataTable)
    {
        $data['menu']     = 'crypto-transactions';
        $data['sub_menu'] = 'crypto-sent-transactions';

        $cryptoSentTransactions                   = $this->transaction->where('transaction_type_id', Crypto_Sent);
        $data['cryptoSentTransactionsCurrencies'] = $cryptoSentTransactions->with('currency:id,code')->groupBy('currency_id')->get(['currency_id']);
        $data['cryptoSentTransactionsStatus']     = $cryptoSentTransactions->groupBy('status')->get(['status']);

        $data['from']     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['status']   = isset(request()->status) ? request()->status : 'all';
        $data['currency'] = isset(request()->currency) ? request()->currency : 'all';
        $data['user']     = $user    = isset(request()->user_id) ? request()->user_id : null;
        $data['getName']  = $getName = $this->transaction->getTransactionsUsersEndUsersName($user, Crypto_Sent);
        return $dataTable->render('admin.crypto_transactions.sent.index', $data);
    }

    public function cryptoSentTransactionsSearchUser(Request $request)
    {
        $search = $request->search;
        $user   = $this->transaction->getTransactionsUsersResponse($search, Crypto_Sent);
        $res    = [
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

    public function cryptoSentTransactionsCsv()
    {
        $from     = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to       = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status   = isset(request()->status) ? request()->status : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user     = isset(request()->user_id) ? request()->user_id : null;

        $getCryptoSentTransactions = $this->transaction->getCryptoSentTransactions($from, $to, $status, $currency, $user)->orderBy('transactions.id', 'desc')->get();

        $datas = [];
        if (!empty($getCryptoSentTransactions))
        {
            foreach ($getCryptoSentTransactions as $key => $value)
            {
                $datas[$key]['Date'] = dateFormat($value->created_at);

                $datas[$key]['Sender'] = !empty($value->user) ? $value->user->first_name . ' ' . $value->user->last_name : "-";

                $datas[$key]['Amount'] = $value->subtotal;

                $datas[$key]['Fees'] = $value->charge_fixed;

                $datas[$key]['Total'] = $value->total > 0 ? '+' . $value->total : $value->total;

                $datas[$key]['Crypto Currency'] = $value->currency->code;

                $datas[$key]['Receiver'] = !empty($value->end_user) ? $value->end_user->first_name . ' ' . $value->end_user->last_name : "-";

                $datas[$key]['Status'] = $value->status;
            }
        }
        else
        {
            $datas[0]['Date']            = '';
            $datas[0]['Sender']          = '';
            $datas[0]['Amount']          = '';
            $datas[0]['Fees']            = '';
            $datas[0]['Total']           = '';
            $datas[0]['Crypto Currency'] = '';
            $datas[0]['Receiver']        = '';
            $datas[0]['Status']          = '';
        }
        return Excel::create('crypto_sent_transactions_list_' . time() . '', function ($excel) use ($datas)
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

    public function cryptoSentTransactionsPdf()
    {
        $data['company_logo'] = getCompanyLogoWithoutSession();
        $from     = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to       = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status   = isset(request()->status) ? request()->status : null;

        $currency = isset(request()->currency) ? request()->currency : null;

        $user     = isset(request()->user_id) ? request()->user_id : null;

        $data['getCryptoSentTransactions'] = $getCryptoSentTransactions = $this->transaction->getCryptoSentTransactions($from, $to, $status, $currency, $user)->orderBy('transactions.id', 'desc')->get();
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
        $mpdf->WriteHTML(view('admin.crypto_transactions.sent.crypto_sent_transactions_report_pdf', $data));
        $mpdf->Output('cyprto_sent_transactions_report_' . time() . '.pdf', 'D');
    }

    public function view($id)
    {
        $data['menu']     = 'crypto-transactions';
        $data['sub_menu'] = 'crypto-sent-transactions';

        $data['transaction'] = $transaction = $this->transaction->with([
            'user:id,first_name,last_name',
            'end_user:id,first_name,last_name',
            'currency:id,code,symbol',
            'payment_method:id,name',
            'cryptoapi_log:id,object_id,payload,confirmations',
        ])
            ->where('transaction_type_id', Crypto_Sent)
            ->exclude(['merchant_id', 'bank_id', 'file_id', 'refund_reference', 'transaction_reference_id', 'email', 'phone', 'note'])
            ->find($id);

        // Get crypto api log details for Crypto_Sent
        if (!empty($transaction->cryptoapi_log))
        {
            $getCryptoDetails = $this->cryptoCurrency->getCryptoPayloadConfirmationsDetails($transaction->transaction_type_id, $transaction->cryptoapi_log->payload, $transaction->cryptoapi_log->confirmations);
            if (count($getCryptoDetails) > 0)
            {
                if (isset($getCryptoDetails['senderAddress']))
                {
                    $data['senderAddress']   = $getCryptoDetails['senderAddress'];
                }
                if (isset($getCryptoDetails['receiverAddress']))
                {
                    $data['receiverAddress'] = $getCryptoDetails['receiverAddress'];
                }
                $data['txId']            = $getCryptoDetails['txId'];
                $data['confirmations']   = $getCryptoDetails['confirmations'];
            }
        }
        return view('admin.crypto_transactions.sent.view', $data);
    }
}
