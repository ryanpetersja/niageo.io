<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ClientServer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'client_id',
        'label',
        'host',
        'port',
        'username',
        'auth_type',
        'private_key_path',
        'encrypted_password',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->label} ({$this->host})";
    }

    public function setEncryptedPasswordAttribute(?string $value): void
    {
        $this->attributes['encrypted_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedPasswordAttribute(): ?string
    {
        if (!$this->attributes['encrypted_password']) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['encrypted_password']);
        } catch (\Exception $e) {
            return null;
        }
    }
}
