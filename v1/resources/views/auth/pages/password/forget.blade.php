@extends('auth.template.layout')
@section('title')
    Forget Password
@endsection
@section('content')
    <div class="form-content">
        <div class="form-items">
            <h5>Forget Password </h5>
            <small>Provide your email address to reset password</small>
            <div class="page-links mt-4">

            </div>
            <form class=""
                action="javascript:forgot_password('forgot-password','{{ route('forgot.password') }}','{{ route('get.new.csrf-token') }}')"
                id="forgot-password">

                <div class="input-group mb-2 mr-sm-2">
                    <div class="input-group-prepend flat">
                        <div class="input-group-text flat" style="border:none;background: #000000;color:white">
                            <i class="bi bi-envelope-at"></i>
                        </div>
                    </div>
                    <input type="email" name="email" class="form-control flat" placeholder="E-mail Address" required>
                </div>

                <div class="form-button" id="forgot-btn">
                    <a href="{{ route('auth.sign.in') }}">Back to <strong class="text-primary">Sign-In</strong></a>
                    <button type="submit" class="ibtn flat" style="width: 100px">Verify</button>
                </div>

                <div class="form-button d-none" id="forgot-processing">
                    <a href="{{ route('auth.sign.in') }}">Back to <strong class="text-primary">Sign-In</strong></a>
                    <button type="button" class="ibtn flat" style="width: 100px;" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                            <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2">
                                <path stroke-dasharray="60" stroke-dashoffset="60" stroke-opacity="0.3"
                                    d="M12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3Z">
                                    <animate fill="freeze" attributeName="stroke-dashoffset" dur="1.3s"
                                        values="60;0" />
                                </path>
                                <path stroke-dasharray="15" stroke-dashoffset="15" d="M12 3C16.9706 3 21 7.02944 21 12">
                                    <animate fill="freeze" attributeName="stroke-dashoffset" dur="0.3s"
                                        values="15;0" />
                                    <animateTransform attributeName="transform" dur="1.5s" repeatCount="indefinite"
                                        type="rotate" values="0 12 12;360 12 12" />
                                </path>
                            </g>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
