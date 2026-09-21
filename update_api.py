import os

def update_api():
    with open('routes/api.php', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Add imports
    if "use Illuminate\Support\Facades\Mail;" not in content:
        content = content.replace("use App\Http\Controllers\AuthController;", "use App\Http\Controllers\AuthController;\nuse Illuminate\Support\Facades\Mail;\nuse App\Mail\SentEmail;")
        
    # Replace email creation block
    old_block = """
        $email = Email::create([
            'sender' => $request->sender,
            'subject' => $request->subject,
            'body' => $request->body,
            'is_read' => true,
            'folder' => 'sent',
            'attachments' => $attachmentPaths,
        ]);

        return response()->json($email, 201);
"""
    new_block = """
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
"""
    
    if old_block in content:
        content = content.replace(old_block, new_block)
    
    with open('routes/api.php', 'w', encoding='utf-8') as f:
        f.write(content)

update_api()
print("Updated api.php")

