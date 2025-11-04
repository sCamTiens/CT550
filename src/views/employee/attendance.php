<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chấm công</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen" x-data="attendancePage()" x-init="init()">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-6 px-4 shadow-lg">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold mb-2">Chấm công</h1>
                <p class="text-blue-100">Xin chào, <span x-text="userName"></span>!</p>
                <p class="text-sm text-blue-100 mt-1">Ngày: <span x-text="formatDate(currentDate)"></span></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto p-4 mt-6">
            <!-- Danh sách ca làm việc hôm nay -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Ca làm việc hôm nay</h2>
                
                <template x-if="loading">
                    <div class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    </div>
                </template>

                <template x-if="!loading">
                    <div class="space-y-4">
                        <template x-for="shift in shifts" :key="shift.id">
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                                 :class="getShiftClass(shift)">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-800" x-text="shift.name"></h3>
                                        <p class="text-gray-600">
                                            <span x-text="shift.start_time"></span> - <span x-text="shift.end_time"></span>
                                        </p>
                                        
                                        <!-- Hiển thị trạng thái chấm công -->
                                        <template x-if="getAttendance(shift.id)">
                                            <div class="mt-2 space-y-1">
                                                <p class="text-sm text-green-600">
                                                    ✓ Giờ vào: <span class="font-semibold" x-text="formatDateTime(getAttendance(shift.id).check_in_time)"></span>
                                                </p>
                                                <p class="text-sm" :class="getAttendance(shift.id).check_out_time ? 'text-green-600' : 'text-orange-600'">
                                                    <template x-if="getAttendance(shift.id).check_out_time">
                                                        <span>✓ Giờ ra: <span class="font-semibold" x-text="formatDateTime(getAttendance(shift.id).check_out_time)"></span></span>
                                                    </template>
                                                    <template x-if="!getAttendance(shift.id).check_out_time">
                                                        <span>⏳ Chưa chấm công ra</span>
                                                    </template>
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <div class="ml-4">
                                        <template x-if="!getAttendance(shift.id)">
                                            <button @click="checkIn(shift.id)"
                                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-md">
                                                Vào ca
                                            </button>
                                        </template>
                                        
                                        <template x-if="getAttendance(shift.id) && !getAttendance(shift.id).check_out_time">
                                            <button @click="checkOut(getAttendance(shift.id).id)"
                                                    class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-semibold shadow-md">
                                                Ra ca
                                            </button>
                                        </template>
                                        
                                        <template x-if="getAttendance(shift.id) && getAttendance(shift.id).check_out_time">
                                            <div class="px-6 py-3 bg-green-100 text-green-800 rounded-lg font-semibold">
                                                ✓ Hoàn thành
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Lịch sử chấm công -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Lịch sử chấm công</h2>
                    <div class="flex gap-2">
                        <select x-model="historyMonth" @change="loadHistory()" class="border rounded px-3 py-2">
                            <template x-for="m in 12" :key="m">
                                <option :value="m" x-text="'Tháng ' + m"></option>
                            </template>
                        </select>
                        <select x-model="historyYear" @change="loadHistory()" class="border rounded px-3 py-2">
                            <template x-for="y in [2024, 2025, 2026]" :key="y">
                                <option :value="y" x-text="y"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Ngày</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Ca</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Giờ vào</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Giờ ra</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Lương</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="att in history" :key="att.id">
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3" x-text="formatDate(att.attendance_date)"></td>
                                    <td class="px-4 py-3" x-text="att.shift_name"></td>
                                    <td class="px-4 py-3" x-text="formatDateTime(att.check_in_time)"></td>
                                    <td class="px-4 py-3" x-text="formatDateTime(att.check_out_time) || '-'"></td>
                                    <td class="px-4 py-3 font-semibold text-green-600" x-text="formatMoney(att.wage_per_shift)"></td>
                                </tr>
                            </template>
                            <template x-if="history.length === 0">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Chưa có dữ liệu chấm công
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Toast notification -->
        <div x-show="toast.show" 
             x-transition:enter="animate__animated animate__fadeInDown"
             x-transition:leave="animate__animated animate__fadeOutUp"
             class="fixed top-4 right-4 z-50 max-w-sm"
             style="display: none;">
            <div class="rounded-lg shadow-lg p-4"
                 :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'">
                <p class="text-white font-semibold" x-text="toast.message"></p>
            </div>
        </div>
    </div>

    <script>
        function attendancePage() {
            return {
                loading: true,
                shifts: [],
                todayAttendances: [],
                history: [],
                currentDate: new Date().toISOString().split('T')[0],
                userName: '<?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Nhân viên') ?>',
                historyMonth: new Date().getMonth() + 1,
                historyYear: new Date().getFullYear(),
                toast: { show: false, message: '', type: 'success' },

                async init() {
                    await this.loadShifts();
                    await this.loadTodayAttendances();
                    await this.loadHistory();
                    this.loading = false;
                },

                async loadShifts() {
                    try {
                        const res = await fetch('/employee/api/work-shifts');
                        const data = await res.json();
                        this.shifts = data.shifts || [];
                    } catch (err) {
                        console.error('Lỗi tải ca làm việc:', err);
                    }
                },

                async loadTodayAttendances() {
                    try {
                        const res = await fetch('/employee/api/attendance/today');
                        const data = await res.json();
                        this.todayAttendances = data.attendances || [];
                    } catch (err) {
                        console.error('Lỗi tải chấm công hôm nay:', err);
                    }
                },

                async loadHistory() {
                    try {
                        const res = await fetch(`/employee/api/attendance/history?month=${this.historyMonth}&year=${this.historyYear}`);
                        const data = await res.json();
                        this.history = data.items || [];
                    } catch (err) {
                        console.error('Lỗi tải lịch sử:', err);
                    }
                },

                getAttendance(shiftId) {
                    return this.todayAttendances.find(a => a.shift_id == shiftId);
                },

                getShiftClass(shift) {
                    const att = this.getAttendance(shift.id);
                    if (!att) return 'border-gray-300';
                    if (att.check_out_time) return 'border-green-500 bg-green-50';
                    return 'border-orange-500 bg-orange-50';
                },

                async checkIn(shiftId) {
                    try {
                        const res = await fetch('/employee/api/attendance/check-in', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ shift_id: shiftId })
                        });
                        const data = await res.json();
                        
                        if (res.ok) {
                            this.showToast(data.message, 'success');
                            await this.loadTodayAttendances();
                        } else {
                            this.showToast(data.error, 'error');
                        }
                    } catch (err) {
                        this.showToast('Lỗi kết nối', 'error');
                    }
                },

                async checkOut(attendanceId) {
                    try {
                        const res = await fetch('/employee/api/attendance/check-out', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ attendance_id: attendanceId })
                        });
                        const data = await res.json();
                        
                        if (res.ok) {
                            this.showToast(data.message, 'success');
                            await this.loadTodayAttendances();
                        } else {
                            this.showToast(data.error, 'error');
                        }
                    } catch (err) {
                        this.showToast('Lỗi kết nối', 'error');
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('vi-VN');
                },

                formatDateTime(dateTimeStr) {
                    if (!dateTimeStr) return '';
                    const d = new Date(dateTimeStr);
                    return d.toLocaleString('vi-VN');
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
</body>
</html>
