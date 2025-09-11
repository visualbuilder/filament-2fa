<?php

namespace Visualbuilder\Filament2fa\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use Visualbuilder\Filament2fa\Enums\ScheduleStatus;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource\Pages;
use Visualbuilder\Filament2fa\Models\Banner;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return config('filament-2fa.banner.navigation.icon');
    }

    public static function canViewAny(): bool
    {
        $panel = Filament::getCurrentPanel();
        if (!$panel) {
            return false;
        }

        // Get panel guard mapping from config (Laravel automatically merges app config with package defaults)
        $panelId = $panel->getId();
        $panelGuardMap = config('filament-2fa.banner.panel_guard_map', []);
        $authGuard = $panelGuardMap[$panelId] ?? $panel->getAuthGuard();

        $bannerGuards = config('filament-2fa.banner.auth_guards', []);

        return isset($bannerGuards[$authGuard]['can_manage'])
            && $bannerGuards[$authGuard]['can_manage'] === true;
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-2fa.banner.navigation.label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()->columnSpanFull()->schema([
                    Tabs::make('Tabs')
                        ->tabs([
                            Tab::make('General')
                                ->icon('heroicon-m-wrench')
                                ->schema([
                                    TextInput::make('name')->required(),
                                    Select::make('auth_guards')
                                        ->required()
                                        ->multiple()
                                        ->hintAction(Action::make('help')
                                            ->icon('heroicon-o-question-mark-circle')
                                            ->extraAttributes(['class' => 'text-gray-500'])
                                            ->tooltip('This banner only visible on selected Auth Panels(guards)'))
                                        ->options(self::getAuthGuards()),
                                    RichEditor::make('content')
                                        ->required()
                                        ->toolbarButtons([
                                            'bold',
                                            'italic',
                                            'link',
                                            'strike',
                                            'underline',
                                            'undo',
                                            'codeBlock',
                                        ]),
                                    Select::make('render_location')
                                        ->searchable()
                                        ->required()
                                        ->hintAction(Action::make('help')
                                            ->icon('heroicon-o-question-mark-circle')
                                            ->extraAttributes(['class' => 'text-gray-500'])
                                            ->hiddenLabel()
                                            ->tooltip('With render location, you can select where a banner is rendered on the page. In combination with scopes, this becomes a powerful tool to manage where and when your banners are displayed. You can choose to render banners in the header, sidebar, or other strategic locations to maximize their visibility and impact.'))
                                        ->options(self::renderLocations()),

                                    Select::make('scope')
                                        ->hintAction(Action::make('help')
                                            ->icon('heroicon-o-question-mark-circle')
                                            ->hiddenLabel()
                                            ->extraAttributes(['class' => 'text-gray-500'])
                                            ->tooltip('With scoping, you can control where your banner is displayed. You can target your banner to specific pages or entire resources, ensuring it is shown to the right audience at the right time.'))
                                        ->searchable()
                                        ->multiple()
                                        ->options(fn() => self::getScopes()),
                                    Fieldset::make('Options')
                                        ->schema([
                                            Checkbox::make('is_2fa_setup')
                                                ->label('Show when 2fa is optional and not setup yet')
                                                ->columnSpan('full'),
                                            Checkbox::make('can_be_closed_by_user')
                                                ->label('User can dismiss banner')
                                                ->columnSpan('full'),
                                            Checkbox::make('can_truncate_message')
                                                ->label('Allow long messages to be truncated to fit on a small screen')
                                                ->columnSpan('full'),
                                        ]),
                                    Toggle::make('is_active'),
                                ]),
                            Tab::make('Styling')
                                ->icon('heroicon-m-paint-brush')
                                ->schema([
                                    ColorPicker::make('text_color')
                                        ->default('#FFFFFF')
                                        ->required(),
                                    Fieldset::make('Icon')
                                        ->schema([
                                            TextInput::make('icon')
                                                ->default('heroicon-m-megaphone')
                                                ->placeholder('heroicon-m-wrench'),
                                            ColorPicker::make('icon_color')
                                                ->default('#fafafa')
                                                ->required(),
                                        ])
                                        ->columns(3),
                                    Fieldset::make('background')
                                        ->schema([
                                            Select::make('background_type')
                                                ->reactive()
                                                ->selectablePlaceholder(false)
                                                ->default('solid')
                                                ->options([
                                                    'solid'    => 'Solid',
                                                    'gradient' => 'Gradient',
                                                ])->default('solid'),
                                            ColorPicker::make('start_color')
                                                ->default('#D97706')
                                                ->required(),
                                            ColorPicker::make('end_color')
                                                ->default('#F59E0C')
                                                ->visible(fn($get) => $get('background_type') === 'gradient'),
                                        ])
                                        ->columns(3),
                                ]),
                            Tab::make('Scheduling')
                                ->reactive()
                                ->icon('heroicon-m-clock')
                                ->badgeIcon('heroicon-m-eye')
                                ->badge(fn($get) => self::calculateScheduleStatus($get('start_time'), $get('end_time')))
                                ->schema([
                                    DateTimePicker::make('start_time')
                                        ->hintAction(
                                            Action::make('reset')
                                                ->icon('heroicon-m-arrow-uturn-left')
                                                ->action(function (Set $set) {
                                                    $set('start_time', null);
                                                })
                                        ),
                                    DateTimePicker::make('end_time')
                                        ->hintAction(
                                            Action::make('reset')
                                                ->icon('heroicon-m-arrow-uturn-left')
                                                ->action(function (Set $set) {
                                                    $set('end_time', null);
                                                })
                                        ),
                                ])->hidden(false),
                        ])->contained(false),
                ])
            ]);
    }

    private static function getAuthGuards()
    {
        $filteredGuards = Arr::where(config('filament-2fa.banner.auth_guards'), fn(array $value, string $key) => (bool) $value['can_see_banner'] === true);
        [$keys, $values] = Arr::divide($filteredGuards);
        return array_combine(array_values($keys), array_values($keys));
    }

    private static function renderLocations(): array
    {
        $constants = (new ReflectionClass(PanelsRenderHook::class))->getConstants();
        
        // Flip the array so that the actual hook values are the keys (what gets saved)
        // and the constant names are the labels (what the user sees)
        return array_flip($constants);
    }

    private static function getScopes(): array
    {
        /**
         * @var resource[] $resources
         */
        $resources = self::getPanelResources();
        $scopes = [];

        foreach ($resources as $resource) {
            $resourceSlug = $resource::getSlug();
            $resourcePath = str($resource)->value();
            $scopes[$resourceSlug] = [$resourcePath => Str::afterLast(str($resourcePath), '\\')];
            $scopes[$resourceSlug] = array_merge($scopes[$resourceSlug], self::getPagesForResource($resource));
        }

        return $scopes;
    }

    private static function getPanelResources(): array
    {
        return array_values(Filament::getCurrentPanel()->getResources());
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return config('filament-2fa.banner.navigation.url');
    }

    /**
     * @param  resource  $resourceClass
     *
     * @return string[]
     */
    private static function getPagesForResource($resourceClass): array
    {
        $pages = [];

        foreach ($resourceClass::getPages() as $page) {
            $pageClass = $page->getPage();
            $pageName = Str::afterLast($pageClass, '\\');
            $pages[$pageClass] = $pageName;
        }

        return $pages;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit'   => Pages\EditBanner::route('/{record}/edit'),
        ];
    }

    private static function calculateScheduleStatus($start_time, $end_time): ScheduleStatus|string
    {
        if (is_null($start_time) && is_null($end_time)) {
            return '';
        }

        if ($start_time && $end_time) {
            if (now()->between($start_time, $end_time)) {
                return ScheduleStatus::Visible->getLabel();
            }

            if (now()->isAfter($end_time)) {
                return ScheduleStatus::Fulfilled->getLabel();
            }

            if (now()->isBefore($start_time)) {
                return ScheduleStatus::Due->getLabel();
            }
        }

        if (is_null($start_time) && $end_time) {
            if (now()->isBefore($end_time)) {
                return ScheduleStatus::Visible->getLabel();
            }

            if (now()->isAfter($end_time)) {
                return ScheduleStatus::Fulfilled->getLabel();
            }
        }

        if (is_null($end_time) && $start_time) {
            if (now()->isBefore($start_time)) {
                return ScheduleStatus::Due->getLabel();
            }

            if (now()->isAfter($start_time)) {
                return ScheduleStatus::Visible->getLabel();
            }
        }

        return '';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('auth_guards')->searchable(),
                TextColumn::make('render_location')
                    ->formatStateUsing(fn(string $state) => self::renderLocation($state)),
                IconColumn::make('can_be_closed_by_user')->label('Dismissable')->alignCenter(),
                IconColumn::make('is_2fa_setup')->label('2FA Banner')->alignCenter(),
                IconColumn::make('is_active')->alignCenter()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('disableSelected')
                        ->color('warning')
                        ->icon('heroicon-m-x-circle')
                        ->requiresConfirmation()
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => false])),
                    BulkAction::make('enableSelected')
                        ->color('success')
                        ->icon('heroicon-m-check-badge')
                        ->requiresConfirmation()
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => true]))
                ]),
            ]);
    }

    private static function renderLocation(string $location): string
    {
        $constants = (new ReflectionClass(PanelsRenderHook::class))->getConstants();
        
        // If location is a hook value (panels::*), find the constant name
        if (in_array($location, $constants)) {
            return array_search($location, $constants);
        }
        
        // If location is a constant name, return it as-is (for backward compatibility)
        if (array_key_exists($location, $constants)) {
            return $location;
        }
        
        return $location;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
}
