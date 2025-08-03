var ajaxTime = new Date().getTime();
function sign_in(form_id, route, callback) {

    var login_blocked_till = localStorage.getItem('login_blocked_till');
    var login_blocked_msg = localStorage.getItem('login_blocked_msg');

    // Check if blocked_till is a valid time and is in the future
    if (login_blocked_till) {
        var year = parseInt(login_blocked_till.substr(0, 4), 10);
        var month = parseInt(login_blocked_till.substr(4, 2), 10) - 1; // Months are zero-indexed
        var day = parseInt(login_blocked_till.substr(6, 2), 10);
        var hour = parseInt(login_blocked_till.substr(8, 2), 10);
        var minute = parseInt(login_blocked_till.substr(10, 2), 10);
        var second = parseInt(login_blocked_till.substr(12, 2), 10);

        var blockedDate = new Date(year, month, day, hour, minute, second);

        console.log(login_blocked_till);
        console.log(blockedDate);
        console.log(new Date());

        if (blockedDate > new Date()) {
            showErrorMessage(login_blocked_msg, 1);
            return; // Stop further execution
        } else {
            localStorage.removeItem('login_blocked_till')
            localStorage.removeItem('login_blocked_msg')
        }
    }

    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: route,
        type: 'post',
        data: $('#' + form_id).serialize(),
        beforeSend: function (jqXHR, settings) {
            $('#submit-btn').addClass('d-none')
            $('#processing-btn').removeClass('d-none')
        },
        success: function (response) {

            if (response.success) {
                showSuccessMessage(response.message, 1)
                setTimeout(() => {
                    window.location = response.route
                    return;
                }, 500);
            }

            if (response.error) {
                setTimeout(() => {
                    $('#processing-btn').addClass('d-none')
                    $('#submit-btn').removeClass('d-none')
                    showErrorMessage(response.message, 1)
                }, 2000);
            }

            if (response.attempt_error) {
                // console.log(response);
                setTimeout(() => {
                    $('#processing-btn').addClass('d-none')
                    $('#submit-btn').removeClass('d-none')

                    showErrorMessage(response.message, 1)

                    if (response.attempt_blocked_till) {
                        console.log(response.attempt_blocked_till);
                        localStorage.setItem('login_blocked_till', response.attempt_blocked_till)
                        localStorage.setItem('login_blocked_msg', response.message)
                    }
                }, 500);
            }
        },
        statusCode: {
            500: function (response) {
                console.log(response);
                $('#processing-btn').addClass('d-none')
                $('#submit-btn').removeClass('d-none')
                showErrorMessage(response.statusText, 1)
            },
            422: function (response) {
                $('#processing-btn').addClass('d-none')
                $('#submit-btn').removeClass('d-none')
                showErrorMessage(response.statusText, 1)
            },
            419: function () {
                $.when(getTokenAndRetry(callback)).then(function () {
                    sign_in(form_id, route, callback)
                })
            }
        },
        error: function (response) {
            if (response && response.responseJSON && 'errors' in response.responseJSON) {
                if ('email' in response.responseJSON.errors) {
                    $('input[name="email"]').addClass('is-invalid');
                    showErrorMessage(response.responseJSON.errors.email[0]); // Displaying the email error message
                }

                if ('password' in response.responseJSON.errors) {
                    $('input[name="password"]').addClass('is-invalid');
                    showErrorMessage(response.responseJSON.errors.password[0]); // Displaying the password error message
                }

            }

            setTimeout(() => {
                $('#processing-btn').addClass('d-none')
                $('#submit-btn').removeClass('d-none')
            }, 3000);
        }
    })
}

function sign_up(form_id, route, callback) {

    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: route,
        type: 'post',
        data: $('#' + form_id).serialize(),
        beforeSend: function (jqXHR, settings) {
            $('#submit-btn').addClass('d-none')
            $('#processing-btn').removeClass('d-none')
        },
        success: function (response) {

            if (response.success) {

                if (response.queue) {

                    var the_queue = $.ajax({
                        url: job_route,
                        type: 'post',
                        data: {
                            '_token': response.token,
                            'queue': response.queue,
                            'timeout': 60
                        },
                        success: function (res) {
                            console.log(res);
                        }
                    });
                    if (the_queue) {
                        var message = '<span class="text-success">' + response.message + '</span>'
                        showSuccessMessage(message, 1)

                        setTimeout(() => {
                            $('.form-items').addClass('d-none')
                            $('.success_msg').html(response.message)
                            $('.form-sent').addClass('show-it')
                            $('#' + form_id).trigger('reset')
                        }, 1000);

                        setTimeout(() => {
                            window.location.replace(response.route)
                        }, 30000);
                    }
                }
            }

            if (response.error) {
                setTimeout(() => {
                    $('#processing-btn').addClass('d-none')
                    $('#submit-btn').removeClass('d-none')
                    showErrorMessage(response.message, 1)

                    if (response.type && response.type == 'email') {
                        $('input[name="email"]').addClass('is-invalid')
                    }
                }, 2000);
            }

        },
        statusCode: {
            500: function (response) {
                console.log(response);
                $('#processing-btn').addClass('d-none')
                $('#submit-btn').removeClass('d-none')
                showErrorMessage(response.statusText, 1)
            },

            419: function () {
                $.when(getTokenAndRetry(callback)).then(function () {
                    sign_up(form_id, route, callback)
                })
            }
        },
        error: function (response) {
            if (response && response.responseJSON && 'errors' in response.responseJSON) {
                if ('email' in response.responseJSON.errors) {
                    $('input[name="email"]').addClass('is-invalid');
                    showErrorMessage(response.responseJSON.errors.email[0]); // Displaying the email error message
                }
                if ('password' in response.responseJSON.errors) {
                    $('input[name="password"]').addClass('is-invalid');
                    showErrorMessage(response.responseJSON.errors.password[0]); // Displaying the password error message
                }
                if ('confirm_password' in response.responseJSON.errors) {
                    $('input[name="confirm_password"]').addClass('is-invalid');
                    showErrorMessage(response.responseJSON.errors.confirm_password[0]);
                }
                if ('name' in response.responseJSON.errors) {
                    $('input[name="name"]').addClass('is-invalid');
                    showErrorMessage(response.responseJSON.errors.name[0]); // Displaying the name error message
                }
                if ('timezone' in response.responseJSON.errors) {
                    $('.timezone-select').addClass('is-invalid');
                    showErrorMessage(response.responseJSON.errors.timezone[0]); // Displaying the timezone error message
                }
            }

            setTimeout(() => {
                $('#processing-btn').addClass('d-none')
                $('#submit-btn').removeClass('d-none')
            }, 3000);
        }
    })
}

function forgot_password(form_id, route, callback) {

    var ajaxTime = new Date().getTime();

    $.ajax({
        url: route,
        type: 'post',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: $('#' + form_id).serialize(),
        beforeSend: function (jqXHR, settings) {
            $('#forgot-btn').addClass('d-none')
            $('#forgot-processing').removeClass('d-none')
        },
        success: function (response) {

            var totalTime = new Date().getTime() - ajaxTime;
            var time = Math.round(totalTime / 1000);

            if (response.queue) {

                var the_queue = $.ajax({
                    url: job_route,
                    type: 'post',
                    data: {
                        '_token': response.token,
                        'queue': response.queue,
                        'timeout': 60
                    },
                    success: function (res) {
                        console.log(res);
                    }
                });
                if (the_queue) {
                    var message = '<span class="text-success">' + response.message + '</span>'
                    showSuccessMessage(message, time)

                    setTimeout(() => {
                        // $('#' + form_id).trigger('reset')
                        window.location.replace(response.route)
                    }, 1000);
                }
            }

            if (response.error) {

                $('#forgot-processing').addClass('d-none')
                $('#forgot-btn').removeClass('d-none')

                var totalTime = new Date().getTime() - ajaxTime;
                var time = Math.round(totalTime / 1000);

                var message = '<span class="text-danger">' + response.message + '</span>'
                showErrorMessage(message, time)

                if (response.type && response.type == 'email') {
                    $('input[name="email"]').addClass('is-invalid')
                }
            }
        },
        error: function (response) {
            var icon = '<i class="bi bi-exclamation-triangle"></i> '
            var totalTime = new Date().getTime() - ajaxTime;
            var time = Math.round(totalTime / 1000);

            if (response && response.status) {
                var status_code = response.status

                if (status_code == 419) {
                    $.when(getTokenAndRetry(callback)).then(function () {
                        forgot_password(form_id, route, callback)
                    })
                }

                if (status_code == 500) {
                    var message = '<span class="text-danger">' + icon +
                        'Something went wrong ! (Error Code: 500)</span>'
                    showErrorMessage(message, time)
                }
            }

            if (response && response.responseJSON && 'errors' in response.responseJSON) {
                if ('email' in response.responseJSON.errors) {
                    var message = '<span class="text-danger">' + icon + response.responseJSON.errors
                        .email + '</span>'
                    showErrorMessage(message, time)
                    $('input[name="email"]').addClass('is-invalid')
                }
            }

            $('#forgot-processing').addClass('d-none')
            $('#forgot-btn').removeClass('d-none')
        }
    })
}

function reset_password(form_id, route, callback) {

    var ajaxTime = new Date().getTime();

    $.ajax({
        url: route,
        type: 'post',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: $('#' + form_id).serialize(),
        beforeSend: function (jqXHR, settings) {
            $('#reset-btn').addClass('d-none')
            $('#reset-processing').removeClass('d-none')
        },
        success: function (response) {

            var totalTime = new Date().getTime() - ajaxTime;
            var time = Math.round(totalTime / 1000);

            if (response.success) {
                var message = '<span class="text-success">' + response.message + '</span>'
                showSuccessMessage(message, time)

                setTimeout(() => {
                    $('.form-items').addClass('d-none')
                    $('.success_msg').html(response.message)
                    $('.form-sent').addClass('show-it')
                    $('#' + form_id).trigger('reset')
                }, 1000);

                setTimeout(() => {
                    window.location.replace(response.route)
                }, 30000);
            }

            if (response.error) {

                $('#reset-processing').addClass('d-none')
                $('#reset-btn').removeClass('d-none')

                var totalTime = new Date().getTime() - ajaxTime;
                var time = Math.round(totalTime / 1000);

                var message = '<span class="text-danger">' + response.message + '</span>'
                showErrorMessage(message, time)

                if (response.type && response.type == 'code') {
                    $('input[name="verification_code"]').addClass('is-invalid')
                }

                if (response.type && response.type == 'session') {
                    setTimeout(() => {
                        $('#' + form_id).trigger('reset')
                        window.location.replace(response.route)
                    }, 1000);
                }

            }
        },
        error: function (response) {
            var icon = '<i class="bi bi-exclamation-triangle"></i> '
            var totalTime = new Date().getTime() - ajaxTime;
            var time = Math.round(totalTime / 1000);

            if (response && response.status) {
                var status_code = response.status

                if (status_code == 419) {
                    $.when(getTokenAndRetry(callback)).then(function () {
                        reset_password(form_id, route, callback)
                    })
                }

                if (status_code == 500) {
                    var message = '<span class="text-danger">' + icon +
                        'Something went wrong ! (Error Code: 500)</span>'
                    showErrorMessage(message, time)
                }
            }

            if (response && response.responseJSON && 'errors' in response.responseJSON) {
                if ('verification_code' in response.responseJSON.errors) {
                    var message = '<span class="text-danger">' + icon + response.responseJSON.errors
                        .verification_code + '</span>'
                    showErrorMessage(message, time)
                    $('input[name="verification_code"]').addClass('is-invalid')
                }
                if ('new_password' in response.responseJSON.errors) {
                    var message = '<span class="text-danger">' + icon + response.responseJSON.errors
                        .new_password + '</span>'
                    showErrorMessage(message, time)
                    $('input[name="new_password"]').addClass('is-invalid')
                }
                if ('confirm_password' in response.responseJSON.errors) {
                    $('input[name="confirm_password"]').addClass('is-invalid');
                    $('#confirmHelp .help-text').text(response.responseJSON.errors.confirm_password[0]);
                    $('#confirmHelp').show();
                }
            }

            $('#reset-processing').addClass('d-none')
            $('#reset-btn').removeClass('d-none')
        }
    })
}
