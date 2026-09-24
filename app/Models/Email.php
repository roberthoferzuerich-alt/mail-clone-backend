<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    /** @use HasFactory<\Database\Factories\EmailFactory> */
    use HasFactory;

    protected $fillable = ['mail_account_id', 'sender', 'subject', 'body', 'is_read', 'folder', 'attachments'];
    
    public function mailAccount()
    {
        return $this->belongsTo(MailAccount::class);
    }
    
    protected $casts = [
        'is_read' => 'boolean',
        'attachments' => 'array',
    ];
}
