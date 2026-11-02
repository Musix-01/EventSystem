<?php

namespace App\Models;

use App\Enums\Organization;
use App\Enums\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'student_number',
        'email',
        'name',
        'organization',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'organization' => Organization::class,
            'role'         => Role::class,
            'password'     => 'hashed',
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /** Short label for the header. Admins are from the PUP office, so they have no organization. */
    public function affiliation(): string
    {
        if ($this->isAdmin()) {
            return 'Admin';
        }

        return $this->organization->value . ', ' . $this->role->label();
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isOfficer(): bool
    {
        return $this->role === Role::Officer;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }
}
