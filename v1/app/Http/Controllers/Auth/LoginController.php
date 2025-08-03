<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Backend\Client\TrialController;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\ClientTrial;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use WhichBrowser\Parser;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function authenticate(Request $request)
    {
        return DB::transaction(function () use ($request) {

            Validator::make($request->all(), [
                'email'            => 'required|email',
                'password'         => 'required',
            ])->validate();

            $input = $request->all();

            if (Session::has('signin_attempt_blocked')) {
                $blocked_till = Session::get('signin_attempt_blocked');

                if ($blocked_till > date('Y-m-d H:i:s')) {

                    $blocked_till = date('h:i:s a', strtotime($blocked_till));

                    return response()->json([
                        'attempt_error'        => true,
                        'attempt_blocked_till' => date('YmdHis', strtotime($blocked_till)),
                        'message'              => "<i class=\"bi bi-shield-exclamation\"></i> Attempt blocked! Try again after some time."
                    ]);
                } else {
                    Session::forget('signin_attempt_blocked');
                }
            }

            $email    = $request->email;
            $password = $request->password;

            $credentials = [
                'email'       => $email,
                'password'    => $password,
                'is_active'   => 1,
                'is_verified' => 1,
                'is_blocked'  => 0,
                'is_deleted'  => 0
            ];

            if (Auth::attempt($credentials)) {
                Session::forget('signin_attempt_blocked');

                $user = User::where('user_id', Auth::user()->user_id)->first();

                $user->is_loggedin  = 1;
                $user->update();

                return response()->json([
                    'success' => true,
                    'route'   => route('dashboard'),
                    'message' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"><path stroke-dasharray="60" stroke-dashoffset="60" stroke-opacity=".3" d="M12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="1.3s" values="60;0"/></path><path stroke-dasharray="15" stroke-dashoffset="15" d="M12 3C16.9706 3 21 7.02944 21 12"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.3s" values="15;0"/><animateTransform attributeName="transform" dur="1.5s" repeatCount="indefinite" type="rotate" values="0 12 12;360 12 12"/></path></g></svg> Access granted ! Redirecting...'
                ]);
            }

            if (Session::has('signin_wrong_attempt')) {
                $signin_wrong_attempt = Session::get('signin_wrong_attempt');
            } else {
                $signin_wrong_attempt = 1;
            }

            if ($signin_wrong_attempt >= 3) {
                Session::forget('signin_wrong_attempt');
                Session::put('signin_attempt_blocked', date('Y-m-d H:i:s',  strtotime('+3 minutes', strtotime(date('Y-m-d H:i:s')))));
            } else {
                Session::put('signin_wrong_attempt', $signin_wrong_attempt + 1);
            }

            $isBlockedUserExists = User::where([
                'email'          => $input['email'],
                'is_active'      => 0,
                'is_verified'    => 1,
                'is_blocked'     => 1,
                'is_deleted'     => 0,
            ])->first();

            if ($isBlockedUserExists) {
                return response()->json([
                    'error'      => true,
                    'message'    => 'This user account is currently blocked.'
                ]);
            }

            return response()->json([
                'error'      => true,
                'message'    => '<i class="bi bi-shield-exclamation"></i> Sorry ! authentication failed.'
            ]);
        });

        return response()->json([
            'error'      => true,
            'message'    => '<i class="bi bi-shield-exclamation"></i> Sorry ! authentication failed.'
        ]);
    }
}
