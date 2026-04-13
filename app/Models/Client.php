<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_name',
        'billing_terms',
        'billing_email',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function primaryContact()
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function pricingPresets(): HasMany
    {
        return $this->hasMany(PricingPreset::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(ClientRepository::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(ClientServer::class);
    }

    public function monitoredEndpoints(): HasMany
    {
        return $this->hasMany(MonitoredEndpoint::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(Scope::class);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->sum(DB::raw('total - amount_paid'));
    }

    public function getBillingTermsLabelAttribute(): string
    {
        return match($this->billing_terms) {
            'net_15' => 'Net 15',
            'net_30' => 'Net 30',
            'net_60' => 'Net 60',
            'due_on_receipt' => 'Due on Receipt',
            default => $this->billing_terms,
        };
    }

    public function getDueDaysAttribute(): int
    {
        return match($this->billing_terms) {
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'due_on_receipt' => 0,
            default => 30,
        };
    }
}
