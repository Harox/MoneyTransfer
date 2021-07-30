<?php

namespace App\Http\Middleware;

use App\Http\Helpers\Common;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserInactive
{
    protected $helper;
    public function __construct()
    {
        $this->helper = new Common();
    }

    public function handle($request, Closure $next)
    {
        // if user inactive wouldn't be able to login

        $user = $this->helper->getUserStatus(auth()->user()->status);
        
        if ($user == 'Inactive')
        {
            auth()->logout();
            $this->helper->one_time_message('danger', __('Your account is inactivated. Please try again later!'));
            return redirect('/login');
        }

        return $next($request);
    }
}
