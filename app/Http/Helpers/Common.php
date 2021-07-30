<?php
namespace App\Http\Helpers;

use App\Http\Controllers\Users\EmailController;
use App\Models\CryptoapiLog;
use App\Models\CryptoCurrenciesSetting;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\FeesLimit;
use App\Models\NotificationSetting;
use App\Models\PayoutSetting;
use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Preference;
use App\Models\RoleUser;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Exception;

class Common
{
    public static $templateIds = ['deposit' => 23, 'payout' => 24, 'exchange' => 25, 'send' => 26, 'request' => 27, 'payment' => 28];
    public static $languages   = [1 => 'en', 2 => 'ar', 3 => 'fr', 4 => 'pt', 5 => 'ru', 6 => 'es', 7 => 'tr', 8 => 'ch'];
    protected $email;
    protected $cryptoCurrenciesSetting;
    protected $cryptoapiLog;

    public function __construct()
    {
        setlocale(LC_ALL, 'en_US.UTF8');
        $this->email                   = new EmailController();
        $this->cryptoCurrenciesSetting = new CryptoCurrenciesSetting();
        $this->cryptoapiLog            = new CryptoapiLog();
    }

    public static function one_time_message($class, $message)
    {
        if ($class == 'error')
        {
            $class = 'danger';
        }
        Session::flash('alert-class', 'alert-' . $class);
        Session::flash('message', $message);
    }

    // key_value [key,value, collection]
    public static function key_value($key, $value, $collection)
    {
        $return_value = [];
        foreach ($collection as $row)
        {
            $return_value[$row[$key]] = $row[$value];
        }
        return $return_value;
    }

    /*
     * @param $user_id
     * @param $permissions
     * @static has_permission
     */
    public static function has_permission($user_id, $permissions = '')
    {
        $permissions = explode('|', $permissions);

        $permission_id = [];
        $i             = 0;

        $prefix = str_replace('/', '', request()->route()->getPrefix());
        if ($prefix == 'admin')
        {
            $user_type = 'Admin';
        }
        else
        {
            $user_type = 'User';
        }

        $userPermissions = Permission::whereIn('name', $permissions)->get(['id']);
        foreach ($userPermissions as $value)
        {
            $permission_id[$i++] = $value->id;
        }
        $role = RoleUser::where(['user_id' => $user_id, 'user_type' => $user_type])->first(['role_id']);
        if (count($permission_id) && isset($role->role_id))
        {
            $has_permit = PermissionRole::where('role_id', $role->role_id)->whereIn('permission_id', $permission_id);
            return $has_permit->count();
        }
        else
        {
            return 0;
        }
    }

    /**
     * Undocumented function
     *
     * @param  [type] $host
     * @param  [type] $user
     * @param  [type] $pass
     * @param  [type] $name
     * @param  string $tables
     * @return void
     */
    public function backup_tables($host, $user, $pass, $name, $tables = '*')
    {
        try {
            $con = mysqli_connect($host, $user, $pass, $name);
        }
        catch (Exception $e)
        {
            print_r($e->getMessage());
        }

        if (mysqli_connect_errno())
        {
            $this->one_time_message('danger', "Failed to connect to MySQL: " . mysqli_connect_error());
            return 0;
        }

        if ($tables == '*')
        {
            $tables = array();
            $result = mysqli_query($con, 'SHOW TABLES');
            while ($row = mysqli_fetch_row($result))
            {
                $tables[] = $row[0];
            }
        }
        else
        {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }

        $return = '';
        foreach ($tables as $table)
        {
            $result     = mysqli_query($con, 'SELECT * FROM ' . $table);
            $num_fields = mysqli_num_fields($result);

            $row2 = mysqli_fetch_row(mysqli_query($con, 'SHOW CREATE TABLE ' . $table));
            $return .= "\n\n" . str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $row2[1]) . ";\n\n";

            for ($i = 0; $i < $num_fields; $i++)
            {
                while ($row = mysqli_fetch_row($result))
                {
                    $return .= 'INSERT INTO ' . $table . ' VALUES(';
                    for ($j = 0; $j < $num_fields; $j++)
                    {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
                        if (isset($row[$j]))
                        {
                            $return .= '"' . $row[$j] . '"';
                        }
                        else
                        {
                            $return .= '""';
                        }
                        if ($j < ($num_fields - 1))
                        {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
            }

            $return .= "\n\n\n";
        }

        $backup_name = date('Y-m-d-His') . '.sql';

        $handle = fopen(storage_path("db-backups") . '/' . $backup_name, 'w+');
        fwrite($handle, $return);
        fclose($handle);

        return $backup_name;
    }

    //  Check user status
    public function getUserStatus($userStatus)
    {
        if ($userStatus == 'Suspended')
        {
            return 'Suspended';
        }
        elseif ($userStatus == 'Inactive')
        {
            return 'Inactive';
        }
    }

    public function checkWalletBalanceAgainstAmount($totalWithFee, $currency_id, $user_id)
    {
        //Backend Validation - Wallet Balance Again Amount Check - Starts here
        $wallet = Wallet::where(['currency_id' => $currency_id, 'user_id' => $user_id])->first(['id', 'balance']);
        if (!empty($wallet))
        {
            if (($totalWithFee > $wallet->balance) || ($wallet->balance < 0))
            {
                return true;
            }
        }
        //Backend Validation - Wallet Balance Again Amount Check - Ends here
    }

    /**
     * [Fetch email template]
     * param  integer $type     [temp_id]
     * param  string  $lang     [language]
     * return object  [subject, body]
     */
    public function getEmailOrSmsTemplate($tempId, $type, $lang = 'en')
    {
        $templateObject = EmailTemplate::where(['temp_id' => $tempId, 'type' => $type, 'lang' => $lang])->select('subject', 'body')->first();
        return $templateObject;
    }

    /**
     * [Get Current Date & Time - Carbon]
     * return [string] [Cardbon Date Time]
     */
    public function getCurrentDateTime()
    {
        return dateFormat(now());
    }

    public function clearSessionWithRedirect($sessionArr, $exception, $path)
    {
        Session::forget($sessionArr);
        clearActionSession();
        $this->one_time_message('error', $exception->getMessage());
        return redirect($path);
    }

    public function returnUnauthorizedResponse($unauthorisedStatus, $exception)
    {
        $success            = [];
        $success['status']  = $unauthorisedStatus;
        $success['message'] = $exception->getMessage();
        return response()->json(['success' => $success], $unauthorisedStatus);
    }

    public function getPrefProcessedBy()
    {
        $processedBy = Preference::where(['category' => 'preference', 'field' => 'processed_by'])->first(['value'])->value;
        return $processedBy;
    }

    public function validateEmailInput($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function validatePhoneInput($value)
    {
        return preg_match('%^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$%i',
            $value);
    }

    public function getEmailPhoneValidatedUserInfo($emailFilterValidate, $phoneRegex, $emailOrPhone)
    {
        $selectOptions = ['id', 'first_name', 'last_name', 'email', 'carrierCode', 'phone'];
        if ($emailFilterValidate)
        {
            $userInfo = User::where(['email' => $emailOrPhone])->first($selectOptions);
        }
        elseif ($phoneRegex)
        {
            $userInfo = User::where(['formattedPhone' => $emailOrPhone])->first($selectOptions);
        }
        return $userInfo;
    }

    /**
     * fetch Deposit Active Fees Limit
     * param  int $transactionType
     * param  int $currencyId
     * param  int $paymentMethodId
     * param  array $options
     * return object
     */
    public function getFeesLimitObject($withOptions = [], $transactionType, $currencyId, $paymentMethodId, $hasTransaction, $options)
    {
        if (!empty($hasTransaction) && !empty($paymentMethodId))
        {
            $getFeesLimitObject = FeesLimit::with($withOptions)->where(['transaction_type_id' => $transactionType, 'currency_id' => $currencyId, 'payment_method_id' => $paymentMethodId, 'has_transaction' => $hasTransaction])
                ->first($options);
        }
        elseif (!empty($hasTransaction) && empty($paymentMethodId))
        {
            $getFeesLimitObject = FeesLimit::with($withOptions)->where(['transaction_type_id' => $transactionType, 'currency_id' => $currencyId, 'has_transaction' => $hasTransaction])
                ->first($options);
        }
        elseif (empty($hasTransaction) && !empty($paymentMethodId))
        {
            $getFeesLimitObject = FeesLimit::with($withOptions)->where(['transaction_type_id' => $transactionType, 'currency_id' => $currencyId, 'payment_method_id' => $paymentMethodId])
                ->first($options);
        }
        else
        {
            $getFeesLimitObject = FeesLimit::with($withOptions)->where(['transaction_type_id' => $transactionType, 'currency_id' => $currencyId])->first($options);
        }
        return $getFeesLimitObject;
    }

    /**
     * Get Wallet Object
     * param  array  $withOptions   [eagar loaded relations]
     * param  array $constraints   [where or other conditions]
     * param  array $selectOptions [specific fields]
     * return object
     */
    public function getUserWallet($withOptions = [], $constraints, $selectOptions)
    {
        return Wallet::with($withOptions)->where($constraints)->first($selectOptions);
    }

    /**
     * Get All Wallets
     * param  array  $withOptions   [eagar loaded relations]
     * param  array $constraints   [where or other conditions]
     * param  array $selectOptions [specific fields]
     * return collection
     */
    public function getUserWallets($withOptions = [], $constraints, $selectOptions)
    {
        return Wallet::with($withOptions)->where($constraints)->get($selectOptions);
    }

    /**
     * Get Currency
     * @param  array    $constraints
     * @param  array    $selectOptions
     * @return Object
     */
    public function getCurrencyObject($constraints, $selectOptions)
    {
        return Currency::where($constraints)->first($selectOptions);
    }

    /**
     * Get Payout Setting
     * @param  array    $constraints
     * @param  array    $selectOptions
     * @return Object
     */
    public function getPayoutSettingObject($withOptions = [], $constraints, $selectOptions)
    {
        return PayoutSetting::with($withOptions)->where($constraints)->first($selectOptions);
    }

    public function sendTransactionSMSNotification($value = '')
    {
        //For SMS - in future
    }

    public function sendTransactionEmailNotification($type = null, $data = [])
    {
        //Only Email is done here; need similiar function for SMS too
        if (empty($type) || empty($data))
        {
            return false;
        }

        if (!checkAppMailEnvironment())
        {
            return false;
        }

        $emailSetting = NotificationSetting::getSettings(['nt.alias' => $type, 'notification_settings.recipient_type' => 'email', 'notification_settings.status' => 'Yes']);
        if ($emailSetting->isNotEmpty())
        {
            $recipient = $emailSetting[0]['recipient'];
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL))
            {
                $lang       = !empty(getDefaultLanguage()) ? self::$languages[getDefaultLanguage()] : 'en';
                $senderInfo = self::getEmailOrSmsTemplate(self::$templateIds[$type], 'email', $lang);
                if (!empty($senderInfo->subject) && !empty($senderInfo->body))
                {
                    $subject = $senderInfo->subject;
                }
                else
                {
                    $senderInfo = self::getEmailOrSmsTemplate(self::$templateIds[$type], 'email', $lang);
                    $subject    = $senderInfo->subject;
                }
                $message = str_replace('{uuid}', $data->uuid, $senderInfo->body);
                $message = str_replace('{soft_name}', getCompanyName(), $message);

                if (in_array($type, ['deposit', 'payout']))
                {
                    $message = str_replace('{created_at}', dateFormat($data->created_at, $data->user_id), $message);
                    $message = str_replace('{user}', $data->user->first_name . ' ' . $data->user->last_name, $message);
                    $message = str_replace('{amount}', moneyFormat($data->currency->symbol, formatNumber($data->amount)), $message);
                    $message = str_replace('{code}', $data->currency->code, $message);
                    $message = str_replace('{fee}', moneyFormat($data->currency->symbol, formatNumber($data->charge_fixed + $data->charge_percentage)), $message);

                }
                else if ($type == 'exchange')
                {
                    $message = str_replace('{created_at}', dateFormat($data->created_at, $data->user_id), $message);
                    $message = str_replace('{user}', $data->user->first_name . ' ' . $data->user->last_name, $message);
                    $message = str_replace('{amount}', moneyFormat($data->fromWallet->currency->symbol, formatNumber($data->amount)), $message);
                    $message = str_replace('{from_wallet}', $data->fromWallet->currency->code, $message);
                    $message = str_replace('{to_wallet}', $data->toWallet->currency->code, $message);
                    $message = str_replace('{fee}', moneyFormat($data->fromWallet->currency->symbol, formatNumber($data->fee)), $message);
                }
                else if ($type == 'send')
                {
                    $message = str_replace('{created_at}', dateFormat($data->created_at, $data->sender_id), $message);
                    $message = str_replace('{sender}', $data->sender->first_name . ' ' . $data->sender->last_name, $message);
                    if (!empty($data->receiver))
                    {
                        $message = str_replace('{receiver}', $data->receiver->first_name . ' ' . $data->receiver->last_name, $message);
                    }
                    elseif (empty($data->receiver) && !empty($data->email))
                    {
                        $message = str_replace('{receiver}', $data->email, $message);
                    }
                    elseif (empty($data->receiver) && !empty($data->phone))
                    {
                        $message = str_replace('{receiver}', $data->phone, $message);
                    }
                    $message = str_replace('{amount}', moneyFormat($data->currency->symbol, formatNumber($data->amount)), $message);
                    $message = str_replace('{fee}', moneyFormat($data->currency->symbol, formatNumber($data->fee)), $message);
                }
                else if ($type == 'request')
                {
                    $message = str_replace('{created_at}', dateFormat($data->created_at, $data->receiver_id), $message);
                    $message = str_replace('{creator}', $data->user->first_name . ' ' . $data->user->last_name, $message);
                    if (!empty($data->receiver))
                    {
                        $message = str_replace('{acceptor}', $data->receiver->first_name . ' ' . $data->receiver->last_name, $message);
                    }
                    elseif (empty($data->receiver) && !empty($data->email))
                    {
                        $message = str_replace('{acceptor}', $data->email, $message);
                    }
                    elseif (empty($data->receiver) && !empty($data->phone))
                    {
                        $message = str_replace('{acceptor}', $data->phone, $message);
                    }
                    $message = str_replace('{code}', $data->currency->code, $message);
                    $message = str_replace('{request_amount}', moneyFormat($data->currency->symbol, formatNumber($data->amount)), $message);
                    $message = str_replace('{given_amount}', moneyFormat($data->currency->symbol, formatNumber($data->accept_amount)), $message);
                    $message = str_replace('{fee}', moneyFormat($data->currency->symbol, formatNumber($data->charge_fixed + $data->charge_percentage)), $message);
                }
                else if ($type == 'payment')
                {
                    if ($data->payment_method_id == 1)
                    {
                        $message = str_replace('{created_at}', dateFormat($data->created_at, $data->user_id), $message);
                        $message = str_replace('{user}', $data->user->first_name . ' ' . $data->user->last_name, $message);
                    }
                    else
                    {
                        $message = str_replace('{created_at}', dateFormat($data->created_at), $message);
                        $message = str_replace('{user}', 'Unregistered User', $message);
                    }
                    $message = str_replace('{merchant}', $data->merchant->business_name, $message);
                    $message = str_replace('{code}', $data->currency->code, $message);
                    $message = str_replace('{amount}', moneyFormat($data->currency->symbol, formatNumber($data->total)), $message);
                    $message = str_replace('{fee}', moneyFormat($data->currency->symbol, formatNumber($data->charge_fixed + $data->charge_percentage)), $message);
                }
                try {
                    $this->email->sendEmail($recipient, $subject, $message);
                    return true;
                }
                catch (Exception $e)
                {
                    return $e;
                }
            }
        }
        return false;
    }

    /**
     * Send Transaction Notification
     *
     * @param  string $type
     * @param  array  $options
     * @return void
     */
    public function sendTransactionNotificationToAdmin($type = null, $options = [])
    {
        if (empty($type) || empty($options['data']))
        {
            return [
                'ex' => null,
            ];
        }

        $response = $this->sendTransactionEmailNotification($type, $options['data']);
        if ($response !== true)
        {
            return [
                'exFrom' => 'mailToAdmin',
                'ex'     => $response,
            ];
        }

        //if SMS - for future
        // $response = $this->sendTransactionSMSNotification($type, $data);
        // if ($response !== true)
        // {
        //     return [
        //         'exFrom' => 'mailToAdmin',
        //         'ex' => $response,
        //     ];
        // }

        return [
            'ex' => null,
        ];
    }

}
