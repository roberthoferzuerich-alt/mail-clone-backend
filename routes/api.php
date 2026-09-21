<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Email;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Mail;
use App\Mail\SentEmail;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // E-Mail-Zähler für Ordner abrufen (Ungelesen)
    Route::get('/emails/counts', function (Request $request) {
        $counts = Email::selectRaw('folder, COUNT(*) as count')
            ->where('is_read', false)
            ->groupBy('folder')
            ->pluck('count', 'folder')
            ->toArray();
        
        return response()->json($counts);
    });

    // Alle E-Mails abrufen (neueste zuerst, mit Filter für Ordner und Suche)
    Route::get('/emails', function (Request $request) {
        $query = Email::query();
        
        $folder = $request->query('folder', 'inbox');
        $query->where('folder', $folder);
        
        if ($request->has('search') && !empty($request->query('search'))) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('sender', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($email) {
            return [
                'id' => $email->id,
                'sender' => $email->sender,
                'subject' => $email->subject,
                'body' => $email->body,
                'isRead' => (bool) $email->is_read,
                'folder' => $email->folder,
                'attachments' => $email->attachments ?? [],
                'date' => $email->created_at->toIso8601String(),
            ];
        });
    });

    // Neue E-Mail anlegen (inklusive Datei-Anhänge)
    Route::post('/emails', function (Request $request) {
        $request->validate([
            'sender' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachmentPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path
                ];
            }
        }

        $email = Email::create([
            'sender' => $request->sender,
            'subject' => $request->subject,
            'body' => $request->body,
            'is_read' => true,
            'folder' => 'sent',
            'attachments' => $attachmentPaths,
        ]);

        try {
            Mail::to($request->sender)->send(new SentEmail($request->subject, $request->body, $attachmentPaths));
        } catch (\Exception $e) {
            \Log::error('Mail sending failed: ' . $e->getMessage());
        }

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

    // E-Mail in anderen Ordner verschieben
    Route::patch('/emails/{id}/move', function (Request $request, $id) {
        $request->validate(['folder' => 'required|string']);
        $email = Email::find($id);
        if ($email) {
            $email->update(['folder' => $request->folder]);
            return response()->json(['message' => 'Verschoben']);
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
            $messages = $folder->query()->limit(5)->get();

            $count = 0;
            foreach($messages as $message) {
                $subject = $message->getSubject()[0] ?? 'Kein Betreff';
                $body = $message->getTextBody() ?? '';
                if (empty(trim($body))) {
                    $body = $message->getHTMLBody() ?? 'Kein Inhalt';
                }
                
                $from = $message->getFrom()[0]->mail ?? 'unknown@example.com';
                
                $exists = Email::where('subject', $subject)->where('sender', $from)->exists();
                
                if (!$exists) {
                    Email::create([
                        'sender' => $from,
                        'subject' => $subject,
                        'body' => mb_substr(strip_tags($body), 0, 500),
                        'is_read' => false,
                        'folder' => 'inbox',
                    ]);
                    $count++;
                }
            }
            
            return response()->json(['message' => "$count neue E-Mails importiert!"]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});
