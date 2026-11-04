<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'super_admin',
        'last_entity_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'super_admin' => 'boolean',
        ];
    }

    public function entities()
    {
        return $this->belongsToMany(Entity::class, 'entity_user')
            ->withPivot(['role', 'is_default'])
            ->withTimestamps();
    }

    public function lastEntity()
    {
        return $this->belongsTo(Entity::class, 'last_entity_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->super_admin;
    }

    public function hasEntityRole(int $entityId, string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return $this->entities()
            ->where('entities.id', $entityId)
            ->whereIn('entity_user.role', $roles)
            ->exists();
    }
}
