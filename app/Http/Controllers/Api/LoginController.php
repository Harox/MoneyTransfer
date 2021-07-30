<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Users\EmailController;
use Illuminate\Support\Facades\{Auth,
    DB
};
use Lcobucci\JWT\Parser as JwtParser;
use Laravel\Passport\TokenRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Preference,
    EmailTemplate,
    ActivityLog,
    VerifyUser,
    Setting,
    Country,
    Wallet,
    User
};
use Carbon\Carbon;
use Exception;

class LoginController extends Controller
{
    public $successStatus      = 200;
    public $unauthorisedStatus = 401;
    public $unverifiedUser     = 201;
    public $inactiveUser       = 501;
    protected $helper;
    public $email;
    public $jwt;
    public $tokens;

    public function __construct()
    {
        $this->helper = new Common();
        $this->email  = new EmailController();
        $this->jwt    = new TokenRepository();
        $this->tokens = new JwtParser();
    }

    public function checkLoginVia()
    {
        $loginVia = Setting::where('name', 'login_via')->first(['value'])->value;
        return response()->json([
            'status'   => $this->successStatus,
            'loginVia' => $loginVia,
        ]);
    }

    public function getPreferenceSettings()
    {
        $preference            = Preference::where(['category' => 'preference'])->whereIn('field', ['thousand_separator', 'decimal_format_amount', 'money_format'])->get(['field', 'value'])->toArray();
        $preference            = Common::key_value('field', 'value', $preference);
        $thousand_separator    = $preference['thousand_separator'];
        $decimal_format_amount = $preference['decimal_format_amount'];
        $money_format          = $preference['money_format'];
        return response()->json([
            'status'                => $this->successStatus,
            'thousand_separator'    => $thousand_separator,
            'decimal_format_amount' => $decimal_format_amount,
            'money_format'          => $money_format,
        ]);
    }

    public function login(Request $request)
    {
        //Login Vaia - starts
        $loginVia = Setting::where('name', 'login_via')->first(['value'])->value;
        if ((isset($loginVia) && $loginVia == 'phone_only'))
        {
            //phone only
            //to remove leading '0' (zero) - bangladeshi number
            $formattedRequest = ltrim($request->email, '0');
            $phnUser          = User::where(['phone' => $formattedRequest])->orWhere(['formattedPhone' => $formattedRequest])->first(['email']);
            if (!$phnUser)
            {
                $success['status']  = $this->unauthorisedStatus;
                $success['message'] = "Invalid email & credentials";
                return response()->json(['success' => $success], $this->unauthorisedStatus);
            }
            $request->email = $phnUser->email;
        }
        else if (isset($loginVia) && $loginVia == 'email_or_phone')
        {
            //phone or email
            if (strpos($request->email, '@') !== false)
            {
                $user = User::where(['email' => $request->email])->first(['email']);
                if (!$user)
                {
                    $success['status']  = $this->unauthorisedStatus;
                    $success['message'] = "Invalid email & credentials";
                    return response()->json(['success' => $success], $this->unauthorisedStatus);
                }
                $request->email = $user->email;
            }
            else
            {
                $formattedRequest = ltrim($request->email, '0'); //to remove leading '0' (zero) - bangladeshi number
                $phoneOrEmailUser = User::where(['phone' => $formattedRequest])->orWhere(['formattedPhone' => $formattedRequest])->first(['email']);
                if (!$phoneOrEmailUser)
                {
                    $success['status']  = $this->unauthorisedStatus;
                    $success['message'] = "Invalid email & credentials";
                    return response()->json(['success' => $success], $this->unauthorisedStatus);
                }
                $request->email = $phoneOrEmailUser->email;
            }
        }
        else
        {
            //email only
            $user = User::where(['email' => $request->email])->first(['email']);
            if (!$user)
            {
                $success['status']  = $this->unauthorisedStatus;
                $success['message'] = "Invalid email & credentials";
                return response()->json(['success' => $success], $this->unauthorisedStatus);
            }
            $request->email = $user->email;
        }
        //Login Vaia - ends

        //Check User Status
        $checkLoggedInUser = User::where(['email' => $request->email])->first(['status']);
        if ($checkLoggedInUser->status == 'Inactive')
        {
            $success['status']      = $this->successStatus;
            $success['user-status'] = $checkLoggedInUser->status;
            $success['message']     = 'Your account is inactivated. Please try again later!';
            return response()->json(['response' => $success], $this->successStatus);
        }

        // Check user email verification
        $checkUserVerificationStatus = $this->checkUserVerificationStatusApi($request->email);
        if ($checkUserVerificationStatus == true)
        {
            $success['status']  = $this->unverifiedUser;
            $success['message'] = 'We sent you an activation code. Check your email and click on the link to verify.';
            return response()->json(['response' => $success], $this->unverifiedUser);
        }
        else
        {
            //Auth attempt - starts
            if (Auth::attempt(['email' => $request->email, 'password' => request('password')]))
            {
                $user             = Auth::user();
                $default_currency = Setting::where('name', 'default_currency')->first(['value']);
                $chkWallet        = Wallet::where(['user_id' => $user->id, 'currency_id' => $default_currency->value])->first();
                try
                {
                    DB::beginTransaction();

                    if (empty($chkWallet))
                    {
                        $wallet              = new Wallet();
                        $wallet->user_id     = $user->id;
                        $wallet->currency_id = $default_currency->value;
                        $wallet->balance     = 0.00;
                        $wallet->is_default  = 'No';
                        $wallet->save();
                    }

                    $log                  = [];
                    $log['user_id']       = Auth::check() ? $user->id : null;
                    $log['type']          = 'User';
                    $log['ip_address']    = $request->ip();
                    $log['browser_agent'] = $request->header('user-agent');
                    ActivityLog::create($log);

                    //user_detail - adding last_login_at and last_login_ip
                    $user->user_detail()->update([
                        'last_login_at' => Carbon::now()->toDateTimeString(),
                        'last_login_ip' => $request->getClientIp(),
                    ]);
                    DB::commit();

                    $defaultCountry = Country::where('is_default', 'yes')->first();

                    $success['user_id']        = $user->id;
                    $success['first_name']     = $user->first_name;
                    $success['last_name']      = $user->last_name;
                    $success['email']          = $user->email;
                    $success['formattedPhone'] = $user->formattedPhone;
                    $success['picture']        = $user->picture;
                    $success['defaultCountry'] = strtolower($defaultCountry->short_name);
                    $fullName                  = $user->first_name . ' ' . $user->last_name;
                    $accessToken               = DB::table('oauth_access_tokens')->where('user_id', $user->id);
                    $getAccessToken            = $accessToken->first(['id']);
                    if (empty($getAccessToken))
                    {
                        $success['token'] = $user->createToken($fullName)->accessToken;
                    }
                    else
                    {
                        $accessToken->delete();
                        $success['token'] = $user->createToken($fullName)->accessToken;
                    }
                    $success['status']      = $this->successStatus;
                    $success['user-status'] = $checkLoggedInUser->status;
                    return response()->json(['response' => $success], $this->successStatus);
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                    $success['status']  = $this->unauthorisedStatus;
                    $success['message'] = $e->getMessage();
                    return response()->json(['response' => $success], $this->unauthorisedStatus);
                }
            }
            else
            {
                //d($request->all(),1);
                $success['status']  = $this->unauthorisedStatus;
                $success['message'] = "Invalid email & credentials";
                return response()->json(['response' => $success], $this->unauthorisedStatus);
            }
            //Auth attempt - ends
        }
    }

    //Check User Verification Status
    protected function checkUserVerificationStatusApi($userEmail)
    {
        $checkLoginDataOfUser = User::where(['email' => $userEmail])->first(['id', 'first_name', 'last_name', 'email', 'status']);
        if (checkVerificationMailStatus() == 'Enabled' && $checkLoginDataOfUser->user_detail->email_verification == 0)
        {
            try
            {
                $verifyUser = VerifyUser::where(['user_id' => $checkLoginDataOfUser->id])->first(['id']);
                if (empty($verifyUser))
                {
                    $verifyUserNewRecord          = new VerifyUser();
                    $verifyUserNewRecord->user_id = $checkLoginDataOfUser->id;
                    $verifyUserNewRecord->token   = str_random(40);
                    $verifyUserNewRecord->save();
                }
                $englishUserVerificationEmailTempInfo = EmailTemplate::where(['temp_id' => 17, 'lang' => 'en', 'type' => 'email'])->select('subject', 'body')->first();
                $userVerificationEmailTempInfo        = EmailTemplate::where([
                    'temp_id'     => 17,
                    'language_id' => getDefaultLanguage(),
                    'type'        => 'email',
                ])->select('subject', 'body')->first();

                if (!empty($userVerificationEmailTempInfo->subject) && !empty($userVerificationEmailTempInfo->body))
                {
                    $userVerificationEmailTempInfo_sub = $userVerificationEmailTempInfo->subject;
                    $userVerificationEmailTempInfo_msg = str_replace('{user}', $checkLoginDataOfUser->first_name . ' ' . $checkLoginDataOfUser->last_name, $userVerificationEmailTempInfo->body);
                }
                else
                {
                    $userVerificationEmailTempInfo_sub = $englishUserVerificationEmailTempInfo->subject;
                    $userVerificationEmailTempInfo_msg = str_replace('{user}', $checkLoginDataOfUser->first_name . ' ' . $checkLoginDataOfUser->last_name, $englishUserVerificationEmailTempInfo->body);
                }
                $userVerificationEmailTempInfo_msg = str_replace('{email}', $checkLoginDataOfUser->email, $userVerificationEmailTempInfo_msg);
                $userVerificationEmailTempInfo_msg = str_replace('{verification_url}', url('user/verify', $checkLoginDataOfUser->verifyUser->token), $userVerificationEmailTempInfo_msg);
                $userVerificationEmailTempInfo_msg = str_replace('{soft_name}', getCompanyName(), $userVerificationEmailTempInfo_msg);

                if (checkAppMailEnvironment())
                {
                    try
                    {
                        $this->email->sendEmail($checkLoginDataOfUser->email, $userVerificationEmailTempInfo_sub, $userVerificationEmailTempInfo_msg);
                        return true;
                    }
                    catch (Exception $e)
                    {
                        $success['status']  = $this->unauthorisedStatus;
                        $success['message'] = $e->getMessage();
                        return response()->json(['success' => $success], $this->unauthorisedStatus);
                    }
                }
            }
            catch (Exception $e)
            {
                $success['status']  = $this->unauthorisedStatus;
                $success['message'] = $e->getMessage();
                return response()->json(['response' => $success], $this->unauthorisedStatus);
            }
        }
    }
}
