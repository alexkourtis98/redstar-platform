<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'title')
                            ->searchable(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'deposit' => 'Deposit',
                                'full' => 'Full Payment',
                                'package' => 'Package',
                                'subscription' => 'Subscription',
                            ])
                            ->default('full')
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01),
                        Forms\Components\Select::make('gateway')
                            ->options([
                                'stripe' => 'Stripe',
                                'viva' => 'Viva Wallet',
                            ])
                            ->label('Payment Gateway'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->isPaid()),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(2),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.title')
                    ->label('Project')
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'deposit',
                        'success' => 'full',
                        'warning' => 'package',
                        'info' => 'subscription',
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->badge()
                    ->colors([
                        'primary' => 'stripe',
                        'success' => 'viva',
                    ])
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('gateway')
                    ->options([
                        'stripe' => 'Stripe',
                        'viva' => 'Viva Wallet',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('copyLink')
                    ->label('Copy Link')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->visible(fn (Payment $record) => $record->payment_link_token && !$record->linkExpired())
                    ->action(function (Payment $record) {
                        $url = route('payment.checkout', $record->payment_link_token);
                        Notification::make()
                            ->title('Payment link copied')
                            ->body($url)
                            ->success()
                            ->send();
                    })
                    ->extraAttributes(fn (Payment $record) => [
                        'x-on:click' => "window.navigator.clipboard.writeText('" . route('payment.checkout', $record->payment_link_token) . "')",
                    ]),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'paid')->count();
    }
}
