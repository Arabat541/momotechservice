<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $request->query('client_id');
        $query    = CreditTransaction::with('client')->orderByDesc('created_at');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $transactions = $query->paginate(30);
        $clients      = Client::where('type', 'revendeur')->orderBy('nom')->get();

        return view('credit.index', compact('transactions', 'clients'));
    }
}
