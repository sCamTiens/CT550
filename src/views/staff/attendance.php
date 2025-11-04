<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

// Kiểm tra đăng nhập
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$pageTitle = 'Chấm Công';
include __DIR__ . '/../partials/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Chấm Công Hôm Nay</h1>
            <p class="text-gray-600 mt-2">Ngày <?= date('d/m/Y') ?> - <span x-text="currentTime"></span></p>
        </div>

        <div x-data="attendanceApp()" x-init="init()">
            <!-- Loading State -->
            <div x-show="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                <p class="mt-4 text-gray-600">Đang tải thông tin...</p>
            </div>

            <!-- No Shifts -->
            <div x-show="!loading && shifts.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Không có ca làm việc hôm nay</h3>
                <p class="mt-2 text-gray-600">Bạn không được xếp lịch làm việc trong ngày hôm nay.</p>
            </div>

            <!-- Shifts List -->
            <div x-show="!loading && shifts.length > 0" class="space-y-6">
                <template x-for="shift in shifts" :key="shift.shift_id">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                        <!-- Shift Header -->
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-semibold text-white" x-text="shift.shift_name"></h3>
                                    <p class="text-indigo-100 mt-1">
                                        <span x-text="shift.start_time.substring(0, 5)"></span> - 
                                        <span x-text="shift.end_time.substring(0, 5)"></span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span x-show="shift.check_in_time && shift.check_out_time" 
                                          class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        ✓ Hoàn thành
                                    </span>
                                    <span x-show="shift.check_in_time && !shift.check_out_time" 
                                          class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        Đang làm việc
                                    </span>
                                    <span x-show="!shift.check_in_time" 
                                          class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        Chưa check-in
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Shift Body -->
                        <div class="px-6 py-5">
                            <!-- Check-in Info -->
                            <div x-show="shift.check_in_time" class="mb-4 p-4 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Check-in</p>
                                        <p class="text-lg font-semibold text-gray-900 mt-1" x-text="formatTime(shift.check_in_time)"></p>
                                    </div>
                                    <div>
                                        <span :class="{
                                            'bg-green-100 text-green-800': shift.check_in_status === 'Đúng giờ',
                                            'bg-red-100 text-red-800': shift.check_in_status === 'Muộn'
                                        }" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                            <span x-text="shift.check_in_status"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Check-out Info -->
                            <div x-show="shift.check_out_time" class="mb-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Check-out</p>
                                        <p class="text-lg font-semibold text-gray-900 mt-1" x-text="formatTime(shift.check_out_time)"></p>
                                    </div>
                                    <div>
                                        <span :class="{
                                            'bg-green-100 text-green-800': shift.check_out_status === 'Đúng giờ',
                                            'bg-red-100 text-red-800': shift.check_out_status === 'Sớm'
                                        }" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                            <span x-text="shift.check_out_status"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Work Hours -->
                            <div x-show="shift.work_hours > 0" class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-sm font-medium text-gray-700">Số giờ làm việc</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">
                                    <span x-text="shift.work_hours"></span> giờ
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 mt-6">
                                <!-- Check-in Button -->
                                <button x-show="!shift.check_in_time && canCheckIn(shift)" 
                                        @click="checkIn(shift.shift_id)"
                                        :disabled="processing"
                                        class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span x-text="processing ? 'Đang xử lý...' : 'Check-in'"></span>
                                </button>

                                <!-- Check-in Disabled -->
                                <div x-show="!shift.check_in_time && !canCheckIn(shift)" 
                                     class="flex-1 bg-gray-200 text-gray-600 font-semibold py-3 px-6 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    <span>Chưa đến giờ</span>
                                </div>

                                <!-- Check-out Button -->
                                <button x-show="shift.check_in_time && !shift.check_out_time && canCheckOut(shift)" 
                                        @click="checkOut(shift.shift_id)"
                                        :disabled="processing"
                                        class="flex-1 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span x-text="processing ? 'Đang xử lý...' : 'Check-out'"></span>
                                </button>

                                <!-- Check-out Disabled -->
                                <div x-show="shift.check_in_time && !shift.check_out_time && !canCheckOut(shift)" 
                                     class="flex-1 bg-gray-200 text-gray-600 font-semibold py-3 px-6 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    <span>Chưa đến giờ</span>
                                </div>
                            </div>

                            <!-- Time Window Info -->
                            <div x-show="!shift.check_in_time || (shift.check_in_time && !shift.check_out_time)" 
                                 class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-show="!shift.check_in_time">
                                        Bạn có thể check-in từ <strong x-text="getCheckInWindow(shift)"></strong>
                                    </span>
                                    <span x-show="shift.check_in_time && !shift.check_out_time">
                                        Bạn có thể check-out từ <strong x-text="getCheckOutWindow(shift)"></strong>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function attendanceApp() {
    return {
        shifts: [],
        loading: true,
        processing: false,
        currentTime: '',
        
        init() {
            this.loadShifts();
            this.updateCurrentTime();
            setInterval(() => {
                this.updateCurrentTime();
                this.loadShifts(); // Refresh data every minute
            }, 60000);
        },
        
        updateCurrentTime() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('vi-VN');
        },
        
        async loadShifts() {
            try {
                const response = await fetch('/admin/api/attendance/today-shift.php');
                const data = await response.json();
                
                if (data.success) {
                    this.shifts = data.data;
                }
            } catch (error) {
                console.error('Error loading shifts:', error);
            } finally {
                this.loading = false;
            }
        },
        
        canCheckIn(shift) {
            const now = new Date();
            const currentTime = now.getHours() * 60 + now.getMinutes();
            
            const [startHour, startMin] = shift.start_time.split(':').map(Number);
            const shiftStart = startHour * 60 + startMin;
            
            // Cho phép check-in trước 5 phút và sau 5 phút
            return currentTime >= (shiftStart - 5);
        },
        
        canCheckOut(shift) {
            const now = new Date();
            const currentTime = now.getHours() * 60 + now.getMinutes();
            
            const [endHour, endMin] = shift.end_time.split(':').map(Number);
            const shiftEnd = endHour * 60 + endMin;
            
            // Cho phép check-out trước 5 phút
            return currentTime >= (shiftEnd - 5);
        },
        
        getCheckInWindow(shift) {
            const [hour, min] = shift.start_time.split(':').map(Number);
            const startMin = hour * 60 + min - 5;
            const h = Math.floor(startMin / 60);
            const m = startMin % 60;
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
        },
        
        getCheckOutWindow(shift) {
            const [hour, min] = shift.end_time.split(':').map(Number);
            const endMin = hour * 60 + min - 5;
            const h = Math.floor(endMin / 60);
            const m = endMin % 60;
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
        },
        
        async checkIn(shiftId) {
            if (this.processing) return;
            
            this.processing = true;
            try {
                const response = await fetch('/admin/api/attendance/check-in.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ shift_id: shiftId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToast(data.message, 'success');
                    await this.loadShifts();
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (error) {
                this.showToast('Lỗi kết nối: ' + error.message, 'error');
            } finally {
                this.processing = false;
            }
        },
        
        async checkOut(shiftId) {
            if (this.processing) return;
            
            this.processing = true;
            try {
                const response = await fetch('/admin/api/attendance/check-out.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ shift_id: shiftId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showToast(data.message, 'success');
                    await this.loadShifts();
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (error) {
                this.showToast('Lỗi kết nối: ' + error.message, 'error');
            } finally {
                this.processing = false;
            }
        },
        
        formatTime(datetime) {
            if (!datetime) return '';
            const date = new Date(datetime);
            return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        },
        
        showToast(message, type) {
            // Sử dụng toast notification có sẵn trong header
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                alert(message);
            }
        }
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
