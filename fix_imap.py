import re

def fix_api_imap():
    with open('routes/api.php', 'r', encoding='utf-8') as f:
        content = f.read()

    old_logic = """
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
"""
    new_logic = """
            $client = \Webklex\IMAP\Facades\Client::make([
                'host'          => $account->imap_host,
                'port'          => $account->imap_port,
                'encryption'    => $account->imap_port == 993 ? 'ssl' : 'tls',
                'validate_cert' => false,
                'username'      => $account->email,
                'password'      => $account->password,
                'protocol'      => 'imap'
            ]);
"""
    
    if "purge('default')" in content:
        content = content.replace(old_logic.strip(), new_logic.strip())
        with open('routes/api.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("Fixed IMAP!")
    else:
        print("Not found")

fix_api_imap()

