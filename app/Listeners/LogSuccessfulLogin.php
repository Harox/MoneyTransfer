<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        //
    }

    /**
     * Handle the event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user                = $event->user;
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $this->request->ip();
        $user->save();

        //saving last_login_at and last_login_ip of user_detail at the same time
        // $user->user_detail->last_login_at = date('Y-m-d H:i:s');
        // $user->user_detail->last_login_ip = $this->request->ip();
        // $user->user_detail->save();
    }
}
