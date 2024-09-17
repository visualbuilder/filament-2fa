<?php

namespace Optimacloud\Filament2fa\Livewire;

use BladeUI\Icons\IconsManifest;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as ComponentAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Kenepa\Banner\Banner;
use Kenepa\Banner\BannerPlugin;
use Kenepa\Banner\Enums\ScheduleStatus;
// use Kenepa\Banner\Facades\BannerManager;
use Kenepa\Banner\Livewire\BannerManagerPage as BaseBannerManagerPage;
use Optimacloud\Filament2fa\BannerManager\Facade\BannerManagerFacade;
use Optimacloud\Filament2fa\Livewire\BannerManager\BannerData;

class BannerManagerPage extends BaseBannerManagerPage
{    

    public function getAuthGuards()
    {
        $filteredGuards = Arr::where(config('filament-2fa.auth_guards'), fn (array $value, string $key) => (bool)$value['enabled'] === true);
        [$keys, $values] = Arr::divide($filteredGuards);
        return $keys;
    }

    public function getSchema(): array
    {
        return [
            Tabs::make('Tabs')
                ->tabs([
                    Tab::make(__('banner::form.tabs.general'))
                        ->icon('heroicon-m-wrench')
                        ->schema([
                            Hidden::make('id')->default(fn () => uniqid()),
                            TextInput::make('name')->required()->label(__('banner::form.fields.name')),
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
                                ->label(__('banner::form.fields.content')),
                            Select::make('auth_guards')
                                ->searchable()
                                ->required()
                                ->multiple()
                                ->hintAction(ComponentAction::make('help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->extraAttributes(['class' => 'text-gray-500'])
                                    ->label('')
                                    ->tooltip('This banner only visible on selected Auth Panels(guards)'))
                                ->label('Auth Guards')
                                ->options($this->getAuthGuards()),
                            Select::make('render_location')
                                ->searchable()
                                ->required()
                                ->hintAction(ComponentAction::make('help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->extraAttributes(['class' => 'text-gray-500'])
                                    ->label('')
                                    ->tooltip(__('banner::form.fields.render_location_help')))
                                ->label(__('banner::form.fields.render_location'))
                                ->options([
                                    'Panel' => [
                                        PanelsRenderHook::BODY_START => __('banner::form.fields.render_location_options.panel.header'),
                                        PanelsRenderHook::PAGE_START => __('banner::form.fields.render_location_options.panel.page_start'),
                                        PanelsRenderHook::PAGE_END => __('banner::form.fields.render_location_options.panel.page_end'),
                                    ],
                                    'Authentication' => [
                                        PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE => __('banner::form.fields.render_location_options.authentication.login_form_before'),
                                        PanelsRenderHook::AUTH_LOGIN_FORM_AFTER => __('banner::form.fields.render_location_options.authentication.login_form_after'),
                                        PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_BEFORE => __('banner::form.fields.render_location_options.authentication.password_reset_form_before'),
                                        PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER => __('banner::form.fields.render_location_options.authentication.password_reset_form_after'),
                                        PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE => __('banner::form.fields.render_location_options.authentication.register_form_before'),
                                        PanelsRenderHook::AUTH_REGISTER_FORM_AFTER => __('banner::form.fields.render_location_options.authentication.register_form_after'),
                                    ],
                                    'Global search' => [
                                        PanelsRenderHook::GLOBAL_SEARCH_BEFORE => __('banner::form.fields.render_location_options.global_search.before'),
                                        PanelsRenderHook::GLOBAL_SEARCH_AFTER => __('banner::form.fields.render_location_options.global_search.after'),
                                    ],
                                    'Page Widgets' => [
                                        PanelsRenderHook::PAGE_HEADER_WIDGETS_BEFORE => __('banner::form.fields.render_location_options.page_widgets.header_before'),
                                        PanelsRenderHook::PAGE_HEADER_WIDGETS_AFTER => __('banner::form.fields.render_location_options.page_widgets.header_after'),
                                        PanelsRenderHook::PAGE_FOOTER_WIDGETS_BEFORE => __('banner::form.fields.render_location_options.page_widgets.footer_before'),
                                        PanelsRenderHook::PAGE_FOOTER_WIDGETS_AFTER => __('banner::form.fields.render_location_options.page_widgets.footer_after'),
                                    ],
                                    'Sidebar' => [
                                        PanelsRenderHook::SIDEBAR_NAV_START => __('banner::form.fields.render_location_options.sidebar.nav_start'),
                                        PanelsRenderHook::SIDEBAR_NAV_END => __('banner::form.fields.render_location_options.sidebar.nav_end'),
                                    ],
                                    'Resource Table' => [
                                        PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE => __('banner::form.fields.render_location_options.resource_table.before'),
                                        PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER => __('banner::form.fields.render_location_options.resource_table.after'),
                                    ],
                                ]),

                            Select::make('scope')
                                ->hintAction(ComponentAction::make('help')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->label('')
                                    ->extraAttributes(['class' => 'text-gray-500'])
                                    ->tooltip(__('banner::form.fields.scope_help')))
                                ->searchable()
                                ->multiple()
                                ->options(fn () => $this->getScopes())
                                ->label(__('banner::form.fields.scope')),
                            Fieldset::make(__('banner::form.fields.options'))
                                ->schema([
                                    Checkbox::make('can_be_closed_by_user')
                                        ->label(__('banner::form.fields.can_be_closed_by_user'))
                                        ->columnSpan('full'),
                                    Checkbox::make('can_truncate_message')
                                        ->label(__('banner::form.fields.can_truncate_message'))
                                        ->columnSpan('full'),
                                ]),
                            Toggle::make('is_active')
                                ->label(__('banner::form.fields.is_active')),
                        ]),
                    Tab::make(__('banner::form.tabs.styling'))
                        ->icon('heroicon-m-paint-brush')
                        ->schema([
                            ColorPicker::make('text_color')
                                ->label(__('banner::form.fields.text_color'))
                                ->default('#FFFFFF')
                                ->required(),
                            Fieldset::make(__('banner::form.fields.icon'))
                                ->schema([
                                    TextInput::make('icon')
                                        ->label(__('banner::form.fields.icon'))
                                        ->default('heroicon-m-megaphone')
                                        ->placeholder('heroicon-m-wrench'),
                                    ColorPicker::make('icon_color')
                                        ->label(__('banner::form.fields.icon_color'))
                                        ->default('#fafafa')
                                        ->required(),
                                ])
                                ->columns(3),
                            Fieldset::make('background')
                                ->label(__('banner::form.fields.background'))
                                ->schema([
                                    Select::make('background_type')
                                        ->label(__('banner::form.fields.background_type'))
                                        ->reactive()
                                        ->selectablePlaceholder(false)
                                        ->default('solid')
                                        ->options([
                                            'solid' => __('banner::form.fields.background_type_solid'),
                                            'gradient' => __('banner::form.fields.background_type_gradient'),
                                        ])->default('solid'),
                                    ColorPicker::make('start_color')
                                        ->label(__('banner::form.fields.start_color'))
                                        ->default('#D97706')
                                        ->required(),
                                    ColorPicker::make('end_color')
                                        ->label(__('banner::form.fields.end_color'))
                                        ->default('#F59E0C')
                                        ->visible(fn ($get) => $get('background_type') === 'gradient'),
                                ])
                                ->columns(3),
                        ]),
                    Tab::make(__('banner::form.tabs.scheduling'))
                        ->reactive()
                        ->icon('heroicon-m-clock')
                        ->badgeIcon('heroicon-m-eye')
                        ->badge(fn ($get) => $this->calculateScheduleStatus($get('start_time'), $get('end_time')))
                        ->schema([
                            DateTimePicker::make('start_time')
                                ->hintAction(
                                    ComponentAction::make('reset')
                                        ->label(__('banner::form.actions.reset'))
                                        ->icon('heroicon-m-arrow-uturn-left')
                                        ->action(function (Set $set) {
                                            $set('start_time', null);
                                        })
                                )
                                ->label(__('banner::form.fields.start_time')),
                            DateTimePicker::make('end_time')
                                ->hintAction(
                                    ComponentAction::make('reset')
                                        ->label(__('banner::form.actions.reset'))
                                        ->icon('heroicon-m-arrow-uturn-left')
                                        ->action(function (Set $set) {
                                            $set('end_time', null);
                                        })
                                )
                                ->label(__('banner::form.fields.end_time')),
                        ]),
                ])->contained(false),
        ];
    }

    public function createBanner($data)
    {
        BannerManagerFacade::store(BannerData::fromArray($data));

        Notification::make()
            ->title(__('banner::manager.successfully_created_banner'))
            ->success()
            ->send();

        $this->getBanners();
    }

    public function updateBanner()
    {
        $updatedBannerData = $this->form->getState();

        FacadeBannerManagerFacade::update(BannerData::fromArray($updatedBannerData));

        $this->getBanners();

        Notification::make()
            ->title(__('banner::manager.successfully_updated_banner'))
            ->success()
            ->send();
    }

    private function getIcons(): array
    {
        // TODO: Add alternative option to use a free input form instead of select
        //TODO: able to configure the sets
        $heroicons = app(IconsManifest::class)->getManifest(['heroicons'])['heroicons'];

        return array_values($heroicons)[0];
    }

    private function getScopes(): array
    {
        /**
         * @var resource[] $resources
         */
        $resources = $this->getPanelResources();
        $scopes = [];

        foreach ($resources as $resource) {
            $resourceSlug = $resource::getSlug();
            $resourcePath = str($resource)->value();
            $scopes[$resourceSlug] = [$resourcePath => Str::afterLast(str($resourcePath), '\\')];
            $scopes[$resourceSlug] = array_merge($scopes[$resourceSlug], $this->getPagesForResource($resource));
        }

        return $scopes;
    }

    /**
     * @param  resource  $resourceClass
     * @return string[]
     */
    private function getPagesForResource($resourceClass): array
    {
        $pages = [];

        foreach ($resourceClass::getPages() as $page) {
            $pageClass = $page->getPage();
            $pageName = Str::afterLast($pageClass, '\\');
            $pages[$pageClass] = $pageName;
        }

        return $pages;
    }

    private function getPanelResources(): array
    {
        return array_values(Filament::getCurrentPanel()->getResources());
    }

    private function calculateScheduleStatus($start_time, $end_time): ScheduleStatus | string
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
}
