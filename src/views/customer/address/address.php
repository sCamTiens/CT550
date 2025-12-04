<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
</head>

<body class="bg-gray-50">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold flex items-center gap-3">
                    <i class="fa-solid fa-location-dot text-[#002975]"></i>
                    Địa chỉ giao hàng
                </h1>
                <button onclick="openAddressModal()"
                    class="bg-[#002975] text-white px-6 py-2 rounded-lg hover:bg-[#001a54] transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Thêm địa chỉ mới
                </button>
            </div>

            <?php if (empty($addresses)): ?>
                <!-- No addresses -->
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <i class="fa-solid fa-map-location-dot text-8xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-700 mb-2">Chưa có địa chỉ giao hàng</h2>
                    <p class="text-gray-500 mb-6">Thêm địa chỉ để thuận tiện khi đặt hàng</p>
                    <button onclick="openAddressModal()"
                        class="bg-[#002975] text-white px-8 py-3 rounded-lg hover:bg-[#001a54] transition-colors font-semibold">
                        <i class="fa-solid fa-plus mr-2"></i>
                        Thêm địa chỉ mới
                    </button>
                </div>
            <?php else: ?>
                <!-- Address list -->
                <div class="space-y-4" id="address-list">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="bg-white rounded-xl shadow-md p-6 relative <?= $addr['is_default'] ? 'border-2 border-[#002975]' : '' ?>"
                            data-address-id="<?= $addr['id'] ?>">
                            <?php if ($addr['is_default']): ?>
                                <div class="absolute top-4 right-4">
                                    <span class="bg-[#002975] text-white px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="fa-solid fa-star mr-1"></i>
                                        Mặc định
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2 relative">
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($addr['recipient_name']) ?></h3>
                                    <span class="text-gray-500">|</span>
                                    <span class="text-gray-600"><?= htmlspecialchars($addr['phone_number']) ?></span>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 pr-4 min-w-0">
                                        <p class="text-gray-700 break-words">
                                            <?= htmlspecialchars($addr['address_line']) ?>
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-3">
                                        <button onclick="editAddress(<?= $addr['id'] ?>)"
                                            class="text-blue-600 hover:text-[#002975] hover:underline font-semibold mt-1" title="Sửa">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.862 4.487l1.65-1.65a1.875 1.875 0 112.652 2.652l-1.65 1.65M18.513 6.138L7.5 17.25H4.5v-3l11.013-11.112z" />
                                            </svg>
                                        </button>
                                        <button onclick="deleteAddress(<?= $addr['id'] ?>)"
                                            class="text-red-400 hover:text-red-600 hover:underline font-semibold" title="Xóa">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 7h12M9 7V4h6v3m-7 4v7m4-7v7m4-7v7M4 7h16v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 pt-4 border-t">
                                <?php if (!$addr['is_default']): ?>
                                    <button onclick="setDefaultAddress(<?= $addr['id'] ?>)"
                                        class="text-[#002975] hover:underline font-semibold">
                                        <i class="fa-regular fa-star mr-1"></i>
                                        Đặt làm mặc định
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Address Modal -->
    <div id="address-modal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-[#002975] to-[#004bbd] text-white p-6 rounded-t-2xl">
                <h2 class="text-2xl font-bold" id="modal-title">Thêm địa chỉ mới</h2>
            </div>

            <form id="address-form" class="p-6 space-y-4">
                <input type="hidden" id="address-id" value="">

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Tên người nhận <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="recipient_name" required placeholder="Nhập họ tên người nhận"
                        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-50">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Số điện thoại <span class="text-red-600">*</span>
                    </label>
                    <input type="tel" id="phone_number" required pattern="0\d{9}" placeholder="Nhập số điện thoại"
                        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-50">
                </div>

                <div class="relative" x-data="{
                    open: false,
                    search: '',
                    filtered: [],
                    highlight: -1,
                    selected: false,
                    choose(province) {
                        document.getElementById('province_code').value = province.province_code;
                        document.getElementById('province_name').value = province.name;
                        this.search = province.name;
                        this.selected = true;
                        this.open = false;
                        // Load wards
                        loadWards(province.province_code);
                        // Reset ward
                        document.getElementById('ward_code').value = '';
                        document.getElementById('ward_name').value = '';
                        document.getElementById('ward-search').value = '';
                    },
                    clear() {
                        document.getElementById('province_code').value = '';
                        document.getElementById('province_name').value = '';
                        this.search = '';
                        this.selected = false;
                        this.filtered = provinces;
                        this.open = false;
                        // Reset ward
                        document.getElementById('ward_code').value = '';
                        document.getElementById('ward_name').value = '';
                        document.getElementById('ward-search').value = '';
                    }
                }" @click.away="open = false">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Tễnh/Thành phố <span class="text-red-600">*</span>
                    </label>
                    <input type="hidden" id="province_code">
                    <input type="hidden" id="province_name">

                    <div class="relative">
                        <input type="text" x-model="search" @focus="open = true; filtered = provinces"
                            @input="open = true; filtered = provinces.filter(p => p.name.toLowerCase().includes(search.toLowerCase()))"
                            class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 pr-8 bg-white focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-50"
                            :class="selected ? 'text-slate-900' : 'text-slate-400'"
                            placeholder="-- Chọn Tỉnh/Thành phố --" />

                        <button x-show="selected" type="button" @click.stop="clear()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                            ✕
                        </button>

                        <svg x-show="!selected"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open"
                        class="absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-auto">
                        <template x-for="(province, i) in filtered" :key="province.province_code">
                            <div @click="choose(province)" @mouseenter="highlight = i" @mouseleave="highlight = -1"
                                :class="[
                                    highlight === i ? 'bg-[#002975] text-white'
                                    : (document.getElementById('province_code')?.value == province.province_code ? 'bg-[#002975] text-white'
                                    : 'hover:bg-[#002975] hover:text-white text-black'),
                                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                                ]">
                                <div x-text="province.name"></div>
                            </div>
                        </template>
                        <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                            Không tìm thấy tỉnh/thành phố
                        </div>
                    </div>
                </div>

                <div class="relative" x-data="{
                    open: false,
                    search: '',
                    filtered: [],
                    highlight: -1,
                    selected: false,
                    choose(ward) {
                        document.getElementById('ward_code').value = ward.ward_code;
                        document.getElementById('ward_name').value = ward.name;
                        this.search = ward.name;
                        this.selected = true;
                        this.open = false;
                    },
                    clear() {
                        document.getElementById('ward_code').value = '';
                        document.getElementById('ward_name').value = '';
                        this.search = '';
                        this.selected = false;
                        this.filtered = wards;
                        this.open = false;
                    }
                }" @click.away="open = false">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Phường/Xã <span class="text-red-600">*</span>
                    </label>
                    <input type="hidden" id="ward_code">
                    <input type="hidden" id="ward_name">

                    <div class="relative">
                        <input type="text" x-model="search" id="ward-search" @focus="open = true; filtered = wards"
                            @input="open = true; filtered = wards.filter(w => w.name.toLowerCase().includes(search.toLowerCase()))"
                            class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 pr-8 bg-white focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-50"
                            :class="selected ? 'text-slate-900' : 'text-slate-400'"
                            placeholder="-- Chọn Phường/Xã --" />

                        <button x-show="selected" type="button" @click.stop="clear()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 focus:outline-none">
                            ✕
                        </button>

                        <svg x-show="!selected"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open"
                        class="absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-auto">
                        <template x-for="(ward, i) in filtered" :key="ward.ward_code">
                            <div @click="choose(ward)" @mouseenter="highlight = i" @mouseleave="highlight = -1" :class="[
                                    highlight === i ? 'bg-[#002975] text-white'
                                    : (document.getElementById('ward_code')?.value == ward.ward_code ? 'bg-[#002975] text-white'
                                    : 'hover:bg-[#002975] hover:text-white text-black'),
                                    'px-3 py-2 cursor-pointer transition-colors text-sm'
                                ]">
                                <div x-text="ward.name"></div>
                            </div>
                        </template>
                        <div x-show="filtered.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                            Không tìm thấy phường/xã
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Địa chỉ cụ thể <span class="text-red-600">*</span>
                    </label>
                    <textarea id="address_line" required rows="3" placeholder="Nhập địa chỉ cụ thể"
                        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-50"></textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="is_default"
                        class="w-5 h-5 text-[#002975] rounded border-gray-300 focus:ring-2 focus:ring-[#002975]">
                    <label for="is_default" class="ml-2 text-gray-700 font-semibold">
                        Đặt làm địa chỉ mặc định
                    </label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit"
                        class="flex-1 bg-[#002975] text-white px-6 py-3 rounded-lg hover:bg-[#001a54] transition-colors font-semibold">
                        <i class="fa-solid fa-check mr-2"></i>
                        Lưu địa chỉ
                    </button>
                    <button type="button" onclick="closeAddressModal()"
                        class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                        <i class="fa-solid fa-xmark mr-2"></i>
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let provinces = [];
        let wards = [];

        // Load provinces on page load
        document.addEventListener('DOMContentLoaded', async function () {
            await loadProvinces();
        });

        async function loadProvinces() {
            try {
                const response = await fetch('/api/provinces');
                const result = await response.json();

                if (result.success && result.data) {
                    provinces = result.data;
                }
            } catch (error) {
                console.error('Error loading provinces:', error);
                showToast('Không thể tải danh sách tỉnh/thành phố', 'error');
            }
        }

        async function loadWards(provinceCode) {
            try {
                const response = await fetch(`/api/wards?province_code=${provinceCode}`);
                const result = await response.json();

                if (result.success && result.data) {
                    wards = result.data;
                }
            } catch (error) {
                console.error('Error loading wards:', error);
                showToast('Không thể tải danh sách phường/xã', 'error');
            }
        }

        // Toast notification
        function showToast(msg, type = 'success') {
            const box = document.getElementById('toast-container');
            if (!box) return;
            box.innerHTML = '';

            const toast = document.createElement('div');
            toast.className = `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold
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

        async function openAddressModal(addressData = null) {
            const modal = document.getElementById('address-modal');
            const form = document.getElementById('address-form');
            const title = document.getElementById('modal-title');

            if (addressData) {
                title.textContent = 'Cập nhật địa chỉ';
                document.getElementById('address-id').value = addressData.id;
                document.getElementById('recipient_name').value = addressData.recipient_name;
                document.getElementById('phone_number').value = addressData.phone_number;

                // Load and set province
                if (addressData.province_code) {
                    document.getElementById('province_code').value = addressData.province_code;
                    document.getElementById('province_name').value = addressData.province || '';

                    await loadWards(addressData.province_code);

                    if (addressData.commune_code) {
                        document.getElementById('ward_code').value = addressData.commune_code;
                        document.getElementById('ward_name').value = addressData.ward || '';
                    }
                }

                document.getElementById('address_line').value = addressData.address_line;
                document.getElementById('is_default').checked = addressData.is_default == 1;

                // Wait for modal to be visible then update Alpine state
                setTimeout(() => {
                    const provinceSearchInputs = document.querySelectorAll('input[x-model="search"]');
                    if (provinceSearchInputs[0] && addressData.province) {
                        provinceSearchInputs[0].value = addressData.province;
                        provinceSearchInputs[0].dispatchEvent(new Event('input', { bubbles: true }));
                        // Set Alpine selected state
                        const provinceDiv = provinceSearchInputs[0].closest('[x-data]');
                        if (provinceDiv && provinceDiv.__x) {
                            provinceDiv.__x.$data.selected = true;
                            provinceDiv.__x.$data.search = addressData.province;
                        }
                    }
                    if (provinceSearchInputs[1] && addressData.ward) {
                        provinceSearchInputs[1].value = addressData.ward;
                        provinceSearchInputs[1].dispatchEvent(new Event('input', { bubbles: true }));
                        // Set Alpine selected state
                        const wardDiv = provinceSearchInputs[1].closest('[x-data]');
                        if (wardDiv && wardDiv.__x) {
                            wardDiv.__x.$data.selected = true;
                            wardDiv.__x.$data.search = addressData.ward;
                        }
                    }
                }, 100);
            } else {
                title.textContent = 'Thêm địa chỉ mới';
                form.reset();
                document.getElementById('address-id').value = '';
                document.getElementById('province_code').value = '';
                document.getElementById('province_name').value = '';
                document.getElementById('ward_code').value = '';
                document.getElementById('ward_name').value = '';
                wards = [];

                // Reset Alpine state
                setTimeout(() => {
                    const provinceSearchInputs = document.querySelectorAll('input[x-model="search"]');
                    provinceSearchInputs.forEach(input => {
                        const div = input.closest('[x-data]');
                        if (div && div.__x) {
                            div.__x.$data.selected = false;
                            div.__x.$data.search = '';
                        }
                    });
                }, 100);
            }

            modal.classList.remove('hidden');
        }

        function closeAddressModal() {
            document.getElementById('address-modal').classList.add('hidden');
        }

        document.getElementById('address-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const addressId = document.getElementById('address-id').value;
            const url = addressId ? `/addresses/${addressId}` : '/addresses';
            const method = addressId ? 'PUT' : 'POST';

            // Get values from hidden inputs
            const provinceCode = document.getElementById('province_code').value;
            const provinceName = document.getElementById('province_name').value;
            const wardCode = document.getElementById('ward_code').value;
            const wardName = document.getElementById('ward_name').value;

            // Validation
            if (!provinceCode) {
                showToast('Vui lòng chọn Tỉnh/Thành phố', 'error');
                return;
            }

            if (!wardCode) {
                showToast('Vui lòng chọn Phường/Xã', 'error');
                return;
            }

            const data = {
                recipient_name: document.getElementById('recipient_name').value,
                phone_number: document.getElementById('phone_number').value,
                province_code: provinceCode,
                province_name: provinceName,
                ward_code: wardCode,
                ward_name: wardName,
                address_line: document.getElementById('address_line').value,
                is_default: document.getElementById('is_default').checked
            };

            console.log('Submitting data:', data); // Debug

            fetch(url, {
                method: method,
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Không thể lưu địa chỉ', 'error');
                });
        });

        function editAddress(id) {
            fetch(`/api/addresses/${id}`, {
                credentials: 'include'
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        openAddressModal(data.address);
                    }
                });
        }

        function deleteAddress(id) {
            // Check if this is the default address
            const addressCard = document.querySelector(`[data-address-id="${id}"]`);
            const isDefault = addressCard?.classList.contains('border-2');

            if (isDefault) {
                showToast('Không thể xóa địa chỉ mặc định. Vui lòng đặt địa chỉ khác làm mặc định trước khi xóa.', 'error');
                return;
            }

            if (!confirm('Bạn có chắc muốn xóa địa chỉ này?')) return;

            fetch(`/addresses/${id}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Không thể xóa địa chỉ', 'error');
                });
        }

        function setDefaultAddress(id) {
            fetch(`/addresses/${id}/set-default`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Không thể cập nhật địa chỉ', 'error');
                });
        }
    </script>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>