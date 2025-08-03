<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendRegistrationLink;
use App\Models\User;
use App\Models\VCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function signUp(Request $request)
    {
        Validator::make($request->all(), [
            'name'             => 'required',
            'email'            => 'required|email|unique:users',
            'timezone'         => 'required',
            'password'         => 'required|min:9|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{9,}$/',
            'confirm_password' => 'required|same:password'
        ])->validate();

        return DB::transaction(function () use ($request) {

            // Extract the domain from the email address
            $e_domain = substr(strrchr($request->email, "@"), 1);

            // Check if the domain has MX records
            if (!checkdnsrr($e_domain, 'MX')) {
                return response()->json([
                    'error'      => true,
                    'type'       => 'email',
                    'message'    => 'Invalid email address. Please use a valid email.'
                ]);
            }

            $name       = $request->name;
            $email      = $request->email;
            $password   = $request->password;

            $delete_inUser = User::where([
                'email'         => $email,
                'is_deleted'    => 1
            ])->exists();

            if ($delete_inUser) {
                return response()->json([
                    'error'      => true,
                    'message'    => 'This email is associated with a deleted account. Please use a different email.'
                ]);
            }

            $emailExists_inUser = User::where([
                'email'         => $email,
                'is_active'     => 1,
                'is_verified'   => 1,
                'is_deleted'    => 0
            ])->exists();

            if ($emailExists_inUser) {
                return response()->json([
                    'error'      => true,
                    'message'    => 'This email is already registered.'
                ]);
            }

            $isNotVerifyUserExists = User::where([
                'email'         => $email,
                'is_active'     => 0,
                'is_verified'   => 0,
                'is_deleted'    => 0
            ])->first();

            if ($isNotVerifyUserExists) {
                $queue_name = $this->verification($isNotVerifyUserExists->user_id, $email, $name);
                return response()->json([
                    'success'  => true,
                    'message'  => "A profile activation link has been sent to your email.",
                    'route'    => route('auth.sign.in'),
                    'queue'    => $queue_name,
                    'token'    => csrf_token(),
                ]);
            }

            $isBlockedUserExists = User::where([
                'email'          => $email,
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

            $user = new User();
            $user->user_id           = $user_id = Str::uuid();
            $user->name              = $name;
            $user->timezone          = $request->timezone;
            $user->email             = $email;
            $user->password          = $password;
            $user->save();

            $queue_name = $this->verification($user_id, $email, $name);

            return response()->json([
                'success'  => true,
                'message'  => "A profile activation link has been sent to your email.",
                'route'    => route('auth.sign.in'),
                'queue'    => $queue_name,
                'token'    => csrf_token(),
            ]);
        });
    }

    public function verification($user_id, $email, $name)
    {
        $code = rand(10000, 99999);
        $date = date('Y-m-d');
        $time = date('H:i:s', strtotime("+60 minutes"));
        $vcode = new VCode();

        $vcode->tracking_id  = Str::uuid();
        $vcode->user_id      = $user_id;
        $vcode->email        = $email;
        $vcode->code         = $code;
        $vcode->origin       = 'Account Registration';
        $vcode->expired_date = $date;
        $vcode->expired_time = $time;
        $vcode->save();

        // $queue_name = Str::uuid();
        $queue_name = null;

        $email_body = 'We\'ve received a request for your account registration. Please click the activation button to activate your account.';

        Mail::to($email)->send(new SendRegistrationLink($name, $vcode->tracking_id, $date . ' ' . $time, $email_body));
        return  $queue_name;
    }

    public function activate_account($tracking_id)
    {

        return DB::transaction(function () use ($tracking_id) {

            $code = VCode::where([
                'tracking_id' => $tracking_id
            ])->first();

            $x_date = str_replace('-', '', $code->expired_date);
            $x_time = str_replace(':', '', $code->expired_time);

            $x_timestamp = $x_date . $x_time;

            if (date('YmdHis') > $x_timestamp) {

                $code->is_expired = 1;
                $code->save();
                abort(419);
            }

            $code->is_validated = 1;
            $code->save();

            if (Auth::check()) {
                Auth::logout();
            }

            Session::flush();

            $user = User::where([
                'user_id'   => $code->user_id,
            ])->first();

            if (!$user) {
                abort(403);
            }

            $user->is_active   = 1;
            $user->is_verified = 1;
            $user->save();


            if ($user) {
                Auth::login($user);

                $user->is_loggedin  = 1;
                $user->update();
                return redirect()->route('dashboard');
            } else {
                abort(403);
            }
        });
        abort(403);
    }
}
