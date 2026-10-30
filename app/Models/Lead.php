<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A company from a directory (Finstat, Azet) that is not on the list of companies to approach.
 */
#[Fillable(['name', 'name_key', 'source', 'address', 'city', 'web', 'ico', 'employees', 'revenue', 'founded_on', 'registry_status', 'status_note'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'founded_on' => 'date',
            'revenue' => 'integer',
        ];
    }
}
