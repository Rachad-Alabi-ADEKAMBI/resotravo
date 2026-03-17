<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    /**
     * Dashboard client
     * GET /client/dashboard
     */
    public function index()
    {
        $user   = Auth::user();
        $client = $user->client;

        return view('pages.back.client.dashboard', [
            'active'  => 'dashboard',
            'user'    => $user,
            'client'  => $client,
        ]);
    }
}