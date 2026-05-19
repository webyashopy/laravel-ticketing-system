<?php

declare(strict_types=1);

namespace Webyashopy\Tickets\Tests\Stubs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Webyashopy\Tickets\Tests\Database\Factories\UserFactory;

/**
 * Testovací User model balíčku.
 *
 * Balíček sám žádný User model nemá — host aplikace ho dodává a balíček
 * ho čte z `config('tickets.models.user_model')`.
 * V izolovaném Testbench prostředí proto potřebujeme minimální stub, který
 * splňuje kontrakt host User modelu:
 *
 *   - je `Authenticatable` (kvůli `actingAs()` ve feature testech),
 *   - je `Notifiable` (kvůli `Notification::assertSentTo()` v dispatcheru),
 *   - má atributy `name` a `email`.
 *
 * Sloupec `tenant_id` (nullable) drží příslušnost uživatele k tenantovi —
 * čte ho testovací {@see \Webyashopy\Tickets\Tests\Stubs\MultiTenantTicketResolver}.
 * Sloupec `is_superadmin` (bool) řídí cross-tenant viditelnost a roli.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $tenant_id
 * @property bool $is_superadmin
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password'];

    protected $casts = [
        'is_superadmin' => 'boolean',
    ];

    /**
     * Je uživatel superadmin? Řídí cross-tenant viditelnost a roli
     * v testovacích implementacích kontraktů.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Factory pro testovacího uživatele.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
