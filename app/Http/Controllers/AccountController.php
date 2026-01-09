<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.edit');
    }
}
