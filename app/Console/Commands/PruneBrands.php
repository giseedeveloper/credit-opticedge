<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\PhoneModel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:prune-brands {--apply : Persist changes (otherwise dry-run)} {--keep=* : Brand slugs to keep active (repeatable)} {--also-disable-models : Disable phone models for disabled brands}')]
#[Description('Disable fake/unknown brands (and optionally their models)')]
class PruneBrands extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $keep = collect((array) $this->option('keep'))
            ->map(fn ($slug) => trim(Str::lower((string) $slug)))
            ->filter()
            ->unique()
            ->values();

        if ($keep->isEmpty()) {
            $keep = collect([
                'samsung',
                'nokia',
                'tecno',
                'infinix',
                'itel',
                'oppo',
                'vivo',
                'xiaomi',
                'realme',
                'apple',
                'huawei',
            ]);
        }

        $apply = (bool) $this->option('apply');
        $disableModels = (bool) $this->option('also-disable-models');

        $targets = Brand::query()
            ->whereNotIn('slug', $keep->all())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active']);

        if ($targets->isEmpty()) {
            $this->info('No active brands to disable.');

            return self::SUCCESS;
        }

        $this->line('Brands that will be disabled:');
        foreach ($targets as $brand) {
            $this->line("- {$brand->name} ({$brand->slug})");
        }

        if (! $apply) {
            $this->warn('Dry-run only. Re-run with --apply to persist.');

            return self::SUCCESS;
        }

        $brandIds = $targets->pluck('id')->all();

        Brand::query()
            ->whereIn('id', $brandIds)
            ->update(['is_active' => false]);

        if ($disableModels) {
            PhoneModel::query()
                ->whereIn('brand_id', $brandIds)
                ->update(['is_active' => false]);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
