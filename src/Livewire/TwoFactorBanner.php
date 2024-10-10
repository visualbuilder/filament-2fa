<?php

namespace Optimacloud\Filament2fa\Livewire;

use Filament\Forms\Components\Actions\Action as ComponentAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Optimacloud\Filament2fa\Enums\ScheduleStatus;
use Optimacloud\Filament2fa\Models\TwoFactorBanner as ModelsTwoFactorBanner;

class TwoFactorBanner extends SimplePage implements HasForms
{
    use InteractsWithForms, InteractsWithFormActions;

    protected static string $view = 'filament-2fa::livewire.two-factor-banner';

    public ?array $data = [];

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::FiveExtraLarge;
    }

    public function mount()
    {
        $twoFactorBanner = ModelsTwoFactorBanner::first();
        $this->form->fill($twoFactorBanner ? $twoFactorBanner->toArray() : $this->defaultBanner());
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')->schema([
                Tabs::make('Tabs')
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-m-wrench')
                        ->schema([
                            TextInput::make('name')->required(),
                            Select::make('auth_guards')
                                ->required()
                                ->multiple()
                                ->hintAction(ComponentAction::make('help')
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
                                ])
                                ->columnSpanFull(),
                            Select::make('render_location')
                                ->searchable()
                                ->required()
                                ->hintAction(ComponentAction::make('help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->extraAttributes(['class' => 'text-gray-500'])                                    
                                    ->tooltip('With render location, you can select where a banner is rendered on the page. In combination with scopes, this becomes a powerful tool to manage where and when your banners are displayed. You can choose to render banners in the header, sidebar, or other strategic locations to maximize their visibility and impact.'))
                                ->options([
                                    'Panel' => [
                                        PanelsRenderHook::BODY_START => 'Header',
                                        PanelsRenderHook::PAGE_START => 'Page Start',
                                        PanelsRenderHook::PAGE_END => 'Page End',
                                    ],
                                    'Authentication' => [
                                        PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE => 'Before login Form',
                                        PanelsRenderHook::AUTH_LOGIN_FORM_AFTER => 'After login form',
                                        PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_BEFORE => 'Before reset password form',
                                        PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER => 'After reset password form',
                                        PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE => 'Before register form',
                                        PanelsRenderHook::AUTH_REGISTER_FORM_AFTER => 'After register form',
                                    ],
                                    'Global search' => [
                                        PanelsRenderHook::GLOBAL_SEARCH_BEFORE => 'Before global search',
                                        PanelsRenderHook::GLOBAL_SEARCH_AFTER => 'After global search',
                                    ],
                                    'Page Widgets' => [
                                        PanelsRenderHook::PAGE_HEADER_WIDGETS_BEFORE => 'Before header widgets',
                                        PanelsRenderHook::PAGE_HEADER_WIDGETS_AFTER => 'After header widgets',
                                        PanelsRenderHook::PAGE_FOOTER_WIDGETS_BEFORE => 'Before footer widgets',
                                        PanelsRenderHook::PAGE_FOOTER_WIDGETS_AFTER => 'After footer widgets',
                                    ],
                                    'Sidebar' => [
                                        PanelsRenderHook::SIDEBAR_NAV_START => 'Before sidebar navigation',
                                        PanelsRenderHook::SIDEBAR_NAV_END => 'After sidebar navigation',
                                    ],
                                    'Resource Table' => [
                                        PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE => 'Before resource table',
                                        PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER =>'After resource table',
                                    ],
                                ]),

                            Select::make('scope')
                                ->hintAction(ComponentAction::make('help')
                                    ->icon('heroicon-o-question-mark-circle')                                    
                                    ->extraAttributes(['class' => 'text-gray-500'])
                                    ->tooltip('With scoping, you can control where your banner is displayed. You can target your banner to specific pages or entire resources, ensuring it is shown to the right audience at the right time.'))
                                ->searchable()
                                ->multiple()
                                ->options(fn () => self::getScopes()),
                            Fieldset::make('Options')
                                ->schema([
                                    Checkbox::make('can_be_closed_by_user')                                        
                                        ->columnSpan('full'),
                                    Checkbox::make('can_truncate_message')                                
                                        ->columnSpan('full'),
                                ]),
                            Toggle::make('is_active'),
                        ])->columns(2),
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
                                            'solid' => 'Solid',
                                            'gradient' => 'Gradient',
                                        ])->default('solid'),
                                    ColorPicker::make('start_color')                                        
                                        ->default('#D97706')
                                        ->required(),
                                    ColorPicker::make('end_color')                                        
                                        ->default('#F59E0C')
                                        ->visible(fn ($get) => $get('background_type') === 'gradient'),
                                ])
                                ->columns(3),
                        ]),
                    Tab::make('Scheduling')
                        ->reactive()
                        ->icon('heroicon-m-clock')
                        ->badgeIcon('heroicon-m-eye')
                        ->badge(fn ($get) => self::calculateScheduleStatus($get('start_time'), $get('end_time')))
                        ->schema([
                            DateTimePicker::make('start_time')
                                ->native(false)
                                ->hintAction(
                                    ComponentAction::make('reset')                                        
                                        ->icon('heroicon-m-arrow-uturn-left')
                                        ->action(function (Set $set) {
                                            $set('start_time', null);
                                        })
                                ),
                            DateTimePicker::make('end_time')
                                ->native(false)
                                ->hintAction(
                                    ComponentAction::make('reset')                                        
                                        ->icon('heroicon-m-arrow-uturn-left')
                                        ->action(function (Set $set) {
                                            $set('end_time', null);
                                        })
                                ),
                        ])->columns(2),
                ])->contained(false)
            ])
        ])->statePath('data');
    }

    public function submit(): void
    {        
        $formData = $this->form->getState();
        $twoFactorBanner = ModelsTwoFactorBanner::first();
        if(!$twoFactorBanner) {
            $twoFactorBanner = new ModelsTwoFactorBanner();
        }
        $twoFactorBanner = $twoFactorBanner->fill($formData);
        $twoFactorBanner->save();

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }

    public static function calculateScheduleStatus($start_time, $end_time): ScheduleStatus | string
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

    /**
     * @param  resource  $resourceClass
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

    private static function getPanelResources(): array
    {
        return array_values(Filament::getCurrentPanel()->getResources());
    }

    private static function getAuthGuards()
    {
        $filteredGuards = Arr::where(config('filament-2fa.auth_guards'), fn (array $value, string $key) => (bool)$value['enabled'] === true);
        [$keys, $values] = Arr::divide($filteredGuards);
        return array_combine(array_values($keys),array_values($keys));
    }

    private function defaultBanner()
    {
        return [
            'name' => 'Two Factor Authentication - Alert',
            'content' => '',
            'is_active' => true,
            'active_since' => null,
            'icon' => 'heroicon-m-megaphone',
            'background_type' => 'gradient',
            'start_color' => '#FF6B6B',
            'end_color' => '#FFD97D',
            'start_time' => null,
            'end_time' => null,
            'can_be_closed_by_user' => true,
            'text_color' => '#333333',
            'icon_color' => '#FFFFFF',
            'render_location' => PanelsRenderHook::BODY_START,
            'scope' => [],
            'auth_guards' => []
        ];
    }

}