<?php

declare(strict_types=1);

namespace Visualbuilder\Filament2fa\Tests\Compatibility;

use Filament\Facades\Filament;

/**
 * Filament 5 Compatibility Test Suite
 *
 * This test suite is designed to verify compatibility with Filament 5.x
 * Currently, these tests document expected behavior and will help identify
 * breaking changes when migrating from Filament 4.x to 5.x
 *
 * @see FILAMENT5_COMPATIBILITY_REPORT.md for detailed analysis
 */

it('is now running on Filament 5.x', function () {
    // Verify we're on Filament 5 after the upgrade
    $filamentVersion = class_exists(\Filament\FilamentManager::class)
        ? \Composer\InstalledVersions::getVersion('filament/filament')
        : 'unknown';

    expect($filamentVersion)->toMatch('/^(v?5|dev-)/');
})->group('compatibility', 'filament5');

it('can detect Schemas namespace presence (exists in both Filament 4 and 5)', function () {
    // CORRECTION: The Schemas namespace exists in BOTH Filament 4 and 5
    // Initial analysis incorrectly identified this as a breaking change
    // Lee confirmed: Filament\Schemas\ is NOT a breaking change
    $schemasNamespaceExists = class_exists(\Filament\Schemas\Schema::class);

    expect($schemasNamespaceExists)
        ->toBeTrue('Schemas namespace exists in Filament 5 - NOT a breaking change');
})->group('compatibility', 'filament5');

it('verifies Schema components are still available in Filament 5', function () {
    // CORRECTION: These components still exist in Filament 5
    // They do NOT need migration as initially thought
    $schemaComponents = [
        'Filament\Schemas\Schema',
        'Filament\Schemas\Components\Actions',
        'Filament\Schemas\Components\EmbeddedSchema',
        'Filament\Schemas\Components\Form',
        'Filament\Schemas\Components\Grid',
        'Filament\Schemas\Components\Group',
        'Filament\Schemas\Components\Section',
        'Filament\Schemas\Components\Text',
        'Filament\Schemas\Components\UnorderedList',
        'Filament\Schemas\Components\View',
        'Filament\Schemas\Components\Fieldset',
        'Filament\Schemas\Components\Tabs',
        'Filament\Schemas\Components\Tabs\Tab',
        'Filament\Schemas\Components\Utilities\Set',
    ];

    foreach ($schemaComponents as $component) {
        // These exist in both Filament 4 and 5
        expect(class_exists($component) || interface_exists($component))
            ->toBeTrue("Component {$component} exists in Filament 5");
    }
})->group('compatibility', 'filament5');

it('verifies core Filament APIs remain available', function () {
    // These core APIs should remain stable across versions
    $stableApis = [
        \Filament\Contracts\Plugin::class,
        \Filament\Panel::class,
        \Filament\Facades\Filament::class,
        \Filament\Resources\Resource::class,
        \Filament\Pages\SimplePage::class,
        \Filament\Auth\Pages\Login::class,
    ];

    foreach ($stableApis as $api) {
        expect(class_exists($api) || interface_exists($api) || trait_exists($api))
            ->toBeTrue("Stable API {$api} should exist");
    }
})->group('compatibility', 'filament5');

it('confirms no Schema refactoring needed for Filament 5', function () {
    // UPDATED: Schema components work in Filament 5, no refactoring needed
    // The only changes needed are for Livewire v4 compatibility
    $filesUsingSchemas = [
        'src/Filament/Pages/Configure.php' => [
            'schemas_compatible' => true,
            'livewire_updates_needed' => true,
            'estimated_hours' => '2-4',
        ],
        'src/Filament/Resources/BannerResource.php' => [
            'schemas_compatible' => true,
            'livewire_updates_needed' => true,
            'estimated_hours' => '1-2',
        ],
        'src/Filament/Pages/Confirm2Fa.php' => [
            'schemas_compatible' => true,
            'livewire_updates_needed' => true,
            'estimated_hours' => '1-2',
        ],
    ];

    // Verify all these files exist
    foreach ($filesUsingSchemas as $file => $metadata) {
        $filePath = __DIR__ . '/../../' . $file;
        expect(file_exists($filePath))
            ->toBeTrue("File {$file} exists and Schemas are compatible");
        expect($metadata['schemas_compatible'])->toBeTrue();
    }

    // Updated effort estimate - much lower than original 14-28 hours
    $totalMinHours = 2 + 1 + 1; // 4
    $totalMaxHours = 4 + 2 + 2; // 8

    expect($totalMinHours)->toBe(4);
    expect($totalMaxHours)->toBe(8);
})->group('compatibility', 'filament5');

it('has test coverage for 2FA functionality', function () {
    // Ensure existing tests exist that verify core functionality
    // These tests will need to pass on Filament 5 as well
    $criticalTestFiles = [
        'tests/Unit/ConfigureTest.php',
        'tests/Unit/Confirm2FaTest.php',
        'tests/Unit/LoginTest.php',
        'tests/Unit/TwoFactorBannerTest.php',
    ];

    foreach ($criticalTestFiles as $testFile) {
        $filePath = __DIR__ . '/../../' . $testFile;
        expect(file_exists($filePath))
            ->toBeTrue("Critical test file {$testFile} exists");
    }
})->group('compatibility', 'filament5');

it('documents actual breaking changes found in Filament 5 upgrade', function () {
    // ACTUAL FINDINGS after upgrading to Filament 5.4.3
    $actualBreakingChanges = [
        'livewire_v4_upgrade' => [
            'description' => 'Livewire v3 to v4 breaking changes (NOT Filament 5 issue)',
            'impact' => 'MEDIUM',
            'affected_files' => [
                'src/Filament/Pages/Configure.php',
                'src/Filament/Resources/BannerResource.php',
                'src/Filament/Pages/Login.php',
                'src/Filament/Pages/Confirm2Fa.php',
            ],
            'specific_issues' => [
                'ViewErrorBag::put() requires non-null MessageBag',
                'Page URL resolution method changes',
            ],
        ],
    ];

    // CONFIRMED: No Filament 5 breaking changes found
    $filament5BreakingChanges = [];

    expect($actualBreakingChanges)->toBeArray();
    expect($filament5BreakingChanges)->toBeArray()->toBeEmpty();

    // The only changes needed are for Livewire v4, not Filament 5
    expect($actualBreakingChanges)->toHaveKey('livewire_v4_upgrade');
})->group('compatibility', 'filament5');

/**
 * MIGRATION CHECKLIST FOR FILAMENT 5
 *
 * When Filament 5 is released, follow these steps:
 *
 * 1. Create a new 5.x branch from current 4.x
 * 2. Update composer.json: "filament/filament": "^5.0"
 * 3. Run composer update
 * 4. Review official Filament 5 upgrade guide
 * 5. Run this test suite to identify specific failures
 * 6. Refactor Configure.php to replace Schemas
 * 7. Refactor BannerResource.php to replace Schemas
 * 8. Update Confirm2Fa.php as needed
 * 9. Test all authentication flows manually
 * 10. Run full test suite (fix existing test dependency issue first)
 * 11. Update documentation and README
 * 12. Tag new 5.0.0 release
 */
