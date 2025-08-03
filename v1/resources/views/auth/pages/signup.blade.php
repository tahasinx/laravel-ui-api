@extends('auth.template.layout')
@section('content')
    <div class="form-content">
        <div class="form-items">
            <h5>Sign up to access your account</h5>
            <small>Create your account's potential</small>
            <div class="page-links mt-4">
                <a href="{{ route('auth.sign.in') }}"><i class="bi bi-shield-check"></i> Sign in</a>
                <a href="{{ route('auth.sign.up') }}" class="active"><i class="bi bi-person-plus"></i> Sign up</a>
            </div>
            <form action="javascript:sign_up('signup-form','{{ route('sign.up') }}','{{ route('get.new.csrf-token') }}')"
                method="POST" autocomplete="off" id="signup-form">
                <div class="input-group mb-2 mr-sm-2">
                    <div class="input-group-prepend flat">
                        <div class="input-group-text flat" style="border:none;background: #000000;color:white">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                    <input type="text" name="name" class="form-control flat" placeholder="Name" required>
                </div>

                <div class="input-group mb-2 mr-sm-2">
                    <div class="input-group-prepend flat">
                        <div class="input-group-text flat" style="border:none;background: #000000;color:white">
                            <i class="bi bi-envelope-at"></i>
                        </div>
                    </div>
                    <input type="email" name="email" class="form-control flat" placeholder="E-mail Address" required>
                </div>
                <div class="input-group mb-2 mr-sm-2">
                    <div class="input-group-prepend flat">
                        <div class="input-group-text flat" style="border:none;background: #000000;color:white">
                            <i class="bi bi-globe"></i>
                        </div>
                    </div>
                    <select name="timezone" class="form-control flat timezone-select text-white" required>
                        <option value="">Select Timezone</option>
                        @foreach (timezone_identifiers_list() as $timezone)
                            <option value="{{ $timezone }}">{{ $timezone }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="input-group mb-2 mr-sm-2">
                    <div class="input-group-prepend flat">
                        <div class="input-group-text flat" style="border:none;background: #000000;color:white">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                    </div>
                    <input type="password" name="password" id="password" class="form-control flat" placeholder="Password"
                        autocomplete="new-password" required>
                </div>
                <div id="passwordHelp">
                    <small class="help-text text-danger"></small>
                </div>

                <div class="input-group mb-2 mr-sm-2">
                    <div class="input-group-prepend flat">
                        <div class="input-group-text flat" style="border:none;background: #000000;color:white">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                    </div>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control flat"
                        placeholder="Confirm Password" autocomplete="new-password" required>
                </div>
                <div id="confirmHelp">
                    <small class="help-text text-danger"></small>
                </div>
                <div class="password-requirements">
                    <div class="password-requirements-title">
                        <i class="bi bi-shield-lock"></i>
                        Password Requirements
                    </div>
                    <div class="requirement-item" id="uppercase">
                        <i class="bi bi-circle"></i>
                        At least 1 capital letter
                    </div>
                    <div class="requirement-item" id="lowercase">
                        <i class="bi bi-circle"></i>
                        At least 1 small letter
                    </div>
                    <div class="requirement-item" id="number">
                        <i class="bi bi-circle"></i>
                        At least 1 number
                    </div>
                    <div class="requirement-item" id="special">
                        <i class="bi bi-circle"></i>
                        At least 1 special character
                    </div>
                    <div class="requirement-item" id="length">
                        <i class="bi bi-circle"></i>
                        At least 9 characters
                    </div>
                    <div class="password-strength-meter">
                        <div class="password-strength-meter-fill"></div>
                    </div>
                    <div class="password-strength-text">Password strength: <span id="strength-text">None</span></div>
                </div>
                <div class="form-button" id="submit-btn">
                    <button type="submit" class="ibtn flat" style="width: 120px">Sign Up</button>
                </div>

                <div class="form-button d-none" id="processing-btn">
                    <button type="button" class="ibtn flat" style="width: 120px" disabled>
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
        <div class="form-sent">
            <div class="tick-holder">
                <div class="tick-icon"></div>
            </div>
            <h3 class="success_msg" style="font-size: 16px"></h3>
            <p>Please check your inbox/spam</p>
            <div class="info-holder">
                <span>Unsure if that email address was correct?</span> <a href="{{ route('auth.sign.up') }}">Retry</a>.
            </div>
            <br>
            <div class="other-links">
                <a href="{{ route('auth.sign.in') }}">Back to <strong class="text-primary">Sign-In</strong></a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.timezone-select').select2({
                placeholder: 'Select Timezone',
                allowClear: true,
                width: '100%',
                theme: 'default',
                dropdownParent: $('.timezone-select').parent(),
                containerCssClass: 'form-control flat',
                dropdownCssClass: 'select2-dropdown-timezone'
            });

            function updateRequirementIcon(element, isValid) {
                const icon = element.find('i');
                icon.removeClass('bi-circle bi-check-circle-fill bi-x-circle-fill valid invalid');
                if (isValid) {
                    icon.addClass('bi-check-circle-fill valid');
                } else {
                    icon.addClass('bi-x-circle-fill invalid');
                }
            }

            function calculatePasswordStrength(password) {
                let strength = 0;
                if (password.length >= 9) strength += 20;
                if (/[A-Z]/.test(password)) strength += 20;
                if (/[a-z]/.test(password)) strength += 20;
                if (/\d/.test(password)) strength += 20;
                if (/[\W_]/.test(password)) strength += 20;

                return strength;
            }

            function updatePasswordStrength(password) {
                const strength = calculatePasswordStrength(password);
                const meterFill = $('.password-strength-meter-fill');
                const strengthText = $('#strength-text');

                meterFill.css('width', strength + '%');

                if (strength <= 20) {
                    meterFill.css('background-color', '#dc3545');
                    strengthText.text('Very Weak');
                } else if (strength <= 40) {
                    meterFill.css('background-color', '#ffc107');
                    strengthText.text('Weak');
                } else if (strength <= 60) {
                    meterFill.css('background-color', '#17a2b8');
                    strengthText.text('Medium');
                } else if (strength <= 80) {
                    meterFill.css('background-color', '#28a745');
                    strengthText.text('Strong');
                } else {
                    meterFill.css('background-color', '#20c997');
                    strengthText.text('Very Strong');
                }
            }

            function checkPasswordRequirements(password) {
                let length = password.length >= 9;
                let uppercase = /[A-Z]/.test(password);
                let lowercase = /[a-z]/.test(password);
                let number = /\d/.test(password);
                let special = /[\W_]/.test(password);

                return length && uppercase && lowercase && number && special;
            }

            function updateSubmitButtonState() {
                let password = $('#password').val();
                let confirmPassword = $('#confirm_password').val();
                let passwordValid = checkPasswordRequirements(password);
                let passwordsMatch = password === confirmPassword && password !== '';

                $('#submit-btn button').prop('disabled', !(passwordValid && passwordsMatch));
            }

            $('#password').on('input', function() {
                let password = $(this).val();
                let length = password.length >= 9;
                let uppercase = /[A-Z]/.test(password);
                let lowercase = /[a-z]/.test(password);
                let number = /\d/.test(password);
                let special = /[\W_]/.test(password);

                // Update requirement icons
                updateRequirementIcon($('#length'), length);
                updateRequirementIcon($('#uppercase'), uppercase);
                updateRequirementIcon($('#lowercase'), lowercase);
                updateRequirementIcon($('#number'), number);
                updateRequirementIcon($('#special'), special);

                // Update password strength
                updatePasswordStrength(password);

                // Display error message if invalid
                if (length && uppercase && lowercase && number && special) {
                    $('#passwordHelp .help-text').text('');
                    $('#passwordHelp').hide();
                } else {
                    $('#passwordHelp .help-text').text('Password does not meet the criteria!');
                    $('#passwordHelp').show();
                }

                // Update confirm password validation if it has a value
                if ($('#confirm_password').val()) {
                    $('#confirm_password').trigger('input');
                }

                updateSubmitButtonState();
            });

            $('#confirm_password').on('input', function() {
                let password = $('#password').val();
                let confirmPassword = $(this).val();

                if (confirmPassword === '') {
                    $('#confirmHelp .help-text').text('');
                    $('#confirmHelp').hide();
                } else if (password !== confirmPassword) {
                    $('#confirmHelp .help-text').text('Passwords do not match!');
                    $('#confirmHelp').show();
                } else {
                    $('#confirmHelp .help-text').text('');
                    $('#confirmHelp').hide();
                }

                updateSubmitButtonState();
            });

            // Initial button state
            updateSubmitButtonState();

            $('#password').on('focus', function() {
                $('.password-requirements').addClass('show');
            });

            $('#password').on('blur', function() {
                if ($(this).val() === '') {
                    $('.password-requirements').removeClass('show');
                }
            });
        });
    </script>
@endpush
