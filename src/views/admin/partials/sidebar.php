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

    <nav class="px-2 pb-6 text-base">
        <!-- Dashboard -->
        <a href="/admin" :class="[
         'flex items-center gap-2 px-3 py-2 rounded',
         currentPath === '/admin' ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
       ]">
            <span>Dashboard</span>
        </a>

        <!-- Bán hàng -->
        <a href="/admin/orders" :class="[
         'flex items-center gap-2 px-3 py-2 rounded',
         currentPath.startsWith('/admin/orders') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
       ]">
            <span>Quản lý bán hàng</span>
        </a>

        <!-- Danh mục sản phẩm -->
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
                <span>Danh mục sản phẩm</span>
                <svg class="w-4 h-4 transition-transform" :class="groups.catalog?'rotate-90':''" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path d="M6 6l4 4 4-4" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.catalog" x-collapse>
                <a href="/admin/categories" :class="[
             'block px-3 py-2 rounded',
             currentPath.startsWith('/admin/categories') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
           ]">Loại sản phẩm</a>
                <a href="/admin/brands" :class="[
             'block px-3 py-2 rounded',
             currentPath.startsWith('/admin/brands') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
           ]">Thương hiệu</a>
                <a href="/admin/products" :class="[
             'block px-3 py-2 rounded',
             currentPath.startsWith('/admin/products') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
           ]">Sản phẩm</a>
                <a href="/admin/suppliers" :class="[
             'block px-3 py-2 rounded',
             currentPath.startsWith('/admin/suppliers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
           ]">Nhà cung cấp</a>
                <a href="/admin/units" :class="[
             'block px-3 py-2 rounded',
             currentPath.startsWith('/admin/units') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
           ]">Đơn vị tính</a>
            </div>
        </div>

        <!-- Quản lý kho -->
        <div class="mt-2">
            <button @click="groups.inventory=!groups.inventory" :class="[
                  'w-full flex justify-between items-center px-3 py-2 rounded',
                  (currentPath.includes('/admin/stocks') 
                    || currentPath.includes('/admin/purchase-orders') 
                    || currentPath.includes('/admin/stock-exports') 
                    || currentPath.includes('/admin/stocktakes') 
                    || currentPath.includes('/admin/product-batches'))
                        ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                ]">
                <span>Quản lý kho</span>
                <svg class="w-4 h-4 transition-transform" :class="groups.inventory?'rotate-90':''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.inventory" x-collapse>
                <a href="/admin/stocks"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/stocks') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Tồn
                    kho</a>
                <a href="/admin/purchase-orders"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/purchase-orders') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Phiếu
                    nhập kho</a>
                <a href="/admin/stock-outs"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/stock-outs') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Phiếu
                    xuất kho</a>
                <a href="/admin/stocktakes"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/stocktakes') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Kiểm
                    kê kho</a>
                <a href="/admin/product-batches"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/product-batches') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Lô
                    sản phẩm</a>
            </div>
        </div>

        <!-- Thu chi -->
        <div class="mt-2">
            <button @click="groups.expense=!groups.expense" :class="[
                  'w-full flex justify-between items-center px-3 py-2 rounded',
                  (currentPath.includes('/admin/receipt_vouchers') 
                    || currentPath.includes('/admin/expense_vouchers'))
                    ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                ]">
                <span>Quản lý thu chi</span>
                <svg class="w-4 h-4 transition-transform" :class="groups.expense?'rotate-90':''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.expense" x-collapse>
                <a href="/admin/receipt_vouchers"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/receipt_vouchers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Phiếu
                    thu</a>
                <a href="/admin/expense_vouchers"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/expense_vouchers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Phiếu
                    chi</a>
            </div>
        </div>

        <!-- Ưu đãi -->
        <div class="mt-2">
            <button @click="groups.promo=!groups.promo" :class="[
                  'w-full flex justify-between items-center px-3 py-2 rounded',
                  (currentPath.includes('/admin/coupons') 
                    || currentPath.includes('/admin/promotions'))
                    ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white'
                ]">
                <span>Ưu đãi</span>
                <svg class="w-4 h-4 transition-transform" :class="groups.promo?'rotate-90':''" viewBox="0 0 20 20">
                    <path d="M6 6l4 4 4-4" fill="currentColor" />
                </svg>
            </button>
            <div class="pl-4 space-y-1 mt-1" x-show="groups.promo" x-collapse>
                <a href="/admin/coupons"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/coupons') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Mã
                    giảm giá</a>
                <a href="/admin/promotions"
                    :class="['block px-3 py-2 rounded', currentPath.startsWith('/admin/promotions') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">Chương
                    trình khuyến mãi</a>
            </div>
        </div>

        <!-- Nhân viên -->
        <a href="/admin/staff"
            :class="['flex items-center gap-2 px-3 py-2 mt-2 rounded', currentPath.startsWith('/admin/staff') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
            <span>Quản lý nhân viên</span>
        </a>

        <!-- Khách hàng -->
        <a href="/admin/customers"
            :class="['flex items-center gap-2 px-3 py-2 rounded', currentPath.startsWith('/admin/customers') ? 'bg-[#002975] text-white' : 'hover:bg-[#002975] hover:text-white']">
            <span>Quản lý khách hàng</span>
        </a>
    </nav>
</aside>