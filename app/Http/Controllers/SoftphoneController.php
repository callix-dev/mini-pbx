<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SoftphoneController extends Controller
{
    /**
     * Display the softphone in a popup window.
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user->extension) {
            return view('softphone.no-extension');
        }
        
        return view('softphone.index', [
            'user' => $user,
            'extension' => $user->extension,
        ]);
    }
}


