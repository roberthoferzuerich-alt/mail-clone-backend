import os
import re

def update_api():
    with open('routes/api.php', 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the POST /emails route logic
    old_logic = """
        try {
            Mail::to($request->sender)->send(new SentEmail($request->subject, $request->body, $attachmentPaths));
        } catch (\Exception $e) {
            \Log::error('Mail sending failed: ' . $e->getMessage());
        }
"""

    new_logic = """
        // Dynamische SMTP-Konfiguration laden
        $account = $request->user()->mailAccounts()->first();
        if ($account) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $account->smtp_host,
                'mail.mailers.smtp.port' => $account->smtp_port,
                'mail.mailers.smtp.encryption' => $account->smtp_port == 465 ? 'ssl' : 'tls',
                'mail.mailers.smtp.username' => $account->email,
                'mail.mailers.smtp.password' => $account->password,
                'mail.from.address' => $account->email,
                'mail.from.name' => $request->user()->name,
            ]);
            
            // Wichtig: Wir müssen Laravel zwingen, die Konfiguration neu zu laden
            app()->singleton('mail.manager', function ($app) {
                return new \Illuminate\Mail\MailManager($app);
            });
            \Illuminate\Support\Facades\Mail::clearResolvedInstance('mail.manager');
        }

        try {
            if (!$account) {
                throw new \Exception("Kein E-Mail-Konto hinterlegt.");
            }
            \Illuminate\Support\Facades\Mail::to($request->sender)->send(new SentEmail($request->subject, $request->body, $attachmentPaths));
        } catch (\Exception $e) {
            \Log::error('Mail sending failed: ' . $e->getMessage());
            return response()->json(['error' => 'Mail sending failed: ' . $e->getMessage()], 500);
        }
"""

    if old_logic in content:
        content = content.replace(old_logic, new_logic)
        with open('routes/api.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("Updated POST /emails with dynamic SMTP!")
    else:
        print("Could not find the old logic block.")

update_api()

