<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendResetCodeJob;
use App\Models\User;
use App\Models\VCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class AuthViewController extends Controller
{

    public function signin_page()
    {
        return view('auth.pages.signin');
    }
    
    public function signup_page()
    {
        return view('auth.pages.signup');
    }

    public function password_forgot()
    {
        return view('auth.pages.password.forget');
    }

    public function password_reset()
    {
        if (Session::has('reset_email')) {
            return view('auth.pages.password.reset');
        }

        return redirect()->route('auth.sign.in');
    }
}
