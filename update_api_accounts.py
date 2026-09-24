import os
import re

def update_api():
    with open('routes/api.php', 'r', encoding='utf-8') as f:
        content = f.read()

    # /emails/counts
    old_counts = """
    Route::get('/emails/counts', function (Request $request) {
        $counts = Email::selectRaw('folder, COUNT(*) as count')
            ->where('is_read', false)
            ->groupBy('folder')
            ->pluck('count', 'folder')
            ->toArray();
        
        return response()->json($counts);
    });
"""
    new_counts = """
    Route::get('/emails/counts', function (Request $request) {
        $accountId = $request->query('account_id');
        $query = Email::selectRaw('folder, COUNT(*) as count')->where('is_read', false);
        if ($accountId) {
            $query->where('mail_account_id', $accountId);
        } else {
            $query->whereNull('mail_account_id');
        }
        
        $counts = $query->groupBy('folder')
            ->pluck('count', 'folder')
            ->toArray();
        
        return response()->json($counts);
    });
"""
    content = content.replace(old_counts.strip(), new_counts.strip())

    # /emails GET
    old_get = """
    Route::get('/emails', function (Request $request) {
        $query = Email::query();
        
        $folder = $request->query('folder', 'inbox');
        $query->where('folder', $folder);
"""
    new_get = """
    Route::get('/emails', function (Request $request) {
        $query = Email::query();
        
        $accountId = $request->query('account_id');
        if ($accountId) {
            $query->where('mail_account_id', $accountId);
        } else {
            $query->whereNull('mail_account_id');
        }
        
        $folder = $request->query('folder', 'inbox');
        $query->where('folder', $folder);
"""
    content = content.replace(old_get.strip(), new_get.strip())

    # /emails POST
    old_post_mail = """
        $email = Email::create([
            'sender' => $request->sender,
            'subject' => $request->subject,
            'body' => $request->body,
            'is_read' => true,
            'folder' => 'sent',
            'attachments' => $attachmentPaths,
        ]);

        // Dynamische SMTP-Konfiguration laden
        $account = $request->user()->mailAccounts()->first();
"""
    new_post_mail = """
        $accountId = $request->input('account_id');
        $account = $accountId ? $request->user()->mailAccounts()->find($accountId) : $request->user()->mailAccounts()->first();

        $email = Email::create([
            'mail_account_id' => $account ? $account->id : null,
            'sender' => $request->sender,
            'subject' => $request->subject,
            'body' => $request->body,
            'is_read' => true,
            'folder' => 'sent',
            'attachments' => $attachmentPaths,
        ]);

        // Dynamische SMTP-Konfiguration laden
"""
    content = content.replace(old_post_mail.strip(), new_post_mail.strip())
    
    # Also fix the validation for POST emails
    old_validate = """
        $request->validate([
            'sender' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);
"""
    new_validate = """
        $request->validate([
            'account_id' => 'nullable|integer',
            'sender' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);
"""
    content = content.replace(old_validate.strip(), new_validate.strip())

    with open('routes/api.php', 'w', encoding='utf-8') as f:
        f.write(content)

update_api()
print("Updated API")

