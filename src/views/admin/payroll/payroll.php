<?php
// views/admin/payroll/payroll.php
$month = $month ?? date('n');
$year = $year ?? date('Y');
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / <span class="text-slate-800 font-medium">Quản lý bảng lương</span>
</nav>

<div x-data="payrollPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý bảng lương</h1>
        <div class="flex gap-2 items-center">
            <!-- Chọn tháng -->
            <div class="relative" x-data="{
                    open: false,
                    months: Array.from({length: 12}, (_, i) => i + 1),
                    choose(m) {
                        month = m;
                        open = false;
                        loadData();
                    }
                }" @click.away="open = false">
                <div @click="open = !open"
                    class="border rounded px-3 py-2 bg-white text-sm cursor-pointer flex justify-between items-center min-w-[110px]">
                    <span x-text="'Tháng ' + month"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <ul x-show="open" x-transition.opacity
                    class="absolute left-0 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow z-10 overflow-auto max-h-60 text-sm">
                    <template x-for="m in months" :key="m">
                        <li @click="choose(m)"
                            class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer transition-colors"
                            :class="month === m ? 'bg-[#002975] text-white' : 'text-black'">
                            Tháng <span x-text="m"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Chọn năm -->
            <div class="relative" x-data="{
                    open: false,
                    years: (() => {
                        const startYear = 2020;
                        const currentYear = new Date().getFullYear();
                        return Array.from({ length: currentYear - startYear + 1 }, (_, i) => startYear + i).reverse();
                    })(),
                    choose(y) {
                        year = y;
                        open = false;
                        loadData();
                    }
                }" @click.away="open = false">
                <div @click="open = !open"
                    class="border rounded px-3 py-2 bg-white text-sm cursor-pointer flex justify-between items-center min-w-[110px]">
                    <span x-text="'Năm ' + year"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <ul x-show="open" x-transition.opacity
                    class="absolute left-0 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow z-10 overflow-auto text-sm">
                    <template x-for="y in years" :key="y">
                        <li @click="choose(y)"
                            class="px-3 py-2 hover:bg-[#002975] hover:text-white cursor-pointer transition-colors"
                            :class="year === y ? 'bg-[#002975] text-white' : 'text-black'">
                            Năm <span x-text="y"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Nút tính lương -->
            <button @click="calculateAll()"
                class="px-4 py-2 bg-[#002975] text-white rounded-lg hover:bg-[#003caa] font-semibold">
                Tính lương tất cả
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng bảng lương</div>
            <div class="text-2xl font-bold text-blue-600" x-text="items.length"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Tổng tiền lương</div>
            <div class="text-lg font-bold text-green-600" x-text="formatMoney(totalSalary())"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Đã duyệt</div>
            <div class="text-2xl font-bold text-purple-600" x-text="countByStatus('Đã duyệt')"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm mb-1">Đã thanh toán</div>
            <div class="text-2xl font-bold text-orange-600" x-text="countByStatus('Đã trả')"></div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow pb-4">
        <template x-if="loading">
            <div class="flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                <p class="text-gray-500">Đang tải dữ liệu...</p>
            </div>
        </template>

        <template x-if="!loading">
            <div style="overflow-x:auto; max-width:100%;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead class="bg-[#002975] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Nhân viên</th>
                            <th class="px-4 py-3 text-center">Vai trò</th>
                            <th class="px-4 py-3 text-center">Số ca làm</th>
                            <th class="px-4 py-3 text-center">Yêu cầu</th>
                            <th class="px-4 py-3 text-right">Lương cơ bản</th>
                            <th class="px-4 py-3 text-right">Lương thực tế</th>
                            <th class="px-4 py-3 text-right">Thưởng</th>
                            <th class="px-4 py-3 text-right">Phạt</th>
                            <th class="px-4 py-3 text-right">Tổng lương</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in items" :key="item.id">
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold" x-text="item.full_name"></div>
                                    <div class="text-sm text-gray-500" x-text="item.username"></div>
                                </td>
                                <td class="px-4 py-3 text-center" x-text="item.staff_role"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-semibold" x-text="item.total_shifts_worked"></span>
                                </td>
                                <td class="px-4 py-3 text-center" x-text="item.required_shifts"></td>
                                <td class="px-4 py-3 text-right" x-text="formatMoney(item.base_salary)"></td>
                                <td class="px-4 py-3 text-right font-semibold text-blue-600"
                                    x-text="formatMoney(item.actual_salary)"></td>
                                <td class="px-4 py-3 text-right text-green-600" x-text="formatMoney(item.bonus)"></td>
                                <td class="px-4 py-3 text-right text-red-600" x-text="formatMoney(item.deduction)"></td>
                                <td class="px-4 py-3 text-right font-bold text-lg text-green-700"
                                    x-text="formatMoney(item.total_salary)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="getStatusClass(item.status)">
                                        <span x-text="getStatusText(item.status)"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2 justify-center">
                                        <button @click="editBonusDeduction(item)"
                                            class="text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50"
                                            title="Sửa thưởng/phạt">
                                            💰
                                        </button>
                                        <button @click="approve(item.id)" x-show="item.status === 'Nháp'"
                                            class="text-green-600 hover:text-green-800 px-2 py-1 rounded hover:bg-green-50"
                                            title="Duyệt">
                                            ✓
                                        </button>
                                        <button @click="markPaid(item.id)" x-show="item.status === 'Đã duyệt'"
                                            class="text-purple-600 hover:text-purple-800 px-2 py-1 rounded hover:bg-purple-50"
                                            title="Đánh dấu đã trả">
                                            💵
                                        </button>
                                        <button @click="deleteItem(item.id)"
                                            class="text-red-600 hover:text-red-800 px-2 py-1 rounded hover:bg-red-50"
                                            title="Xóa">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                                    Chưa có dữ liệu bảng lương. Nhấn "Tính lương tất cả" để tạo bảng lương.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal Edit Bonus/Deduction -->
    <div x-show="showEditModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        style="display: none;">
        <div class="bg-white w-full max-w-md rounded-xl shadow-lg" @click.outside="showEditModal = false">
            <div class="px-5 py-3 border-b">
                <h3 class="text-xl font-bold text-gray-800">Sửa thưởng/phạt</h3>
            </div>
            <form @submit.prevent="submitBonusDeduction()" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nhân viên</label>
                    <input type="text" :value="editForm.full_name" disabled
                        class="border rounded px-3 py-2 w-full bg-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Thưởng (VNĐ)</label>
                    <input type="number" x-model="editForm.bonus" class="border rounded px-3 py-2 w-full" min="0"
                        step="1000">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Phạt/Khấu trừ (VNĐ)</label>
                    <input type="number" x-model="editForm.deduction" class="border rounded px-3 py-2 w-full" min="0"
                        step="1000">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="showEditModal = false"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">
                        Hủy
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function payrollPage() {
        return {
            loading: true,
            items: [],
            month: <?= $month ?>,
            year: <?= $year ?>,
            showEditModal: false,
            editForm: { id: null, full_name: '', bonus: 0, deduction: 0 },

            async init() {
                await this.loadData();
            },

            async loadData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/api/payroll?month=${this.month}&year=${this.year}`);
                    const data = await res.json();
                    this.items = data.items || [];
                } catch (err) {
                    console.error('Lỗi tải dữ liệu:', err);
                } finally {
                    this.loading = false;
                }
            },

            async calculateAll() {
                if (!confirm(`Tính lương cho tất cả nhân viên tháng ${this.month}/${this.year}?`)) return;

                this.loading = true;
                try {
                    const res = await fetch('/admin/api/payroll/calculate', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ month: this.month, year: this.year })
                    });
                    const data = await res.json();

                    if (res.ok) {
                        alert(data.message);
                        await this.loadData();
                    } else {
                        alert(data.error || 'Lỗi tính lương');
                    }
                } catch (err) {
                    alert('Lỗi kết nối');
                } finally {
                    this.loading = false;
                }
            },

            editBonusDeduction(item) {
                this.editForm = {
                    id: item.id,
                    full_name: item.full_name,
                    bonus: item.bonus || 0,
                    deduction: item.deduction || 0
                };
                this.showEditModal = true;
            },

            async submitBonusDeduction() {
                try {
                    const res = await fetch(`/admin/api/payroll/${this.editForm.id}/bonus-deduction`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            bonus: parseFloat(this.editForm.bonus),
                            deduction: parseFloat(this.editForm.deduction)
                        })
                    });

                    if (res.ok) {
                        alert('Cập nhật thành công');
                        this.showEditModal = false;
                        await this.loadData();
                    } else {
                        alert('Lỗi cập nhật');
                    }
                } catch (err) {
                    alert('Lỗi kết nối');
                }
            },

            async approve(id) {
                if (!confirm('Xác nhận duyệt bảng lương này?')) return;

                try {
                    const res = await fetch(`/admin/api/payroll/${id}/approve`, { method: 'POST' });
                    if (res.ok) {
                        alert('Duyệt thành công');
                        await this.loadData();
                    }
                } catch (err) {
                    alert('Lỗi duyệt');
                }
            },

            async markPaid(id) {
                if (!confirm('Xác nhận đã trả lương?')) return;

                try {
                    const res = await fetch(`/admin/api/payroll/${id}/mark-paid`, { method: 'POST' });
                    if (res.ok) {
                        alert('Đã đánh dấu đã trả lương');
                        await this.loadData();
                    }
                } catch (err) {
                    alert('Lỗi');
                }
            },

            async deleteItem(id) {
                if (!confirm('Xác nhận xóa bảng lương này?')) return;

                try {
                    const res = await fetch(`/admin/api/payroll/${id}`, { method: 'DELETE' });
                    if (res.ok) {
                        alert('Xóa thành công');
                        await this.loadData();
                    }
                } catch (err) {
                    alert('Lỗi xóa');
                }
            },

            countByStatus(status) {
                return this.items.filter(i => i.status === status).length;
            },

            totalSalary() {
                return this.items.reduce((sum, i) => sum + parseFloat(i.total_salary || 0), 0);
            },

            getStatusClass(status) {
                const map = {
                    // Status tiếng Việt
                    'Nháp': 'bg-gray-100 text-gray-800',
                    'Đã duyệt': 'bg-green-100 text-green-800',
                    'Đã trả': 'bg-blue-100 text-blue-800',
                    // Backwards compatibility
                    'draft': 'bg-gray-100 text-gray-800',
                    'approved': 'bg-green-100 text-green-800',
                    'paid': 'bg-blue-100 text-blue-800'
                };
                return map[status] || 'bg-gray-100 text-gray-800';
            },

            getStatusText(status) {
                // Status tiếng Việt từ database: 'Nháp', 'Đã duyệt', 'Đã trả'
                const map = {
                    'Nháp': 'Nháp',
                    'Đã duyệt': 'Đã duyệt',
                    'Đã trả': 'Đã trả',
                    // Backwards compatibility
                    'draft': 'Nháp',
                    'approved': 'Đã duyệt',
                    'paid': 'Đã trả'
                };
                return map[status] || status || '—';
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount || 0);
            }
        };
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>