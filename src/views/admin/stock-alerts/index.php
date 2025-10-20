<?php
$pageTitle = 'Cấu hình Cảnh báo Tồn kho';
require __DIR__ . '/../partials/layout-start.php';
?>

<div x-data="stockAlertPage()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Cấu hình Cảnh báo Tồn kho Tự động</h1>
        <p class="text-gray-600 mt-2">
            Hệ thống sẽ tự động kiểm tra và gửi thông báo mỗi ngày lúc <strong>7:00 sáng</strong> cho các sản phẩm có tồn kho thấp.
        </p>
    </div>

    <!-- Thống kê tổng quan -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Tổng sản phẩm đang bán -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Sản phẩm đang bán</p>
                    <p class="text-3xl font-bold text-blue-600" x-text="stats.active_products || 0"></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fa-solid fa-box text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Hết hàng -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Hết hàng</p>
                    <p class="text-3xl font-bold text-red-600" x-text="stats.out_of_stock || 0"></p>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fa-solid fa-exclamation-circle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Tồn kho thấp -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Tồn kho thấp</p>
                    <p class="text-3xl font-bold text-orange-600" x-text="stats.low_stock || 0"></p>
                </div>
                <div class="bg-orange-100 p-3 rounded-lg">
                    <i class="fa-solid fa-triangle-exclamation text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

        <!-- Tồn kho rất thấp -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Rất thấp (< 50%)</p>
                    <p class="text-3xl font-bold text-yellow-600" x-text="stats.critical || 0"></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <i class="fa-solid fa-bolt text-2xl text-yellow-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Nút chạy kiểm tra thủ công -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Kiểm tra Thủ công</h2>
                <p class="text-gray-600">
                    Chạy kiểm tra ngay bây giờ để tạo thông báo cho tất cả sản phẩm có tồn kho thấp.
                </p>
            </div>
            <button @click="runCheck" 
                    :disabled="isRunning"
                    :class="isRunning ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2">
                <i class="fa-solid fa-play" x-show="!isRunning"></i>
                <i class="fa-solid fa-spinner fa-spin" x-show="isRunning"></i>
                <span x-text="isRunning ? 'Đang chạy...' : 'Chạy Kiểm tra'"></span>
            </button>
        </div>

        <!-- Kết quả kiểm tra -->
        <div x-show="checkResult" x-transition class="mt-4 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <div class="flex items-start">
                <i class="fa-solid fa-check-circle text-green-500 text-xl mt-1 mr-3"></i>
                <div class="flex-1">
                    <h3 class="font-semibold text-green-800 mb-2">Kiểm tra hoàn tất!</h3>
                    <ul class="text-sm text-gray-700 space-y-1">
                        <li>✓ Đã reset <strong x-text="checkResult.reset_old_notifications || 0"></strong> thông báo cũ</li>
                        <li>✓ Tìm thấy <strong x-text="checkResult.out_of_stock_products || 0"></strong> sản phẩm hết hàng</li>
                        <li>✓ Tìm thấy <strong x-text="checkResult.low_stock_products || 0"></strong> sản phẩm tồn kho thấp</li>
                        <li>✓ Đã tạo <strong x-text="checkResult.notifications_created || 0"></strong> thông báo mới</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-2">
                        Thời gian: <span x-text="checkResult.timestamp"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Hướng dẫn cài đặt Task Scheduler -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock text-blue-600"></i>
            Cài đặt Tự động (Windows Task Scheduler)
        </h2>
        
        <div class="space-y-4 text-gray-700">
            <p class="text-sm">
                Để hệ thống tự động kiểm tra mỗi ngày lúc <strong>7:00 sáng</strong>, bạn cần cài đặt Windows Task Scheduler:
            </p>

            <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                <div class="flex items-start gap-3">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 text-sm font-semibold">1</div>
                    <div class="flex-1">
                        <p class="font-semibold">Mở Task Scheduler</p>
                        <p class="text-sm text-gray-600">Nhấn <kbd class="px-2 py-1 bg-white border rounded">Win + R</kbd>, gõ <code class="bg-white px-2 py-1 rounded">taskschd.msc</code></p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 text-sm font-semibold">2</div>
                    <div class="flex-1">
                        <p class="font-semibold">Tạo Task mới</p>
                        <p class="text-sm text-gray-600">Click "Create Basic Task..." → Name: "Daily Stock Alert - 7AM"</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 text-sm font-semibold">3</div>
                    <div class="flex-1">
                        <p class="font-semibold">Cài đặt Trigger</p>
                        <p class="text-sm text-gray-600">Chọn "Daily" → Time: <strong>07:00:00</strong> → Recur every: <strong>1 days</strong></p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 text-sm font-semibold">4</div>
                    <div class="flex-1">
                        <p class="font-semibold">Cài đặt Action</p>
                        <p class="text-sm text-gray-600">Chọn "Start a program"</p>
                        <div class="mt-2 space-y-1 text-xs">
                            <p><strong>Program/script:</strong></p>
                            <code class="block bg-white p-2 rounded">C:\Users\Dell\OneDrive\Documents\Course\CT550\daily_stock_check.bat</code>
                            <p class="mt-2"><strong>Start in:</strong></p>
                            <code class="block bg-white p-2 rounded">C:\Users\Dell\OneDrive\Documents\Course\CT550</code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-info-circle text-blue-600 mt-1"></i>
                    <div class="text-sm">
                        <p class="font-semibold text-blue-800 mb-1">Lưu ý quan trọng:</p>
                        <ul class="list-disc list-inside space-y-1 text-gray-700">
                            <li>File batch đã được tạo sẵn tại: <code>daily_stock_check.bat</code></li>
                            <li>Log file sẽ được lưu tại: <code>logs/daily_stock_check.log</code></li>
                            <li>Bạn có thể double-click file <code>daily_stock_check.bat</code> để chạy thử</li>
                            <li>Thông báo chỉ được gửi cho sản phẩm <strong>đang bán</strong> (is_active = 1)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function stockAlertPage() {
    return {
        stats: <?= json_encode($stats) ?>,
        isRunning: false,
        checkResult: null,

        async runCheck() {
            if (this.isRunning) return;
            
            this.isRunning = true;
            this.checkResult = null;

            try {
                const res = await fetch('/admin/api/stock-alerts/run-check', {
                    method: 'POST'
                });

                if (res.ok) {
                    const data = await res.json();
                    this.checkResult = data.data;
                    
                    // Cập nhật lại thống kê
                    await this.refreshStats();
                } else {
                    alert('Có lỗi xảy ra khi chạy kiểm tra!');
                }
            } catch (e) {
                console.error('Error running check:', e);
                alert('Có lỗi xảy ra: ' + e.message);
            } finally {
                this.isRunning = false;
            }
        },

        async refreshStats() {
            try {
                const res = await fetch('/admin/api/stock-alerts/stats');
                if (res.ok) {
                    const data = await res.json();
                    this.stats = data.data;
                }
            } catch (e) {
                console.error('Error refreshing stats:', e);
            }
        }
    };
}
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>
