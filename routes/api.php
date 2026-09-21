<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Models\Email;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Alle E-Mails abrufen (neueste zuerst)
Route::get('/emails', function () {
    // Echte Daten aus der Datenbank laden und in das von Flutter erwartete Format mappen
    return Email::orderBy('created_at', 'desc')->get()->map(function ($email) {
        return [
            'id' => $email->id,
            'sender' => $email->sender,
            'subject' => $email->subject,
            'body' => $email->body,
            'isRead' => (bool) $email->is_read,
            'date' => $email->created_at->toIso8601String(),
        ];
    });
});

// Neue E-Mail anlegen (wird über Postman oder App genutzt)
Route::post('/emails', function (Request $request) {
    $request->validate([
        'sender' => 'required|email',
        'subject' => 'required|string|max:255',
        'body' => 'required|string',
    ]);

    $email = Email::create([
        'sender' => $request->sender,
        'subject' => $request->subject,
        'body' => $request->body,
        'is_read' => false,
    ]);

    return response()->json($email, 201);
});
