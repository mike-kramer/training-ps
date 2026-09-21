<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Policies\UserPolicy;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    const STATUS_ACTIVE = 0;
    const STATUS_BANNED = 1;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
        ];
    }

    public function checkPassword(string $password): void
    {
        if (!Hash::check($password, $this->password)) {
            throw new \Exception('Wrong password');
        }
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function cashboxes(): HasMany
    {
        return $this->hasMany(Cashbox::class);
    }

    public function hasPermission(string $permissionName): bool
    {
        $permissions = $this->role?->permissions?->map(
            fn(Permission $permission) => $permission->name
        ) ?? collect([]);
        return $permissions->contains($permissionName);
    }

    #[Scope]
    protected function hasCashbox(Builder $query, int $cashboxId): void
    {
        $query->select("users.*")
            ->join("cashboxes", "cashboxes.user_id", "=", "users.id")
            ->where("cashboxes.id", "=", $cashboxId);
    }

    #[Scope]
    protected function hasCashboxWithName(Builder $query, string $cashboxName): void
    {
        $query->select("users.*")
            ->join("cashboxes", "cashboxes.user_id", "=", "users.id")
            ->where("cashboxes.name", "=", $cashboxName);
    }


}
