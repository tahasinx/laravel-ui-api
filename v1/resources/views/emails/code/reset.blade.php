@component('mail::message')
    <div
        style="background-color: #f2f2f2; padding: 20px;font-size:15px;">
        <p>Hi {{ $name }}, <br><br>
            {{ $mail_body }}
            <br>
            <br>
            <center>
                <div style="height: 30px;width: 150px;background: rgba(0, 0, 0, 0.8);color:white;padding-top:5px">
                    {{ $code }}</div>
            </center>
            <br>
            #Note: This code expire after 3 minutes at <span
                style="color: red">{{ $time }}.</span>
            <br>
            <br>
            Thanks,<br>
            {{ env('APP_NAME') }}
        </p>
    </div>
@endcomponent
