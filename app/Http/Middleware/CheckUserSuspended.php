<?php

namespace App\Http\Middleware;

use App\Http\Helpers\Common;
use App\Models\Merchant;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserSuspended
{
    protected $helper;
    public function __construct()
    {
        $this->helper = new Common();
    }

    public function handle($request, Closure $next)
    {
        // if user suspended can't do any transactions
        $user = $this->helper->getUserStatus(auth()->user()->status);

        if ($user == 'Suspended')
        {
            return redirect('check-user-status');
        }

        return $next($request);
    }
}
