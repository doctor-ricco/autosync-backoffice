<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Gestão de Veículos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Referência')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => Vehicle::generateReference()),
                        
                        Forms\Components\Select::make('stand_id')
                            ->label('Stand')
                            ->relationship('stand', 'name')
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('brand')
                            ->label('Marca')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('model')
                            ->label('Modelo')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('year')
                            ->label('Ano')
                            ->required()
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y') + 1),
                    ])->columns(2),

                Forms\Components\Section::make('Especificações Técnicas')
                    ->schema([
                        Forms\Components\TextInput::make('mileage')
                            ->label('Quilometragem')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('km'),
                        
                        Forms\Components\Select::make('fuel_type')
                            ->label('Combustível')
                            ->options([
                                'gasoline' => 'Gasolina',
                                'diesel' => 'Diesel',
                                'hybrid' => 'Híbrido',
                                'electric' => 'Elétrico',
                                'lpg' => 'GPL',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('transmission')
                            ->label('Transmissão')
                            ->options([
                                'manual' => 'Manual',
                                'automatic' => 'Automático',
                                'semi_automatic' => 'Semi-Automático',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('engine_size')
                            ->label('Cilindrada')
                            ->numeric()
                            ->step(0.1)
                            ->suffix('L'),
                        
                        Forms\Components\TextInput::make('power_hp')
                            ->label('Potência')
                            ->numeric()
                            ->suffix('cv'),
                        
                        Forms\Components\TextInput::make('doors')
                            ->label('Portas')
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(5),
                        
                        Forms\Components\TextInput::make('seats')
                            ->label('Lugares')
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(9),
                        
                        Forms\Components\TextInput::make('color')
                            ->label('Cor')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Preços')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Preço Atual')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€'),
                        
                        Forms\Components\TextInput::make('original_price')
                            ->label('Preço Original')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€'),
                        
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Desconto (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                    ])->columns(3),

                Forms\Components\Section::make('Status e Configurações')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'available' => 'Disponível',
                                'sold' => 'Vendido',
                                'reserved' => 'Reservado',
                                'maintenance' => 'Em Manutenção',
                            ])
                            ->required()
                            ->default('available'),
                        
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Destaque')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('is_new')
                            ->label('Veículo Novo')
                            ->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('Descrição e Características')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(4)
                            ->maxLength(1000),
                        
                        Forms\Components\TagsInput::make('features')
                            ->label('Características')
                            ->separator(','),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Ref.')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('year')
                    ->label('Ano')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('mileage')
                    ->label('Km')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', '.') . ' km'),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Preço')
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'available',
                        'danger' => 'sold',
                        'warning' => 'reserved',
                        'info' => 'maintenance',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'available' => 'Disponível',
                        'sold' => 'Vendido',
                        'reserved' => 'Reservado',
                        'maintenance' => 'Manutenção',
                        default => $state,
                    }),
                
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destaque')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_new')
                    ->label('Novo')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Visualizações')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Disponível',
                        'sold' => 'Vendido',
                        'reserved' => 'Reservado',
                        'maintenance' => 'Em Manutenção',
                    ]),
                
                Tables\Filters\SelectFilter::make('fuel_type')
                    ->label('Combustível')
                    ->options([
                        'gasoline' => 'Gasolina',
                        'diesel' => 'Diesel',
                        'hybrid' => 'Híbrido',
                        'electric' => 'Elétrico',
                        'lpg' => 'GPL',
                    ]),
                
                Tables\Filters\SelectFilter::make('transmission')
                    ->label('Transmissão')
                    ->options([
                        'manual' => 'Manual',
                        'automatic' => 'Automático',
                        'semi_automatic' => 'Semi-Automático',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Destaque'),
                
                Tables\Filters\TernaryFilter::make('is_new')
                    ->label('Veículo Novo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
            'view' => Pages\ViewVehicle::route('/{record}'),
        ];
    }
} 