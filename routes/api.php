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

// E-Mail löschen
Route::delete('/emails/{id}', function ($id) {
    $email = Email::find($id);
    if ($email) {
        $email->delete();
        return response()->json(['message' => 'Gelöscht']);
    }
    return response()->json(['message' => 'Nicht gefunden'], 404);
});

// E-Mail als gelesen markieren
Route::patch('/emails/{id}/read', function ($id) {
    $email = Email::find($id);
    if ($email) {
        $email->update(['is_read' => true]);
        return response()->json(['message' => 'Als gelesen markiert']);
    }
    return response()->json(['message' => 'Nicht gefunden'], 404);
});

// Echte E-Mails über IMAP abrufen
Route::get('/imap/sync', function () {
    try {
        $client = \Webklex\IMAP\Facades\Client::account('default');
        $client->connect();

        $folder = $client->getFolder('INBOX');
        // Die 5 neuesten E-Mails holen
        $messages = $folder->query()->limit(5)->get();

        $count = 0;
        foreach($messages as $message) {
            $subject = $message->getSubject()[0] ?? 'Kein Betreff';
            $body = $message->getTextBody() ?? '';
            if (empty(trim($body))) {
                $body = $message->getHTMLBody() ?? 'Kein Inhalt';
            }
            
            $from = $message->getFrom()[0]->mail ?? 'unknown@example.com';
            
            // Verhindern, dass Mails doppelt importiert werden
            $exists = Email::where('subject', $subject)->where('sender', $from)->exists();
            
            if (!$exists) {
                Email::create([
                    'sender' => $from,
                    'subject' => $subject,
                    'body' => mb_substr(strip_tags($body), 0, 500), // Als reinen Text speichern
                    'is_read' => false,
                ]);
                $count++;
            }
        }
        
        return response()->json(['message' => "$count neue E-Mails importiert!"]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
