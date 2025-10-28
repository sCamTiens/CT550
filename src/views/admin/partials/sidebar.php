<?php
use App\Middlewares\RoleMiddleware;

// Lấy quyền truy cập sections
$allowedSections = RoleMiddleware::getAllowedSections();
?>
<!-- SIDEBAR -->
<aside class="w-72 shrink-0 transition-all duration-200 bg-blue-50 text-slate-800" x-data="{
        openSidebar: true,
        groups: { catalog:false, inventory:false, expense:false, promo:false },
        currentPath: window.location.pathname,
        init() {
        if (this.currentPath.includes('/admin/categories') 
            || this.currentPath.includes('/admin/brands') 
            || this.currentPath.includes('/admin/products') 
            || this.currentPath.includes('/admin/suppliers') 
            || this.currentPath.includes('/admin/units')) {
            this.groups.catalog = true;
        }
        if (this.currentPath.includes('/admin/stocks') 
            || this.currentPath.includes('/admin/purchase-orders') 
            || this.currentPath.includes('/admin/stock-outs') 
            || this.currentPath.includes('/admin/stocktakes') 
            || this.currentPath.includes('/admin/product-batches')) {
            this.groups.inventory = true;
        }
        if (this.currentPath.includes('/admin/receipt_vouchers') 
            || this.currentPath.includes('/admin/expense_vouchers')) {
            this.groups.expense = true;
        }
        if (this.currentPath.includes('/admin/coupons') 
            || this.currentPath.includes('/admin/promotions')) {
            this.groups.promo = true;
        }
        }
    }" x-init="init()" :class="openSidebar ? 'translate-x-0' : '-translate-x-full'">

    <nav class="px-2 pb-6 text-base mt-6">
        <!-- Dashboard -->
        <?php if ($allowedSections['dashboard']): ?>
        <a href="/admin" :class="[
                'flex items-center gap-2 px-3 py-2 rounded',
                currentPath === '/admin' ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                ]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
            </svg>
            <span>Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- Bán hàng -->
        <?php if ($allowedSections['orders']): ?>
        <a href="/admin/orders" :class="[
                    'flex items-center gap-2 px-3 py-2 rounded',
                    currentPath.startsWith('/admin/orders') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                    ]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 3h18l-1.68 9.39A2 2 0 0117.35 14H6.65a2 2 0 01-1.97-1.61L3 3z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16 19a2 2 0 11-4 0 2 2 0 014 0zm-6 0a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Quản lý đơn hàng</span>
        </a>
        <?php endif; ?>


        <!-- Danh mục sản phẩm -->
        <?php if ($allowedSections['catalog']): ?>
        <div class="mt-2">
            <button @click="groups.catalog=!groups.catalog" :class="[
                        'w-full flex justify-between items-center px-3 py-2 rounded',
                        (currentPath.includes('/admin/categories') 
                        || currentPath.includes('/admin/brands') 
                        || currentPath.includes('/admin/products') 
                        || currentPath.includes('/admin/suppliers') 
                        || currentPath.includes('/admin/units'))
                        ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                    ]">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5" />
                    </svg>
                    <span>Danh mục sản phẩm</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="groups.catalog?'rotate-90':''" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path d="M6 6l4 4 4-4" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.catalog" x-collapse>
                <a href="/admin/categories"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/categories') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Loại sản phẩm
                </a>

                <a href="/admin/brands"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/brands') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10v10H7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h4v4H3zM17 17h4v4h-4z" />
                    </svg>
                    Thương hiệu
                </a>

                <a href="/admin/products"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/products') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.3 7L12 12l8.7-5" />
                    </svg>
                    Sản phẩm
                </a>

                <a href="/admin/suppliers"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/suppliers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Truck icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h13v10H3V7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 13h4l1 2v2h-5v-4z" />
                        <circle cx="7.5" cy="17.5" r="1.5" />
                        <circle cx="17.5" cy="17.5" r="1.5" />
                    </svg>
                    Nhà cung cấp
                </a>

                <a href="/admin/units"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/units') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Scale / ruler icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                    Đơn vị tính
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quản lý kho -->
        <?php if ($allowedSections['inventory']): ?>
        <div class="mt-2">
            <button @click="groups.inventory=!groups.inventory" :class="[
                    'w-full flex justify-between items-center px-3 py-2 rounded',
                    (currentPath.includes('/admin/stocks') 
                    || currentPath.includes('/admin/purchase-orders') 
                    || currentPath.includes('/admin/stock-outs') 
                    || currentPath.includes('/admin/stocktakes') 
                    || currentPath.includes('/admin/product-batches'))
                        ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                ]">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20 21V7a2 2 0 00-2-2h-5V3H7v2H6a2 2 0 00-2 2v14h16z" />
                    </svg>
                    <span>Quản lý kho</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="groups.inventory?'rotate-90':''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.inventory" x-collapse>
                <a href="/admin/stocks"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/stocks') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Box icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4a2 2 0 001-1.73z" />
                    </svg>
                    Tồn kho
                </a>

                <a href="/admin/purchase-orders"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/purchase-orders') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Arrow down box -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16" />
                    </svg>
                    Phiếu nhập kho
                </a>

                <a href="/admin/stock-outs"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/stock-outs') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Arrow up box -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9m0 0l-4 4m4-4l4 4M4 3h16" />
                    </svg>
                    Phiếu xuất kho
                </a>

                <a href="/admin/stocktakes"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/stocktakes') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Clipboard check -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 11l3 3L22 4M9 21H5a2 2 0 01-2-2V7a2 2 0 012-2h4" />
                    </svg>
                    Kiểm kê kho
                </a>

                <a href="/admin/product-batches"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/product-batches') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Collection / layers -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4-9 4-9-4z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 17l9 4 9-4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9 4 9-4" />
                    </svg>
                    Lô sản phẩm
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quản lý thu chi -->
        <?php if ($allowedSections['expense']): ?>
        <div class="mt-2">
            <button @click="groups.expense=!groups.expense" :class="[
                    'w-full flex justify-between items-center px-3 py-2 rounded',
                    (currentPath.includes('/admin/receipt_vouchers') 
                    || currentPath.includes('/admin/expense_vouchers'))
                    ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                ]">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 16v-4" />
                    </svg>
                    <span>Quản lý thu chi</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="groups.expense?'rotate-90':''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.expense" x-collapse>
                <a href="/admin/receipt_vouchers"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/receipt_vouchers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Cash in -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <!-- Tờ tiền -->
                        <rect x="3" y="6" width="18" height="12" rx="2" ry="2" stroke-width="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <!-- Mũi tên hướng xuống (tiền vào) -->
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4m0 0l-2-2m2 2l2-2" />
                    </svg>
                    Phiếu thu
                </a>

                <a href="/admin/expense_vouchers"
                    :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/expense_vouchers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                    <!-- Cash out -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <!-- Tờ tiền -->
                        <rect x="3" y="6" width="18" height="12" rx="2" ry="2" stroke-width="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <!-- Mũi tên hướng lên (tiền ra) -->
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 22v-4m0 0l-2 2m2-2l2 2" />
                    </svg>
                    Phiếu chi
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ưu đãi -->
        <?php if ($allowedSections['promo']): ?>
        <div class="mt-2">
            <button @click="groups.promo=!groups.promo" :class="[
                    'w-full flex justify-between items-center px-3 py-2 rounded',
                    (currentPath.includes('/admin/coupons') 
                    || currentPath.includes('/admin/promotions'))
                    ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                ]">
                <div class="flex items-center gap-2">
                    <!-- Icon Ưu đãi -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M3 7a4 4 0 014-4h4.586a1 1 0 01.707.293l7.414 7.414a1 1 0 010 1.414L12.414 20.707a1 1 0 01-1.414 0L3.293 13.414A1 1 0 013 12.707V7z" />
                    </svg>
                    <span>Ưu đãi</span>
                </div>

                <!-- Icon mũi tên -->
                <svg class="w-4 h-4 transition-transform" :class="groups.promo ? 'rotate-90' : ''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>

            <!-- Menu con -->
            <div class="pl-4 space-y-1 mt-1" x-show="groups.promo" x-collapse>

                <!-- Mã giảm giá -->
                <a href="/admin/coupons" :class="['flex items-center gap-2 px-3 py-2 rounded', 
                        currentPath.startsWith('/admin/coupons') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                    ]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a1 1 0 1 0 0 2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a1 1 0 1 0 0-2V8z" />
                    </svg>
                    <span>Mã giảm giá</span>
                </a>

                <!-- Chương trình khuyến mãi -->
                <a href="/admin/promotions" :class="['flex items-center gap-2 px-3 py-2 rounded',
                        currentPath.startsWith('/admin/promotions') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                    ]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20 7h-1.17A3 3 0 0 0 16 4a3 3 0 0 0-2.83 2H10.83A3 3 0 0 0 8 4a3 3 0 0 0-2.83 3H4a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM8 6a1 1 0 0 1 2 0v1H8Zm6 0a1 1 0 0 1 2 0v1h-2ZM5 9h6v2H5Zm8 10h-2v-8h2Zm0-10h6v2h-6Zm5 10h-3v-8h3Z" />
                    </svg>
                    <span>Chương trình khuyến mãi</span>
                </a>

            </div>
        </div>
        <?php endif; ?>

        <?php if ($allowedSections['staff']): ?>
        <div class="mt-2">
            <!-- Nhân viên -->
            <a href="/admin/staff"
                :class="['flex items-center gap-2 px-3 py-2 mt-2 rounded', currentPath.startsWith('/admin/staff') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5V4H2v16h5m10 0v-4a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>Quản lý nhân viên</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($allowedSections['customers']): ?>
        <div class="mt-2">
            <!-- Khách hàng -->
            <a href="/admin/customers"
                :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/customers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 5h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a2 2 0 11-4 0 2 2 0 014 0zM12 16h4" />
                </svg>
                <span>Quản lý khách hàng</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($allowedSections['reports']): ?>
        <div class="mt-2">
            <!-- Thống kê & Báo cáo -->
            <a href="/admin/reports"
                :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/reports') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Thống kê & Báo cáo</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($allowedSections['audit-logs']): ?>
        <div class="mt-2">
            <!-- Lịch sử thao tác (Chỉ Admin) -->
            <a href="/admin/audit-logs"
                :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/audit-logs') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Lịch sử thao tác</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>
</aside>