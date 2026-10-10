<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Peran;
use App\Models\Concerns\TercatatAktivitas;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $nip
 * @property string|null $nidn
 * @property string|null $no_hp
 * @property string|null $prodi_id
 * @property bool $is_aktif
 * @property array<int, string>|null $app_authentication_recovery_codes
 * @property-read Pegawai|null $pegawai
 */
#[Fillable(['name', 'email', 'password', 'nip', 'nidn', 'prodi_id', 'no_hp', 'is_aktif'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, TercatatAktivitas;

    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            if (auth()->id() === $user->getKey()) {
                throw new \LogicException('Pengguna tidak dapat menghapus akunnya sendiri.');
            }
        });
    }

    public function pegawai(): HasOne
    {
        return $this->hasOne(Pegawai::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_aktif || $this->hasRole(Peran::KlienApi->value)) {
            return false; // klien API hanya boleh memakai token, tidak pernah panel
        }

        if ($panel->getId() === 'swalayan') {
            return $this->can('swalayan.akses') && $this->pegawai()->exists();
        }

        if (! $this->hasAnyRole(array_map(fn (Peran $p) => $p->value, Peran::panelAdmin()))) {
            return false;
        }

        return self::surelDiizinkan($this->email);
    }

    /** Akhiran surel yang diizinkan (config sdm.domain_surel). */
    public static function surelDiizinkan(?string $surel): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        /** @var list<string> $domain */
        $domain = config('sdm.domain_surel');

        return array_any($domain, fn (string $akhiran): bool => str_ends_with(mb_strtolower((string) $surel), $akhiran));
    }

    /** Aturan validasi `ends_with` untuk surel pengguna; longgar di lingkungan local. */
    public static function aturanDomainSurel(): string
    {
        return app()->environment('local') ? 'nullable' : 'ends_with:'.implode(',', config('sdm.domain_surel'));
    }

    /** Pesan galat domain surel, mis. "@unsil.ac.id atau @staff.unsil.ac.id". */
    public static function pesanDomainSurel(): string
    {
        return 'Surel harus berakhiran '.implode(' atau ', config('sdm.domain_surel')).'.';
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[\SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /** @return ?array<string> */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /** @param  ?array<string>  $codes */
    public function saveAppAuthenticationRecoveryCodes(#[\SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    public function wajibMfa(): bool
    {
        return $this->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value]);
    }

    /** Hanya super-admin dapat memulai impersonate (stechstudio/filament-impersonate). */
    public function canImpersonate(): bool
    {
        return $this->is_aktif && $this->hasRole(Peran::SuperAdmin->value);
    }

    /** Sesama super-admin dan klien API tidak dapat ditiru; akun nonaktif juga tidak. */
    public function canBeImpersonated(): bool
    {
        return $this->is_aktif && ! $this->hasAnyRole([Peran::SuperAdmin->value, Peran::KlienApi->value]);
    }

    /** URL panel yang sesuai untuk pengguna ini, dipakai saat impersonate mengalihkan. */
    public function urlPanelUtama(): string
    {
        return $this->can('swalayan.akses') && $this->pegawai()->exists() ? url('/saya') : url('/admin');
    }

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
            'is_aktif' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'last_login_at' => 'datetime',
        ];
    }
}
