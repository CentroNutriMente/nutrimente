<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Booking/Index');
    }

    public function show(string $profile): Response
    {
        // TODO: load professional by slug/booking_token when public booking is implemented
        return Inertia::render('Booking/Show', ['profile' => $profile]);
    }

    public function store(Request $request, string $profile)
    {
        // TODO: implement public booking submission
        return back()->with('message', 'Richiesta ricevuta. Ti contatteremo per confermare.');
    }
}
