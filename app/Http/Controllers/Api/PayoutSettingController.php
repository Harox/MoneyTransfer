<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\Common;
use App\Models\{Country,
    PaymentMethod,
    PayoutSetting
};
use Exception;

class PayoutSettingController extends Controller
{
    public $successStatus      = 200;
    public $unsuccessStatus    = 403;
    public $unauthorisedStatus = 401;
    protected $helper;
    protected $withdrawal;

    public function __construct()
    {
        $this->helper     = new Common();
    }

    public function index()
    {
        $user_id           = request('user_id');
        $payout_setting_id = request('payout_setting_id');
        $payoutSettings = PayoutSetting::with(['paymentMethod:id,name'])
            ->where(['user_id' => $user_id])
            ->where(function ($query) use ($payout_setting_id) {
                if (!is_null($payout_setting_id)) {
                    $query->where(['id' => $payout_setting_id]);
                }
            })->get();
        $success['status'] = $this->successStatus;
        return response()->json(['success' => $success, 'payoutSettings' => $payoutSettings,], $this->successStatus);
    }


    public function delete()
    {
        try {
            DB::beginTransaction();
            $payoutSetting       = PayoutSetting::where(['user_id' => request('user_id'), 'id' => request('payout_setting_id')])->first();
            if (!empty($payoutSetting)) {
                $payoutSetting->delete();
            }
            $success['status']   = $this->successStatus;
            $success['message']  = "Payout Setting Deleted Successfully!";
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $success['status']         = $this->unsuccessStatus;
            $success['exception_msg']  = $e->getMessage();
            $success['message']        = "Something went wrong!";
        }
        return response()->json(['success' => $success,], $this->successStatus);
    }
    public function store()
    {
        try {
            DB::beginTransaction();
            $paymentMethodType      = request('paymentmethod');
            $user_id                = request('user_id');

            $payoutSetting          = new PayoutSetting();
            $payoutSetting->type    = $paymentMethodType;
            $payoutSetting->user_id = $user_id;

            if ($paymentMethodType == 6) {
                $payoutSetting->account_name        = request('account_name');
                $payoutSetting->account_number      = request('account_number');
                $payoutSetting->swift_code          = request('swift_code');
                $payoutSetting->bank_name           = request('bank_name');
                $payoutSetting->bank_branch_name    = request('branch_name');
                $payoutSetting->bank_branch_city    = request('branch_city');
                $payoutSetting->bank_branch_address = request('branch_address');
                $payoutSetting->country             = request('country');
            } else {
                $payoutSetting->email = request('email');
            }

            $payoutSetting->save();

            $success['status']   = $this->successStatus;
            $success['message']  = "Payout Setting Added Successfully!";
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $success['status']         = $this->unsuccessStatus;
            $success['exception_msg']  = $e->getMessage();
            $success['message']        = "Sorry, Unexpected error occurred";
        }
        return response()->json(['success' => $success,], $this->successStatus);
    }
    public function update()
    {
        try {
            DB::beginTransaction();
            $payout_setting_id      = request('payout_setting_id');
            $user_id                = request('user_id');
            $payoutSetting          = PayoutSetting::where(['user_id' => $user_id])
                ->where(['id' => $payout_setting_id])
                ->first();

            $paymentMethodType       = $payoutSetting->type;
            if ($paymentMethodType == 6) {
                $payoutSetting->account_name        = request('account_name');
                $payoutSetting->account_number      = request('account_number');
                $payoutSetting->swift_code          = request('swift_code');
                $payoutSetting->bank_name           = request('bank_name');
                $payoutSetting->bank_branch_name    = request('branch_name');
                $payoutSetting->bank_branch_city    = request('branch_city');
                $payoutSetting->bank_branch_address = request('branch_address');
                $payoutSetting->country             = request('country');
            } else {
                $payoutSetting->email = request('email');
            }

            $payoutSetting->save();

            $success['status']   = $this->successStatus;
            $success['message']  = "Payout Setting Updated Successfully!";
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $success['status']         = $this->unsuccessStatus;
            $success['exception_msg']  = $e->getMessage();
            $success['message']        = "Sorry, Unexpected error occurred";
        }
        return response()->json(['success' => $success,], $this->successStatus);
    }

    public function paymentMethods()
    {
        $paymentMethods    = PaymentMethod::whereNotIn('name', ['Mts', 'Stripe', '2Checkout', 'PayUMoney', 'Coinpayments', 'Payeer','BlockIo'])
            ->where(['status' => 'Active'])
            ->get(['id', 'name']);

        $success['status']  = $this->successStatus;
        return response()->json(['success' => $success, 'paymentMethods' => $paymentMethods,], $this->successStatus);
    }

    public function getAllCountries()
    {
        $success['countries'] = Country::get(['id', 'name']);
        $success['status']    = $this->successStatus;
        return response()->json(['success' => $success], $this->successStatus);
    }
}
