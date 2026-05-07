<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceModuleDisabled
{
    public function handle(Request $request, Closure $next): Response
    {
        return redirect()->route('dashboard');
    }
}
