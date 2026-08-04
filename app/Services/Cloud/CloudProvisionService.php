<?php namespace App\Services\Cloud;

use App\Models\Cloud;
use App\Models\Module;
use App\Models\Store;
use App\Models\Zone;
use App\Services\Geo\ZoneResolver;
use App\Services\Wallet\CloudWalletService;
use Illuminate\Support\Facades\DB;

class CloudProvisionService
{
    public function __construct(
        private CloudWalletService $walletService,
        private ZoneResolver $zoneResolver
    ) {
    }

    public function provisionFor(Cloud $cloud): Cloud
    {
        return DB::transaction(function () use ($cloud) {
            $this->walletService->walletFor($cloud);

            if (!Store::where('cloud_id', $cloud->id)->exists()) {
                Store::create($this->defaultStoreAttributes($cloud));
            }

            return $cloud->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultStoreAttributes(Cloud $cloud): array
    {
        $moduleId = $this->resolveModuleId();

        return [
            'name' => $cloud->company_name,
            'phone' => $cloud->phone,
            'email' => $cloud->email,
            'logo' => $cloud->company_logo,
            'latitude' => $cloud->latitude,
            'longitude' => $cloud->longitude,
            'address' => $cloud->business_address,
            'cloud_id' => $cloud->id,
            'module_id' => $moduleId,
            'zone_id' => $this->resolveZoneId($cloud),
            'status' => 1,
        ];
    }

    private function resolveModuleId(): int
    {
        $moduleId = config('module.current_module_id');
        if (is_numeric($moduleId)) {
            return (int) $moduleId;
        }

        $module = Module::query()->active()->orderBy('id')->first();
        if (!$module) {
            throw new \DomainException('No active module found. Enable a module before approving clouds.');
        }

        return (int) $module->id;
    }

    private function resolveZoneId(Cloud $cloud): ?int
    {
        if ($cloud->latitude !== null && $cloud->longitude !== null) {
            $zone = $this->zoneResolver->resolve((float) $cloud->latitude, (float) $cloud->longitude);
            if ($zone) {
                return $zone->id;
            }
        }

        return Zone::query()->active()->orderBy('id')->value('id');
    }
}
