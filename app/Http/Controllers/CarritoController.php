<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class CarritoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Carrito/index', [
            'lastOrder' => session()->pull('lastOrder'),
        ]);
    }
}
