<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Backend\TaskManagerController;
use App\Http\Controllers\Controller;
use App\Jobs\SendResetCodeJob;
use App\Models\User;
use App\Models\VCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class PasswordController extends Controller
{
    public function forgot_password(Request $request)
    {

        Validator::make($request->all(), [
            'email'   => 'required|email',
        ])->validate();

        return DB::transaction(function () use ($request) {
            $email = User::where([
                'email'       => $request->email,
                'is_deleted'  => 0,
                'is_blocked'  => 0
            ])->first();

            $isBlockedUserExists = User::where([
                'email'          => $request->email,
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

            if ($email) {

                VCode::where([
                    'is_expired' => 0,
                    'email'      => $email->email
                ])->update([
                    'is_expired' => 1
                ]);

                $code = rand(100000, 999999);
                $date = date('Y-m-d');
                $time = date('H:i:s', strtotime("+3 minutes"));

                $vcode = new VCode();

                $vcode->tracking_id  = Str::uuid();
                $vcode->user_id      = $email->user_id;
                $vcode->email        = $email->email;
                $vcode->code         = $code;
                $vcode->origin       = 'password reset';
                $vcode->expired_date = $date;
                $vcode->expired_time = $time;
                $vcode->save();

                // $queue_name = Str::uuid();

                $email_body = 'We\'ve received a password reset request for your account. Please use the code below to reset your password.';

                Mail::to($email->email)->send(new SendResetCode($email->name, $code, $date . ' ' . $time, $email_body));

                Session::put('reset_email', $email->email);

                return response()->json([
                    'success'  => true,
                    'email'    => $email->email,
                    'message'  => "We have sent a 6 digit code in your email",
                    'route'    => route('auth.reset.password'),
                    'queue'    => $queue_name ?? null,
                    'token'    => csrf_token(),
                ]);
            } else {
                return response()->json([
                    'error'     => true,
                    'type'      => 'email',
                    'message'   => 'Can\'t find a record for this email.'
                ]);
            }
        });
    }

    public function reset_password(Request $request)
    {
        Validator::make($request->all(), [
            'email'             => 'required',
            'verification_code' => 'required',
            'new_password'      => 'required|min:9|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{9,}$/',
            'confirm_password'  => 'required|same:new_password'
        ])->validate();

        return DB::transaction(function () use ($request) {

            if (!Session::has('reset_email')) {
                return response()->json([
                    'error'    => true,
                    'type'     => 'session',
                    'message'  => "Sorry! Session expired. Try again.",
                    'route'    => route('auth.forgot.password')
                ]);
            }

            $code = VCode::where([
                'email'      => $request->email,
                'code'       => $request->verification_code,
                'origin'     => 'password reset',
                'is_expired' => 0
            ])->latest()->first();

            if (!$code) {
                return response()->json([
                    'error'   => true,
                    'type'    => 'code',
                    'message' => "Sorry! Code doesn't match."
                ]);
            }

            $x_date = str_replace('-', '', $code->expired_date);
            $x_time = str_replace(':', '', $code->expired_time);

            $x_timestamp = $x_date . $x_time;

            if (date('YmdHis') > $x_timestamp) {

                $code->is_expired = 1;
                $code->save();

                return response()->json([
                    'error'    => true,
                    'message'  => "Sorry! Code is expired."
                ]);
            }

            $code->is_expired   = 1;
            $code->is_validated = 1;
            $code->save();

            $user = User::where([
                'email'       => $request->email,
                'is_deleted'  => 0,
                'is_blocked'  => 0
            ])->first();

            $user->password = $request->new_password;
            $user->save();

            $user->tokens()->delete();

            Session::forget('reset_email');

            return response()->json([
                'success'  =>  true,
                'message'  =>  "Password Changed.",
                'route'    =>   route('auth.sign.in')
            ]);
        });
    }
}
