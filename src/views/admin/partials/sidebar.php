<!-- SIDEBAR -->
<!-- SIDEBAR -->
<aside class="w-72 shrink-0 transition-all duration-200 bg-blue-50 text-slate-800"
    :class="openSidebar ? 'translate-x-0' : '-translate-x-full'" style="border-radius: 8px; color: #002795;">
    <nav class="px-2 pb-6 text-sm">
        <a href="/admin" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-[#002975] hover:text-white">
            <span>Dashboard</span>
        </a>

        <a href="/admin/orders" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-[#002975] hover:text-white">
            <span>Quản lý bán hàng</span>
        </a>

        <!-- Danh mục sản phẩm -->
        <div class="mt-2">
            <button @click="groups.catalog=!groups.catalog"
                class="w-full flex justify-between items-center px-3 py-2 rounded hover:bg-[#002975] hover:text-white">
                <span>Danh mục sản phẩm</span>
                <svg class="w-4 h-4 transition-transform" :class="groups.catalog?'rotate-90':''" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path d="M6 6l4 4 4-4" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.catalog" x-collapse>
                <a href="/admin/categories" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Loại sản
                    phẩm</a>
                <a href="/admin/brands" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Thương
                    hiệu</a>
                <a href="/admin/products" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Sản
                    phẩm</a>
                <a href="/admin/suppliers" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Nhà cung
                    cấp</a>
                <a href="/admin/units" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Đơn vị tính</a>
            </div>
        </div>

        <!-- Kho -->
        <div class="mt-2">
            <button @click="groups.inventory=!groups.inventory"
                class="w-full flex justify-between items-center px-3 py-2 rounded hover:bg-[#002975] hover:text-white">
                <span>Kho</span>
                <svg class="w-4 h-4 transition-transform" :class="groups.inventory?'rotate-90':''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.inventory" x-collapse>
                <a href="/admin/stocks" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Tồn kho</a>
                <a href="/admin/purchase-orders"
                    class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Phiếu nhập kho</a>
                <a href="/admin/stock-exports" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Phiếu
                    xuất kho</a>
                <a href="/admin/stocktakes" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Kiểm kê
                    kho</a>
            </div>
        </div>

        <!-- Ưu đãi -->
        <div class="mt-2">
            <button @click="groups.promo=!groups.promo"
                class="w-full flex justify-between items-center px-3 py-2 rounded hover:bg-[#002975] hover:text-white">
                <span>Ưu đãi</span>
                <svg class="w-4 h-4 transition-transform" :class="groups.promo?'rotate-90':''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.promo" x-collapse>
                <a href="/admin/coupons" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Mã giảm
                    giá</a>
                <a href="/admin/promotions" class="block px-3 py-2 rounded hover:bg-[#002975] hover:text-white">Chương
                    trình khuyến mãi</a>
            </div>
        </div>

        <a href="/admin/staff"
            class="flex items-center gap-2 px-3 py-2 mt-2 rounded hover:bg-[#002975] hover:text-white">
            <span>Quản lý nhân viên</span>
        </a>
        <a href="/admin/customers"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-[#002975] hover:text-white">
            <span>Quản lý khách hàng</span>
        </a>
        <a href="/admin/roles" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-[#002975] hover:text-white">
            <span>Phân quyền (Vai trò)</span>
        </a>

        <a href="/admin/settings"
            class="flex items-center gap-2 px-3 py-2 mt-2 rounded hover:bg-[#002975] hover:text-white">
            <span>Cấu hình</span>
        </a>
    </nav>
</aside>