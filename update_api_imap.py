import os

def update_api_imap():
    with open('routes/api.php', 'r', encoding='utf-8') as f:
        content = f.read()

    new_imap_logic = """
    // Echte E-Mails über IMAP abrufen
    Route::get('/imap/sync', function (\Illuminate\Http\Request $request) {
        $accountId = $request->query('account_id');
        if (!$accountId) {
            return response()->json(['error' => 'No account_id provided'], 400);
        }

        $account = $request->user()->mailAccounts()->find($accountId);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            config([
                'imap.accounts.default.host' => $account->imap_host,
                'imap.accounts.default.port' => $account->imap_port,
                'imap.accounts.default.encryption' => $account->imap_port == 993 ? 'ssl' : 'tls',
                'imap.accounts.default.validate_cert' => false,
                'imap.accounts.default.username' => $account->email,
                'imap.accounts.default.password' => $account->password,
                'imap.accounts.default.protocol' => 'imap',
            ]);

            // Clear cache and connect
            \Webklex\IMAP\Facades\Client::purge('default');
            $client = \Webklex\IMAP\Facades\Client::account('default');
            $client->connect();

            $folder = $client->getFolder('INBOX');
            $messages = $folder->query()->limit(10)->get();

            $count = 0;
            foreach($messages as $message) {
                $subject = $message->getSubject()[0] ?? 'Kein Betreff';
                $body = $message->getTextBody() ?? '';
                if (empty(trim($body))) {
                    $body = $message->getHTMLBody() ?? 'Kein Inhalt';
                }
                
                $from = $message->getFrom()[0]->mail ?? 'unknown@example.com';
                
                $exists = \App\Models\Email::where('mail_account_id', $account->id)
                               ->where('subject', $subject)
                               ->where('sender', $from)
                               ->exists();
                
                if (!$exists) {
                    \App\Models\Email::create([
                        'mail_account_id' => $account->id,
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
            \Log::error('IMAP Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
"""
    
    # Simple search and replace for the IMAP block
    start_str = "// Echte E-Mails über IMAP abrufen"
    end_str = "});\n});"
    
    if start_str in content:
        before = content.split(start_str)[0]
        # Just append new_imap_logic and close the group
        content = before + new_imap_logic.strip() + "\n});\n"
        with open('routes/api.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("Updated IMAP route!")
    else:
        print("Could not find the IMAP block.")

update_api_imap()

