<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Users\EmailController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\{DB, 
    Validator
};
use Illuminate\Http\Request;
use App\Models\{RoleUser,
    Setting,
    User,
    Role
};
use Exception;

class RegistrationController extends Controller
{
    public $successStatus      = 200;
    public $unauthorisedStatus = 401;
    public $email;
    protected $user;

    public function __construct()
    {
        $this->email = new EmailController();
        $this->user  = new User();
    }

    public function getMerchantUserRoleExistence()
    {
        $data['checkMerchantRole'] = $checkMerchantRole = Role::where(['user_type' => 'User', 'customer_type' => 'merchant', 'is_default' => 'Yes'])->first(['id']);
        $data['checkUserRole']     = $checkUserRole     = Role::where(['user_type' => 'User', 'customer_type' => 'user', 'is_default' => 'Yes'])->first(['id']);

        return response()->json([
            'status'            => $this->successStatus,
            'checkMerchantRole' => $checkMerchantRole,
            'checkUserRole'     => $checkUserRole,
        ]);
    }

    public function duplicateEmailCheckApi(Request $request)
    {
        $email = User::where(['email' => $request->email])->exists();
        if ($email)
        {
            $data['status'] = true;
            $data['fail']   = 'The email has already been taken!';
        }
        else
        {
            $data['status']  = false;
            $data['success'] = "Email Available!";
        }
        return json_encode($data);
    }

    public function duplicatePhoneNumberCheckApi(Request $request)
    {
        $req_id = $request->id;
        if (isset($req_id))
        {
            $phone = User::where(['phone' => preg_replace("/[\s-]+/", "", $request->phone)])->where(function ($query) use ($req_id)
            {
                $query->where('id', '!=', $req_id);
            })->exists();
        }
        else
        {
            $phone = User::where(['phone' => preg_replace("/[\s-]+/", "", $request->phone)])->exists();
        }

        if ($phone) {
            $data['status'] = true;
            $data['fail']   = "The phone number has already been taken!";
        } else {
            $data['status']  = false;
            $data['success'] = "The phone number is Available!";
        }
        return json_encode($data);
    }

    public function getDefaultCountryShortName()
    {
        $defaultCountryShortName = getDefaultCountry();

        $success['status']  = $this->successStatus;
        $success['defaultCountryShortName'] = $defaultCountryShortName;
        return response()->json(['success' => $success], $this->successStatus);
    }

    public function registration(Request $request)
    {
        $rules = array(
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required',
        );

        $fieldNames = array(
            'first_name' => 'First Name',
            'last_name'  => 'Last Name',
            'email'      => 'Email',
            'password'   => 'Password',
        );

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails())
        {
            $response['message'] = "Email/Phone already exist.";
            $response['status']  = $this->unauthorisedStatus;
            return response()->json(['success' => $response], $this->successStatus);
        }
        else
        {
            //default_currency
            $default_currency = Setting::where('name', 'default_currency')->first(['value']);

            try
            {
                DB::beginTransaction();

                //Create user
                $user = $this->user->createNewUser($request, 'user');

                //Assign user type and role to new user
                RoleUser::insert(['user_id' => $user->id, 'role_id' => $user->role_id, 'user_type' => 'User']);

                // Create user detail
                $this->user->createUserDetail($user->id);

                // Create user's default wallet
                $this->user->createUserDefaultWallet($user->id, $default_currency->value);

                // Create user's crypto wallet/wallets address
                $this->user->generateUserCryptoWalletAddress($user);

                // Create user's crypto wallet/wallets address
                $generateUserCryptoWalletAddress = $this->user->generateUserCryptoWalletAddress($user);

                if ($generateUserCryptoWalletAddress['status'] == 401)
                {
                    DB::rollBack();
                    $success['status']  = $this->successStatus;
                    $success['reason']  = 'create-wallet-address-failed';
                    $success['message'] = $generateUserCryptoWalletAddress['message'];
                    return response()->json(['success' => $success], $this->successStatus);
                }

                $userEmail          = $user->email;
                $userFormattedPhone = $user->formattedPhone;

                // Process Registered User Transfers
                $this->user->processUnregisteredUserTransfers($userEmail, $userFormattedPhone, $user, $default_currency->value);

                // Process Registered User Request Payments
                $this->user->processUnregisteredUserRequestPayments($userEmail, $userFormattedPhone, $user, $default_currency->value);

                // Email verification
                if (!$user->user_detail->email_verification) {
                    if (checkVerificationMailStatus() == "Enabled") {
                        if (checkAppMailEnvironment()) {
                            $emainVerificationArr = $this->user->processUserEmailVerification($user);
                            try {
                                $this->email->sendEmail($emainVerificationArr['email'], $emainVerificationArr['subject'], $emainVerificationArr['message']);

                               DB::commit();
                                $success['status']  = $this->successStatus;
                                $success['reason']  = 'email_verification';
                                $success['message'] = 'We sent you an activation code. Check your email and click on the link to verify.';
                                return response()->json(['success' => $success], $this->successStatus);
                            } catch (Exception $e) {
                                DB::rollBack();
                                $success['status']  = $this->unauthorisedStatus;
                                $success['message'] = $e->getMessage();
                                return response()->json(['success' => $success], $this->unauthorisedStatus);
                            }
                        }
                    }
                }
                //
                DB::commit();
                $success['status']  = $this->successStatus;
                $success['message'] = "Registration Successfull!";
                return response()->json(['success' => $success], $this->successStatus);
            } catch (Exception $e) {
                DB::rollBack();
                $success['status']  = $this->unauthorisedStatus;
                $success['message'] = $e->getMessage();
                return response()->json(['success' => $success], $this->unauthorisedStatus);
            }
        }
    }
}
