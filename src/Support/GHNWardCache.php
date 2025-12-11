<?php

namespace App\Support;

/**
 * GHN Ward-District Mapping Cache
 * Cache the mapping between ward codes and districts to avoid multiple API calls
 */
class GHNWardCache
{
    private static $cacheFile = __DIR__ . '/../../storage/cache/ghn_ward_district_map.json';
    private static $cacheExpiry = 86400; // 24 hours

    /**
     * Get district info by ward code
     * 
     * @param string $wardCode
     * @param string|null $provinceCode
     * @return array|null ['district_id' => int, 'district_name' => string]
     */
    public static function getDistrictByWardCode(string $wardCode, ?string $provinceCode = null): ?array
    {
        $cache = self::loadCache();

        // Check if we have cached data for this ward
        if (isset($cache[$wardCode])) {
            return $cache[$wardCode];
        }

        // If not cached and province code provided, try to fetch from GHN
        if ($provinceCode) {
            try {
                $districtInfo = self::fetchDistrictFromGHN($wardCode, $provinceCode);
                if ($districtInfo) {
                    // Cache it for future use
                    self::cacheWardDistrict($wardCode, $districtInfo);
                    return $districtInfo;
                }
            } catch (\Exception $e) {
                error_log("GHNWardCache: Failed to fetch from GHN: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Fetch district info from GHN API
     */
    private static function fetchDistrictFromGHN(string $wardCode, string $provinceCode): ?array
    {
        $ghn = new GHNService();

        try {
            // Get provinces to find province ID
            $provinces = $ghn->getProvinces();
            $provinceId = null;

            foreach ($provinces as $province) {
                if (
                    $province['ProvinceID'] == $provinceCode ||
                    $province['ProvinceCode'] == $provinceCode
                ) {
                    $provinceId = $province['ProvinceID'];
                    break;
                }
            }

            if (!$provinceId) {
                return null;
            }

            // Get districts for this province
            $districts = $ghn->getDistricts($provinceId);

            // Search through each district's wards
            foreach ($districts as $district) {
                $wards = $ghn->getWards($district['DistrictID']);

                foreach ($wards as $ward) {
                    if ($ward['WardCode'] == $wardCode) {
                        return [
                            'district_id' => $district['DistrictID'],
                            'district_name' => $district['DistrictName']
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return null;
    }

    /**
     * Cache ward-district mapping
     */
    private static function cacheWardDistrict(string $wardCode, array $districtInfo): void
    {
        $cache = self::loadCache();
        $cache[$wardCode] = $districtInfo;
        self::saveCache($cache);
    }

    /**
     * Load cache from file
     */
    private static function loadCache(): array
    {
        if (!file_exists(self::$cacheFile)) {
            return [];
        }

        $content = file_get_contents(self::$cacheFile);
        $data = json_decode($content, true);

        if (!$data || !isset($data['timestamp'])) {
            return [];
        }

        // Check if cache is expired
        if (time() - $data['timestamp'] > self::$cacheExpiry) {
            return [];
        }

        return $data['mappings'] ?? [];
    }

    /**
     * Save cache to file
     */
    private static function saveCache(array $mappings): void
    {
        $dir = dirname(self::$cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'timestamp' => time(),
            'mappings' => $mappings
        ];

        file_put_contents(self::$cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
    }
}
