<?php

namespace App\Filament\Resources;

use App\Exports\StudentsExport;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;


class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            TextInput::make('name')
                ->required()
                ->autofocus()
                ->unique(),
            TextInput::make('email')
                ->required()
                ->unique(),
            TextInput::make('phone_number')
                ->required()
                ->tel()
                ->unique(),
            TextInput::make('address')
                ->required(),

            Select::make('class_id')
                ->relationship('class', 'name')
                ->reactive(),

            Select::make('section_id')
                ->label('Select Section')
                ->options(function (callable $get) {
                    $classId = $get('class_id');

                    if ($classId) {
                        return Section::where('class_id', $classId)->pluck('name', 'id')->toArray();
                    }
                })

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
               ->sortable()
                ->searchable(),
                TextColumn::make('email')
                 ->sortable()
                  ->searchable()
                  ->toggleable(),
               TextColumn::make('phone_number')
                  ->sortable()
                  ->searchable()
                  ->toggleable(),
                 TextColumn::make('address')
                  ->sortable()
                  ->searchable()
                  ->toggleable()
                  ->wrap(),

                  TextColumn::make('class.name')  //class - relationship, name-column name
                  ->sortable()
                  ->searchable(),

                  TextColumn::make('section.name')  //class - relationship, name-column name
                  ->sortable()
                  ->searchable(),

            
            ])
            ->filters([
                Filter::make('class-section-filter')
                ->form([
                    Select::make('class_id')
                        ->label('Filter By Class')
                        ->placeholder('Select a Class')
                        ->options(
                            Classes::pluck('name', 'id')->toArray()
                        )
                        ->afterStateUpdated(
                            fn (callable $set) => $set('section_id', null)
                        ),
                    Select::make('section_id')
                        ->label('Filter By Section')
                        ->placeholder('Select a Section')
                        ->options(
                            function (callable $get) {
                                $classId = $get('class_id');

                                if ($classId) {
                                    return Section::where('class_id', $classId)->pluck('name', 'id')->toArray();
                                }
                            }
                        ),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['class_id'],
                            fn (Builder $query, $record): Builder => $query->where('class_id', $record),
                        )
                        ->when(
                            $data['section_id'],
                            fn (Builder $query, $record): Builder => $query->where('section_id', $record),
                        );
                })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                BulkAction::make('export')
                ->label('Export Selected')
                ->icon('heroicon-o-document-download')
                ->action(fn (Collection $records) => (new StudentsExport($records))->download('students.xlsx'))
            ]);
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }    

    protected static function getNavigationBadge(): ?string
    {
        return self::$model::count();
    }
}
