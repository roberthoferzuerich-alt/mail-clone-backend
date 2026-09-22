import os

def update_api():
    with open('routes/api.php', 'r', encoding='utf-8') as f:
        content = f.read()

    new_routes = """
    Route::put('/mail-accounts/{id}', function (Request $request, $id) {
        $account = $request->user()->mailAccounts()->find($id);
        if (!$account) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'email' => 'required|email',
            'imap_host' => 'required|string',
            'imap_port' => 'required|integer',
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = $request->password;
        }

        $account->update($validated);
        return response()->json(['message' => 'Account updated']);
    });

    Route::delete('/mail-accounts/{id}', function (Request $request, $id) {
        $account = $request->user()->mailAccounts()->find($id);
        if (!$account) return response()->json(['message' => 'Not found'], 404);

        $account->delete();
        return response()->json(['message' => 'Account deleted']);
    });

    // E-Mail-Zähler
"""
    if "Route::put('/mail-accounts/{id}'" not in content:
        content = content.replace("    // E-Mail-Zähler", new_routes.strip() + "\n\n    // E-Mail-Zähler")
        with open('routes/api.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("Added PUT and DELETE for mail-accounts")
    else:
        print("Already added")

update_api()

