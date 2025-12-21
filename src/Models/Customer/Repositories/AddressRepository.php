<?php

namespace App\Models\Customer\Repositories;

use App\Core\DB;
use PDO;

class AddressRepository
{
    /**
     * Get all addresses for a customer
     */
    public function getCustomerAddresses(int $customerId): array
    {
        $stmt = DB::pdo()->prepare("
        SELECT 
            ua.id,
            ua.user_id,
            ua.receiver_name as recipient_name,
            ua.receiver_phone as phone_number,
            ua.line1 as address_line,
            ua.province_code,
            ua.province_name as province,
            ua.district_id,
            ua.district_name as district,
            ua.commune_code,
            ua.ward_name as ward,
            ua.is_default,
            ua.created_at,
            ua.updated_at
        FROM user_addresses ua
        WHERE ua.user_id = :user_id 
        ORDER BY ua.is_default DESC, ua.created_at DESC
    ");
        $stmt->execute(['user_id' => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get default address for a customer
     */
    public function getDefaultAddress(int $customerId): ?array
    {
        $stmt = DB::pdo()->prepare("
            SELECT 
                ua.id,
                ua.user_id,
                ua.receiver_name as recipient_name,
                ua.receiver_phone as phone_number,
                ua.line1 as address_line,
                ua.province_code,
                ua.province_name as province,
                ua.district_id,
                ua.district_name as district,
                ua.commune_code,
                ua.ward_name as ward,
                ua.is_default,
                ua.created_at,
                ua.updated_at
            FROM user_addresses ua
            WHERE ua.user_id = :user_id AND ua.is_default = 1 
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $customerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get address by ID (verify ownership)
     */
    public function getAddressById(int $addressId, int $customerId): ?array
    {
        $stmt = DB::pdo()->prepare("
        SELECT 
            ua.id,
            ua.user_id,
            ua.receiver_name as recipient_name,
            ua.receiver_phone as phone_number,
            ua.line1 as address_line,
            ua.province_code,
            ua.province_name as province,
            ua.district_id,
            ua.district_name as district,
            ua.commune_code,
            ua.ward_name as ward,
            ua.is_default,
            ua.created_at,
            ua.updated_at
        FROM user_addresses ua
        WHERE ua.id = :id AND ua.user_id = :user_id
    ");
        $stmt->execute([
            'id' => $addressId,
            'user_id' => $customerId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Create new address
     */
    public function createAddress(int $customerId, array $data): int
    {
        // === GIỚI HẠN KHU VỰC GIAO HÀNG: CHỈ CẦN THƠ ===
        $this->validateCanThoAddress($data);

        // If this is the first address or set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            $this->unsetAllDefaults($customerId);
        }

        $stmt = DB::pdo()->prepare("
            INSERT INTO user_addresses (
                user_id, receiver_name, receiver_phone, 
                line1, province_code, province_name, district_id, district_name, commune_code, ward_name, is_default, created_by
            ) VALUES (
                :user_id, :receiver_name, :receiver_phone, 
                :line1, :province_code, :province_name, :district_id, :district_name, :commune_code, :ward_name, :is_default, :created_by
            )
        ");


        $stmt->execute([
            'user_id' => $customerId,
            'receiver_name' => $data['recipient_name'],
            'receiver_phone' => $data['phone_number'],
            'line1' => $data['address_line'], // Store raw address line only
            'province_code' => $data['province_code'] ?? null,
            'province_name' => $data['province_name'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'district_name' => $data['district_name'] ?? null,
            'commune_code' => $data['ward_code'] ?? null,
            'ward_name' => $data['ward_name'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'created_by' => $customerId
        ]);

        return (int) DB::pdo()->lastInsertId();
    }

    /**
     * Update address
     */
    public function updateAddress(int $addressId, int $customerId, array $data): bool
    {
        // === GIỚI HẠN KHU VỰC GIAO HÀNG: CHỈ CẦN THƠ ===
        $this->validateCanThoAddress($data);

        // If setting as default, unset other defaults
        if ($data['is_default'] ?? false) {
            $this->unsetAllDefaults($customerId);
        }

        $stmt = DB::pdo()->prepare("
            UPDATE user_addresses 
            SET receiver_name = :receiver_name,
                receiver_phone = :receiver_phone,
                line1 = :line1,
                province_code = :province_code,
                province_name = :province_name,
                district_id = :district_id,
                district_name = :district_name,
                commune_code = :commune_code,
                ward_name = :ward_name,
                is_default = :is_default,
                updated_by = :updated_by,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND user_id = :user_id
        ");


        return $stmt->execute([
            'id' => $addressId,
            'user_id' => $customerId,
            'receiver_name' => $data['recipient_name'],
            'receiver_phone' => $data['phone_number'],
            'line1' => $data['address_line'], // Store raw address line only
            'province_code' => $data['province_code'] ?? null,
            'province_name' => $data['province_name'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'district_name' => $data['district_name'] ?? null,
            'commune_code' => $data['ward_code'] ?? null,
            'ward_name' => $data['ward_name'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'updated_by' => $customerId
        ]);
    }

    /**
     * Delete address (cannot delete default if it's the only one)
     */
    public function deleteAddress(int $addressId, int $customerId): bool
    {
        $stmt = DB::pdo()->prepare("
            DELETE FROM user_addresses 
            WHERE id = :id AND user_id = :user_id
        ");

        return $stmt->execute([
            'id' => $addressId,
            'user_id' => $customerId
        ]);
    }

    /**
     * Set address as default
     */
    public function setAsDefault(int $addressId, int $customerId): bool
    {
        $this->unsetAllDefaults($customerId);

        $stmt = DB::pdo()->prepare("
            UPDATE user_addresses 
            SET is_default = 1, updated_by = :updated_by 
            WHERE id = :id AND user_id = :user_id
        ");

        return $stmt->execute([
            'id' => $addressId,
            'user_id' => $customerId,
            'updated_by' => $customerId
        ]);
    }

    /**
     * Unset all default addresses for a customer
     */
    private function unsetAllDefaults(int $customerId): void
    {
        $stmt = DB::pdo()->prepare("
            UPDATE user_addresses 
            SET is_default = 0 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $customerId]);
    }

    /**
     * Count customer addresses
     */
    public function countAddresses(int $customerId): int
    {
        $stmt = DB::pdo()->prepare("
            SELECT COUNT(*) FROM user_addresses 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $customerId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Validate address is within delivery area (distance-based)
     */
    private function validateCanThoAddress(array $data): void
    {
        $deliveryService = new \App\Services\DeliveryDistanceService();

        // Chuẩn bị dữ liệu địa chỉ để kiểm tra
        $addressData = [
            'address_line' => $data['address_line'] ?? $data['line1'] ?? '',
            'ward' => $data['ward_name'] ?? '',
            'district' => $data['district_name'] ?? '',
            'province' => $data['province_name'] ?? '',
            'province_code' => $data['province_code'] ?? '',
        ];

        $deliveryCheck = $deliveryService->checkDeliveryArea($addressData);

        if (!$deliveryCheck['success']) {
            throw new \Exception($deliveryCheck['message']);
        }
    }
}
