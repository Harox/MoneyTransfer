<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\EachUserTransactionsDataTable;
use App\Http\Controllers\Users\EmailController;
use App\Repositories\CryptoCurrencyRepository;
use App\DataTables\Admin\{AdminsDataTable,
    UsersDataTable
};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{ActivityLog,
    EmailTemplate,
    PaymentMethod,
    Transaction,
    Withdrawal,
    FeesLimit,
    Currency,
    RoleUser,
    Setting,
    Dispute,
    Deposit,
    Wallet,
    Ticket,
    Admin,
    User,
    Role
};
use Illuminate\Support\Facades\{Hash,
    Validator,
    Session,
    DB
};
use Exception;

class UserController extends Controller
{
    protected $helper;
    protected $email;
    protected $currency;
    protected $user;
    /**
     * The CryptoCurrency repository instance.
     *
     * @var CryptoCurrencyRepository
     */
    protected $cryptoCurrency;

    public function __construct()
    {
        $this->helper         = new Common();
        $this->email          = new EmailController();
        $this->currency       = new Currency();
        $this->user           = new User();
        $this->cryptoCurrency = new CryptoCurrencyRepository();
    }

    public function index(UsersDataTable $dataTable)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';
        return $dataTable->render('admin.users.index', $data);
    }

    public function create()
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        $data['roles'] = $roles = Role::select('id', 'display_name')->where('user_type', "User")->get();

        return view('admin.users.create', $data);
    }

    public function store(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $rules = array(
                'first_name'            => 'required',
                'last_name'             => 'required',
                'email'                 => 'required|unique:users,email',
                'password'              => 'required|confirmed',
                'password_confirmation' => 'required',
                'status'                => 'required',
            );

            $fieldNames = array(
                'first_name'            => 'First Name',
                'last_name'             => 'Last Name',
                'email'                 => 'Email',
                'password'              => 'Password',
                'password_confirmation' => 'Confirm Password',
                'status'                => 'Status',
            );
            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($fieldNames);

            if ($validator->fails())
            {
                return back()->withErrors($validator)->withInput();
            }
            else
            {
                $default_currency = Setting::where('name', 'default_currency')->first(['value']);

                try
                {
                    DB::beginTransaction();

                    // Create user
                    $user = $this->user->createNewUser($request, 'admin');

                    // Assigning user_type and role id to new user
                    RoleUser::insert(['user_id' => $user->id, 'role_id' => $user->role_id, 'user_type' => 'User']);

                    // Create user detail
                    $this->user->createUserDetail($user->id);

                    // Create user's default wallet
                    $this->user->createUserDefaultWallet($user->id, $default_currency->value);

                    // Create user's crypto wallet/wallets address
                    $generateUserCryptoWalletAddress = $this->user->generateUserCryptoWalletAddress($user);
                    if ($generateUserCryptoWalletAddress['status'] == 401)
                    {
                        DB::rollBack();
                        $this->helper->one_time_message('error', $generateUserCryptoWalletAddress['message']);
                        return redirect('admin/users');
                    }

                    $userEmail          = $user->email;
                    $userFormattedPhone = $user->formattedPhone;

                    // Process Registered User Transfers
                    $this->user->processUnregisteredUserTransfers($userEmail, $userFormattedPhone, $user, $default_currency->value);

                    // Process Registered User Request Payments
                    $this->user->processUnregisteredUserRequestPayments($userEmail, $userFormattedPhone, $user, $default_currency->value);

                    // Email verification
                    if (!$user->user_detail->email_verification)
                    {
                        if (checkVerificationMailStatus() == "Enabled")
                        {
                            if (checkAppMailEnvironment())
                            {
                                $emainVerificationArr = $this->user->processUserEmailVerification($user);
                                try
                                {
                                    $this->email->sendEmail($emainVerificationArr['email'], $emainVerificationArr['subject'], $emainVerificationArr['message']);

                                    DB::commit();
                                    $this->helper->one_time_message('success', 'An email has been sent to ' . $user->email . ' with verification code!');
                                    return redirect('admin/users');
                                }
                                catch (Exception $e)
                                {
                                    DB::rollBack();
                                    $this->helper->one_time_message('error', $e->getMessage());
                                    return redirect('admin/users');
                                }
                            }
                        }
                    }

                    DB::commit();
                    $this->helper->one_time_message('success', 'User Created Successfully');
                    return redirect('admin/users');
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                    $this->helper->one_time_message('error', $e->getMessage());
                    return redirect('admin/users');
                }
            }
        }
    }

    public function edit($id)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        $data['users'] = $users = User::find($id);

        $data['roles'] = $roles = Role::select('id', 'display_name')->where('user_type', "User")->get();

        // Check whether user has any crypto wallet address
        $data['getUserWalletCryptoapiLogs'] = $this->cryptoCurrency->getUserWalletCryptoapiLogs($users->wallets)->get(['id']);

        // Check enabled currencies in preference
        $data['getCurrenciesPreference'] = $getCurrenciesPreference = $this->cryptoCurrency->getCurrenciesPreference();

        return view('admin.users.edit', $data);
    }

    public function update(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $rules = array(
                'first_name' => 'required',
                'last_name'  => 'required',
                'email'      => 'required|email|unique:users,email,' . $request->id,
            );

            $fieldNames = array(
                'first_name' => 'First Name',
                'last_name'  => 'Last Name',
                'email'      => 'Email',
            );
            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($fieldNames);

            if ($validator->fails())
            {
                return back()->withErrors($validator)->withInput();
            }
            else
            {

                try
                {
                    DB::beginTransaction();
                    $user             = User::find($request->id);
                    $user->first_name = $request->first_name;
                    $user->last_name  = $request->last_name;
                    $user->email      = $request->email;
                    $user->role_id    = $request->role;

                    $formattedPhone = ltrim($request->phone, '0');
                    if (!empty($request->phone))
                    {
                        /*phone*/
                        $user->phone          = preg_replace("/[\s-]+/", "", $formattedPhone);
                        $user->defaultCountry = $request->user_defaultCountry;
                        $user->carrierCode    = $request->user_carrierCode;
                        $user->formattedPhone = $request->formattedPhone;
                        /**/
                    }
                    else
                    {
                        $user->phone          = null;
                        $user->defaultCountry = null;
                        $user->carrierCode    = null;
                        $user->formattedPhone = null;
                    }

                    if (!is_null($request->password) && !is_null($request->password_confirmation))
                    {
                        $user->password = \Hash::make($request->password);
                    }

                    // Send mail to user for Status change
                    if ($request->status != $user->status)
                    {
                        //update user status
                        $user->status = $request->status;

                        $englishSenderLanginfo = EmailTemplate::where(['temp_id' => 29, 'lang' => 'en', 'type' => 'email'])->select('subject', 'body')->first();
                        $sender_info           = EmailTemplate::where(['temp_id' => 29, 'language_id' => getDefaultLanguage(), 'type' => 'email'])->select('subject', 'body')->first();
                        if (!empty($sender_info->subject) && !empty($sender_info->body))
                        {
                            $sender_subject = $sender_info->subject;
                            $sender_msg     = str_replace('{user}', $user->first_name . ' ' . $user->last_name, $sender_info->body);
                        }
                        else
                        {
                            $sender_subject = $englishSenderLanginfo->subject;
                            $sender_msg     = str_replace('{user}', $user->first_name . ' ' . $user->last_name, $englishSenderLanginfo->body);
                        }
                        $sender_msg = str_replace('{status}', $user->status, $sender_msg);
                        $sender_msg = str_replace('{soft_name}', getCompanyName(), $sender_msg);
                        if (checkAppMailEnvironment())
                        {
                            try
                            {
                                $this->email->sendEmail($user->email, $sender_subject, $sender_msg);
                            }
                            catch (Exception $e)
                            {
                                DB::rollBack();
                                $this->helper->one_time_message('error', $e->getMessage());
                                return redirect('admin/users');
                            }
                        }
                    }
                    $user->save();

                    RoleUser::where(['user_id' => $request->id, 'user_type' => 'User'])->update(['role_id' => $request->role]); //by tuhin

                    DB::commit();

                    $this->helper->one_time_message('success', 'User Updated Successfully');
                    return redirect('admin/users');
                }
                catch (Exception $e)
                {
                    DB::rollBack();
                    $this->helper->one_time_message('error', $e->getMessage());
                    return redirect('admin/users');
                }
            }
        }
    }

    /* Start of Admin Depsosit */
    public function eachUserDeposit($id, Request $request)
    {
        setActionSession();

        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        // $data['users']           = $users           = User::find($id);
        $data['users']           = $users           = User::find($id, ['id', 'first_name', 'last_name']);
        $data['payment_met']     = $payment_met     = PaymentMethod::where(['name' => 'Mts', 'status' => 'Active'])->first(['id', 'name']);
        $data['active_currency'] = $activeCurrency = Currency::where(['status' => 'Active'])->get(['id', 'status', 'code']);
        $feesLimitCurrency       = FeesLimit::where(['transaction_type_id' => Deposit, 'payment_method_id' => $payment_met->id, 'has_transaction' => 'Yes'])->get(['currency_id', 'has_transaction']);
        $data['activeCurrencyList'] = $this->currencyList($activeCurrency, $feesLimitCurrency);

        //check Decimal Thousand Money Format Preference
        $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);

        if ($request->isMethod('post'))
        {
            $currency               = Currency::where(['id' => $request->currency_id, 'status' => 'Active'])->first(['symbol']);
            $request['currSymbol']  = $currency->symbol;
            $amount                 = $request->amount;
            $request['totalAmount'] = $amount + $request->fee;
            session(['transInfo' => $request->all()]);
            $data['transInfo'] = $transInfo = $request->all();

            //check amount and limit
            $feesDetails = FeesLimit::where(['transaction_type_id' => Deposit, 'currency_id' => $request->currency_id, 'payment_method_id' => $transInfo['payment_method'], 'has_transaction' => 'Yes'])
                ->first(['min_limit', 'max_limit']);
            if (@$feesDetails->max_limit == null)
            {
                if ((@$amount < @$feesDetails->min_limit))
                {
                    $data['error'] = 'Minimum amount ' . formatNumber($feesDetails->min_limit);
                    $this->helper->one_time_message('error', $data['error']);
                    return view('admin.users.deposit.create', $data);
                }
            }
            else
            {
                if ((@$amount < @$feesDetails->min_limit) || (@$amount > @$feesDetails->max_limit))
                {
                    $data['error'] = 'Minimum amount ' . formatNumber($feesDetails->min_limit) . ' and Maximum amount ' . formatNumber($feesDetails->max_limit);
                    $this->helper->one_time_message('error', $data['error']);
                    return view('admin.users.deposit.create', $data);
                }
            }
            return view('admin.users.deposit.confirmation', $data);
        }
        return view('admin.users.deposit.create', $data);
    }

    //Extended function below - deposit
    public function currencyList($activeCurrency, $feesLimitCurrency)
    {
        $selectedCurrency = [];
        foreach ($activeCurrency as $aCurrency)
        {
            foreach ($feesLimitCurrency as $flCurrency)
            {
                if ($aCurrency->id == $flCurrency->currency_id && $aCurrency->status == 'Active' && $flCurrency->has_transaction == 'Yes')
                {
                    $selectedCurrency[$aCurrency->id]['id']   = $aCurrency->id;
                    $selectedCurrency[$aCurrency->id]['code'] = $aCurrency->code;
                }
            }
        }
        return $selectedCurrency;
    }
    /* End of Admin Depsosit */

    public function eachUserDepositSuccess(Request $request)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        $user_id = $request->user_id;

        //Check Session - starts
        $sessionValue = session('transInfo');
        if (empty($sessionValue))
        {
            return redirect("admin/users/deposit/create/$user_id");
        }
        //Check Session - ends

        actionSessionCheck();

        $amount  = $sessionValue['amount'];
        $uuid    = unique_code();
        $feeInfo = FeesLimit::where(['transaction_type_id' => Deposit, 'currency_id' => $sessionValue['currency_id'], 'payment_method_id' => $sessionValue['payment_method']])
            ->first(['charge_percentage', 'charge_fixed']);
        //charge percentage calculation
        $p_calc = (($amount) * (@$feeInfo->charge_percentage) / 100);

        try
        {
            DB::beginTransaction();
            //Deposit
            $deposit                    = new Deposit();
            $deposit->user_id           = $user_id;
            $deposit->currency_id       = $sessionValue['currency_id'];
            $deposit->payment_method_id = $sessionValue['payment_method'];
            $deposit->uuid              = $uuid;
            $deposit->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
            $deposit->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
            $deposit->amount            = $amount;
            $deposit->status            = 'Success';
            $deposit->save();

            //Transaction
            $transaction                           = new Transaction();
            $transaction->user_id                  = $user_id;
            $transaction->currency_id              = $sessionValue['currency_id'];
            $transaction->payment_method_id        = $sessionValue['payment_method'];
            $transaction->transaction_reference_id = $deposit->id;
            $transaction->transaction_type_id      = Deposit;
            $transaction->uuid                     = $uuid;
            $transaction->subtotal                 = $amount;
            $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
            $transaction->charge_percentage        = $deposit->charge_percentage;
            $transaction->charge_fixed             = $deposit->charge_fixed;
            $transaction->total                    = $amount + $deposit->charge_percentage + $deposit->charge_fixed;
            $transaction->status                   = 'Success';
            $transaction->save();

            //Wallet
            $wallet = Wallet::where(['user_id' => $user_id, 'currency_id' => $sessionValue['currency_id']])->first(['id', 'balance']);
            if (empty($wallet))
            {
                $createWallet              = new Wallet();
                $createWallet->user_id     = $user_id;
                $createWallet->currency_id = $sessionValue['currency_id'];
                $createWallet->balance     = $amount;
                $createWallet->is_default  = 'No';
                $createWallet->save();
            }
            else
            {
                $wallet->balance = ($wallet->balance + $amount);
                $wallet->save();
            }

            if (checkAppMailEnvironment())
            {
                $english_deposit_email_temp = EmailTemplate::where(['temp_id' => 30, 'lang' => 'en', 'type' => 'email'])->select('subject', 'body')->first();
                $deposit_email_temp         = EmailTemplate::where([
                    'temp_id'     => 30,
                    'language_id' => Session::get('default_language'),
                    'type'        => 'email',
                ])->select('subject', 'body')->first();

                if (!empty($english_deposit_email_temp->subject) && !empty($english_deposit_email_temp->body))
                {
                    $d_success_sub = str_replace('{uuid}', $deposit->uuid, $english_deposit_email_temp->subject);
                    $d_success_msg = str_replace('{user_id}', $deposit->user->first_name . ' ' . $deposit->user->last_name, $english_deposit_email_temp->body);
                }
                else
                {
                    $d_success_sub = str_replace('{uuid}', $deposit->uuid, $deposit_email_temp->subject);
                    $d_success_msg = str_replace('{user_id}', $deposit->user->first_name . ' ' . $deposit->user->last_name, $deposit_email_temp->body);
                }

                $d_success_msg = str_replace('{amount}', moneyFormat($deposit->currency->symbol, formatNumber($deposit->amount)), $d_success_msg);
                $d_success_msg = str_replace('{created_at}', dateFormat($deposit->created_at, $deposit->user_id), $d_success_msg);
                $d_success_msg = str_replace('{uuid}', $deposit->uuid, $d_success_msg);
                $d_success_msg = str_replace('{code}', $deposit->currency->code, $d_success_msg);
                $d_success_msg = str_replace('{amount}', moneyFormat($deposit->currency->symbol, formatNumber($deposit->amount)), $d_success_msg);
                $d_success_msg = str_replace('{fee}', moneyFormat($deposit->currency->symbol, formatNumber($deposit->charge_fixed + $deposit->charge_percentage)), $d_success_msg);
                $d_success_msg = str_replace('{soft_name}', getCompanyName(), $d_success_msg);

                if (checkAppMailEnvironment())
                {
                    $this->email->sendEmail($deposit->user->email, $d_success_sub, $d_success_msg);
                }
            }
            DB::commit();

            if (checkAppSmsEnvironment())
            {
                $payoutMessage = 'Amount ' . moneyFormat($deposit->currency->symbol, formatNumber($deposit->amount)) . ' was deposited by System Administrator.';
                if (!empty($deposit->user->formattedPhone)) {
                    sendSMS($deposit->user->formattedPhone, $payoutMessage);
                }
            }

            $data['transInfo']['currSymbol'] = $transaction->currency->symbol;
            $data['transInfo']['subtotal']   = $transaction->subtotal;
            $data['transInfo']['id']         = $transaction->id;
            $data['user_id']                 = $user_id;
            $data['name']                    = $sessionValue['fullname'];

            Session::forget('transInfo');
            clearActionSession();
            return view('admin.users.deposit.success', $data);
        }
        catch (Exception $e)
        {
            DB::rollBack();
            Session::forget('transInfo');
            clearActionSession();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect("admin/users/deposit/create/$user_id");
        }
    }

    public function eachUserdepositPrintPdf($transaction_id)
    {
        $data['companyInfo'] = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);

        $data['transactionDetails'] = $transactionDetails = Transaction::with(['payment_method:id,name', 'currency:id,symbol'])
            ->where(['id' => $transaction_id])
            ->first(['uuid', 'created_at', 'status', 'currency_id', 'payment_method_id', 'subtotal', 'charge_percentage', 'charge_fixed', 'total']);

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A3',
            'orientation' => 'P',
        ]);
        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('admin.users.deposit.depositPrintPdf', $data));
        $mpdf->Output('deposit_' . time() . '.pdf', 'I'); //
    }

    /* Start of Admin Withdraw */
    public function eachUserWithdraw($id, Request $request)
    {
        setActionSession();

        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        // $data['users']       = $users       = User::find($id);
        $data['users']       = $users       = User::find($id, ['id', 'first_name', 'last_name']);
        $data['payment_met'] = $payment_met = PaymentMethod::where(['name' => 'Mts'])->first(['id', 'name']);
        $payment_met_id      = $payment_met->id;
        $data['wallets']     = $wallets     = $users->wallets()->whereHas('active_currency', function ($q) use ($payment_met_id)
        {
            $q->whereHas('fees_limit', function ($query) use ($payment_met_id)
            {
                $query->where('has_transaction', 'Yes')->where('transaction_type_id', Withdrawal)->where('payment_method_id', $payment_met_id);
            });
        })
            ->with(['active_currency:id,code', 'active_currency.fees_limit:id,currency_id']) //Optimized
            ->get(['id', 'currency_id']);

        //check Decimal Thousand Money Format Preference
        $data['preference'] = getDecimalThousandMoneyFormatPref(['decimal_format_amount']);

        if ($request->isMethod('post'))
        {
            $amount                 = $request->amount;
            $currency               = Currency::where(['id' => $request->currency_id])->first(['symbol']);
            $request['currSymbol']  = $currency->symbol;
            $request['totalAmount'] = $request->amount + $request->fee;
            session(['transInfo' => $request->all()]);
            $data['transInfo'] = $transInfo = $request->all();

            //backend validation starts
            $request['transaction_type_id'] = Withdrawal;
            $request['currency_id']         = $request->currency_id;
            $request['payment_method_id']   = $request->payment_method;
            $amountFeesLimitCheck           = $this->amountFeesLimitCheck($request);
            if ($amountFeesLimitCheck)
            {
                if ($amountFeesLimitCheck->getData()->success->status == 200)
                {
                    if ($amountFeesLimitCheck->getData()->success->totalAmount > $amountFeesLimitCheck->getData()->success->balance)
                    {
                        $data['error'] = "Insufficient Balance!";
                        $this->helper->one_time_message('error', $data['error']);
                        return view('admin.users.withdraw.create', $data);
                    }
                }
                elseif ($amountFeesLimitCheck->getData()->success->status == 401)
                {
                    $data['error'] = $amountFeesLimitCheck->getData()->success->message;
                    $this->helper->one_time_message('error', $data['error']);
                    return view('admin.users.withdraw.create', $data);
                }
            }
            //backend valdation ends
            return view('admin.users.withdraw.confirmation', $data);
        }
        return view('admin.users.withdraw.create', $data);
    }

    public function amountFeesLimitCheck(Request $request)
    {
        $amount      = $request->amount;
        $feesDetails = FeesLimit::where(['transaction_type_id' => $request->transaction_type_id, 'currency_id' => $request->currency_id, 'payment_method_id' => $request->payment_method_id])
            ->first(['min_limit', 'max_limit', 'charge_percentage', 'charge_fixed']);
        $wallet = Wallet::where(['currency_id' => $request->currency_id, 'user_id' => $request->user_id])->first(['balance']);

        if ($request->transaction_type_id == Withdrawal)
        {
            //Wallet Balance Limit Check Starts here
            $checkAmount = $amount + $feesDetails->charge_fixed + $feesDetails->charge_percentage;
            if (@$wallet)
            {
                if ((@$checkAmount) > (@$wallet->balance) || (@$wallet->balance < 0))
                {
                    $success['message'] = "Insufficient Balance!";
                    $success['status']  = '401';
                    return response()->json(['success' => $success]);
                }
            }
            //Wallet Balance Limit Check Ends here
        }

        //Amount Limit Check Starts here
        if (empty($feesDetails))
        {
            $feesPercentage            = 0;
            $feesFixed                 = 0;
            $totalFess                 = $feesPercentage + $feesFixed;
            $totalAmount               = $amount + $totalFess;
            $success['feesPercentage'] = $feesPercentage;
            $success['feesFixed']      = $feesFixed;
            $success['totalFees']      = $totalFess;
            $success['totalFeesHtml']  = formatNumber($totalFess);
            $success['totalAmount']    = $totalAmount;
            $success['pFees']          = $feesPercentage;
            $success['pFeesHtml']      = formatNumber($feesPercentage);
            $success['fFees']          = $feesFixed;
            $success['fFeesHtml']      = formatNumber($feesFixed);
            $success['min']            = 0;
            $success['max']            = 0;
            $success['balance']        = 0;
        }
        else
        {
            if (@$feesDetails->max_limit == null)
            {
                if ((@$amount < @$feesDetails->min_limit))
                {
                    $success['message'] = 'Minimum amount ' . formatNumber($feesDetails->min_limit);
                    $success['status']  = '401';
                }
                else
                {
                    $success['status'] = 200;
                }
            }
            else
            {
                if ((@$amount < @$feesDetails->min_limit) || (@$amount > @$feesDetails->max_limit))
                {
                    $success['message'] = 'Minimum amount ' . formatNumber($feesDetails->min_limit) . ' and Maximum amount ' . formatNumber($feesDetails->max_limit);
                    $success['status']  = '401';
                }
                else
                {
                    $success['status'] = 200;
                }
            }
            $feesPercentage            = $amount * ($feesDetails->charge_percentage / 100);
            $feesFixed                 = $feesDetails->charge_fixed;
            $totalFess                 = $feesPercentage + $feesFixed;
            $totalAmount               = $amount + $totalFess;
            $success['feesPercentage'] = $feesPercentage;
            $success['feesFixed']      = $feesFixed;
            $success['totalFees']      = $totalFess;
            $success['totalFeesHtml']  = formatNumber($totalFess);
            $success['totalAmount']    = $totalAmount;
            $success['pFees']          = $feesDetails->charge_percentage;
            $success['pFeesHtml']      = formatNumber($feesDetails->charge_percentage);
            $success['fFees']          = $feesDetails->charge_fixed;
            $success['fFeesHtml']      = formatNumber($feesDetails->charge_fixed);
            $success['min']            = $feesDetails->min_limit;
            $success['max']            = $feesDetails->max_limit;
            $success['balance']        = @$wallet->balance ? @$wallet->balance : 0;
        }
        //Amount Limit Check Ends here
        return response()->json(['success' => $success]);
    }

    public function eachUserWithdrawSuccess(Request $request)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        $user_id = $request->user_id;

        //Check Session - starts
        $sessionValue = session('transInfo');
        if (empty($sessionValue))
        {
            return redirect("admin/users/withdraw/create/$user_id");
        }
        //Check Session - ends

        actionSessionCheck();

        $uuid    = unique_code();
        $feeInfo = FeesLimit::where(['transaction_type_id' => Withdrawal, 'currency_id' => $sessionValue['currency_id'], 'payment_method_id' => $sessionValue['payment_method']])
            ->first(['charge_percentage', 'charge_fixed']);
        $p_calc = (($sessionValue['amount']) * (@$feeInfo->charge_percentage) / 100); //charge percentage calculation

        try
        {
            DB::beginTransaction();
            //Withdrawal
            $withdrawal                    = new Withdrawal();
            $withdrawal->user_id           = $user_id;
            $withdrawal->currency_id       = $sessionValue['currency_id'];
            $withdrawal->payment_method_id = $sessionValue['payment_method'];
            $withdrawal->uuid              = $uuid;
            $withdrawal->charge_percentage = @$feeInfo->charge_percentage ? $p_calc : 0;
            $withdrawal->charge_fixed      = @$feeInfo->charge_fixed ? @$feeInfo->charge_fixed : 0;
            $withdrawal->subtotal          = ($sessionValue['amount'] - ($p_calc + (@$feeInfo->charge_fixed)));
            $withdrawal->amount            = $sessionValue['amount'];
            $withdrawal->status            = 'Success';
            $withdrawal->save();

            //Transaction
            $transaction                           = new Transaction();
            $transaction->user_id                  = $user_id;
            $transaction->currency_id              = $sessionValue['currency_id'];
            $transaction->payment_method_id        = $sessionValue['payment_method'];
            $transaction->transaction_reference_id = $withdrawal->id;
            $transaction->transaction_type_id      = Withdrawal;
            $transaction->uuid                     = $uuid;
            $transaction->subtotal                 = $withdrawal->amount;
            $transaction->percentage               = @$feeInfo->charge_percentage ? @$feeInfo->charge_percentage : 0;
            $transaction->charge_percentage        = $withdrawal->charge_percentage;
            $transaction->charge_fixed             = $withdrawal->charge_fixed;
            $transaction->total                    = '-' . ($withdrawal->amount + $withdrawal->charge_percentage + $withdrawal->charge_fixed);
            $transaction->status                   = 'Success';
            $transaction->save();

            //Wallet
            $wallet = Wallet::where(['user_id' => $user_id, 'currency_id' => $sessionValue['currency_id']])->first();
            if (!empty($wallet))
            {
                $wallet->balance = ($wallet->balance - ($withdrawal->amount + $withdrawal->charge_percentage + $withdrawal->charge_fixed));
                $wallet->save();
            }

            if (checkAppMailEnvironment())
            {
                $english_withdrawal_email_temp = EmailTemplate::where(['temp_id' => 31, 'lang' => 'en', 'type' => 'email'])->select('subject', 'body')->first();
                $withdrawal_email_temp         = EmailTemplate::where([
                    'temp_id'     => 31,
                    'language_id' => Session::get('default_language'),
                    'type'        => 'email',
                ])->select('subject', 'body')->first();

                if (!empty($english_withdrawal_email_temp->subject) && !empty($english_withdrawal_email_temp->body))
                {
                    $w_success_sub = str_replace('{uuid}', $withdrawal->uuid, $english_withdrawal_email_temp->subject);
                    $w_success_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $english_withdrawal_email_temp->body);
                }
                else
                {
                    $w_success_sub = str_replace('{uuid}', $withdrawal->uuid, $withdrawal_email_temp->subject);
                    $w_success_msg = str_replace('{user_id}', $withdrawal->user->first_name . ' ' . $withdrawal->user->last_name, $withdrawal_email_temp->body);
                }

                $w_success_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($withdrawal->amount)), $w_success_msg);
                $w_success_msg = str_replace('{created_at}', dateFormat($withdrawal->created_at, $withdrawal->user_id), $w_success_msg);
                $w_success_msg = str_replace('{uuid}', $withdrawal->uuid, $w_success_msg);
                $w_success_msg = str_replace('{code}', $withdrawal->currency->code, $w_success_msg);
                $w_success_msg = str_replace('{amount}', moneyFormat($withdrawal->currency->symbol, formatNumber($withdrawal->amount)), $w_success_msg);
                $w_success_msg = str_replace('{fee}', moneyFormat($withdrawal->currency->symbol, formatNumber($withdrawal->charge_fixed + $withdrawal->charge_percentage)), $w_success_msg);
                $w_success_msg = str_replace('{soft_name}', getCompanyName(), $w_success_msg);

                $this->email->sendEmail($withdrawal->user->email, $w_success_sub, $w_success_msg);
            }
            DB::commit();
            if (checkAppSmsEnvironment())
            {
                $payoutMessage = 'Amount ' . moneyFormat($withdrawal->currency->symbol, formatNumber($withdrawal->amount)) . ' was withdrawn by System Administrator.';
                if (!empty($withdrawal->user->formattedPhone)) {
                    sendSMS($withdrawal->user->formattedPhone, $payoutMessage);
                }
            }

            $data['transInfo']['currSymbol'] = $transaction->currency->symbol;
            $data['transInfo']['subtotal']   = $transaction->subtotal;
            $data['transInfo']['id']         = $transaction->id;
            $data['user_id']                 = $user_id;
            $data['name']                    = $sessionValue['fullname'];

            Session::forget('transInfo');
            clearActionSession();
            return view('admin.users.withdraw.success', $data);
        }
        catch (Exception $e)
        {
            DB::rollBack();
            Session::forget('transInfo');
            clearActionSession();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect("users/withdraw/create/$user_id");
        }
    }

    public function eachUserWithdrawPrintPdf($trans_id)
    {
        $data['companyInfo'] = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);

        $data['transactionDetails'] = $transactionDetails = Transaction::with(['payment_method:id,name', 'currency:id,symbol'])
            ->where(['id' => $trans_id])->first(['uuid', 'created_at', 'status', 'currency_id', 'payment_method_id', 'subtotal', 'charge_percentage', 'charge_fixed', 'total']);

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf = new \Mpdf\Mpdf([
            'mode'        => 'utf-8',
            'format'      => 'A3',
            'orientation' => 'P',
        ]);
        $mpdf->autoScriptToLang         = true;
        $mpdf->autoLangToFont           = true;
        $mpdf->allow_charset_conversion = false;
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('admin.users.withdraw.withdrawalPrintPdf', $data));
        $mpdf->Output('payout_' . time() . '.pdf', 'I');
    }
    /* End of Admin Withdraw */

/* Start of Admin Crypto Send */
    public function eachUserCryptoSend($id, Request $request)
    {
        setActionSession();
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';
        $data['users']    = $users    = User::find($id, ['id', 'first_name', 'last_name']);

        // Get active crypto currencies
        $data['activeCryptoCurrencies'] = $activeCryptoCurrencies = $this->cryptoCurrency->getActiveCryptoCurrencies();

        if ($request->isMethod('post'))
        {
            $res = $this->cryptoSendReceiveConfirm($data, $request, 'send');
            if ($res['status'] == 401)
            {
                $this->helper->one_time_message('error', $res['message']);
                return redirect('admin/users/crypto/send/' . $request->user_id);
            }
            //for confirm page only
            $data['cryptoTrx'] = $res['cryptoTrx'];
            return view('admin.users.crypto.send.confirmation', $data);
        }
        return view('admin.users.crypto.send.create', $data);
    }

    public function eachUserCryptoSendSuccess(Request $request)
    {
        // .env - APP_DEMO - check
        if (checkDemoEnvironment() == true)
        {
            $this->helper->one_time_message('error', 'Crypto Send is not possible on demo site.');
            return redirect('admin/users/crypto/send/' . $request->user_id);
        }

        $res = $this->cryptoSendReceiveSuccess($request, 'send');
        if ($res['status'] == 401)
        {
            $this->helper->one_time_message('error', $res['message']);
            return redirect('admin/users/crypto/send/' . $res['user_id']);
        }
        return view('admin.users.crypto.send.success', $res['data']);
    }

    /**
     * Generate pdf print for merchant crypto sent & received
     */
    public function merchantCryptoSentReceivedTransactionPrintPdf($id)
    {
        $id                  = decrypt($id);
        $data['companyInfo'] = Setting::where(['type' => 'general', 'name' => 'logo'])->first(['value']);
        $data['transaction'] = $transaction = Transaction::with(['currency:id,symbol', 'cryptoapi_log:id,object_id,payload,confirmations'])->where(['id' => $id])->first();
        // Get crypto api log details for Crypto_Sent & Crypto_Received (via custom relationship)
        if (!empty($transaction->cryptoapi_log))
        {
            $getCryptoDetails = $this->cryptoCurrency->getCryptoPayloadConfirmationsDetails($transaction->transaction_type_id, $transaction->cryptoapi_log->payload, $transaction->cryptoapi_log->confirmations);
            if (count($getCryptoDetails) > 0)
            {
                // For "Tracking block io account receiver address changes, if amount is sent from other payment gateways like CoinBase, CoinPayments, etc"
                if (isset($getCryptoDetails['senderAddress']))
                {
                    $data['senderAddress'] = $getCryptoDetails['senderAddress'];
                }
                $data['receiverAddress'] = $getCryptoDetails['receiverAddress'];
                $data['confirmations']   = $getCryptoDetails['confirmations'];
            }
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
        $mpdf->SetJS('this.print();');
        $mpdf->WriteHTML(view('user_dashboard.transactions.crypto_sent_received', $data));
        $mpdf->Output('crypto-sent-received_' . time() . '.pdf', 'I'); // this will output data
    }

    // Extended Functions (Crypto Send)- starts
    //Get merchant network address, merchant network balance and user network address
    public function getMerchantUserNetworkAddressWithMerchantBalance(Request $request)
    {
        try {
            $user_id = $request->user_id;
            $network = $request->network;

            //Get merchant network address
            $merchantAddress = $this->cryptoCurrency->getMerchantNetworkAddress($network);

            //Check merchant network address
            $checkMerchantNetworkAddress = $this->cryptoCurrency->checkNetworkAddressValidity($network, $merchantAddress);
            if (!$checkMerchantNetworkAddress)
            {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Invalid merchant ' . $network . ' address',
                ]);
            }

            //Get merchant network address balance
            $merchantAddressBalance = $this->cryptoCurrency->getUserCryptoAddressBalance($network, $merchantAddress);

            //Get Use Wallet Address
            $getUserNetworkWalletAddress = $this->cryptoCurrency->getUserNetworkWalletAddress($user_id, $network);
            if ($getUserNetworkWalletAddress->getData()->status == 200)
            {
                //Check user network wallet address
                $checkUserAddress = $this->cryptoCurrency->checkNetworkAddressValidity($network, $getUserNetworkWalletAddress->getData()->userAddress);
                if (!$checkUserAddress)
                {
                    return response()->json([
                        'status'  => 400,
                        'message' => 'Invalid user ' . $network . ' address',
                    ]);
                }
            }
            else
            {
                return response()->json([
                    'status'  => 400,
                    'message' => $getUserNetworkWalletAddress->getData()->message,
                ]);
            }
            return response()->json([
                'status'                 => 200,
                'merchantAddress'        => $merchantAddress,
                'merchantAddressBalance' => $merchantAddressBalance,
                'userAddress'            => $getUserNetworkWalletAddress->getData()->userAddress,
            ]);
        }
        catch (Exception $e)
        {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }

    //validate merchant Address Balance Against Amount
    public function validateMerchantAddressBalanceAgainstAmount(Request $request)
    {
        try {
            $validateMerchantAddressBalance = $this->cryptoCurrency->validateNetworkAddressBalance($request->network, $request->amount, $request->merchantAddress, $request->userAddress);
            if (!$validateMerchantAddressBalance['status']) {
                return response()->json([
                    'status'      => 400,
                    'message'     => 'Network fee ' . $validateMerchantAddressBalance['network-fee'] . ' and amount ' . $request->amount . ' exceeds your ' . strtoupper($request->network) . ' balance',
                ]);
            } else {
                return response()->json([
                    'status'      => 200,
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }
    // Extended Functions (Crypto Send)- ends
/* End of Admin Crypto Send */

/* Start of Admin Crypto Receive */
    public function eachUserCryptoReceive($id, Request $request)
    {
        setActionSession();

        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';
        $data['users']    = $users    = User::find($id, ['id', 'first_name', 'last_name']);

        // Get active crypto currencies
        $data['activeCryptoCurrencies'] = $activeCryptoCurrencies = $this->cryptoCurrency->getActiveCryptoCurrencies();

        if ($request->isMethod('post'))
        {
            $res = $this->cryptoSendReceiveConfirm($data, $request, 'receive');
            if ($res['status'] == 401)
            {
                $this->helper->one_time_message('error', $res['message']);
                return redirect('admin/users/crypto/receive/' . $request->user_id);
            }
            //for confirm page only
            $data['cryptoTrx'] = $res['cryptoTrx'];
            return view('admin.users.crypto.receive.confirmation', $data);
        }
        return view('admin.users.crypto.receive.create', $data);
    }

    public function eachUserCryptoReceiveSuccess(Request $request)
    {
        // .env - APP_DEMO - check
        if (checkDemoEnvironment() == true)
        {
            $this->helper->one_time_message('error', 'Crypto Receive is not possible on demo site.');
            return redirect('admin/users/crypto/send/' . $request->user_id);
        }

        $res = $this->cryptoSendReceiveSuccess($request, 'receive');
        if ($res['status'] == 401)
        {
            $this->helper->one_time_message('error', $res['message']);
            return redirect('admin/users/crypto/receive/' . $res['user_id']);
        }
        return view('admin.users.crypto.receive.success', $res['data']);
    }
    // Extended Functions (Crypto Receive)- starts
    //Get user network address, user network balance and merchant network address
    public function getUserNetworkAddressBalanceWithMerchantNetworkAddress(Request $request)
    {
        try {
            $user_id = $request->user_id;
            $network = $request->network;

            //Get Use Wallet Address
            $getUserNetworkWalletAddress = $this->cryptoCurrency->getUserNetworkWalletAddress($user_id, $network);
            if ($getUserNetworkWalletAddress->getData()->status == 200)
            {
                //Check user network wallet address
                $checkUserAddress = $this->cryptoCurrency->checkNetworkAddressValidity($network, $getUserNetworkWalletAddress->getData()->userAddress);
                if (!$checkUserAddress)
                {
                    return response()->json([
                        'status'  => 400,
                        'message' => 'Invalid user ' . $network . ' address',
                    ]);
                }

                //Get user network address balance
                $userAddressBalance = $this->cryptoCurrency->getUserCryptoAddressBalance($network, $getUserNetworkWalletAddress->getData()->userAddress);

                //Get merchant network address
                $merchantAddress = $this->cryptoCurrency->getMerchantNetworkAddress($network);

                return response()->json([
                    'status'             => 200,
                    'userAddress'        => $getUserNetworkWalletAddress->getData()->userAddress,
                    'userAddressBalance' => $userAddressBalance,
                    'merchantAddress'    => $merchantAddress,
                ]);
            }
            else
            {
                return response()->json([
                    'status'  => 400,
                    'message' => $getUserNetworkWalletAddress->getData()->message,
                ]);
            }
        }
        catch (Exception $e)
        {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }

    //validate merchant Address Balance Against Amount
    public function validateUserAddressBalanceAgainstAmount(Request $request)
    {
        try {
            $validateUserAddressBalance = $this->cryptoCurrency->validateNetworkAddressBalance($request->network, $request->amount, $request->userAddress, $request->merchantAddress);
            if (!$validateUserAddressBalance['status']) {
                return response()->json([
                    'status'      => 400,
                    'message'     => 'Network fee ' . $validateUserAddressBalance['network-fee'] . ' and amount ' . $request->amount . ' exceeds your ' . strtoupper($request->network) . ' balance',
                ]);
            } else {
                return response()->json([
                    'status'      => 200,
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }
    // Extended Functions (Crypto Receive)- ends
/* End of Admin Crypto Receive */

// Extended Functions (Crypto Send & Receive)- starts
    public function cryptoSendReceiveConfirm($data, $request, $type)
    {
        $userId          = $request->user_id;
        $network         = $request->network;
        $amount          = $request->amount;
        $merchantAddress = $request->merchantAddress;
        $userAddress     = $request->userAddress;
        $currency        = $this->currency->getCurrency(['code' => $network], ['id', 'symbol']);

        //merge currency symbol with request array
        $request->merge(['currency-symbol' => $currency->symbol]);
        $request->merge(['currency-id' => $currency->id]);
        $request->merge(['user-full-name' => $data['users']->first_name . ' ' . $data['users']->last_name]);

        //unset users & cryptoCurrenciesSettings - not needed in confirm page
        unset($data['users'], $data['cryptoCurrenciesSettings']);

        //Form back-end validations - starts
            if ($type === 'send')
            {
                $rules = array(
                    'merchantAddress' => 'required',
                    'merchantBalance' => 'required',
                    'userAddress'     => 'required',
                    'amount'          => 'required',
                );
                $fieldNames = array(
                    'merchantAddress' => 'Merchant Address',
                    'merchantBalance' => 'Merchant Balance',
                    'userAddress'     => 'User Address',
                    'amount'          => 'Amount',
                );
            }
            elseif ($type === 'receive')
            {
                $rules = array(
                    'userAddress'     => 'required',
                    'userBalance'     => 'required',
                    'merchantAddress' => 'required',
                    'amount'          => 'required',
                );
                $fieldNames = array(
                    'userAddress'     => 'User Address',
                    'userBalance'     => 'User Balance',
                    'merchantAddress' => 'User Address',
                    'amount'          => 'Amount',
                );
            }
        //Form back-end validations - ends

        //Backend validation of wallet currency code/network & amount - starts
            if (($network == 'DOGE' || $network == 'DOGETEST') && $amount < 2)
            {
                return [
                    'message' => "The minimum amount must be 2 $network",
                    'status'  => 401,
                ];
            }
            elseif (($network == 'BTC' || $network == 'BTCTEST') && $amount < 0.00002)
            {
                return [
                    'message' => "The minimum amount must be 0.00002 $network",
                    'status'  => 401,
                ];
            }
            elseif (($network == 'LTC' || $network == 'LTCTEST') && $amount < 0.0002)
            {
                return [
                    'message' => "The minimum amount must be 0.0002 $network",
                    'status'  => 401,
                ];
            }
        //Backend validation of wallet currency code/network & amount - ends

        //Backend validation of merchant & user network address validity & correct address - starts
            //Backend validation of merchant network address validity
            $checkMerchantNetworkAddress = $this->cryptoCurrency->checkNetworkAddressValidity($network, $merchantAddress);
            if (!$checkMerchantNetworkAddress)
            {
                return [
                    'message' => 'Invalid merchant ' . $network . ' address',
                    'status'  => 401,
                ];
            }
            //Backend validation of correct merchant network address
            $getMerchantNetworkAddress = $this->cryptoCurrency->getMerchantNetworkAddress($network);
            if ($merchantAddress != $getMerchantNetworkAddress)
            {
                return [
                    'message' => 'Incorrect merchant ' . $network . ' address',
                    'status'  => 401,
                ];
            }
            //Backend validation of user network address validity
            $checkUserNetworkAddress = $this->cryptoCurrency->checkNetworkAddressValidity($network, $userAddress);
            if (!$checkUserNetworkAddress)
            {
                return [
                    'message' => 'Invalid user ' . $network . ' address',
                    'status'  => 401,
                ];
            }
            //Backend validation of correct user network address
            $getUserNetworkWalletAddress  = $this->cryptoCurrency->getUserNetworkWalletAddress($userId, $network);
            if ($userAddress != $getUserNetworkWalletAddress->getData()->userAddress)
            {
                return [
                    'message' => 'Incorrect user ' . $network . ' address',
                    'status'  => 401,
                ];
            }
        //Backend validation of merchant & user network address validity & correct address - ends

        //Backend validation of merchant & user network address balance - starts
            if ($type === 'send')
            {
                //Backend merchant network address balance
                $getMerchantNetworkAddressBalance = $this->cryptoCurrency->getUserCryptoAddressBalance($network, $this->cryptoCurrency->getMerchantNetworkAddress($network));
                if ($request->merchantBalance != $getMerchantNetworkAddressBalance)
                {
                    return [
                        'message' => 'Incorrect merchant ' . $network . ' balance',
                        'status'  => 401,
                    ];
                }
                //Backend merchant network address balance against amount
                $validateAddressBlnce = $this->validateMerchantAddressBalanceAgainstAmount($request);
            }
            elseif ($type === 'receive')
            {
                //Backend user network address balance
                $getUserNetworkAddressBalance = $this->cryptoCurrency->getUserCryptoAddressBalance($network, $getUserNetworkWalletAddress->getData()->userAddress);
                if ($request->userBalance != $getUserNetworkAddressBalance)
                {
                    return [
                        'message' => 'Incorrect user ' . $network . ' balance',
                        'status'  => 401,
                    ];
                }
                //Backend user network address balance against amount
                $validateAddressBlnce = $this->validateUserAddressBalanceAgainstAmount($request);
            }
            if ($validateAddressBlnce->getData()->status == 400)
            {
                return [
                    'message' => $validateAddressBlnce->getData()->message,
                    'status'  => 401,
                ];
            }
        //Backend validation of merchant & user network address balance - ends

        $validator = \Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return [
                'message' => $validator,
                'status'  => 401,
            ];
        } else {
            //Call network fee API of block io
            if ($type === 'send') {
                $getNetworkFeeEstimate = $this->cryptoCurrency->getNetworkFeeEstimate($network, $userAddress, $amount);
            } elseif ($type === 'receive') {
                $getNetworkFeeEstimate = $this->cryptoCurrency->getNetworkFeeEstimate($network, $merchantAddress, $amount);
            }

            //merge network fee with request array
            $request->merge(['network-fee' => $getNetworkFeeEstimate]);

            //Put data in session for success page
            session(['cryptoTrx' => $request->all()]);

            //for confirm page only
            $data['cryptoTrx'] = $request->only('currency-symbol', 'amount', 'network-fee', 'user_id', 'user-full-name');

            return [
                'cryptoTrx' => $data['cryptoTrx'],
                'status'    => 200,
            ];
        }
    }

    public function cryptoSendReceiveSuccess($request, $type)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        //Check Session - starts
        $user_id   = $request->user_id;
        $cryptoTrx = session('cryptoTrx');
        if (empty($cryptoTrx))
        {
            return [
                'message' => null,
                'user_id' => $user_id,
                'status'  => 401,
            ];
        }
        //Check Session - ends

        //Backend validation of sender crypto wallet balance -- for multiple tab submit
        $request['network']         = $cryptoTrx['network'];
        $request['merchantAddress'] = $cryptoTrx['merchantAddress'];
        $request['userAddress']     = $cryptoTrx['userAddress'];
        $request['amount']          = $cryptoTrx['amount'];
        if ($type === 'send')
        {
            $validateAddressBlnceSuccess = $this->validateMerchantAddressBalanceAgainstAmount($request);
        }
        elseif ($type === 'receive')
        {
            $validateAddressBlnceSuccess = $this->validateUserAddressBalanceAgainstAmount($request);
        }

        if ($validateAddressBlnceSuccess->getData()->status == 400)
        {
            return [
                'message' => $validateAddressBlnceSuccess->getData()->message,
                'user_id' => $user_id,
                'status'  => 401,
            ];
        }
        else
        {
            try
            {
                //
                $uniqueCode = unique_code();
                $arr        = [
                    'walletCurrencyCode' => $cryptoTrx['network'],
                    'amount'             => $cryptoTrx['amount'],
                    'networkFee'         => $cryptoTrx['network-fee'],
                    'userId'             => null,
                    'endUserId'          => null,
                    'currencyId'         => $cryptoTrx['currency-id'],
                    'currencySymbol'     => $cryptoTrx['currency-symbol'],
                    'uniqueCode'         => $uniqueCode,
                ];

                if ($type === 'send') {
                    $arr['senderAddress']   = $cryptoTrx['merchantAddress'];
                    $arr['receiverAddress'] = $cryptoTrx['userAddress'];
                    $arr['endUserId']       = $cryptoTrx['user_id'];
                } elseif ($type === 'receive') {
                    $arr['senderAddress']   = $cryptoTrx['userAddress'];
                    $arr['receiverAddress'] = $cryptoTrx['merchantAddress'];
                    $arr['userId']          = $cryptoTrx['user_id'];
                }

                $withdrawInfo = '';

                if ($arr['walletCurrencyCode'] == 'BTC' || $arr['walletCurrencyCode'] == 'LTC' || $arr['walletCurrencyCode'] == 'DOGE') {
                    try {
                        $notification = $this->cryptoCurrency->getNotificationStatus($arr['walletCurrencyCode']);
                        if ($notification['status'] == false) {
                            $enableNotification = $this->cryptoCurrency->enableNotificationStatus($arr['walletCurrencyCode'], $notification['notificationId']);
                            if ($enableNotification['status'] == true) {
                                try {
                                    $withdrawInfo = $this->cryptoCurrency->withdrawOrSendAmountToReceiverAddress($arr['walletCurrencyCode'], $arr['senderAddress'], $arr['receiverAddress'], $arr['amount'], $arr['uniqueCode']);
                                } catch (Exception $e) {
                                    return [
                                        'message' => $e->getMessage(),
                                        'user_id' => $user_id,
                                        'status'  => 401,
                                    ];
                                }
                            } else {
                                return [
                                    'message' => 'Your Subscription on block.io is Expired! Please get a plan for properly work.',
                                    'user_id' => $user_id,
                                    'status'  => 401,
                                ];
                            }
                        } else {
                            try {
                                $withdrawInfo = $this->cryptoCurrency->withdrawOrSendAmountToReceiverAddress($arr['walletCurrencyCode'], $arr['senderAddress'], $arr['receiverAddress'], $arr['amount'], $arr['uniqueCode']);
                            } catch (Exception $e) {
                                return [
                                    'message' => $e->getMessage(),
                                    'user_id' => $user_id,
                                    'status'  => 401,
                                ];
                            }
                        }
                    } catch (Exception $e) {
                        return [
                            'message' => $e->getMessage(),
                            'user_id' => $user_id,
                            'status'  => 401,
                        ];
                    }
                } else {
                    try {
                        $withdrawInfo = $this->cryptoCurrency->withdrawOrSendAmountToReceiverAddress($arr['walletCurrencyCode'], $arr['senderAddress'], $arr['receiverAddress'], $arr['amount'], $arr['uniqueCode']);
                    } catch (Exception $e) {
                        return [
                            'message' => $e->getMessage(),
                            'user_id' => $user_id,
                            'status'  => 401,
                        ];
                    }
                }

                DB::beginTransaction();

                //Create Merchant Crypto Transaction
                $createCryptoTransactionId = $this->cryptoCurrency->createCryptoTransaction($arr);

                //Create merchant new withdrawal/Send/Receive crypt api log
                $arr['transactionId']    = $createCryptoTransactionId;
                $arr['withdrawInfoData'] = $withdrawInfo->data;
                if ($type === 'send')
                {
                    //need this for showing send address against Crypto Receive Type Transaction in user/admin panel
                    $arr['withdrawInfoData']->senderAddress = $cryptoTrx['merchantAddress'];

                    //need this for nodejs websocket server
                    $arr['withdrawInfoData']->receiverAddress = $cryptoTrx['userAddress'];
                }
                elseif ($type === 'receive')
                {
                    $arr['withdrawInfoData']->senderAddress = $cryptoTrx['userAddress'];

                    $arr['withdrawInfoData']->receiverAddress = $cryptoTrx['merchantAddress'];
                }
                $this->cryptoCurrency->createWithdrawalOrSendCryptoApiLog($arr);

                //Update Sender/Receiver Network Address Balance
                if ($type === 'receive')
                {
                    $this->cryptoCurrency->getUpdatedSendWalletBalance($arr);
                }

                DB::commit();

                //for success page
                // Recommended
                // $cryptConfirmationsArr = [
                //     'BTC'      => 3,
                //     'BTCTEST'  => 3,
                //     'DOGE'     => 10,
                //     'DOGETEST' => 10,
                //     'LTC'      => 5,
                //     'LTCTEST'  => 5,
                // ];

                // Initially after 1 confirmations of blockio response, websocket queries will be executed
                $cryptConfirmationsArr = [
                    'BTC'      => 1,
                    'BTCTEST'  => 1,
                    'DOGE'     => 1,
                    'DOGETEST' => 1,
                    'LTC'      => 1,
                    'LTCTEST'  => 1,
                ];
                $data['confirmations']      = $cryptConfirmationsArr[$arr['walletCurrencyCode']];
                $data['walletCurrencyCode'] = $arr['walletCurrencyCode'];
                $data['receiverAddress']    = $arr['receiverAddress'];
                $data['currencySymbol']     = $arr['currencySymbol'];
                $data['amount']             = $arr['amount'];
                $data['transactionId']      = $arr['transactionId'];
                if ($type === 'send')
                {
                    $data['userId'] = $arr['endUserId'];
                }
                elseif ($type === 'receive')
                {
                    $data['userId'] = $arr['userId'];
                }
                $data['user_full_name'] = $cryptoTrx['user-full-name'];
                //

                //clear cryptoTrx from session
                session()->forget(['cryptoTrx']);
                clearActionSession();
                return [
                    'data'   => $data,
                    'status' => 200,
                ];
            }
            catch (Exception $e)
            {
                DB::rollBack();
                //clear cryptoTrx from session
                session()->forget(['cryptoTrx']);
                clearActionSession();
                return [
                    'message' => $e->getMessage(),
                    'user_id' => $user_id,
                    'status'  => 401,
                ];
            }
        }
    }
// Extended Functions (Crypto Send & Receive)- ends

    public function eachUserTransaction($id, EachUserTransactionsDataTable $dataTable)
    {
        $data['menu']         = 'users';
        $data['sub_menu']     = 'users_list';
        $data['users']        = $users        = User::find($id, ['id', 'first_name', 'last_name']);
        $eachUserTransactions = Transaction::where(function ($q) use ($id)
        {
            $q->where(['user_id' => $id])->orWhere(['end_user_id' => $id]);
        });
        $data['t_status']   = $t_status   = $eachUserTransactions->select('status')->groupBy('status')->get();
        $data['t_currency'] = $t_currency = $eachUserTransactions->select('currency_id')->groupBy('currency_id')->get();
        $data['t_type']     = $t_type     = $eachUserTransactions->select('transaction_type_id')->groupBy('transaction_type_id')->get();

        $data['from']     = isset(request()->from) ? setDateForDb(request()->from) : null;
        $data['to']       = isset(request()->to ) ? setDateForDb(request()->to) : null;
        $data['status']   = isset(request()->status) ? request()->status : 'all';
        $data['currency'] = isset(request()->currency) ? request()->currency : 'all';
        $data['type']     = isset(request()->type) ? request()->type : 'all';

        return $dataTable->with('user_id', $id)->render('admin.users.eachusertransaction', $data); //passing $id to dataTable ass user_id
    }

    public function eachUserWallet($id)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        $data['wallets'] = $wallets = Wallet::with('currency:id,type,code')->where(['user_id' => $id])->orderBy('id', 'desc')->get();
        $data['users']   = User::find($id, ['id', 'first_name', 'last_name']);
        return view('admin.users.eachuserwallet', $data);
    }

    public function eachUserTicket($id)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        $data['tickets'] = $tickets = Ticket::where(['user_id' => $id])->orderBy('id', 'desc')->get();
        $data['users']   = User::find($id, ['id', 'first_name', 'last_name']);
        return view('admin.users.eachuserticket', $data);
    }

    public function eachUserDispute($id)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'users_list';

        $data['disputes'] = $disputes = Dispute::where(['claimant_id' => $id])->orWhere(['defendant_id' => $id])->orderBy('id', 'desc')->get();

        $data['users'] = User::find($id, ['id', 'first_name', 'last_name']);

        return view('admin.users.eachuserdispute', $data);
    }

    public function destroy($id)
    {
        // $id = decrypt($id);

        $user = User::find($id);
        if ($user)
        {
            try
            {
                DB::beginTransaction();
                // Deleting Non-Relational Table Entries

                // Delete User wallet address {crypto sent, crypyo received and wallet address} object type api logs
                $this->cryptoCurrency->deleteWalletAddressCryptoSentCryptoReceivedApiLogs($user->wallets);

                ActivityLog::where(['user_id' => $id])->delete();
                RoleUser::where(['user_id' => $id, 'user_type' => 'User'])->delete();

                $user->delete();

                DB::commit();

                $this->helper->one_time_message('success', 'User Deleted Successfully');
                return redirect('admin/users');
            }
            catch (Exception $e)
            {
                DB::rollBack();
                $this->helper->one_time_message('error', $e->getMessage());
                return redirect('admin/users');
            }
        }
    }

    public function postEmailCheck(Request $request)
    {

        if (isset($request->admin_id) || isset($request->user_id))
        {
            if (isset($request->type) && $request->type == "admin-email")
            {
                $req_id = $request->admin_id;
                $email  = Admin::where(['email' => $request->email])->where(function ($query) use ($req_id)
                {
                    $query->where('id', '!=', $req_id);
                })->exists();
            }
            else
            {
                $req_id = $request->user_id;
                $email  = User::where(['email' => $request->email])->where(function ($query) use ($req_id)
                {
                    $query->where('id', '!=', $req_id);
                })->exists();
            }
        }
        else
        {
            if (isset($request->type) && $request->type == "admin-email")
            {
                $email = Admin::where(['email' => $request->email])->exists();
            }
            else
            {
                $email = User::where(['email' => $request->email])->exists();
            }
        }

        if ($email)
        {
            $data['status'] = true;
            $data['fail']   = "The email has already been taken!";
        }
        else
        {
            $data['status']  = false;
            $data['success'] = "Email Available!";
        }
        return json_encode($data);
    }

    public function duplicatePhoneNumberCheck(Request $request)
    {
        $req_id = $request->id;

        if (isset($req_id))
        {
            $user = User::where(['phone' => preg_replace("/[\s-]+/", "", $request->phone), 'carrierCode' => $request->carrierCode])->where(function ($query) use ($req_id)
            {
                $query->where('id', '!=', $req_id);
            })->first(['phone', 'carrierCode']);
        }
        else
        {
            $user = User::where(['phone' => preg_replace("/[\s-]+/", "", $request->phone), 'carrierCode' => $request->carrierCode])->first(['phone', 'carrierCode']);
        }

        if (!empty($user->phone) && !empty($user->carrierCode))
        {
            $data['status'] = true;
            $data['fail']   = "The phone number has already been taken!";
        }
        else
        {
            $data['status']  = false;
            $data['success'] = "The phone number is Available!";
        }
        return json_encode($data);
    }

    public function adminList(AdminsDataTable $dataTable)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'admin_users_list';

        return $dataTable->render('admin.users.adminList', $data);
    }

    public function adminCreate()
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'admin_users_list';

        $data['roles'] = $roles = Role::select('id', 'display_name')->where('user_type', 'Admin')->get();

        return view('admin.users.adminCreate', $data);
    }

    public function adminStore(Request $request)
    {

        $rules = array(
            'first_name'            => 'required',
            'last_name'             => 'required',
            'email'                 => 'required|unique:admins,email',
            'password'              => 'required|confirmed',
            'password_confirmation' => 'required',
        );

        $fieldNames = array(
            'first_name'            => 'First Name',
            'last_name'             => 'Last Name',
            'email'                 => 'Email',
            'password'              => 'Password',
            'password_confirmation' => 'Confirm Password',
        );
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);

        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }
        else
        {
            $admin             = new Admin();
            $admin->first_name = $request->first_name;
            $admin->last_name  = $request->last_name;
            $admin->email      = $request->email;
            $admin->password   = Hash::make($request->password);
            $admin->role_id    = $request->role;
            $admin->save();
            RoleUser::insert(['user_id' => $admin->id, 'role_id' => $request->role, 'user_type' => 'Admin']);
        }

        //condition because same function used in installer for create admin
        if (!isset($request->from_installer))
        {
            $this->helper->one_time_message('success', 'Admin Created Successfully!');
            return redirect()->intended("admin/admin_users");
        }
    }

    public function adminEdit($id)
    {
        $data['menu']     = 'users';
        $data['sub_menu'] = 'admin_users_list';

        $data['admin'] = $users = Admin::find($id);
        $data['roles'] = $roles = Role::select('id', 'display_name')->where('user_type', "Admin")->get();
        return view('admin.users.adminEdit', $data);
    }

    public function adminUpdate(Request $request)
    {

        $rules = array(
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => 'required|email|unique:admins,email,' . $request->admin_id,
        );

        $fieldNames = array(
            'first_name' => 'First Name',
            'last_name'  => 'Last Name',
            'email'      => 'Email',
        );
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }
        else
        {
            $admin             = Admin::find($request->admin_id);
            $admin->first_name = $request->first_name;
            $admin->last_name  = $request->last_name;
            $admin->email      = $request->email;
            $admin->role_id    = $request->role;
            $admin->save();
            RoleUser::where(['user_id' => $admin->id, 'user_type' => 'Admin'])->update(['role_id' => $request->role]);
            $this->helper->one_time_message('success', 'Admin Updated Successfully!');
            return redirect()->intended("admin/admin_users");
        }
    }

    public function adminDestroy($id)
    {
        $admin = Admin::find($id);
        if ($admin)
        {
            $admin->delete();

            // Deleting Non-Relational Table Entries
            ActivityLog::where(['user_id' => $id])->delete();
            RoleUser::where(['user_id' => $id, 'user_type' => 'Admin'])->delete();

            $this->helper->one_time_message('success', 'Admin Deleted Successfully');
            return redirect()->intended("admin/admin_users");
        }
    }
}