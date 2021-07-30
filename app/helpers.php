<?php

use App\Models\Country;
use App\Models\CurrencyPaymentMethod;
use App\Models\Meta;
use App\Models\Pages;
use App\Models\SmsConfig;
use App\Models\Preference;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Twilio\Rest\Client;

function setDateForDb($value)
{
    $separator   = Session::get('date_sepa');
    $date_format = Session::get('date_format_type');

    if (str_replace($separator, '', $date_format) == "mmddyyyy")
    {
        $value = str_replace($separator, '/', $value);
        $date  = date('Y-m-d', strtotime($value));
    }
    else
    {
        $date = date('Y-m-d', strtotime(strtr($value, $separator, '-')));
    }
    return $date;
}

function array2string($data)
{
    $log_a = "";
    foreach ($data as $key => $value)
    {
        if (is_array($value))
        {
            $log_a .= "\r\n'" . $key . "' => [\r\n" . array2string($value) . "\r\n],";
        }
        else
        {
            $log_a .= "'" . $key . "'" . " => " . "'" . str_replace("'", "\\'", $value) . "',\r\n";
        }

    }
    return $log_a;
}

function d($var, $a = false)
{
    echo "<pre>";
    print_r($var);
    echo "</pre>";
    if ($a)
    {
        exit;
    }
}

/**
 * [unique code
 * @return [void] [unique code for each transaction]
 */
function unique_code()
{
    $length = 13;
    if (function_exists("random_bytes"))
    {
        $bytes = random_bytes(ceil($length / 2));
    }
    elseif (function_exists("openssl_random_pseudo_bytes"))
    {
        $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
    }
    else
    {
        throw new Exception("no cryptographically secure random function available");
    }
    return strtoupper(substr(bin2hex($bytes), 0, $length));
}

/**
 * [current_balance description]
 * @return [void] [displaying default wallet balance on page header]
 */
function current_balance() //TODO: remove it
{
    $wallet            = App\Models\Wallet::with('currency:id,code')->where(['user_id' => \Auth::user()->id, 'is_default' => 'Yes'])->first();
    $balance_with_code = moneyFormat($wallet->currency->code, '+' . formatNumber($wallet->balance));
    return $balance_with_code;
}

/**
 * [userWallets description]
 * @return [void] [dropdown of wallets on page header]
 */
function userWallets()
{
    $wallet = App\Models\Wallet::where(['user_id' => \Auth::user()->id])->get();
    return $wallet;
}

function AssColumn($a = array(), $column = 'id')
{
    $two_level = func_num_args() > 2 ? true : false;
    if ($two_level)
    {
        $scolumn = func_get_arg(2);
    }

    $ret = array();
    settype($a, 'array');
    if (false == $two_level)
    {
        foreach ($a as $one)
        {
            if (is_array($one))
            {
                $ret[@$one[$column]] = $one;
            }
            else
            {
                $ret[@$one->$column] = $one;
            }

        }
    }
    else
    {
        foreach ($a as $one)
        {
            if (is_array($one))
            {
                if (false == isset($ret[@$one[$column]]))
                {
                    $ret[@$one[$column]] = array();
                }
                $ret[@$one[$column]][@$one[$scolumn]] = $one;
            }
            else
            {
                if (false == isset($ret[@$one->$column]))
                {
                    $ret[@$one->$column] = array();
                }

                $ret[@$one->$column][@$one->$scolumn] = $one;
            }
        }
    }
    return $ret;
}

/**
 * [dateFormat description]
 * @param  [type] $value    [any number]
 * @return [type] [formates date according to preferences setting in Admin Panel]
 */
function dateFormat($value, $userId = null) //$userId - needed for using user_id for mobile app (as mobile app does not know auth()->user()->id)
{
    $timezone = '';
    $prefix   = str_replace('/', '', request()->route()->getPrefix());
    if ($prefix == 'admin')
    {
        $timezone = Preference::where(['category' => 'preference', 'field' => 'dflt_timezone'])->first(['value'])->value;
    }
    else
    {
        if (!empty($userId))
        {
            $user = App\Models\User::with('user_detail:user_id,timezone')->where(['id' => $userId])->first(['id']);
        }
        else
        {
            if (!empty(auth()->user()->id))
            {
                $user = App\Models\User::with('user_detail:user_id,timezone')->where(['id' => auth()->user()->id])->first(['id']);
            }
        }
        if (!empty(auth()->user()->id) || !empty($userId))
        {
            $timezone = $user->user_detail->timezone;
        }
        else
        {
            $timezone = 'UTC';
        }
    }
    $today = new DateTime($value, new DateTimeZone(config('app.timezone')));
    $today->setTimezone(new DateTimeZone($timezone));
    $value = $today->format('Y-m-d');

    $preferenceData = Preference::where(['category' => 'preference'])->whereIn('field', ['date_format_type', 'date_sepa'])->get(['field', 'value'])->toArray();
    $preferenceData = App\Http\Helpers\Common::key_value('field', 'value', $preferenceData);
    $preference     = $preferenceData['date_format_type'];
    $separator      = $preferenceData['date_sepa'];

    $data   = str_replace(['/', '.', ' ', '-'], $separator, $preference);
    $data   = explode($separator, $data);
    $first  = $data[0];
    $second = $data[1];
    $third  = $data[2];

    $dateInfo = str_replace(['/', '.', ' ', '-'], $separator, $value);
    $datas    = explode($separator, $dateInfo);
    $year     = $datas[0];
    $month    = $datas[1];
    $day      = $datas[2];

    $dateObj   = DateTime::createFromFormat('!m', $month);
    $monthName = $dateObj->format('F');

    $toHoursMin = \Carbon\Carbon::createFromTimeStamp(strtotime($value))->format(' g:i A');
    if ($first == 'yyyy' && $second == 'mm' && $third == 'dd')
    {
        $value = $year . $separator . $month . $separator . $day . $toHoursMin;
    }
    elseif ($first == 'dd' && $second == 'mm' && $third == 'yyyy')
    {

        $value = $day . $separator . $month . $separator . $year . $toHoursMin;
    }
    elseif ($first == 'mm' && $second == 'dd' && $third == 'yyyy')
    {

        $value = $month . $separator . $day . $separator . $year . $toHoursMin;
    }
    elseif ($first == 'dd' && $second == 'M' && $third == 'yyyy')
    {
        $value = $day . $separator . $monthName . $separator . $year . $toHoursMin;
    }
    elseif ($first == 'yyyy' && $second == 'M' && $third == 'dd')
    {
        $value = $year . $separator . $monthName . $separator . $day . $toHoursMin;
    }
    return $value;

}

/**
 * [roundFormat description]
 * @param  [type] $value   [any number]
 * @return [type] [formats to 2 decimal places]
 */
function decimalFormat($value) //modified on may 21,2018
{
    $pref_amount = Session::get('decimal_format_amount');

    if ($pref_amount == "1")
    {
        $condition = 1;
    }

    if ($pref_amount == "2")
    {
        $condition = 2;
    }

    if ($pref_amount == "3")
    {
        $condition = 3;
    }

    if ($pref_amount == "4")
    {
        $condition = 4;
    }

    if ($pref_amount == "5")
    {
        $condition = 5;
    }

    if ($pref_amount == "6")
    {
        $condition = 6;
    }

    if ($pref_amount == "7")
    {
        $condition = 7;
    }

    if ($pref_amount == "8")
    {
        $condition = 8;
    }

    if ($pref_amount == "9")
    {
        $condition = 9;
    }

    if ($pref_amount == "10")
    {
        $condition = 10;
    }

    if (!empty($pref_amount))
    {
        $value = number_format((float) ($value), $condition, '.', '');
        return $value;
    }
}

/**
 * [roundFormat description]
 * @param  [type] $value     [any number]
 * @return [type] [placement of money symbol according to preferences setting in Admin Panel]
 */
function moneyFormat($symbol, $value)
{
    // $symbol_position = Session::get('money_format');
    $symbol_position = Preference::where(['category' => 'preference', 'field' => 'money_format'])->first(['value'])->value;
    if (!empty($symbol_position))
    {
        if ($symbol_position == "before")
        {
            $value = $symbol . ' ' . $value;
        }
        elseif ($symbol_position == "after")
        {
            $value = $value . ' ' . $symbol;
        }
        return $value;
    }
}

function moneyFormatForDashboardProgressBars($symbol, $value)
{
    $symbol_position = Session::get('money_format');
    if (!empty($symbol_position))
    {
        if ($symbol_position == "before")
        {
            $value = $symbol . '' . $value;
        }
        elseif ($symbol_position == "after")
        {
            $value = $value . '' . $symbol;
        }
        return $value;
    }
}

/**
 * [roundFormat description]
 * @param  [type] $value     [any number]
 * @return [type] [placement of money symbol according to preferences setting in Admin Panel]
 */
function thousandsCurrencyFormat($num)
{
    if ($num < 1000)
    {
        return $num;
    }
    $x               = round($num);
    $x_number_format = number_format($x);
    $x_array         = explode(',', $x_number_format);
    $x_parts         = array('k', 'm', 'b', 't');
    $x_count_parts   = count($x_array) - 1;
    $x_display       = $x;
    $x_display       = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
    $x_display .= $x_parts[$x_count_parts - 1];
    return $x_display;
}

//function to set pages position on frontend
function getMenuContent($position)
{
    $data = Pages::where('position', 'like', "%$position%")->where('status', 1)->get(['url', 'name']);
    return $data;
}

function getSocialLink()
{
    $data = collect(DB::table('socials')->get(['url', 'icon'])->filter(function ($social)
    {
        return !empty($social->url);
    }))->toArray();
    return $data;
}

function meta($url, $field)
{
    $meta = Meta::where('url', $url)->first(['title']);
    if ($meta)
    {
        return $meta->$field;
    }
    elseif ($field == 'title' || $field == 'description' || $field == 'keyword')
    {
        return "Page Not Found";
    }
    else
    {
        return "";
    }
}

function available_balance()
{
    $wallet = App\Models\Wallet::where(['user_id' => \Auth::user()->id, 'is_default' => 'Yes'])->first(['balance']);
    return $wallet->balance;
}

function getTime($date)
{
    $time = date("H:i A", strtotime($date));
    return $time;
}

function changeEnvironmentVariable($key, $value)
{
    $path = base_path('.env');

    if (is_bool(env($key)))
    {
        $old = env($key) ? 'true' : 'false';
    }
    elseif (env($key) === null)
    {
        $old = 'null';
    }
    else
    {
        $old = env($key);
    }

    if (file_exists($path))
    {
        if ($old == 'null')
        {

            file_put_contents($path, "$key=" . $value, FILE_APPEND);
        }
        else
        {
            file_put_contents($path, str_replace(
                "$key=" . $old, "$key=" . $value, file_get_contents($path)
            ));
        }
    }
}

function getCompanyName()
{
    $setting = App\Models\Setting::where(['name' => 'name'])->first(['value']);
    return $setting->value;
}

function getDefaultLanguage()
{
    $setting = App\Models\Setting::where('name', 'default_language')->first(['value']);
    return $setting->value;
}

function thirtyDaysNameList()
{
    $data = array();
    for ($j = 30; $j > -1; $j--)
    {
        $data[30 - $j] = date("d M", strtotime("-$j day"));
    }
    return $data;
}

function getLastOneMonthDates()
{
    $data = array();
    for ($j = 30; $j > -1; $j--)
    {
        $data[30 - $j] = date("d-m", strtotime(" -$j day"));
    }
    return $data;
}

function encryptIt($value)
{
    $encoded = base64_encode(\Illuminate\Support\Facades\Hash::make($value));
    return ($encoded);
}

function formatNumber($num = 0)
{
    $preference     = Preference::where(['category' => 'preference'])->whereIn('field', ['thousand_separator', 'decimal_format_amount'])->get(['field', 'value'])->toArray();
    $preference     = App\Http\Helpers\Common::key_value('field', 'value', $preference);
    $seperator      = $preference['thousand_separator'];
    $decimal_format = $preference['decimal_format_amount'];

    if ($seperator == '.')
    {
        $num = number_format($num, $decimal_format, ",", ".");
    }
    else if ($seperator == ',')
    {
        $num = number_format($num, $decimal_format, ".", ",");
    }
    return $num;
}

function getLanguagesListAtFooterFrontEnd()
{
    $languages = App\Models\Language::where(['status' => 'Active'])->get(['short_name', 'name']);
    return $languages;
}

function getAppStoreLinkFrontEnd()
{
    $app = App\Models\AppStoreCredentials::where(['has_app_credentials' => 'Yes'])->get(['logo', 'link']);
    return $app;
}

function getCurrencyRate($from, $to)
{
    $url = "https://free.currencyconverterapi.com/api/v6/convert?q=$from" . "_" . "$to&compact=ultra&apiKey=2187e4b0b3a87f0b0aae";
    // example - https://free.currencyconverterapi.com/api/v6/convert?q=USD_EUR&compact=ultra&apiKey=2187e4b0b3a87f0b0aae

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE),
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE),
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    $variable = $from . "_" . $to;
    return json_decode($result)->$variable;
}

function getfavicon()
{
    $getFaviconSetting = \App\Models\Setting::where(['name' => 'favicon', 'type' => 'general'])->first(['value']);
    $getFaviconSetting = $getFaviconSetting->value;
    return $getFaviconSetting;
}

function getCompanyLogo()
{
    $session = session('company_logo');
    if (!$session)
    {
        $session = \App\Models\Setting::where(['name' => 'logo', 'type' => 'general'])->first(['value']);
        $session = $session->value;
        session(['company_logo' => $session]);
    }
    return $session;
}

function setActionSession()
{
    $key = time();
    session(['action-session' => encrypt($key)]);
    session(['session-key' => $key]);
}

function actionSessionCheck()
{
    if (!Session::has('action-session'))
    {
        abort(404);
    }
    else
    {
        $key          = session('session-key');
        $encryptedKey = session('action-session');
        if ($key != decrypt($encryptedKey))
        {
            abort(404);
        }
    }
}

function clearActionSession()
{
    session()->forget('action-session');
    session()->forget('session-key');
}

function getCurrencyIdOfTransaction($transactions)
{
    $currencies = [];
    foreach ($transactions as $trans)
    {
        $currencies[] = $trans->currency_id;
    }
    return $currencies;
}

//fixed - for exchange rate - if set to 0 - which is unusual
function generateAmountBasedOnDfltCurrency($data, $currencyWithRate)
{
    $data_map = [];
    foreach ($data as $key => $value)
    {
        foreach ($currencyWithRate as $currencyRate)
        {
            if ($currencyRate->id == $value->currency_id)
            {
                if (!isset($data_map[$value->day][$value->month]))
                {
                    $data_map[$value->day][$value->month] = 0;
                }
                if ($value->currency_id != session('default_currency'))
                {
                    if ($currencyRate->rate != 0)
                    {
                        $data_map[$value->day][$value->month] += abs($value->amount / $currencyRate->rate);
                    }
                    else
                    {
                        $data_map[$value->day][$value->month] = 0;
                    }
                }
                else
                {
                    $data_map[$value->day][$value->month] += abs($value->amount);
                }
            }
        }
    }
    return $data_map;
}

//fixed - for exchange rate - if set to 0 - which is unusual
function generateAmountForTotal($data, $currencyWithRate)
{
    $final = 0;
    foreach ($data as $key => $value)
    {
        foreach ($currencyWithRate as $currencyRate)
        {
            if ($currencyRate->id == $value->currency_id)
            {
                if ($value->currency_id != session('default_currency'))
                {
                    if ($currencyRate->rate != 0)
                    {
                        $final += abs($value->total_charge / $currencyRate->rate);
                    }
                    else
                    {
                        // $data_map[$value->day][$value->month] = 0;
                        $final += 0;
                    }
                }
                else
                {
                    $final += abs($value->total_charge);
                }
            }
        }
    }
    return $final;
}

function checkAppMailEnvironment()
{
    $checkMail = env('APP_MAIL', 'true');
    return $checkMail;
}

function checkAppSmsEnvironment()
{
    $checkSms = env('APP_SMS', 'true');
    return $checkSms;
}

function getCompanyLogoWithoutSession()
{
    $logo = \App\Models\Setting::where(['name' => 'logo', 'type' => 'general'])->first(['value'])->value;
    return $logo;
}

//PHP Default Timezones
function phpDefaultTimeZones()
{
    $zones_array = array();
    $timestamp   = time();
    foreach (timezone_identifiers_list() as $key => $zone)
    {
        date_default_timezone_set($zone);
        $zones_array[$key]['zone']          = $zone;
        $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
    }
    return $zones_array;
    return $timezones;
}

function getSmsConfigDetails()
{
    return SmsConfig::where(['status' => 'Active'])->first();
}

function sendSMSwithNexmo($nexmoCredentials, $to, $message)
{
    $trimmedMsg = trim(preg_replace('/\s\s+/', ' ', $message));
    $url        = 'https://rest.nexmo.com/sms/json?' . http_build_query([
        'api_key'    => '' . trim($nexmoCredentials['Key']) . '',
        'api_secret' => '' . trim($nexmoCredentials['Secret']) . '',
        'from'       => '' . $nexmoCredentials['default_nexmo_phone_number'] . '',
        'to'         => '' . $to . '',
        'text'       => '' . strip_tags($trimmedMsg) . '',
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    /*
        // Quota Exceeded - rejected - TEST
        $res = json_decode($response, true);
        if (isset($res['messages'][0]['error-text']))
        {
            // return & redirect to correct route
            return [
                'status' => false,
                'message' => $res['messages'][0]['error-text'],
            ];
        }
        else
        {
            return [
                'status' => true,
            ];
        }
    */
}

function sendSMSwithTwilio($twilioCredentials, $to, $message)
{
    $accountSID   = $twilioCredentials['account_sid'];
    $authToken    = $twilioCredentials['auth_token'];
    $twilioNumber = $twilioCredentials['default_twilio_phone_number'];
    $trimmedMsg   = trim(preg_replace('/\s\s+/', ' ', $message));

    $client = new Client($accountSID, $authToken);
    $client->messages->create(
        $to,
        array(
            'from' => $twilioNumber,
            'body' => strip_tags($trimmedMsg)
        )
    );
}

function sendSMS($to, $message)
{
    $smsConfig = getSmsConfigDetails();
    if (!empty($smsConfig))
    {
        $smsCredentials = json_decode($smsConfig->credentials, true);
        if (count($smsCredentials) > 0)
        {
            if ($smsConfig->type == 'nexmo')
            {
                sendSMSwithNexmo($smsCredentials, $to, $message);
                // return sendSMSwithNexmo($smsCredentials, $to, $message);
            }
            elseif ($smsConfig->type == 'twilio')
            {
                sendSMSwithTwilio($smsCredentials, $to, $message);
            }
        }
    }
}

function checkVerificationMailStatus()
{
    $verification_mail = App\Models\Preference::where(['category' => 'preference', 'field' => 'verification_mail'])->first(['value'])->value;
    return $verification_mail;
}

function checkPhoneVerification()
{
    $phoneVerification = App\Models\Preference::where(['category' => 'preference', 'field' => 'phone_verification'])->first(['value'])->value;
    return $phoneVerification;
}

function twoStepVerification()
{
    $two_step_verification = Preference::where(['category' => 'preference', 'field' => 'two_step_verification'])->first(['value'])->value;
    return $two_step_verification;
}

function six_digit_random_number()
{
    return mt_rand(100000, 999999);
}

// http://www.php.net/manual/en/function.get-browser.php#101125
function getBrowser($agent)
{
    // $u_agent  = $_SERVER['HTTP_USER_AGENT'];
    $u_agent  = $agent;
    $bname    = 'Unknown';
    $platform = 'Unknown';
    $version  = "";

    // First get the platform?
    if (preg_match('/linux/i', $u_agent))
    {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent))
    {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent))
    {
        $platform = 'windows';
    }

    // Next get the name of the useragent yes seperately and for good reason
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent))
    {
        $bname = 'Internet Explorer';
        $ub    = "MSIE";
    }
    elseif (preg_match('/Firefox/i', $u_agent))
    {
        $bname = 'Mozilla Firefox';
        $ub    = "Firefox";
    }
    elseif (preg_match('/Chrome/i', $u_agent))
    {
        $bname = 'Google Chrome';
        $ub    = "Chrome";
    }
    elseif (preg_match('/Safari/i', $u_agent))
    {
        $bname = 'Apple Safari';
        $ub    = "Safari";
    }
    elseif (preg_match('/Opera/i', $u_agent))
    {
        $bname = 'Opera';
        $ub    = "Opera";
    }
    elseif (preg_match('/Netscape/i', $u_agent))
    {
        $bname = 'Netscape';
        $ub    = "Netscape";
    }

    // finally get the correct version number
    $known   = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches))
    {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1)
    {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent, "Version") < strripos($u_agent, $ub))
        {
            $version = $matches['version'][0];
        }
        else
        {
            $version = $matches['version'][1];
        }
    }
    else
    {
        $version = $matches['version'][0];
    }

    // check if we have a number
    if ($version == null || $version == "")
    {
        $version = "?";}

    return array(
        'name'     => $bname,
        'version'  => $version,
        'platform' => $platform,
    );
}

function getBrowserFingerprint($user_id, $browser_fingerprint)
{
    $getBrowserFingerprint = App\Models\DeviceLog::where(['user_id' => $user_id, 'browser_fingerprint' => $browser_fingerprint])->first(['browser_fingerprint']);
    return $getBrowserFingerprint;
}

function checkDemoEnvironment()
{
    $checkSms = env('APP_DEMO', 'true');
    return $checkSms;
}

function coinPaymentInfo()
{
    $transInfo = Session::get('transInfo');
    $cpm       = CurrencyPaymentMethod::where(['method_id' => $transInfo['payment_method'], 'currency_id' => $transInfo['currency_id']])->first(['method_data']);
    return json_decode($cpm->method_data);
}

function captchaCheck($setting, $key)
{
    if (isset($setting) && $setting['has_captcha'] == 'Enabled') {
        $captchaKey =  Setting::where(['type' => 'recaptcha', 'name' => $key])->first(['value'])->value;
        Config::set([(($key == 'site_key') ? 'captcha.sitekey' : 'captcha.secret') => $captchaKey]);
    }
}

function getLanguageDefault()
{
    $getDefaultLanguage = \App\Models\Language::where(['default' => '1'])->first(['id', 'short_name']);
    return $getDefaultLanguage;
}

function getAuthUserIdentity()
{
    $getAuthUserIdentity = \App\Models\DocumentVerification::where(['user_id' => auth()->user()->id, 'verification_type' => 'identity'])->first(['verification_type', 'status']);
    return $getAuthUserIdentity;
}

function getAuthUserAddress()
{
    $getAuthUserAddress = \App\Models\DocumentVerification::where(['user_id' => auth()->user()->id, 'verification_type' => 'address'])->first(['verification_type', 'status']);
    return $getAuthUserAddress;
}

function getGoogleAnalyticsTrackingCode()
{
    $setting = App\Models\Setting::where(['name' => 'head_code'])->first(['value']);
    return $setting->value;
}

function getDecimalThousandMoneyFormatPref($optionsArr)
{
    $preferences = Preference::where(['category' => 'preference'])->whereIn('field', $optionsArr)->get(['field', 'value'])->toArray();
    $preferences = App\Http\Helpers\Common::key_value('field', 'value', $preferences);
    return $preferences;
}

function allowedDecimalPlaceMessage($decimalPosition)
{
    $message = "*Allowed upto " . $decimalPosition . " decimal places.";
    return $message;
}

function allowedImageDimension($width, $height, $panel = null)
{
    if ($panel == 'user')
    {
        $message = "*" . __('Recommended Dimension') . ": " . $width . " px * " . $height . " px";
    }
    else
    {
        $message = "*Recommended Dimension: " . $width . " px * " . $height . " px";
    }
    return $message;
}

/**
 * [CUSTOM AES-256 ENCRYPTION/DECRYPTION METHOD]
 * param  $action [encrypt/decrypt]
 * param  $string [string]
 */
function initAES256($action, $plaintext)
{
    $output   = '';
    $cipher   = "AES-256-CBC";
    $password = 'K8m26hzj22TtZxnzX96vmRAVTzPxNXRB';
    $key      = substr(hash('sha256', $password, true), 0, 32); // Must be exact 32 chars (256 bit)
                                                                // $ivlen    = openssl_cipher_iv_length($cipher);
                                                                // $iv       = openssl_random_pseudo_bytes($ivlen); // IV must be exact 16 chars (128 bit)
    $secretIv = 'UP4n2cr8Bwn83X4h';
    $iv       = substr(hash('sha256', $secretIv), 0, 16);
    if ($plaintext != '')
    {
        if ($action == 'encrypt')
        {
            $output = base64_encode(openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv));
        }
        if ($action == 'decrypt')
        {
            $output = openssl_decrypt(base64_decode($plaintext), $cipher, $key, OPENSSL_RAW_DATA, $iv);
        }
    }
    return $output;
}

function getDefaultCountry()
{
   return Country::where(['is_default' => 'yes'])->first()->short_name;
}