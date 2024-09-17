<?php

namespace Optimacloud\Filament2fa\BannerManager\Contracts;

use Kenepa\Banner\Banner;
use Optimacloud\Filament2fa\BannerManager\BannerData;

interface BannerStorage
{
    public function store(BannerData $data);

    public function update(BannerData $data);

    public function delete(string $bannerId);

    public function get(string $bannerId);

    /**
     * @return Banner[]
     */
    public function getAll(): array;

    /**
     * @return ValueObjects\BannerData[]
     */
    public function getActiveBanners(): array;

    public function getActiveBannerCount(): int;

    public function disableAllBanners(): void;

    public function enableAllBanners(): void;
}
