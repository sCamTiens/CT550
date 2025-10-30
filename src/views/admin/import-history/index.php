<?php
// views/admin/import-history/index.php
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Lịch sử nhập file</span>
</nav>

<div x-data="importHistoryPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Lịch sử nhập file Excel</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow mb-4 p-4">
        <div class="grid grid-cols-4 gap-4">
            <!-- Filter theo module -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Module</label>
                <select x-model="filterModule" @change="applyFilters()"
                    class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">-- Tất cả --</option>
                    <option value="categories">Loại sản phẩm</option>
                    <option value="products">Sản phẩm</option>
                    <option value="brands">Thương hiệu</option>
                    <option value="units">Đơn vị tính</option>
                    <option value="suppliers">Nhà cung cấp</option>
                    <option value="customers">Khách hàng</option>
                    <option value="staff">Nhân viên</option>
                    <option value="purchase_orders">Phiếu nhập kho</option>
                    <option value="coupons">Mã giảm giá</option>
                </select>
            </div>

            <!-- Filter theo trạng thái -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Trạng thái</label>
                <select x-model="filterStatus" @change="applyFilters()"
                    class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">-- Tất cả --</option>
                    <option value="success">Thành công</option>
                    <option value="partial">Một phần</option>
                    <option value="failed">Thất bại</option>
                </select>
            </div>

            <!-- Filter theo người nhập -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Người nhập</label>
                <input type="text" x-model="filterUser" @input="applyFilters()" placeholder="Tìm theo tên..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2">
            </div>

            <!-- Reset button -->
            <div class="flex items-end">
                <button @click="resetFilters()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md hover:bg-[#002975] hover:text-white">
                    <i class="fa-solid fa-rotate-right mr-1"></i> Đặt lại
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <div style="overflow-x:auto; max-width:100%;" class="pb-40">
            <table style="width:120%; min-width:1250px; border-collapse:collapse;">
                <thead>
                    <tr class="bg-gray-50 text-slate-600 text-center">
                        <th class="py-3 px-4">Thao tác</th>
                        <th class="py-3 px-4">Module</th>
                        <th class="py-3 px-4">Tên file</th>
                        <th class="py-3 px-4">Tổng dòng</th>
                        <th class="py-3 px-4">Thành công</th>
                        <th class="py-3 px-4">Thất bại</th>
                        <th class="py-3 px-4">Trạng thái</th>
                        <th class="py-3 px-4">Người nhập</th>
                        <th class="py-3 px-4">Thời gian nhập</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="item in paginated()" :key="item.id">
                        <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
                            <td class="py-2 px-4 text-center">
                                <!-- <div class="flex gap-1">
                                    <button @click="viewDetail(item.id)"
                                        class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                        title="Xem chi tiết">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button @click="deleteItem(item.id)"
                                        class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                        title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div> -->

                                <button @click="viewDetail(item.id)"
                                    class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                                    title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded text-xs font-semibold"
                                    :class="getModuleColor(item.table_name)"
                                    x-text="getModuleName(item.table_name)"></span>
                            </td>
                            <td class="py-2 px-4" :title="item.file_name" x-text="truncateText(item.file_name, 40)">
                            </td>
                            <td class="py-2 px-4 text-center font-semibold" x-text="item.total_rows"></td>
                            <td class="py-2 px-4 text-center">
                                <span class="text-green-600 font-semibold" x-text="item.success_rows"></span>
                            </td>
                            <td class="py-2 px-4 text-center">
                                <span class="text-red-600 font-semibold" x-text="item.failed_rows"></span>
                            </td>
                            <td class="py-2 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="{
                                        'bg-green-100 text-green-700': item.status === 'success',
                                        'bg-yellow-100 text-yellow-700': item.status === 'partial',
                                        'bg-red-100 text-red-700': item.status === 'failed'
                                    }" x-text="getStatusText(item.status)"></span>
                            </td>
                            <td class="py-2 px-4" x-text="item.imported_by_name || '—'"></td>
                            <td class="py-2 px-4 text-right" x-text="item.imported_at || '—'"></td>
                        </tr>
                    </template>

                    <tr x-show="!loading && filteredItems.length===0">
                        <td colspan="9" class="py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                                <div class="text-lg text-slate-300">Chưa có lịch sử nhập file</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Chi tiết -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
        x-show="openDetail" x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-6xl rounded-xl shadow animate__animated animate__zoomIn animate__faster max-h-[90vh] overflow-hidden flex flex-col"
            @click.outside="openDetail=false">
            <div class="px-5 py-3 border-b flex justify-center items-center relative">
                <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết nhập file</h3>
                <button class="text-slate-500 absolute right-5" @click="openDetail=false">✕</button>
            </div>
            <div class="p-5 overflow-y-auto flex-1">
                <template x-if="detail">
                    <div class="space-y-4">
                        <!-- Thông tin chung -->
                        <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <span class="text-sm text-slate-600">Module:</span>
                                <p class="font-semibold">
                                    <span class="px-2 py-1 rounded text-xs"
                                        :class="getModuleColor(detail.table_name)"
                                        x-text="getModuleName(detail.table_name)"></span>
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-slate-600">Tên file:</span>
                                <p class="font-semibold" x-text="detail.file_name"></p>
                            </div>
                            <div>
                                <span class="text-sm text-slate-600">Trạng thái:</span>
                                <p>
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="{
                                        'bg-green-100 text-green-700': detail.status === 'success',
                                        'bg-yellow-100 text-yellow-700': detail.status === 'partial',
                                        'bg-red-100 text-red-700': detail.status === 'failed'
                                        }" x-text="getStatusText(detail.status)"></span>
                                </p>
                            </div>
                            <div>
                                <span class="text-sm text-slate-600">Người nhập:</span>
                                <p class="font-semibold" x-text="detail.imported_by_name"></p>
                            </div>
                            <div>
                                <span class="text-sm text-slate-600">Thời gian:</span>
                                <p class="font-semibold" x-text="detail.imported_at"></p>
                            </div>
                        </div>

                        <!-- Thống kê -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 bg-blue-50 rounded-lg text-center">
                                <div class="text-3xl font-bold text-blue-600" x-text="detail.total_rows"></div>
                                <div class="text-sm text-slate-600 mt-1">Tổng dòng</div>
                            </div>
                            <div class="p-4 bg-green-50 rounded-lg text-center">
                                <div class="text-3xl font-bold text-green-600" x-text="detail.success_rows"></div>
                                <div class="text-sm text-slate-600 mt-1">Thành công</div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-lg text-center">
                                <div class="text-3xl font-bold text-red-600" x-text="detail.failed_rows"></div>
                                <div class="text-sm text-slate-600 mt-1">Thất bại</div>
                            </div>
                        </div>

                        <!-- Danh sách lỗi (nếu có) -->
                        <div x-show="errorDetails.length > 0">
                            <h4 class="font-semibold text-lg mb-2 text-red-600">
                                <i class="fa-solid fa-exclamation-triangle mr-1"></i> Danh sách lỗi:
                            </h4>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 max-h-48 overflow-y-auto">
                                <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                                    <template x-for="err in errorDetails" :key="err">
                                        <li x-text="err"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <!-- Nội dung file -->
                        <div x-show="fileContent.length > 0">
                            <h4 class="font-semibold text-lg mb-2">Nội dung chi tiết:</h4>
                            <div class="border rounded-lg overflow-hidden">
                                <div class="overflow-x-auto max-h-96">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-100 sticky top-0">
                                            <tr>
                                                <th class="py-2 px-3 text-center" style="width: 80px;">Dòng</th>
                                                <th class="py-2 px-3 text-left">Dữ liệu</th>
                                                <th class="py-2 px-3 text-center" style="width: 120px;">Kết quả</th>
                                                <th class="py-2 px-3 text-left" style="width: 200px;">Lỗi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, index) in fileContent" :key="index">
                                                <tr class="border-t hover:bg-gray-50"
                                                    :class="getRowStatus(row, index) === 'failed' ? 'bg-red-50' : ''">
                                                    <td class="py-2 px-3 font-mono text-center text-gray-600" x-text="getRowNumber(row, index)"></td>
                                                    <td class="py-2 px-3">
                                                        <div class="space-y-0.5 text-xs" x-html="getRowData(row, detail.table_name)"></div>
                                                    </td>
                                                    <td class="py-2 px-3 text-center">
                                                        <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                                            :class="getRowStatus(row, index) === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                                            x-text="getRowStatus(row, index) === 'success' ? 'Thành công' : 'Thất bại'"></span>
                                                    </td>
                                                    <td class="py-2 px-3 text-red-600 text-xs" 
                                                        :title="getRowErrorMsg(row, index)"
                                                        x-text="truncateText(getRowErrorMsg(row, index) || '—', 50)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-5 py-3 border-t flex justify-end">
                <button type="button" class="px-4 py-2 rounded-md border hover:bg-gray-50" @click="openDetail=false">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast-container" class="z-[60]"></div>

    <!-- Pagination -->
    <div class="flex items-center justify-center mt-4 px-4 gap-6">
        <div class="text-sm text-slate-600">
            Hiển thị <span x-text="paginated().length"></span> / <span x-text="filteredItems.length"></span> bản ghi
        </div>

        <div class="flex items-center gap-2">
            <button @click="goToPage(currentPage-1)" :disabled="currentPage===1"
                class="px-2 py-1 border rounded disabled:opacity-50 hover:bg-gray-50">&lt;</button>
            <span>Trang <span x-text="currentPage"></span> / <span x-text="totalPages()"></span></span>
            <button @click="goToPage(currentPage+1)" :disabled="currentPage===totalPages()"
                class="px-2 py-1 border rounded disabled:opacity-50 hover:bg-gray-50">&gt;</button>

            <div x-data="{ open: false }" class="relative">
                <button @click="open=!open"
                    class="border rounded px-2 py-1 w-28 flex justify-between items-center hover:bg-gray-50">
                    <span x-text="perPage + ' / trang'"></span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open=false"
                    class="absolute right-0 mt-1 bg-white border rounded shadow w-28 z-50">
                    <template x-for="opt in perPageOptions" :key="opt">
                        <div @click="perPage=opt;currentPage=1;open=false"
                            class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
                            x-text="opt + ' / trang'">
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function importHistoryPage() {
        return {
            loading: true,
            items: [],
            filteredItems: [],
            openDetail: false,
            detail: null,
            fileContent: [],
            errorDetails: [],
            errorMap: {},

            // Filters
            filterModule: '',
            filterStatus: '',
            filterUser: '',

            currentPage: 1,
            perPage: 20,
            perPageOptions: [10, 20, 50, 100],

            async init() {
                await this.fetchAll();
            },

            async fetchAll() {
                this.loading = true;
                try {
                    const r = await fetch('/admin/api/import-history');
                    if (r.ok) {
                        const data = await r.json();
                        this.items = data.items || [];
                        this.applyFilters();
                    }
                } finally {
                    this.loading = false;
                }
            },

            applyFilters() {
                let filtered = this.items;

                // Filter by module
                if (this.filterModule) {
                    filtered = filtered.filter(item => item.table_name === this.filterModule);
                }

                // Filter by status
                if (this.filterStatus) {
                    filtered = filtered.filter(item => item.status === this.filterStatus);
                }

                // Filter by user
                if (this.filterUser) {
                    const search = this.filterUser.toLowerCase();
                    filtered = filtered.filter(item =>
                        (item.imported_by_name || '').toLowerCase().includes(search)
                    );
                }

                this.filteredItems = filtered;
                this.currentPage = 1; // Reset to first page
            },

            resetFilters() {
                this.filterModule = '';
                this.filterStatus = '';
                this.filterUser = '';
                this.applyFilters();
            },

            async viewDetail(id) {
                try {
                    const r = await fetch(`/admin/api/import-history/${id}`);
                    if (r.ok) {
                        this.detail = await r.json();
                        console.log('Detail data:', this.detail);
                        
                        // Parse file_content
                        const rawContent = this.detail.file_content ? JSON.parse(this.detail.file_content) : [];
                        console.log('Raw file content:', rawContent);
                        
                        // Detect format: array of arrays (products) vs array of objects (others)
                        if (rawContent.length > 0) {
                            if (Array.isArray(rawContent[0])) {
                                // Format: [[val1, val2, ...], ...] - PRODUCTS
                                this.fileContent = rawContent;
                                console.log('Detected format: array of arrays (products)');
                            } else if (typeof rawContent[0] === 'object') {
                                // Format: [{row, name, result, error, ...}, ...] - OTHER MODULES
                                this.fileContent = rawContent;
                                console.log('Detected format: array of objects (other modules)');
                            } else {
                                this.fileContent = [];
                            }
                        } else {
                            this.fileContent = [];
                        }
                        
                        // Parse error_details - format: [{row: X, errors: [...]}, ...] OR simple error strings
                        const rawErrors = this.detail.error_details ? JSON.parse(this.detail.error_details) : [];
                        console.log('Raw errors:', rawErrors);
                        
                        // Create error map by row number
                        this.errorMap = {};
                        this.errorDetails = [];
                        if (Array.isArray(rawErrors)) {
                            rawErrors.forEach(err => {
                                if (typeof err === 'string') {
                                    // Format: "Dòng X: error message" - MOST COMMON
                                    this.errorDetails.push(err);
                                    const match = err.match(/Dòng (\d+):/);
                                    if (match) {
                                        const rowNum = parseInt(match[1]);
                                        this.errorMap[rowNum] = err.replace(`Dòng ${rowNum}: `, '');
                                    }
                                } else if (err.errors && Array.isArray(err.errors)) {
                                    // Format: {row: X, errors: [...]} - PRODUCTS
                                    const errorMsg = err.errors.join(', ');
                                    this.errorMap[err.row] = errorMsg;
                                    err.errors.forEach(msg => {
                                        this.errorDetails.push(`Dòng ${err.row}: ${msg}`);
                                    });
                                }
                            });
                        }
                        console.log('Error map:', this.errorMap);
                        console.log('Error details:', this.errorDetails);
                        
                        this.openDetail = true;
                    }
                } catch (e) {
                    console.error(e);
                    this.showToast('Không thể tải chi tiết');
                }
            },

            getRowError(rowNumber) {
                return this.errorMap?.[rowNumber] || '';
            },

            getRowNumber(row, index) {
                // If row is object with 'row' property, use it
                if (typeof row === 'object' && row.row) {
                    return row.row;
                }
                // Otherwise, use index + 2 (skip header row)
                return index + 2;
            },

            getRowStatus(row, index) {
                // If row is object
                if (typeof row === 'object' && !Array.isArray(row)) {
                    // Check 'status' property first (from ProductController)
                    if (row.status === 'failed' || row.status === 'success') {
                        return row.status;
                    }
                    // Check 'result' property (legacy)
                    if (row.result === 'failed' || row.result === 'success') {
                        return row.result;
                    }
                    // Check if 'errors' property exists and has value
                    if (row.errors && row.errors !== '' && row.errors !== null) {
                        return 'failed';
                    }
                    // Fallback: check if 'error' property exists
                    if (row.error && row.error !== '' && row.error !== null) {
                        return 'failed';
                    }
                    return 'success';
                }
                // If row is array, check error map
                if (Array.isArray(row)) {
                    const rowNum = index + 2;
                    return this.errorMap[rowNum] ? 'failed' : 'success';
                }
                return 'success';
            },

            getRowErrorMsg(row, index) {
                // If row is object
                if (typeof row === 'object') {
                    // Check 'errors' property first (from ProductController)
                    if (row.errors && row.errors !== '' && row.errors !== null) {
                        return row.errors;
                    }
                    // Check 'error' property (legacy)
                    if (row.error && row.error !== '' && row.error !== null) {
                        return row.error;
                    }
                }
                // If row is array, check error map
                if (Array.isArray(row)) {
                    const rowNum = index + 2;
                    return this.errorMap[rowNum] || '';
                }
                return '';
            },

            getRowData(row, tableName) {
                // If row is object (brands, categories, units, etc.)
                if (typeof row === 'object' && !Array.isArray(row)) {
                    console.log('Row object:', row); // Debug
                    let html = '';
                    const fieldsMap = {
                        'brands': ['name', 'slug'],
                        'categories': ['name', 'slug', 'sort_order'],
                        'units': ['name'],
                        'suppliers': ['name', 'phone', 'email', 'address'],
                        'customers': ['name', 'phone', 'email', 'address'],
                        'staff': ['full_name', 'phone', 'email', 'role']
                    };
                    
                    const fields = fieldsMap[tableName] || Object.keys(row).filter(k => !['row', 'result', 'error', 'id'].includes(k));
                    
                    fields.forEach(field => {
                        if (row[field] !== undefined) {
                            const label = this.getFieldLabel(field);
                            html += `<div class="flex"><span class="text-gray-500 font-medium mr-2">${label}:</span><span class="text-gray-700">${row[field] || '—'}</span></div>`;
                        }
                    });
                    
                    return html;
                }
                
                // If row is array (products)
                if (Array.isArray(row)) {
                    const headers = this.getTableHeaders(tableName);
                    let html = '';
                    headers.forEach(header => {
                        const value = row[header.index] || '—';
                        html += `<div class="flex"><span class="text-gray-500 font-medium mr-2">${header.label}:</span><span class="text-gray-700">${value}</span></div>`;
                    });
                    return html;
                }
                
                return '—';
            },

            getFieldLabel(field) {
                const labels = {
                    'name': 'Tên',
                    'slug': 'Slug',
                    'sort_order': 'Thứ tự',
                    'phone': 'Số điện thoại',
                    'email': 'Email',
                    'address': 'Địa chỉ',
                    'full_name': 'Họ tên',
                    'role': 'Vai trò'
                };
                return labels[field] || field;
            },

            getTableHeaders(tableName) {
                const headers = {
                    'products': [
                        { label: 'STT', index: 0 },
                        { label: 'Tên sản phẩm', index: 1 },
                        { label: 'ID Loại', index: 2 },
                        { label: 'ID Thương hiệu', index: 3 },
                        { label: 'ID Đơn vị', index: 4 },
                        { label: 'Quy cách', index: 5 },
                        { label: 'Giá bán', index: 6 },
                        { label: 'Giá nhập', index: 7 },
                        { label: 'Trạng thái', index: 8 }
                    ],
                    'suppliers': [
                        { label: 'STT', index: 0 },
                        { label: 'Tên nhà cung cấp', index: 1 },
                        { label: 'Số điện thoại', index: 2 },
                        { label: 'Email', index: 3 },
                        { label: 'Địa chỉ', index: 4 },
                        { label: 'Trạng thái', index: 5 }
                    ],
                    'customers': [
                        { label: 'Dòng', field: 'row' },
                        { label: 'Họ tên', field: 'full_name' },
                        { label: 'Email', field: 'email' },
                        { label: 'SĐT', field: 'phone' },
                        { label: 'Ngày sinh', field: 'date_of_birth' },
                        { label: 'Giới tính', field: 'gender' },
                        { label: 'Địa chỉ', field: 'address' },
                        { label: 'Điểm TL', field: 'loyalty_points' },
                        { label: 'Trạng thái', field: 'is_active' }
                    ],
                    'categories': [
                        { label: 'STT', index: 0 },
                        { label: 'Tên loại', index: 1 },
                        { label: 'Thứ tự', index: 2 },
                        { label: 'Trạng thái', index: 3 }
                    ],
                    'brands': [
                        { label: 'STT', index: 0 },
                        { label: 'Tên thương hiệu', index: 1 },
                        { label: 'Trạng thái', index: 2 }
                    ],
                    'units': [
                        { label: 'STT', index: 0 },
                        { label: 'Tên đơn vị', index: 1 },
                        { label: 'Trạng thái', index: 2 }
                    ],
                    'staff': [
                        { label: 'Dòng', field: 'row' },
                        { label: 'Tài khoản', field: 'username' },
                        { label: 'Họ tên', field: 'full_name' },
                        { label: 'Vai trò', field: 'staff_role' },
                        { label: 'Email', field: 'email' },
                        { label: 'Số điện thoại', field: 'phone' },
                        { label: 'Trạng thái', field: 'is_active' }
                    ],
                    'purchase_orders': [
                        { label: 'Dòng', field: 'rows' },
                        { label: 'ID NCC', field: 'supplier_id' },
                        { label: 'Ngày nhập', field: 'created_at' },
                        { label: 'Hạn TT', field: 'due_date' },
                        { label: 'Trạng thái TT', field: 'payment_status' },
                        { label: 'Số tiền trả', field: 'paid_amount' },
                        { label: 'Ghi chú', field: 'note' },
                        { label: 'Số SP', field: 'products_count' }
                    ],
                    'coupons': [
                        { label: 'Dòng', field: 'row' },
                        { label: 'Mã', field: 'code' },
                        { label: 'Tên', field: 'name' },
                        { label: 'Loại', field: 'discount_type' },
                        { label: 'Giá trị', field: 'discount_value' },
                        { label: 'Đơn TT', field: 'min_order_value' },
                        { label: 'Giảm TĐ', field: 'max_discount' },
                        { label: 'Bắt đầu', field: 'starts_at' },
                        { label: 'Kết thúc', field: 'ends_at' },
                        { label: 'Trạng thái', field: 'is_active' }
                    ],
                };
                return headers[tableName] || [
                    { label: 'STT', index: 0 },
                    { label: 'Dữ liệu', index: 1 }
                ];
            },

            async deleteItem(id) {
                if (!confirm('Xóa lịch sử nhập này?')) return;

                try {
                    const r = await fetch(`/admin/api/import-history/${id}`, { method: 'DELETE' });
                    if (r.ok) {
                        this.items = this.items.filter(x => x.id != id);
                        this.applyFilters();
                        this.showToast('Xóa lịch sử thành công!', 'success');
                    } else {
                        throw new Error('Không thể xóa');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Không thể xóa lịch sử');
                }
            },

            getModuleName(tableName) {
                const map = {
                    'categories': 'Loại sản phẩm',
                    'products': 'Sản phẩm',
                    'brands': 'Thương hiệu',
                    'suppliers': 'Nhà cung cấp',
                    'customers': 'Khách hàng',
                    'staff': 'Nhân viên',
                    'units': 'Đơn vị tính',
                    'purchase_orders': 'Phiếu nhập kho',
                    'coupons': 'Mã giảm giá',
                };
                return map[tableName] || tableName;
            },

            getModuleColor(tableName) {
                const colors = {
                    'categories': 'bg-purple-100 text-purple-700',
                    'products': 'bg-blue-100 text-blue-700',
                    'brands': 'bg-orange-100 text-orange-700',
                    'suppliers': 'bg-green-100 text-green-700',
                    'customers': 'bg-pink-100 text-pink-700',
                    'staff': 'bg-indigo-100 text-indigo-700',
                    'units': 'bg-teal-100 text-teal-700',
                    'purchase_orders': 'bg-yellow-100 text-yellow-700',
                    'coupons': 'bg-red-100 text-red-700',
                };
                return colors[tableName] || 'bg-gray-100 text-gray-700';
            },

            getStatusText(status) {
                const map = {
                    'success': 'Thành công',
                    'partial': 'Một phần',
                    'failed': 'Thất bại'
                };
                return map[status] || status;
            },

            truncateText(text, maxLength) {
                if (!text) return '—';
                const str = String(text);
                if (str.length <= maxLength) return str;
                return str.substring(0, maxLength) + '...';
            },

            paginated() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filteredItems.slice(start, start + this.perPage);
            },

            totalPages() {
                return this.filteredItems.length > 0 ? Math.ceil(this.filteredItems.length / this.perPage) : 1;
            },

            goToPage(page) {
                if (page < 1) page = 1;
                if (page > this.totalPages()) page = this.totalPages();
                this.currentPage = page;
            },

            showToast(msg, type = 'error') {
                const box = document.getElementById('toast-container');
                if (!box) return;
                box.innerHTML = '';

                const toast = document.createElement('div');
                toast.className =
                    `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold
            ${type === 'success' ? 'text-green-700 border-green-400' : 'text-red-700 border-red-400'}
            bg-white rounded-xl shadow-lg border-2`;

                toast.innerHTML = `
          <svg class="flex-shrink-0 w-6 h-6 ${type === 'success' ? 'text-green-600' : 'text-red-600'} mr-3" 
              xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            ${type === 'success'
                        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`
                        : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`}
          </svg>
          <div class="flex-1">${msg}</div>
        `;

                box.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        }
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>