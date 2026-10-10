<?php

namespace App\Filament\Admin\Resources\Users;

use App\Enums\Peran;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\TempelPengguna;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use STS\FilamentImpersonate\Actions\Impersonate;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(150),
            TextInput::make('email')->label('Surel')->email()->required()->maxLength(150)
                ->unique(ignoreRecord: true)
                ->rules([
                    fn (): string => User::aturanDomainSurel(),
                ])
                ->validationMessages(['ends_with' => User::pesanDomainSurel()]),
            TextInput::make('password')->label('Kata sandi')->password()->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->minLength(10),
            TextInput::make('nip')->label('NIP')->maxLength(18),
            TextInput::make('nidn')->label('NIDN')->maxLength(10),
            TextInput::make('no_hp')->label('No. HP')->maxLength(20),
            Select::make('roles')->label('Peran')->multiple()->preload()
                ->relationship('roles', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record): string => Peran::tryFrom($record->name)?->getLabel() ?? $record->name),
            Select::make('prodi_id')->label('Program studi')->options(fn (): array => Prodi::opsiAktif())->searchable()
                ->required(fn (Get $get): bool => Role::whereIn('id', (array) $get('roles'))->where('name', Peran::AdminProdi->value)->exists())
                ->validationMessages(['required' => 'Program studi wajib diisi untuk peran Admin Prodi.']),
            Toggle::make('is_aktif')->label('Aktif')->default(true)
                ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false),
            Toggle::make('wajib_ganti_sandi')->label('Wajib ganti sandi saat masuk')
                ->helperText('Nyalakan untuk akun dengan kata sandi awal yang dibuat admin.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Surel')->searchable(),
                TextColumn::make('roles.name')->label('Peran')->badge()
                    ->formatStateUsing(fn (string $state): string => Peran::tryFrom($state)?->getLabel() ?? $state),
                ToggleColumn::make('is_aktif')->label('Aktif')
                    ->disabled(fn (User $record): bool => $record->is(auth()->user())),
                TextColumn::make('last_login_at')->label('Login terakhir')->dateTime('d F Y H:i')->placeholder('-'),
                IconColumn::make('mfa')->label('MFA')->boolean()
                    ->state(fn (User $record): bool => filled($record->app_authentication_secret)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('resetMfa')->label('Reset MFA')->icon(Heroicon::OutlinedShieldExclamation)
                    ->color('warning')->requiresConfirmation()
                    ->visible(fn (User $record): bool => filled($record->app_authentication_secret))
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'app_authentication_secret' => null,
                            'app_authentication_recovery_codes' => null,
                        ])->save();

                        activity()->performedOn($record)->causedBy(auth()->user())->event('reset-mfa')->log('MFA direset');
                    }),
                Impersonate::make()
                    ->redirectTo(fn (User $record): string => $record->urlPanelUtama())
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray'),
                DeleteAction::make()->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'tempel' => TempelPengguna::route('/tempel'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
