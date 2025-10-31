# HỆ THỐNG TÍCH ĐIỂM THÀNH VIÊN (LOYALTY POINTS SYSTEM)

## 📋 Tổng quan

Hệ thống tích điểm cho phép khách hàng tích lũy điểm khi mua hàng và sử dụng điểm để đổi lấy giảm giá.

### Quy tắc tích điểm:
- **1,000đ = 1 điểm**
- **1,000 điểm = 1,000đ giảm giá**

---

## 🗃️ Cấu trúc Database

### 1. Bảng `users` - Thêm cột `loyalty_points`
```sql
ALTER TABLE users 
ADD COLUMN loyalty_points INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Điểm tích lũy hiện tại';
```

### 2. Bảng `loyalty_transactions` - Lịch sử giao dịch điểm
```sql
CREATE TABLE loyalty_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    order_id BIGINT NULL,
    points INT NOT NULL,
    transaction_type ENUM('earn', 'redeem', 'manual_adjust') NOT NULL,
    description VARCHAR(255) NULL,
    balance_before INT UNSIGNED NOT NULL DEFAULT 0,
    balance_after INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Bảng `orders` - Thêm cột liên quan điểm
```sql
ALTER TABLE orders 
ADD COLUMN loyalty_points_used INT UNSIGNED NOT NULL DEFAULT 0,
ADD COLUMN loyalty_discount DECIMAL(15,2) NOT NULL DEFAULT 0,
ADD COLUMN loyalty_points_earned INT UNSIGNED NOT NULL DEFAULT 0;
```

---

## 🔧 Cài đặt

### Bước 1: Chạy migration SQL
```bash
# Trong MySQL/PHPMyAdmin
mysql -u root -p mini_market < database/migrations/add_loyalty_points_system.sql
```

### Bước 2: Kiểm tra cấu trúc database
```sql
-- Kiểm tra cột loyalty_points trong users
DESCRIBE users;

-- Kiểm tra bảng loyalty_transactions
DESCRIBE loyalty_transactions;

-- Kiểm tra cột điểm trong orders
DESCRIBE orders;
```

---

## 💻 Sử dụng trong Code

### 1. Tự động tích điểm khi tạo đơn hàng

**File:** `src/Models/Repositories/OrderRepository.php`

Khi tạo đơn hàng, hệ thống tự động:
- Tính số điểm = `floor(total_amount / 1000)`
- Cộng điểm vào tài khoản khách hàng
- Ghi log vào `loyalty_transactions`
- Cập nhật `loyalty_points_earned` trong orders

```php
// Tự động chạy trong OrderRepository::create()
if ($customerId && $totalAmount > 0) {
    $pointsEarned = floor($totalAmount / 1000);
    $customerRepo->addLoyaltyPoints($customerId, $pointsEarned, $orderId, $description);
}
```

### 2. Sử dụng điểm khi thanh toán (TODO - Frontend cần implement)

**Logic cần thêm vào frontend:**
```javascript
// Trong form tạo đơn hàng
const availablePoints = customer.loyalty_points;
const pointsToUse = prompt("Nhập số điểm muốn sử dụng (1000 điểm = 1000đ):");

if (pointsToUse > 0 && pointsToUse <= availablePoints) {
    const loyaltyDiscount = pointsToUse; // 1 điểm = 1đ giảm
    const finalAmount = totalAmount - loyaltyDiscount;
    
    // Gửi trong payload
    orderData.loyalty_points_used = pointsToUse;
    orderData.loyalty_discount = loyaltyDiscount;
}
```

**Backend cần thêm logic trong OrderRepository::create():**
```php
// Trước khi commit transaction
if (isset($data['loyalty_points_used']) && $data['loyalty_points_used'] > 0) {
    $pointsUsed = (int)$data['loyalty_points_used'];
    $loyaltyDiscount = $pointsUsed; // 1000 điểm = 1000đ
    
    $customerRepo = new CustomerRepository();
    $result = $customerRepo->redeemLoyaltyPoints(
        $customerId,
        $pointsUsed,
        $id,
        "Đổi $pointsUsed điểm lấy giảm giá " . number_format($loyaltyDiscount, 0, ',', '.') . "đ"
    );
    
    if ($result !== true) {
        throw new \Exception($result); // Thông báo lỗi
    }
}
```

### 3. Xem lịch sử điểm của khách hàng

**API Endpoint:**
```
GET /admin/api/customers/{id}/loyalty-transactions
```

**Frontend:**
```javascript
async function loadLoyaltyHistory(customerId) {
    const res = await fetch(`/admin/api/customers/${customerId}/loyalty-transactions`);
    const data = await res.json();
    
    data.transactions.forEach(tx => {
        console.log(`
            ${tx.created_at}: 
            ${tx.transaction_type === 'earn' ? '+' : '-'}${tx.points} điểm
            (${tx.balance_before} → ${tx.balance_after})
            ${tx.description}
        `);
    });
}
```

---

## 📊 API Endpoints

### 1. Lấy lịch sử giao dịch điểm
```
GET /admin/api/customers/{id}/loyalty-transactions
```

**Response:**
```json
{
    "transactions": [
        {
            "id": 1,
            "order_id": 123,
            "order_code": "DH20250130001",
            "points": 50,
            "transaction_type": "earn",
            "description": "Tích điểm từ đơn hàng DH20250130001 (Tổng tiền: 50,000đ)",
            "balance_before": 100,
            "balance_after": 150,
            "created_at": "2025-01-30 10:30:00"
        }
    ]
}
```

---

## 🎯 Luồng hoạt động

### Luồng 1: Khách mua hàng → Tích điểm
```
1. Khách hàng mua 50,000đ
2. Hệ thống tính: 50,000 / 1,000 = 50 điểm
3. Cộng 50 điểm vào tài khoản khách hàng
4. Ghi log: "Tích 50 điểm từ đơn DH20250130001"
5. Số dư điểm: 100 → 150
```

### Luồng 2: Khách dùng điểm → Giảm giá (TODO)
```
1. Khách hàng có 5,000 điểm
2. Đơn hàng 100,000đ
3. Khách chọn dùng 5,000 điểm
4. Giảm giá: 5,000đ
5. Số tiền phải trả: 95,000đ
6. Trừ 5,000 điểm từ tài khoản
7. Ghi log: "Đổi 5,000 điểm lấy giảm 5,000đ"
8. Số dư điểm: 5,000 → 0
```

---

## 📱 Hiển thị trên Frontend

### Trong modal chi tiết khách hàng (customer.php)

**Thêm tab "Lịch sử điểm":**
```html
<div class="mb-4">
    <h3 class="text-lg font-semibold mb-2">
        Điểm tích lũy: 
        <span class="text-green-600" x-text="detailCustomer.loyalty_points || 0"></span> điểm
    </h3>
</div>

<!-- Tab lịch sử điểm -->
<div x-show="activeTab === 'loyalty'" class="mt-4">
    <table class="w-full">
        <thead>
            <tr class="bg-gray-100">
                <th>Thời gian</th>
                <th>Loại</th>
                <th>Điểm</th>
                <th>Số dư</th>
                <th>Mô tả</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="tx in loyaltyTransactions" :key="tx.id">
                <tr>
                    <td x-text="tx.created_at"></td>
                    <td>
                        <span :class="tx.transaction_type === 'earn' ? 'text-green-600' : 'text-red-600'"
                              x-text="tx.transaction_type === 'earn' ? 'Tích điểm' : 'Đổi điểm'">
                        </span>
                    </td>
                    <td x-text="(tx.transaction_type === 'earn' ? '+' : '-') + Math.abs(tx.points)"></td>
                    <td x-text="tx.balance_after"></td>
                    <td x-text="tx.description"></td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
```

**JavaScript:**
```javascript
loyaltyTransactions: [],
activeTab: 'info', // 'info', 'orders', 'loyalty'

async openDetailModal(customerId) {
    // ... existing code ...
    
    // Load loyalty transactions
    const loyaltyRes = await fetch(`/admin/api/customers/${customerId}/loyalty-transactions`);
    const loyaltyData = await loyaltyRes.json();
    this.loyaltyTransactions = loyaltyData.transactions || [];
}
```

---

## ✅ Checklist Implementation

### Phase 1: Cơ bản (✅ DONE)
- [x] Tạo migration SQL
- [x] Thêm cột `loyalty_points` vào users
- [x] Tạo bảng `loyalty_transactions`
- [x] Thêm cột điểm vào orders
- [x] Tự động tích điểm khi tạo đơn
- [x] API lấy lịch sử điểm
- [x] Repository methods (addLoyaltyPoints, redeemLoyaltyPoints, getLoyaltyTransactions)

### Phase 2: Nâng cao (TODO - Cần làm thêm)
- [ ] Frontend: Hiển thị điểm hiện tại trong modal khách hàng
- [ ] Frontend: Tab lịch sử điểm trong modal chi tiết
- [ ] Frontend: Input dùng điểm khi tạo đơn hàng
- [ ] Backend: Logic trừ điểm khi tạo đơn (redeemLoyaltyPoints)
- [ ] Backend: Validate đủ điểm trước khi trừ
- [ ] Backend: Cập nhật `loyalty_discount` trong orders
- [ ] Frontend: Hiển thị cột "Điểm tích lũy" trong bảng khách hàng
- [ ] Frontend: Filter/Search theo điểm
- [ ] Backend: API điều chỉnh điểm thủ công (manual_adjust)
- [ ] Backend: Export Excel có cột điểm

---

## 🐛 Troubleshooting

### Lỗi: Column 'loyalty_points' not found
```sql
-- Chạy lại migration
ALTER TABLE users ADD COLUMN loyalty_points INT UNSIGNED NOT NULL DEFAULT 0;
```

### Lỗi: Table 'loyalty_transactions' doesn't exist
```sql
-- Chạy lại migration
SOURCE database/migrations/add_loyalty_points_system.sql;
```

### Lỗi: Không tích điểm tự động
```php
// Kiểm tra log
error_log("Earning loyalty points: $pointsEarned points for customer $customerId");

// Kiểm tra có customer_id không
var_dump($customerId); // Phải có giá trị, không phải null
```

---

## 📞 Support

Nếu có vấn đề, kiểm tra:
1. Database đã chạy migration chưa
2. Log trong OrderRepository::create()
3. API endpoint có hoạt động không
4. Frontend có gọi API đúng không

---

**Version:** 1.0.0  
**Date:** 2025-01-30  
**Author:** sCamTiens Team
