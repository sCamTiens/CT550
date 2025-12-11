<!-- GHN Address Picker Script -->
<script>
    let ghnProvinces = [];
    let ghnDistricts = [];
    let ghnWards = [];

    // Load provinces from GHN on page load
    async function loadGHNProvinces() {
        try {
            const response = await fetch('/api/shipping/provinces');
            const result = await response.json();
            ghnProvinces = result.data || [];

            const select = document.getElementById('province');
            if (!select) return;

            select.innerHTML = '<option value="">Chọn Tỉnh/Thành phố</option>';
            ghnProvinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province.ProvinceID;
                option.textContent = province.ProvinceName;
                option.dataset.code = province.ProvinceID;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading provinces:', error);
            showToast('Không thể tải danh sách tỉnh/thành', 'error');
        }
    }

    // Load districts when province is selected
    async function onProvinceChange() {
        const provinceSelect = document.getElementById('province');
        const districtSelect = document.getElementById('district');
        const wardSelect = document.getElementById('ward');

        if (!provinceSelect || !districtSelect || !wardSelect) return;

        const provinceId = provinceSelect.value;

        // Reset district and ward
        districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
        wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
        districtSelect.disabled = true;
        wardSelect.disabled = true;

        if (!provinceId) return;

        try {
            const response = await fetch(`/api/shipping/districts?province_id=${provinceId}`);
            const result = await response.json();
            ghnDistricts = result.data || [];

            districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
            ghnDistricts.forEach(district => {
                const option = document.createElement('option');
                option.value = district.DistrictID;
                option.textContent = district.DistrictName;
                option.dataset.code = district.DistrictID;
                select.appendChild(option);
            });

            districtSelect.disabled = false;
        } catch (error) {
            console.error('Error loading districts:', error);
            showToast('Không thể tải danh sách quận/huyện', 'error');
        }
    }

    // Load wards when district is selected
    async function onDistrictChange() {
        const districtSelect = document.getElementById('district');
        const wardSelect = document.getElementById('ward');

        if (!districtSelect || !wardSelect) return;

        const districtId = districtSelect.value;

        // Reset ward
        wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
        wardSelect.disabled = true;

        if (!districtId) return;

        try {
            const response = await fetch(`/api/shipping/wards?district_id=${districtId}`);
            const result = await response.json();
            ghnWards = result.data || [];

            wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
            ghnWards.forEach(ward => {
                const option = document.createElement('option');
                option.value = ward.WardCode;
                option.textContent = ward.WardName;
                option.dataset.code = ward.WardCode;
                wardSelect.appendChild(option);
            });

            wardSelect.disabled = false;
        } catch (error) {
            console.error('Error loading wards:', error);
            showToast('Không thể tải danh sách phường/xã', 'error');
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadGHNProvinces();

        // Attach event listeners
        const provinceSelect = document.getElementById('province');
        const districtSelect = document.getElementById('district');

        if (provinceSelect) {
            provinceSelect.addEventListener('change', onProvinceChange);
        }

        if (districtSelect) {
            districtSelect.addEventListener('change', onDistrictChange);
        }
    });

    // Update form submission to save GHN codes
    async function saveAddress(event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);

        // Get selected values
        const provinceId = document.getElementById('province').value;
        const districtId = document.getElementById('district').value;
        const wardCode = document.getElementById('ward').value;

        // Validate
        if (!provinceId || !districtId || !wardCode) {
            showToast('Vui lòng chọn đầy đủ Tỉnh/Thành, Quận/Huyện, Phường/Xã', 'error');
            return;
        }

        // Prepare data with GHN codes
        const data = {
            recipient_name: formData.get('recipient_name'),
            phone_number: formData.get('phone_number'),
            address_line: formData.get('address_line'),
            province_code: provinceId, // GHN ProvinceID
            commune_code: wardCode, // GHN WardCode
            district_id: districtId, // GHN DistrictID (not saved in user_addresses, but used for checkout)
            is_default: formData.get('is_default') === 'on' ? 1 : 0
        };

        try {
            const addressId = form.dataset.addressId;
            const url = addressId ? `/addresses/${addressId}` : '/addresses';
            const method = addressId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (response.ok) {
                showToast('Lưu địa chỉ thành công!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                const error = await response.json();
                showToast(error.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            console.error('Error saving address:', error);
            showToast('Không thể lưu địa chỉ', 'error');
        }
    }
</script>