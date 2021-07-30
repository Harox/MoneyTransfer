<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Users\EmailController;
use App\DataTables\Admin\MerchantsDataTable;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Currency,
    MerchantPayment,
    MerchantGroup,
    MerchantApp,
    Merchant
};
use Exception;

class MerchantController extends Controller
{
    protected $helper;
    protected $email;
    protected $merchant;

    public function __construct()
    {
        $this->helper   = new Common();
        $this->email    = new EmailController();
        $this->merchant = new Merchant();
    }

    public function index(MerchantsDataTable $dataTable)
    {
        $data['menu']     = 'merchant';
        $data['sub_menu'] = 'merchant_details';

        $data['merchants_status'] = $merchants_status = $this->merchant->select('status')->groupBy('status')->get();

        $data['from']     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['status']   = isset(request()->status) ? request()->status : 'all';
        $data['user']     = $user    = isset(request()->user_id) ? request()->user_id : null;
        $data['getName']  = $getName = $this->merchant->getMerchantsUserName($user);

        return $dataTable->render('admin.merchants.list', $data);
    }

    public function merchantCsv()
    {
        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $data['merchants'] = $merchants = $this->merchant->getMerchantsList($from, $to, $status, $user)->orderBy('merchants.id', 'desc')->get();

        $datas = [];
        if (!empty($merchants))
        {
            foreach ($merchants as $key => $value)
            {
                $datas[$key]['Date']          = dateFormat($value->created_at);
                $datas[$key]['ID']            = (isset($value->merchant_uuid)) ? $value->merchant_uuid : '-';
                $datas[$key]['Type']          = ucfirst($value->type);
                $datas[$key]['Business Name'] = $value->business_name;
                $datas[$key]['User']          = isset($value->user) ? $value->user->first_name . ' ' . $value->user->last_name : "-";
                $datas[$key]['Url']           = $value->site_url;
                $datas[$key]['Group']         = isset($value->merchant_group) ? $value->merchant_group->name : "-";
                $datas[$key]['Logo']          = isset($value->logo) ? $value->logo : "-";
                $datas[$key]['Status']        = $value->status;
            }
        }
        else
        {
            $datas[0]['Date']          = '';
            $datas[$key]['ID']         = '';
            $datas[$key]['Type']       = '';
            $datas[0]['Business Name'] = '';
            $datas[0]['User']          = '';
            $datas[0]['Url']           = '';
            $datas[0]['Group']         = '';
            $datas[0]['Logo']          = '';
            $datas[0]['Status']        = '';
        }

        return Excel::create('merchants_list_' . time() . '', function ($excel) use ($datas)
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

    public function merchantPdf()
    {
        $data['company_logo'] = getCompanyLogoWithoutSession();

        $from = !empty(request()->startfrom) ? setDateForDb(request()->startfrom) : null;

        $to = !empty(request()->endto) ? setDateForDb(request()->endto) : null;

        $status = isset(request()->status) ? request()->status : null;

        $user = isset(request()->user_id) ? request()->user_id : null;

        $data['merchants'] = $merchants = $this->merchant->getMerchantsList($from, $to, $status, $user)->orderBy('merchants.id', 'desc')->get();

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

        $mpdf->WriteHTML(view('admin.merchants.merchants_report_pdf', $data));

        $mpdf->Output('merchants_report_' . time() . '.pdf', 'D');
    }

    public function merchantsUserSearch(Request $request)
    {
        $search = $request->search;
        $user   = $this->merchant->getMerchantsUsersResponse($search);

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
        $data['menu']     = 'merchant';
        $data['sub_menu'] = 'merchant_details';
        $data['merchant'] = $merchant = Merchant::find($id);
        $data['merchantGroup']    = $merchantGroup    = MerchantGroup::get(['id','name']);
        $data['activeCurrencies'] = $activeCurrencies = Currency::where(['status' => 'Active', 'type' => 'fiat'])->get(['id', 'code']);

        //check Decimal Thousand Money Format Preference
        $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);
        return view('admin.merchants.edit', $data);
    }

    public function update(Request $request)
    {
        $rules = array(
            'business_name' => 'required',
            'site_url'      => 'required|url',
            'fee'           => 'required|numeric',
            'logo'          => 'mimes:png,jpg,jpeg,gif,bmp|max:10000',
        );

        $fieldNames = array(
            'business_name' => 'Business Name',
            'site_url'      => 'Site url',
            'fee'           => 'Fee',
            'logo'          => 'Logo',
        );

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);

        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }
        else
        {
            $picture  = $request->logo;
            $filename = null;
            if (isset($picture))
            {
                $dir = public_path("/user_dashboard/merchant/");
                //extension checking
                $ext = strtolower($picture->getClientOriginalExtension());
                if ($ext == 'png' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif' || $ext == 'bmp')
                {
                    $filename = time() . '.' . $ext;
                    $img      = Image::make($picture->getRealPath());
                    $img->resize(100, 80)->save($dir . '/' . $filename);
                    $img->resize(70, 70)->save($dir . '/thumb/' . $filename);
                }
                else
                {
                    $this->helper->one_time_message('error', 'Invalid Image Format!');
                }
            }

            try
            {
                DB::beginTransaction();
                $merchant                    = Merchant::find($request->id);
                $merchant->currency_id       = $request->currency_id; //2.3
                $merchant->merchant_group_id = $request->merchantGroup;
                $merchant->type              = $request->type;
                if ($request->type == 'express')
                {
                    $checkMerchantApp = MerchantApp::where(['merchant_id' => $request->id])->first();

                    if (empty($checkMerchantApp))
                    {
                        $merchant->appInfo()->create([
                            'client_id'     => str_random(30),
                            'client_secret' => str_random(100),
                        ]);
                    }
                    else
                    {
                        $merchantApp                = MerchantApp::find($checkMerchantApp->id);
                        $merchantApp->client_id     = $checkMerchantApp->client_id;
                        $merchantApp->client_secret = $checkMerchantApp->client_secret;
                        $merchant->save();
                    }
                }
                $merchant->business_name = $request->business_name;
                $merchant->site_url      = $request->site_url;
                $merchant->fee           = $request->fee;
                if ($filename != null)
                {
                    $merchant->logo = $filename;
                }
                $merchant->status = $request->status;
                $merchant->save();

                DB::commit();
                $this->helper->one_time_message('success', 'Merchant Updated Successfully!');
                return redirect('admin/merchants');
            }
            catch (Exception $e)
            {
                DB::rollBack();
                $this->helper->one_time_message('error', $e->getMessage());
                return redirect('admin/merchants');
            }
        }
    }

    public function deleteMerchantLogo(Request $request)
    {
        $logo = $request->logo;
        if (isset($logo))
        {
            $merchant = Merchant::where(['id' => $request->merchant_id, 'logo' => $request->logo])->first();

            if ($merchant)
            {
                Merchant::where(['id' => $request->merchant_id, 'logo' => $request->logo])->update(['logo' => null]);

                if ($logo != null)
                {
                    $dir = public_path('user_dashboard/merchant/' . $logo);
                    if (file_exists($dir))
                    {
                        unlink($dir);
                    }
                }
                $data['success'] = 1;
                $data['message'] = 'Logo deleted successfully!';
            }
            else
            {
                $data['success'] = 0;
                $data['message'] = "No Record Found!";
            }
        }
        echo json_encode($data);
        exit();
    }

    public function eachMerchantPayment($id)
    {
        $data['menu']              = 'merchant';
        $data['sub_menu']          = 'merchant_details';
        $data['merchant_payments'] = $merchant_payments = MerchantPayment::where(['merchant_id' => $id])->orderBy('id', 'desc')->get();
        $data['merchant']          = $merchant          = Merchant::find($id);
        return view('admin.merchants.eachMerchantPayment', $data);
    }

    public function changeMerchantFeeWithGroupChange(Request $request)
    {
        if ($request->merchant_group_id)
        {
            $merchantGroup = MerchantGroup::where(['id' => $request->merchant_group_id])->first(['fee']);
            if ($merchantGroup)
            {
                $data['status'] = true;
                $data['fee']    = $merchantGroup->fee;
            }
            else
            {
                $data['status'] = false;
            }
            return $data;
        }
    }
}
