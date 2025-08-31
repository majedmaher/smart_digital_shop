<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Go Out';
    // return view('emails.ticket-notification');
});
