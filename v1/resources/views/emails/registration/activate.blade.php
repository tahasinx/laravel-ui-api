@component('mail::message')
<div style="background-color: #f2f2f2; padding: 20px; font-size: 15px; font-family: Arial, sans-serif;">
<p>Hi {{ $name }},</p>
<p>{{ $mail_body }}</p>

<div style="text-align: center; margin: 20px 0;">
@php
$route = route('activate.account', ['tracking_id' => $tracking_id]);
@endphp
<a href="{{ $route }}" style="display: inline-block; padding: 10px 20px; background-color: #000; color: #fff; text-decoration: none; border-radius: 5px;">
Activate
</a>
</div>

<p style="color: red;">Note: This activation link will expire in 1 hour, at {{ $time }}.</p>

<p>Thanks,<br>{{ config('app.name') }}</p>
</div>
@endcomponent
