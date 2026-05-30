<?php

namespace App\Filament\Resources\TelegramBots;

use App\Actions\Telegram\GenerateTelegramBotHandler;
use App\Filament\Resources\TelegramBots\Pages\CreateTelegramBot;
use App\Filament\Resources\TelegramBots\Pages\EditTelegramBot;
use App\Filament\Resources\TelegramBots\Pages\ListTelegramBots;
use App\Models\TelegramBot;
use App\Services\Telegram\TelegramWebhookManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Throwable;

class TelegramBotResource extends Resource
{
    protected static ?string $model = TelegramBot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Telegram Bots';

    protected static ?string $modelLabel = 'Telegram Bot';

    protected static ?string $pluralModelLabel = 'Telegram Bots';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bot')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdatedJs(<<<'JS'
                                $set('slug', ($state ?? '')
                                    .toLowerCase()
                                    .normalize('NFD')             
                                    .replace(/[\u0300-\u036f]/g, '') 
                                    .replace(/[^a-z0-9]+/g, '-')   
                                    .replace(/(^-|-$)+/g, '')
                                );
                            JS),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash'])
                            ->unique(ignoreRecord: true),
                        TextInput::make('bot_username')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Webhook')
                    ->schema([
                        TextInput::make('token')
                            ->label('Bot token')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Leave blank when editing to keep the current encrypted token.'),
                        TextInput::make('handler_class')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('App\\Telegram\\Bots\\SupportBotHandler'),
                        Placeholder::make('webhook_endpoint')
                            ->label('Webhook endpoint')
                            ->content(fn (?TelegramBot $record): string => $record?->exists ? $record->webhookEndpoint() : 'Available after saving.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('bot_username')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('handler_class')
                    ->toggleable()
                    ->wrap(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('last_webhook_set_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('last_error')
                    ->limit(40)
                    ->tooltip(fn (TelegramBot $record): ?string => $record->last_error)
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('setWebhook')
                    ->label('Set webhook')
                    ->icon(Heroicon::OutlinedCloudArrowUp)
                    ->requiresConfirmation()
                    ->action(fn (TelegramBot $record) => static::setWebhook($record)),
                Action::make('deleteWebhook')
                    ->label('Delete webhook')
                    ->icon(Heroicon::OutlinedTrash)
                    ->requiresConfirmation()
                    ->action(fn (TelegramBot $record) => static::deleteWebhook($record)),
                Action::make('generateHandler')
                    ->label('Generate handler')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->requiresConfirmation()
                    ->action(fn (TelegramBot $record) => static::generateHandler($record)),
                Action::make('regenerateSecret')
                    ->label('Regenerate secret')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (TelegramBot $record) => static::regenerateSecret($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTelegramBots::route('/'),
            'create' => CreateTelegramBot::route('/create'),
            'edit' => EditTelegramBot::route('/{record}/edit'),
        ];
    }

    private static function setWebhook(TelegramBot $record): void
    {
        try {
            $url = app(TelegramWebhookManager::class)->setWebhook($record);

            Notification::make()
                ->title('Webhook set')
                ->body($url)
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $record->forceFill(['last_error' => $exception->getMessage()])->save();

            Notification::make()
                ->title('Telegram webhook failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private static function deleteWebhook(TelegramBot $record): void
    {
        try {
            app(TelegramWebhookManager::class)->deleteWebhook($record);

            Notification::make()
                ->title('Webhook deleted')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $record->forceFill(['last_error' => $exception->getMessage()])->save();

            Notification::make()
                ->title('Telegram webhook delete failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private static function generateHandler(TelegramBot $record): void
    {
        try {
            $handlerClass = app(GenerateTelegramBotHandler::class)->execute($record->name);
            $record->forceFill(['handler_class' => $handlerClass])->save();

            Notification::make()
                ->title('Handler generated')
                ->body($handlerClass)
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Handler generation failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private static function regenerateSecret(TelegramBot $record): void
    {
        $record->forceFill([
            'webhook_secret' => Str::random(48),
            'webhook_url' => null,
            'last_webhook_set_at' => null,
        ])->save();

        Notification::make()
            ->title('Webhook secret regenerated')
            ->body('Set the webhook again so Telegram uses the new URL.')
            ->warning()
            ->send();
    }
}
