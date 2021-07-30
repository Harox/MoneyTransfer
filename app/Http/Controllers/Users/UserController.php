<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\{DB,
    Validator
};
use Illuminate\Http\Request;
use App\Http\Helpers\Common;
use App\Models\{Currency,
    UserDetail,
    Wallet,
    Role,
    User
};

class UserController extends Controller
{
    /**
     * @var mixed
     */
    protected $helper;

    public function __construct()
    {
        $this->helper = new Common();
    }

    public function index()
    {
        $data['menu']     = 'profile';
        $data['sub_menu'] = 'profile';
        $data['user'] = User::find(Auth::user()->id);
        return view('user_dashboard.users.profile', $data);
    }

    public function store(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $rules = array(
                'first_name'            => 'required|alpha_spaces',
                'last_name'             => 'required|alpha_spaces',
                'phone'                 => 'required|max:11',
                'email'                 => 'required|email',
                'password'              => 'required|max:6|confirmed',
                'password_confirmation' => 'required|max:6',
                'phrase'                => 'required',
            );

            $fieldNames = array(
                'first_name'            => 'First Name',
                'last_name'             => 'Last Name',
                'phone'                 => 'Phone',
                'email'                 => 'Email',
                'password'              => 'Password',
                'password_confirmation' => 'Confirm Password',
                'phrase'                => 'Phrase',
            );
            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($fieldNames);

            if ($validator->fails())
            {
                return back()->withErrors($validator)->withInput();
            }
            else
            {
                $users = new User();

                //email to username conversion
                $email_explode   = explode("@", $request->email);
                $users->username = $email_explode[0];

                $users->first_name = $request->first_name;
                $users->last_name  = $request->last_name;
                $users->phone      = $request->phone;
                $users->email      = $request->email;
                $users->password   = \Hash::make($request->password);
                $users->phrase     = $request->phrase;
                $users->role_id    = $request->role;
                $users->save();


                $UserDetail = new UserDetail();
                $UserDetail->user_id = $users->id;
                $UserDetail->country_id = 1;
                $UserDetail->save();

                // Assigning user_type and role id to new user
                DB::table('role_user')->insert(['user_id' => $users->id, 'role_id' => $request->role, 'user_type' => 'User']);

                // Wallet creation
                $active_currency = Currency::where(['default' => 1, 'status' => 'Active'])->select('id', 'symbol')->first();

                Wallet::firstOrCreate([
                    'user_id'     => $users->id,
                    'currency_id' => $active_currency->id,
                    'balance'     => 0.00,
                    'is_default'  => 'Yes',
                ]);
            }
        }
        $this->helper->one_time_message('success', 'User Created Successfully');
        return redirect('admin/users');
    }

    /**
     * @param Request $request
     */
    public function update(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $rules = array(
                'first_name'            => 'required|alpha_spaces',
                'last_name'             => 'required|alpha_spaces',
                'phone'                 => 'required|max:11',
                'email'                 => 'required|email',
                'password'              => 'required|max:6|confirmed',
                'password_confirmation' => 'required|max:6',
                'phrase'                => 'required',
            );

            $fieldNames = array(
                'first_name'            => 'First Name',
                'last_name'             => 'Last Name',
                'phone'                 => 'Phone',
                'email'                 => 'Email',
                'password'              => 'Password',
                'password_confirmation' => 'Confirm Password',
                'phrase'                => 'Phrase',
            );
            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($fieldNames);

            if ($validator->fails())
            {
                return back()->withErrors($validator)->withInput();
            }
            else
            {
                $users             = User::find($request->id);
                $users->first_name = $request->first_name;
                $users->last_name  = $request->last_name;
                $users->phone      = $request->phone;
                $users->email      = $request->email;

                //email to username conversion
                $email_explode   = explode("@", $request->email);
                $users->username = $email_explode[0];

                $users->password = \Hash::make($request->password);
                $users->phrase   = $request->phrase;
                $users->save();
            }
        }
        $this->helper->one_time_message('success', 'User Updated Successfully');
        return redirect('admin/users');
    }

}
