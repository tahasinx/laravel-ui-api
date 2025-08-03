@component('mail::message')
    <div>
        <p>Hi {{ $name }}, <br><br>
            {{ $mail_body }}
            <br>
            <br>
            <br>
            <center>
                <a href="{{ $route }}"
                    style="display: inline-block; background-color: #007bff; color: #fff; padding: 10px 20px; text-decoration: none;">
                    View invitation
                </a>
            </center>
            <br>
            <br>
            Thanks,<br>
            {{ env('APP_NAME') }}
        </p>
    </div>
@endcomponent
