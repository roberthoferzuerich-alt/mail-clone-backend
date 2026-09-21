<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/emails', function () {
    return response()->json([
        [
            'id' => 1,
            'sender' => 'chef@firma.de',
            'subject' => 'Wichtiges Meeting',
            'body' => 'Bitte komme um 10 Uhr in den Konferenzraum.',
            'date' => '2026-09-21T08:00:00Z',
            'isRead' => false,
        ],
        [
            'id' => 2,
            'sender' => 'newsletter@tech.com',
            'subject' => 'Deine wöchentlichen Tech-News',
            'body' => 'Hier sind die neuesten Updates aus der Welt der Technik...',
            'date' => '2026-09-20T14:30:00Z',
            'isRead' => true,
        ]
    ]);
});
