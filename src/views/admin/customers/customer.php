<?php
// views/admin/customers/customer.php
$items = $items ?? [];
?>

<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<!-- Breadcrumb + Title -->
<nav class="text-sm text-slate-500 mb-4">
  Admin / <span class="text-slate-800 font-medium">Quản lý khách hàng</span>
</nav>
<div x-data="customerPage()" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-[#002975]">Quản lý khách hàng</h1>
    <div class="flex items-center gap-2">
      <a href="/admin/import-history?table=customers"
        class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2">
        <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử nhập
      </a>
      <button
        class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
        @click="openImportModal()">
        <i class="fa-solid fa-file-import"></i> Nhập Excel
      </button>
      <button
        class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975] flex items-center gap-2"
        @click="exportExcel()">
        <i class="fa-solid fa-file-excel"></i>
        Xuất Excel
      </button>
      <button
        class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
        @click="openCreate()">+ Thêm khách hàng</button>
    </div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl shadow pb-4">
    <!-- Loading overlay bên trong bảng -->
    <template x-if="loading">
      <div class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-70 z-10">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
      </div>
    </template>
    <div style="overflow-x:auto; max-width:100%;" class="pb-40">
      <table style="width:200%; min-width:1200px; border-collapse:collapse;">
        <thead>
          <tr class="bg-gray-50 text-slate-600">
            <th class="py-2 px-4 whitespace-nowrap text-center" style="width:300px;">Thao tác</th>
            <th class="py-2 px-4 whitespace-nowrap text-center">Ảnh đại diện</th>
            <?= textFilterPopover('username', 'Tài khoản') ?>
            <?= textFilterPopover('full_name', 'Họ tên') ?>
            <?= textFilterPopover('email', 'Email') ?>
            <?= textFilterPopover('phone', 'SĐT') ?>
            <?= selectFilterPopover('gender', 'Giới tính', [
              '' => '-- Tất cả --',
              'Nam' => 'Nam',
              'Nữ' => 'Nữ',
            ]) ?>
            <?= dateFilterPopover('date_of_birth', 'Ngày sinh') ?>
            <th class="py-2 px-4 whitespace-nowrap text-center">Điểm tích lũy</th>
            <?= selectFilterPopover('is_active', 'Trạng thái', [
              '' => '-- Tất cả --',
              '1' => 'Hoạt động',
              '0' => 'Khóa'
            ])
              ?>
            <?= dateFilterPopover('created_at', 'Thời gian tạo') ?>
            <?= textFilterPopover('created_by_name', 'Người tạo') ?>
            <?= dateFilterPopover('updated_at', 'Thời gian cập nhật') ?>
            <?= textFilterPopover('updated_by_name', 'Người cập nhật') ?>
          </tr>
        </thead>
        <tbody>
          <template x-for="c in paginated()" :key="c.id">
            <tr class="border-t hover:bg-blue-50 transition-colors duration-150">
              <td class="py-2 px-4 space-x-2 text-center">
                <button @click="openEditModal(c)" class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Sửa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button @click="openDetailModal(c.id)"
                  class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                  title="Xem chi tiết">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button @click="openChangePasswordModal(c)"
                  class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                  title="Đổi mật khẩu">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16 10V7a4 4 0 00-8 0v3M5 10h14a1 1 0 011 1v8a1 1 0 01-1 1H5a1 1 0 01-1-1v-8a1 1 0 011-1z" />
                  </svg>
                </button>
                <button @click="openAddressModal(c.id, c.full_name)"
                  class="inline-flex items-center justify-center p-2 rounded hover:bg-gray-100 text-[#002975]"
                  title="Xem địa chỉ">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </button>
                <button @click="remove(c.id)" class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Xóa">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </td>
              <td class="py-2 px-4 text-center">
                <template x-if="c.avatar_url">
                  <img x-show="c.avatar_url" :src="'/assets/images/avatar/' + c.avatar_url" :alt="c.full_name"
                    class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
                </template>
                <template x-if="!c.avatar_url">
                  <div
                    class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center mx-auto text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </template>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line uppercase" x-text="c.username"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.full_name"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line" x-text="c.email"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.phone || '—') === '—' ? 'text-center' : 'text-right'" x-text="c.phone || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line text-center">
                <span x-text="c.gender ? (c.gender === 'Nam' ? 'Nam' : 'Nữ') : '—'"></span>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.date_of_birth || '—') === '—' ? 'text-center' : 'text-right'"
                x-text="formatDate(c.date_of_birth) || '—'"></td>
              <td class="py-2 px-4 text-center">
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold"
                  x-text="(c.loyalty_points || 0) + ' điểm'"></span>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line">
                <span x-text="c.is_active ? 'Hoạt động' : 'Khóa'"
                  :class="c.is_active ? 'text-green-600' : 'text-red-600'"></span>
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.created_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="c.created_at || '—'">
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.created_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                x-text="c.created_by_name || '—'"></td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.updated_at || '—') === '—' ? 'text-center' : 'text-right'" x-text="c.updated_at || '—'">
              </td>
              <td class="py-2 px-4 break-words whitespace-pre-line"
                :class="(c.updated_by_name || '—') === '—' ? 'text-center' : 'text-left'"
                x-text="c.updated_by_name || '—'"></td>
            </tr>
          </template>
          <tr x-show="!loading && filtered().length===0">
            <td colspan="15" class="py-12 text-center text-slate-500">
              <div class="flex flex-col items-center justify-center">
                <img src="/assets/images/Null.png" alt="Trống" class="w-40 h-24 mb-3 opacity-80">
                <div class="text-lg text-slate-300">Không có dữ liệu khách hàng</div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <div class="flex items-center justify-center mt-4 px-4 gap-6">
    <div class="text-sm text-slate-600">
      Tổng cộng <span x-text="filtered().length"></span> bản ghi
    </div>
    <div class="flex items-center gap-2">
      <button @click="goToPage(currentPage-1)" :disabled="currentPage===1"
        class="px-2 py-1 border rounded disabled:opacity-50">&lt;</button>
      <span>Trang <span x-text="currentPage"></span> / <span x-text="totalPages()"></span></span>
      <button @click="goToPage(currentPage+1)" :disabled="currentPage===totalPages()"
        class="px-2 py-1 border rounded disabled:opacity-50">&gt;</button>
      <div x-data="{ open: false }" class="relative">
        <button @click="open=!open" class="border rounded px-2 py-1 w-28 flex justify-between items-center">
          <span x-text="perPage + ' / trang'"></span>
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div x-show="open" @click.outside="open=false"
          class="absolute right-0 mt-1 bg-white border rounded shadow w-28 z-50">
          <template x-for="opt in perPageOptions" :key="opt">
            <div @click="perPage=opt;open=false" class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
              x-text="opt + ' / trang'"></div>
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: Create -->
  <div
    class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
    x-show="openAdd" x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
      @click.outside="openAdd=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Thêm khách hàng</h3>
        <button class="text-slate-500 absolute right-5" @click="openAdd=false">✕</button>
      </div>
      <form class="p-5 space-y-4" @submit.prevent="submitCreate()">
        <?php require __DIR__ . '/form.php'; ?>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button"
            class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white"
            @click="openAdd=false">Hủy</button>
          <button
            class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
            :disabled="submitting" x-text="submitting?'Đang lưu...':'Lưu'"></button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: Edit -->
  <div
    class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
    x-show="openEdit" x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
      @click.outside="openEdit=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Sửa khách hàng</h3>
        <button class="text-slate-500 absolute right-5" @click="openEdit=false">✕</button>
      </div>
      <form class="p-5 space-y-4" @submit.prevent="submitUpdate()">
        <?php require __DIR__ . '/form.php'; ?>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md border" @click="openEdit=false">Đóng</button>
          <button
            class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
            :disabled="submitting" x-text="submitting?'Đang lưu...':'Cập nhật'"></button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: Đổi mật khẩu -->
  <div
    class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
    x-show="openChangePassword" x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-md rounded-xl shadow animate__animated animate__zoomIn animate__faster"
      @click.outside="openChangePassword=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Đổi mật khẩu</h3>
        <button class="text-slate-500 absolute right-5" @click="openChangePassword=false">✕</button>
      </div>
      <form class="p-5 space-y-4" @submit.prevent="submitChangePassword()">
        <div>
          <label class="block text-sm text-black font-semibold mb-1">Mật khẩu mới <span
              class="text-red-500">*</span></label>
          <div class="flex gap-2 items-center">
            <div class="relative flex-1 min-w-0">
              <input :type="showChangePassword ? 'text' : 'password'" x-model="formChangePassword.password"
                class="border rounded px-3 py-2 w-full pr-10" placeholder="Nhập mật khẩu mới" minlength="8"
                maxlength="50" autocomplete="new-password" required @blur="validateChangePasswordField('password')">
              <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                @click="showChangePassword = !showChangePassword" tabindex="-1">
                <i :class="showChangePassword ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
              </button>
            </div>
            <button type="button"
              class="px-4 py-2 border rounded text-sm font-semibold text-[#002975] border-[#002975] hover:bg-[#002975] hover:text-white flex-shrink-0"
              style="min-width:64px;" @click="generateChangePassword()">Tạo</button>
          </div>
          <p x-show="changePasswordErrors.password" x-text="changePasswordErrors.password"
            class="text-red-500 text-xs mt-1"></p>
        </div>
        <div>
          <label class="block text-sm text-black font-semibold mb-1">Xác nhận mật khẩu <span
              class="text-red-500">*</span></label>
          <div class="relative flex-1 min-w-0">
            <input :type="showChangePasswordConfirm ? 'text' : 'password'" x-model="formChangePassword.password_confirm"
              class="border rounded px-3 py-2 w-full pr-10" placeholder="Nhập lại mật khẩu" minlength="8" maxlength="50"
              autocomplete="new-password" required @blur="validateChangePasswordField('password_confirm')">
            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
              @click="showChangePasswordConfirm = !showChangePasswordConfirm" tabindex="-1">
              <i :class="showChangePasswordConfirm ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash'"></i>
            </button>
          </div>
          <p x-show="changePasswordErrors.password_confirm" x-text="changePasswordErrors.password_confirm"
            class="text-red-500 text-xs mt-1"></p>
        </div>
        <div class="pt-2 flex justify-end gap-3">
          <button type="button" class="px-4 py-2 rounded-md border" @click="openChangePassword=false">Đóng</button>
          <button
            class="px-4 py-2 rounded-md text-[#002975] hover:bg-[#002975] hover:text-white border border-[#002975]"
            :disabled="submitting" x-text="submitting?'Đang lưu...':'Đổi mật khẩu'"></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal xem chi tiết khách hàng -->
  <div x-show="openDetail"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 animate__animated animate__fadeIn animate__faster"
    @click.self="openDetail = false" x-cloak>
    <div
      class="bg-white rounded-lg w-full max-w-6xl max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn animate__faster"
      @click.stop>
      <div class="px-5 py-3 border-b flex justify-center items-center relative sticky top-0 bg-white z-10">
        <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết khách hàng</h3>
        <button @click="openDetail = false" class="text-slate-500 absolute right-5">✕</button>
      </div>

      <div x-show="loadingDetail" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#002975]"></div>
      </div>

      <div x-show="!loadingDetail" class="p-6">
        <!-- Thông tin khách hàng -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mb-6 border border-blue-200">
          <h4 class="text-xl font-bold text-[#002975] mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Thông tin khách hàng
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-3">
              <template x-if="detailCustomer.avatar_url">
                <img :src="'/assets/images/avatar/' + detailCustomer.avatar_url" :alt="detailCustomer.full_name"
                  class="w-20 h-20 rounded-full object-cover border-2 border-blue-300">
              </template>
              <template x-if="!detailCustomer.avatar_url">
                <div
                  class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 border-2 border-gray-300">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
              </template>
              <div>
                <div class="text-2xl font-bold text-[#002975]" x-text="detailCustomer.full_name"></div>
                <div class="text-sm text-gray-600 uppercase" x-text="'@' + detailCustomer.username"></div>
              </div>
            </div>
            <div class="flex items-center justify-end">
              <span x-text="detailCustomer.is_active ? 'Hoạt động' : 'Khóa'"
                :class="detailCustomer.is_active ? 'px-4 py-2 bg-green-100 text-green-700 rounded-full font-semibold' : 'px-4 py-2 bg-red-100 text-red-700 rounded-full font-semibold'"></span>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
              <div class="text-sm text-gray-600 mb-1">Email</div>
              <div class="font-medium" x-text="detailCustomer.email || '—'"></div>
            </div>
            <div>
              <div class="text-sm text-gray-600 mb-1">Số điện thoại</div>
              <div class="font-medium" x-text="detailCustomer.phone || '—'"></div>
            </div>
            <div>
              <div class="text-sm text-gray-600 mb-1">Giới tính</div>
              <div class="font-medium" x-text="detailCustomer.gender ? 'Nam' : 'Nữ'"></div>
            </div>
            <div>
              <div class="text-sm text-gray-600 mb-1">Ngày sinh</div>
              <div class="font-medium" x-text="formatDate(detailCustomer.date_of_birth) || '—'"></div>
            </div>
            <div>
              <div class="text-sm text-gray-600 mb-1">
                <i class="fa-solid fa-star text-yellow-500"></i> Điểm tích lũy
              </div>
              <div class="font-bold text-green-600 text-lg" x-text="(detailCustomer.loyalty_points || 0) + ' điểm'">
              </div>
            </div>
          </div>
        </div>

        <!-- Danh sách đơn hàng -->
        <div>
          <h4 class="text-xl font-bold text-[#002975] mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            Lịch sử đơn hàng (<span x-text="detailOrders.length"></span> đơn)
          </h4>

          <template x-if="detailOrders.length === 0">
            <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-gray-300" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
              <p class="text-lg">Khách hàng chưa có đơn hàng nào</p>
            </div>
          </template>

          <template x-if="detailOrders.length > 0">
            <div>
              <div class="overflow-x-auto">
                <table class="w-full border-collapse border" style="width:120%;">
                  <thead>
                    <tr class="bg-gray-50 text-slate-600">
                      <th class="py-2 px-4 border text-center">Thao tác</th>
                      <th class="py-2 px-4 border text-center">Mã đơn</th>
                      <th class="py-2 px-4 border text-center">Trạng thái</th>
                      <th class="py-2 px-4 border text-center">Tổng tiền</th>
                      <th class="py-2 px-4 border text-center">Điểm tích được</th>
                      <th class="py-2 px-4 border text-center">Số SP</th>
                      <th class="py-2 px-4 border text-center">Địa chỉ</th>
                      <th class="py-2 px-4 border text-center">Ngày tạo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template x-for="order in paginatedOrders()" :key="order.id">
                      <tr class="border-t hover:bg-blue-50">
                        <td class="py-1 px-3 border text-center">
                          <button @click="openOrderDetailModal(order.id)"
                            class="p-2 rounded hover:bg-gray-100 text-[#002975]" title="Xem chi tiết đơn hàng">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                              stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                              <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                          </button>
                        </td>
                        <td class="py-2 px-4 border text-sm" x-text="order.code || '—'"></td>
                        <td class="py-2 px-4 border">
                          <span x-text="order.status" :class="getStatusClass(order.status)"></span>
                        </td>
                        <td class="py-2 px-4 border text-right font-semibold text-green-600"
                          x-text="formatMoney(order.grand_total)"></td>
                        <td class="py-2 px-4 border text-center">
                          <span class="px-2 py-1 bg-green-100 text-green-600 rounded font-semibold"
                            x-text="(order.loyalty_points_earned || 0) + ' điểm'"></span>
                        </td>
                        <td class="py-2 px-4 border text-center" x-text="order.total_items"></td>
                        <td class="py-2 px-4 border text-sm break-words whitespace-pre-line"
                          x-text="order.delivery_address || '—'"></td>
                        <td class="py-2 px-4 border text-right text-sm" x-text="order.created_at"></td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              <!-- Pagination cho đơn hàng -->
              <div class="flex items-center justify-center mt-4 gap-6">
                <div class="text-sm text-slate-600">
                  Tổng cộng <span x-text="detailOrders.length"></span> đơn hàng
                </div>
                <div class="flex items-center gap-2">
                  <button @click="goToOrderPage(orderCurrentPage-1)" :disabled="orderCurrentPage===1"
                    class="px-2 py-1 border rounded disabled:opacity-50">&lt;</button>
                  <span>Trang <span x-text="orderCurrentPage"></span> / <span x-text="orderTotalPages()"></span></span>
                  <button @click="goToOrderPage(orderCurrentPage+1)" :disabled="orderCurrentPage===orderTotalPages()"
                    class="px-2 py-1 border rounded disabled:opacity-50">&gt;</button>
                  <div x-data="{ open: false }" class="relative">
                    <button @click="open=!open" class="border rounded px-2 py-1 w-28 flex justify-between items-center">
                      <span x-text="orderPerPage + ' / trang'"></span>
                      <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                    <div x-show="open" @click.outside="open=false"
                      class="absolute right-0 mt-1 bg-white border rounded shadow w-28 z-50">
                      <template x-for="opt in orderPerPageOptions" :key="opt">
                        <div @click="orderPerPage=opt;orderCurrentPage=1;open=false"
                          class="px-3 py-2 cursor-pointer hover:bg-[#002975] hover:text-white"
                          x-text="opt + ' / trang'">
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div class="p-6 border-t sticky bottom-0 bg-white flex justify-end">
        <button type="button" class="px-4 py-2 rounded-md border hover:bg-gray-50"
          @click="openDetail = false">Đóng</button>
      </div>
    </div>
  </div>

  <!-- Modal xem chi tiết đơn hàng -->
  <div x-show="openOrderDetail"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] animate__animated animate__fadeIn animate__faster"
    @click.self="openOrderDetail = false" x-cloak>
    <div
      class="bg-white rounded-lg w-full max-w-5xl max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn animate__faster"
      @click.stop>
      <div class="px-5 py-3 border-b flex justify-center items-center relative sticky top-0 bg-white z-10">
        <h3 class="font-semibold text-2xl text-[#002975]">Chi tiết đơn hàng</h3>
        <button @click="openOrderDetail = false" class="text-slate-500 absolute right-5">✕</button>
      </div>

      <div x-show="loadingOrderDetail" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#002975]"></div>
      </div>

      <div x-show="!loadingOrderDetail" class="p-6">
        <template x-if="orderDetailItems.length === 0">
          <div class="text-center py-8 text-gray-500">
            <p class="text-lg">Không có sản phẩm trong đơn hàng</p>
          </div>
        </template>

        <template x-if="orderDetailItems.length > 0">
          <div class="overflow-x-auto">
            <table class="w-full border-collapse border">
              <thead>
                <tr class="bg-gray-50 text-slate-600">
                  <th class="py-2 px-4 border text-center">Sản phẩm</th>
                  <th class="py-2 px-4 border text-center">Số lượng</th>
                  <th class="py-2 px-4 border text-center">Đơn giá</th>
                  <th class="py-2 px-4 border text-center">Thành tiền</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="item in orderDetailItems" :key="item.id">
                  <tr class="border-t">
                    <td class="py-2 px-4 border">
                      <div class="flex items-center gap-3">
                        <img :src="item.product_image || '/assets/images/products/default.png'" :alt="item.product_name"
                          class="w-12 h-12 object-cover rounded">
                        <div>
                          <div class="font-medium" x-text="item.product_name"></div>
                        </div>
                      </div>
                    </td>
                    <td class="py-2 px-4 border text-center" x-text="item.quantity"></td>
                    <td class="py-2 px-4 border text-right" x-text="formatMoney(item.unit_price)"></td>
                    <td class="py-2 px-4 border text-right font-semibold text-green-600"
                      x-text="formatMoney(item.total)">
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>
      </div>

      <div class="p-6 border-t sticky bottom-0 bg-white flex justify-end">
        <button type="button" class="px-4 py-2 rounded-md border hover:bg-gray-50"
          @click="openOrderDetail = false">Đóng</button>
      </div>
    </div>
  </div>

  <!-- Modal xem địa chỉ -->
  <div x-show="openAddress"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 animate__animated animate__fadeIn animate__faster"
    @click.self="openAddress = false" x-cloak>
    <div
      class="bg-white rounded-lg p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn animate__faster"
      @click.stop>
      <div class="px-5 pb-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Địa chỉ của khách hàng <span
            x-text="addressCustomerName"></span>
        </h3>
        <button @click="openAddress = false" class="text-slate-500 absolute right-5">✕
        </button>
      </div>

      <div x-show="loadingAddress" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#002975]"></div>
      </div>

      <div x-show="!loadingAddress">
        <template x-if="addresses.length === 0">
          <div class="text-center py-8 text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-gray-300" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-lg">Khách hàng chưa có địa chỉ nào</p>
          </div>
        </template>

        <template x-if="addresses.length > 0">
          <div class="space-y-4">
            <template x-for="(addr, index) in addresses" :key="addr.id">
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                :class="addr.is_default ? 'border-[#002975] bg-blue-50' : 'border-gray-200'">
                <div class="flex items-start justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-[#002975]" x-text="addr.label"></span>
                    <template x-if="addr.is_default">
                      <span class="px-2 py-1 bg-[#002975] text-white text-xs rounded-full">Mặc định</span>
                    </template>
                  </div>
                </div>

                <div class="space-y-2 text-sm">
                  <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 flex-shrink-0 mt-0.5"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div>
                      <span class="font-medium">Người nhận:</span>
                      <span x-text="addr.recipient_name"></span>
                    </div>
                  </div>

                  <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 flex-shrink-0 mt-0.5"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <div>
                      <span class="font-medium">Số điện thoại:</span>
                      <span x-text="addr.phone"></span>
                    </div>
                  </div>

                  <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 flex-shrink-0 mt-0.5"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div class="flex-1">
                      <span class="font-medium">Địa chỉ:</span>
                      <p x-text="addr.full_address" class="text-gray-700"></p>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </template>
      </div>

      <div class="mt-6 flex justify-end">
        <button type="button" class="px-4 py-2 rounded-md border hover:bg-gray-50"
          @click="openAddress = false">Đóng</button>
      </div>
    </div>
  </div>

  <!-- MODAL: Import Excel -->
  <div
    class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 animate__animated animate__fadeIn animate__faster"
    x-show="showImportModal" x-transition.opacity style="display:none">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow animate__animated animate__zoomIn animate__faster"
      @click.outside="showImportModal=false">
      <div class="px-5 py-3 border-b flex justify-center items-center relative">
        <h3 class="font-semibold text-2xl text-[#002975]">Nhập dữ liệu khách hàng từ Excel</h3>
        <button class="text-slate-500 absolute right-5" @click="showImportModal=false">✕</button>
      </div>

      <div class="p-5 space-y-4">
        <!-- Chọn file -->
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
          <input type="file" @change="handleFileSelect($event)" accept=".xlsx,.xls" class="hidden" x-ref="fileInput">
          <div x-show="!importFile" @click="$refs.fileInput.click()" class="cursor-pointer">
            <i class="fa-solid fa-cloud-arrow-up text-4xl text-[#002975] mb-3"></i>
            <p class="text-slate-600 mb-1">Nhấn để chọn file Excel</p>
            <p class="text-sm text-slate-400">Hỗ trợ định dạng .xlsx, .xls</p>
          </div>
          <div x-show="importFile" class="space-y-3">
            <div class="flex items-center justify-center gap-2 text-[#002975]">
              <i class="fa-solid fa-file-excel text-2xl"></i>
              <span x-text="importFile?.name" class="font-medium"></span>
            </div>
            <button type="button" @click="clearFile()" class="text-sm text-red-600 hover:underline">
              Xóa file
            </button>
          </div>
        </div>

        <!-- Tải file mẫu -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <i class="fa-solid fa-circle-info text-[#002975] text-xl mt-0.5"></i>
            <div class="flex-1">
              <h4 class="font-semibold text-blue-900 mb-2">Hướng dẫn nhập file:</h4>
              <ul class="text-sm text-blue-800 space-y-1 mb-3">
                <li>• Dòng đầu là tiêu đề, dữ liệu bắt đầu từ dòng 2</li>
                <li>• Trường có dấu <span class="text-red-600 font-bold">*</span> là bắt buộc</li>
                <li>• Họ tên: Tối thiểu 3 ký tự</li>
                <li>• Email: Phải đúng định dạng email và duy nhất</li>
                <li>• Số điện thoại: Bắt đầu bằng 0 và có 10 chữ số, duy nhất</li>
                <li>• Ngày sinh: Định dạng <code class="bg-gray-200 px-1 rounded">dd/mm/yyyy</code></li>
                <li>• File phải có định dạng .xls hoặc .xlsx</li>
                <li>• File tối đa 10MB, không quá 10,000 dòng</li>
              </ul>
              <button type="button" @click="downloadTemplate()"
                class="text-sm text-red-400 hover:text-red-600 hover:underline font-semibold flex items-center gap-1">
                <i class="fa-solid fa-download"></i>
                Tải file mẫu Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Nút hành động -->
        <div class="pt-2 flex justify-end gap-3">
          <button type="button"
            class="px-4 py-2 rounded-md text-red-600 border border-red-600 hover:bg-red-600 hover:text-white transition-colors"
            @click="showImportModal=false">
            Hủy
          </button>
          <button type="button" @click="submitImport()" :disabled="!importFile || importing"
            class="px-4 py-2 rounded-md text-white bg-[#002975] hover:bg-[#001a56] disabled:opacity-50 disabled:cursor-not-allowed"
            x-text="importing ? 'Đang nhập...' : 'Nhập dữ liệu'">
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="toast-container" class="z-[60]"></div>
</div>

<script>
  function customerPage() {
    const api = {
      list: '/admin/api/customers',
      create: '/admin/api/customers',
      update: (id) => `/admin/api/customers/${id}`,
      remove: (id) => `/admin/api/customers/${id}`
    };

    return {
      form: {
        id: null,
        username: '',
        full_name: '',
        email: '',
        phone: '',
        gender: '',
        date_of_birth: '',
        is_active: 1
      },

      errors: {
        username: '',
        full_name: '',
        email: '',
        phone: '',
        password: '',
        password_confirm: ''
      },

      touched: {
        username: false,
        full_name: false,
        email: false,
        phone: false,
        password: false,
        password_confirm: false
      },

      openChangePassword: false,
      formChangePassword: { user_id: null, password: '', password_confirm: '' },
      showChangePassword: false,
      showChangePasswordConfirm: false,
      changePasswordErrors: {},
      changePasswordTouched: false,
      showPassword: false,
      showPasswordConfirm: false,
      openAddress: false,
      addresses: [],
      loadingAddress: false,
      addressCustomerName: '',
      openDetail: false,
      detailCustomer: {},
      detailOrders: [],
      loadingDetail: false,
      orderCurrentPage: 1,
      orderPerPage: 10,
      orderPerPageOptions: [5, 10, 20, 50],
      openOrderDetail: false,
      orderDetailItems: [],
      loadingOrderDetail: false,
      loading: true,
      submitting: false,
      openAdd: false,
      openEdit: false,
      items: [],
      currentPage: 1,
      perPage: 20,
      perPageOptions: [5, 10, 20, 50, 100],
      openFilter: {
        username: false,
        full_name: false,
        email: false,
        phone: false,
        gender: false,
        date_of_birth: false,
        is_active: false,
        created_at: false,
        created_by_name: false,
        updated_at: false,
        updated_by_name: false
      },

      formatDate(d) {
        if (!d || d === '0000-00-00') return '';
        const parts = d.split('-');
        if (parts.length === 3) {
          const [year, month, day] = parts;
          return `${day}/${month}/${year}`;
        }
        return d;
      },

      openChangePasswordModal(c) {
        this.formChangePassword = { user_id: c.id, password: '', password_confirm: '' };
        this.showChangePassword = false;
        this.showChangePasswordConfirm = false;
        this.changePasswordErrors = {};
        this.changePasswordTouched = false;
        this.openChangePassword = true;
      },

      async openAddressModal(customerId, customerName) {
        this.addressCustomerName = customerName;
        this.addresses = [];
        this.loadingAddress = true;
        this.openAddress = true;

        try {
          const res = await fetch(`/admin/api/customers/${customerId}/addresses`);
          if (!res.ok) {
            throw new Error('Không thể tải danh sách địa chỉ');
          }
          const data = await res.json();
          this.addresses = data.addresses || [];
        } catch (err) {
          console.error(err);
          showToast('Lỗi khi tải địa chỉ: ' + err.message, 'error');
          this.addresses = [];
        } finally {
          this.loadingAddress = false;
        }
      },

      async openDetailModal(customerId) {
        this.detailCustomer = {};
        this.detailOrders = [];
        this.loadingDetail = true;
        this.openDetail = true;
        this.orderCurrentPage = 1;

        try {
          const res = await fetch(`/admin/api/customers/${customerId}/detail`);
          if (!res.ok) {
            throw new Error('Không thể tải thông tin chi tiết');
          }
          const data = await res.json();
          this.detailCustomer = data.customer || {};
          this.detailOrders = data.orders || [];
        } catch (err) {
          console.error(err);
          this.showToast('Lỗi khi tải thông tin chi tiết: ' + err.message);
          this.detailCustomer = {};
          this.detailOrders = [];
        } finally {
          this.loadingDetail = false;
        }
      },

      async openOrderDetailModal(orderId) {
        this.orderDetailItems = [];
        this.loadingOrderDetail = true;
        this.openOrderDetail = true;

        try {
          const res = await fetch(`/admin/api/orders/${orderId}/items`);
          if (!res.ok) {
            throw new Error('Không thể tải chi tiết đơn hàng');
          }
          const data = await res.json();
          this.orderDetailItems = data.items || [];
        } catch (err) {
          console.error(err);
          this.showToast('Lỗi khi tải chi tiết đơn hàng: ' + err.message);
          this.orderDetailItems = [];
        } finally {
          this.loadingOrderDetail = false;
        }
      },

      paginatedOrders() {
        const start = (this.orderCurrentPage - 1) * this.orderPerPage;
        return this.detailOrders.slice(start, start + this.orderPerPage);
      },

      orderTotalPages() {
        return Math.max(1, Math.ceil(this.detailOrders.length / this.orderPerPage));
      },

      goToOrderPage(page) {
        if (page < 1) page = 1;
        if (page > this.orderTotalPages()) page = this.orderTotalPages();
        this.orderCurrentPage = page;
      },

      getStatusClass(status) {
        const statusMap = {
          'Chờ xác nhận': 'px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-semibold',
          'Đã xác nhận': 'px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold',
          'Đang giao': 'px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-semibold',
          'Hoàn tất': 'px-2 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold',
          'Đã hủy': 'px-2 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold'
        };
        return statusMap[status] || 'px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-semibold';
      },

      formatMoney(amount) {
        if (!amount) return '0';
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
      },

      generateChangePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        let len = Math.floor(Math.random() * 5) + 8; // 8-12 ký tự
        let pw = Array.from({ length: len }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
        this.formChangePassword.password = pw;
        this.formChangePassword.password_confirm = pw;
        this.showChangePassword = true;
        this.showChangePasswordConfirm = true;
        this.changePasswordTouched = true;
        this.changePasswordErrors = {};
      },

      paginated() {
        const start = (this.currentPage - 1) * this.perPage;
        return this.filtered().slice(start, start + this.perPage);
      },

      totalPages() {
        return Math.max(1, Math.ceil(this.filtered().length / this.perPage));
      },

      goToPage(page) {
        if (page < 1) page = 1;
        if (page > this.totalPages()) page = this.totalPages();
        this.currentPage = page;
      },

      // ===== FILTERS =====
      openFilter: {
        username: false, full_name: false, email: false, phone: false, gender: false, is_active: false, date_of_birth: false,
        created_at: false, created_by: false, updated_at: false, updated_by: false
      },

      filters: {
        username: '',
        full_name: '',
        email: '',
        phone: '',
        gender: '',
        is_active: '',
        date_of_birth_type: '', date_of_birth_value: '', date_of_birth_from: '', date_of_birth_to: '',
        created_at_type: '', created_at_value: '', created_at_from: '', created_at_to: '',
        created_by: '',
        updated_at_type: '', updated_at_value: '', updated_at_from: '', updated_at_to: '',
        updated_by: ''
      },

      // -------------------------------------------
      // Hàm lọc tổng quát, hỗ trợ text / number / date
      // -------------------------------------------
      applyFilter(val, type, { value, from, to, dataType }) {
        if (val == null) return false;

        // ---------------- TEXT ----------------
        if (dataType === 'text') {
          const hasAccent = (s) => /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(s);

          const normalize = (str) => String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // xóa dấu
            .trim();

          const raw = String(val || '').toLowerCase();
          const str = normalize(val);
          const query = String(value || '').toLowerCase();
          const queryNoAccent = normalize(value);

          if (!query) return true;

          if (type === 'eq') return hasAccent(query)
            ? raw === query  // có dấu → so đúng dấu
            : str === queryNoAccent; // không dấu → so không dấu

          if (type === 'contains' || type === 'like') {
            if (hasAccent(query)) {
              // Có dấu → tìm chính xác theo dấu
              return raw.includes(query);
            } else {
              // Không dấu → tìm theo không dấu
              return str.includes(queryNoAccent);
            }
          }

          return true;
        }

        // ---------------- NUMBER ----------------
        if (dataType === 'number') {
          const parseNum = (v) => {
            if (v === '' || v === null || v === undefined) return null;
            const s = String(v).replace(/[^\d.-]/g, '');
            const n = Number(s);
            return isNaN(n) ? null : n;
          };

          const num = parseNum(val);
          const v = parseNum(value);
          const f = parseNum(from);
          const t = parseNum(to);

          if (num === null) return false;
          if (!type) return true;

          if (type === 'eq') return v === null ? true : num === v;
          if (type === 'lt') return v === null ? true : num < v;
          if (type === 'gt') return v === null ? true : num > v;
          if (type === 'lte') return v === null ? true : num <= v;
          if (type === 'gte') return v === null ? true : num >= v;
          if (type === 'between') return f === null || t === null ? true : num >= f && num <= t;

          // --- Lọc “mờ” theo chuỗi số ---
          if (type === 'like') {
            const raw = String(val).replace(/[^\d]/g, '');
            const query = String(value || '').replace(/[^\d]/g, '');
            return raw.includes(query);
          }

          return true;
        }

        // ---------------- DATE ----------------
        if (dataType === 'date') {
          if (!val) return false;
          const d = new Date(val);
          const v = value ? new Date(value) : null;
          const f = from ? new Date(from) : null;
          const t = to ? new Date(to) : null;

          if (type === 'eq') return v ? d.toDateString() === v.toDateString() : true;
          if (type === 'lt') return v ? d < v : true;
          if (type === 'gt') {
            if (!v) return true;
            // So sánh chỉ theo ngày, bỏ qua giờ phút giây
            return d.setHours(0, 0, 0, 0) > v.setHours(0, 0, 0, 0);
          }
          if (type === 'lte') {
            if (!v) return true;
            const nextDay = new Date(v);
            nextDay.setDate(v.getDate() + 1);
            return d < nextDay; // <= nghĩa là nhỏ hơn ngày kế tiếp
          }
          if (type === 'gte') return v ? d >= v : true;
          if (type === 'between') return f && t ? d >= f && d <= t : true;

          return true;
        }

        return true;
      },

      filtered() {
        let data = this.items;

        // --- Lọc theo chuỗi ---
        ['username', 'full_name', 'created_by', 'email', 'updated_by', 'phone'].forEach(key => {
          if (this.filters[key]) {
            const field = key === 'created_by' ? 'created_by_name' : key;
            data = data.filter(o =>
              this.applyFilter(o[field], 'contains', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // --- Lọc theo select ---
        ['is_active', 'gender'].forEach(key => {
          if (this.filters[key]) {
            data = data.filter(o =>
              this.applyFilter(o[key], 'eq', {
                value: this.filters[key],
                dataType: 'text'
              })
            );
          }
        });

        // --- Lọc theo ngày ---
        ['date_of_birth', 'created_at', 'updated_at'].forEach(key => {
          if (this.filters[`${key}_type`]) {
            data = data.filter(o =>
              this.applyFilter(o[key], this.filters[`${key}_type`], {
                value: this.filters[`${key}_value`],
                from: this.filters[`${key}_from`],
                to: this.filters[`${key}_to`],
                dataType: 'date'
              })
            );
          }
        });

        return data;
      },

      toggleFilter(key) {
        for (const k in this.openFilter) this.openFilter[k] = false;
        this.openFilter[key] = true;
      },
      closeFilter(key) { this.openFilter[key] = false; },
      resetFilter(key) {
        if (['date_of_birth', 'updated_at', 'created_at'].includes(key)) {
          this.filters[`${key}_type`] = '';
          this.filters[`${key}_value`] = '';
          this.filters[`${key}_from`] = '';
          this.filters[`${key}_to`] = '';
        } else {
          this.filters[key] = '';
        }
        this.openFilter[key] = false;
      },

      exportExcel() {
        const data = this.filtered();

        if (data.length === 0) {
          this.showToast('Không có dữ liệu để xuất', 'error');
          return;
        }

        const now = new Date();
        const dateStr = now.toLocaleDateString('vi-VN').replace(/\//g, '-');
        const timeStr = now.toLocaleTimeString('vi-VN', { hour12: false }).replace(/:/g, '-');
        const filename = `Khach_hang_${dateStr}_${timeStr}.xlsx`;

        const exportData = {
          items: data.map(item => ({
            username: item.username || '',
            full_name: item.full_name || '',
            email: item.email || '',
            phone: item.phone || '',
            gender: item.gender,
            date_of_birth: this.formatDate(item.date_of_birth) || '',
            is_active: item.is_active,
            created_at: item.created_at || '',
            created_by_name: item.created_by_name || '',
            updated_at: item.updated_at || '',
            updated_by_name: item.updated_by_name || ''
          })),
          export_date: now.toLocaleDateString('vi-VN'),
          filename: filename
        };

        fetch('/admin/api/customers/export', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(exportData)
        })
          .then(response => {
            if (!response.ok) throw new Error('Export failed');
            return response.blob();
          })
          .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.showToast('Xuất file Excel thành công!', 'success');
          })
          .catch(e => {
            console.error('Export error:', e);
            this.showToast('Không thể xuất file Excel', 'error');
          });
      },

      async init() {
        await this.fetchAll();
      },

      async fetchAll() {
        this.loading = true;
        try {
          const resp = await fetch(api.list);
          if (!resp.ok) throw new Error('fetch_failed');
          const data = await resp.json();
          this.items = Array.isArray(data) ? data : (data.items || []);
        } catch (e) {
          console.error('Failed to load customers', e);
          this.showToast('Không thể tải danh sách khách hàng');
        } finally {
          this.loading = false;
        }
      },

      openCreate() {
        this.resetForm();
        this.openAdd = true;
      },

      openEditModal(customer) {
        this.resetForm();

        // Chuyển đổi date_of_birth từ yyyy-mm-dd sang dd/mm/yyyy để hiển thị
        let dateOfBirth = customer.date_of_birth || '';
        if (dateOfBirth && dateOfBirth !== '0000-00-00') {
          const parts = dateOfBirth.split('-');
          if (parts.length === 3) {
            const [year, month, day] = parts;
            dateOfBirth = `${day}/${month}/${year}`;
          }
        } else {
          dateOfBirth = '';
        }

        this.form = {
          ...customer,
          date_of_birth: dateOfBirth,
          is_active: String(customer.is_active ?? '1')  // ép về string để dropdown match
        };

        this.openEdit = true;
      },

      // reset validation/password state is handled in resetForm()
      // helper to clear a single field error
      clearError(field) { this.errors[field] = ''; },
      validateField(field) {
        const value = (this.form[field] || '').toString().trim();
        let msg = '';
        switch (field) {
          case 'username':
            if (!value) msg = 'Tài khoản không được để trống';
            else if (value.length < 6) msg = 'Tài khoản phải có ít nhất 6 ký tự';
            else if (value.length > 50) msg = 'Tài khoản tối đa 50 ký tự';
            else if (!/^[a-zA-Z_.]+$/.test(value)) msg = 'Tài khoản chỉ được chứa chữ cái không dấu, dấu gạch dưới _ hoặc dấu chấm .';
            break;
          case 'full_name':
            if (!value) msg = 'Họ tên không được để trống';
            else if (value.length < 3) msg = 'Họ tên phải có ít nhất 3 ký tự';
            break;
          case 'email':
            if (!value) msg = 'Email không được để trống';
            else if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) msg = 'Email không hợp lệ';
            break;
          case 'phone':
            if (!value) msg = 'Số điện thoại không được để trống';
            else if (value && !/^0\d{9,10}$/.test(value)) msg = 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số';
            break;
          case 'password':
            if (!this.form.id && !value) msg = 'Mật khẩu không được để trống';
            else if (value && value.length < 6) msg = 'Mật khẩu phải ít nhất 6 ký tự';
            break;
          case 'password_confirm':
            if (!this.form.id && (!value || value !== (this.form.password || ''))) msg = 'Mật khẩu không khớp';
            break;
        }
        this.errors[field] = msg;
        return msg === '';
      },

      generatePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        let len = Math.floor(Math.random() * 5) + 8; // 8-12
        let pw = Array.from({ length: len }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
        this.form.password = pw;
        this.form.password_confirm = pw;
        this.showPassword = true;
        this.showPasswordConfirm = true;
        this.touched.password = true;
        this.touched.password_confirm = true;
        this.clearError('password');
        this.clearError('password_confirm');
        this.validateField('password');
        this.validateField('password_confirm');
      },

      serializeForm() {
        // Chuyển đổi date_of_birth từ dd/mm/yyyy sang yyyy-mm-dd
        let dateOfBirth = (this.form.date_of_birth || '').trim();
        if (dateOfBirth) {
          // Kiểm tra format dd/mm/yyyy
          const parts = dateOfBirth.split('/');
          if (parts.length === 3) {
            const [day, month, year] = parts;
            // Chuyển sang yyyy-mm-dd
            dateOfBirth = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
          }
        }

        const out = {
          username: (this.form.username || '').trim(),
          full_name: (this.form.full_name || '').trim(),
          email: (this.form.email || '').trim(),
          phone: (this.form.phone || '').trim(),
          gender: this.form.gender || '',
          date_of_birth: dateOfBirth,
          is_active: Number(this.form.is_active ?? 1)
        };
        if (!this.form.id && this.form.password) out.password = this.form.password;
        return out;
      },

      validateForm(isCreate = false) {
        const payload = this.serializeForm();
        if (isCreate && payload.username === '') return 'Tài khoản không được để trống';
        if (payload.full_name === '') return 'Họ tên không được để trống';
        if (!payload.email) return 'Email không được để trống';
        if (payload.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email)) return 'Email không hợp lệ';
        if (payload.phone && !/^0\d{9,10}$/.test(payload.phone)) return 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số';
        if (isCreate) {
          const pw = (this.form.password || '').trim();
          const pw2 = (this.form.password_confirm || '').trim();
          if (!pw) return 'Mật khẩu không được để trống';
          if (pw.length < 6) return 'Mật khẩu phải ít nhất 6 ký tự';
          if (!pw2) return 'Vui lòng nhập lại mật khẩu';
          if (pw !== pw2) return 'Mật khẩu không khớp';
        }
        return '';
      },

      async submitChangePassword() {
        this.changePasswordTouched = true;
        if (!this.validateChangePassword()) return;
        this.submitting = true;
        try {
          const res = await fetch(`/admin/api/customers/${this.formChangePassword.user_id}/password`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: this.formChangePassword.password })
          });
          const data = await res.json();
          if (res.ok && data && data.ok) {
            this.openChangePassword = false;
            this.showToast('Đổi mật khẩu thành công!', 'success');
          } else {
            this.showToast((data && data.error) || 'Không thể đổi mật khẩu');
          }
        } catch (e) {
          this.showToast('Không thể đổi mật khẩu');
        } finally {
          this.submitting = false;
        }
      },

      validateChangePassword() {
        this.validateChangePasswordField('password');
        this.validateChangePasswordField('password_confirm');
        return Object.keys(this.changePasswordErrors).length === 0 ||
          (this.changePasswordErrors.password === '' && this.changePasswordErrors.password_confirm === '');
      },

      validateChangePasswordField(field) {
        const pw = (this.formChangePassword.password || '').trim();
        const pw2 = (this.formChangePassword.password_confirm || '').trim();
        if (field === 'password') {
          if (!pw) this.changePasswordErrors.password = 'Mật khẩu không được để trống';
          else if (pw.length < 8) this.changePasswordErrors.password = 'Mật khẩu phải ít nhất 8 ký tự';
          else this.changePasswordErrors.password = '';
          // Also revalidate confirm if already filled
          if (pw2) this.validateChangePasswordField('password_confirm');
        } else if (field === 'password_confirm') {
          if (!pw2) this.changePasswordErrors.password_confirm = 'Vui lòng nhập lại mật khẩu';
          else if (pw !== pw2) this.changePasswordErrors.password_confirm = 'Mật khẩu không khớp';
          else this.changePasswordErrors.password_confirm = '';
        }
      },

      async submitCreate() {
        const error = this.validateForm(true);
        if (error) {
          this.showToast(error);
          return;
        }

        this.submitting = true;
        try {
          const resp = await fetch(api.create, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.serializeForm())
          });
          const data = await resp.json().catch(() => ({}));
          if (resp.ok) {
            await this.fetchAll();
            this.openAdd = false;
            this.showToast('Thêm khách hàng thành công', 'success');
          } else {
            this.showToast(data.error || 'Không thể thêm khách hàng');
          }
        } catch (e) {
          console.error(e);
          this.showToast('Không thể thêm khách hàng');
        } finally {
          this.submitting = false;
        }
      },

      async submitUpdate() {
        const error = this.validateForm(false);
        if (error) {
          this.showToast(error);
          return;
        }

        const id = this.form.id;
        if (!id) {
          this.showToast('Không xác định được khách hàng cần cập nhật');
          return;
        }

        this.submitting = true;
        try {
          const payload = {
            ...this.serializeForm(),
            is_active: Number(this.form.is_active ?? 1) // ép về số khi gửi API
          };

          const resp = await fetch(api.update(id), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });

          const data = await resp.json().catch(() => ({}));
          if (resp.ok) {
            const idx = this.items.findIndex(item => Number(item.id) === Number(id));
            if (idx !== -1 && data) {
              this.items.splice(idx, 1, data);
            } else {
              await this.fetchAll();
            }
            this.openEdit = false;
            this.showToast('Cập nhật khách hàng thành công', 'success');
          } else {
            this.showToast(data.error || 'Không thể cập nhật khách hàng');
          }
        } catch (e) {
          console.error(e);
          this.showToast('Không thể cập nhật khách hàng');
        } finally {
          this.submitting = false;
        }
      },

      async remove(id) {
        if (!confirm('Xóa khách hàng này?')) return;
        try {
          const resp = await fetch(api.remove(id), { method: 'DELETE' });
          const data = await resp.json().catch(() => ({}));
          if (resp.ok) {
            this.items = this.items.filter(item => Number(item.id) !== Number(id));
            this.showToast('Đã xoá khách hàng', 'success');
          } else {
            this.showToast(data.error || 'Không thể xoá khách hàng');
          }
        } catch (e) {
          console.error(e);
          this.showToast('Không thể xoá khách hàng');
        }
      },

      resetForm() {
        this.form = {
          id: null,
          username: '',
          full_name: '',
          email: '',
          phone: '',
          gender: '',
          date_of_birth: '',
          is_active: '1'
        };
        this.errors = {
          username: '',
          full_name: '',
          email: '',
          phone: '',
          password: '',
          password_confirm: ''
        };
        this.touched = {
          username: false,
          full_name: false,
          email: false,
          phone: false,
          password: false,
          password_confirm: false
        };
      },

      // ===== toast =====
      showToast(msg, type = 'error') {
        const box = document.getElementById('toast-container');
        if (!box) return;
        box.innerHTML = '';

        const toast = document.createElement('div');

        // Xác định màu sắc theo type
        let colorClasses = '';
        let iconColor = '';
        let iconSvg = '';

        if (type === 'success') {
          colorClasses = 'text-green-700 border-green-400';
          iconColor = 'text-green-600';
          iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`;
        } else if (type === 'warning') {
          colorClasses = 'text-yellow-700 border-yellow-400';
          iconColor = 'text-yellow-600';
          iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 12a7 7 0 1114 0 7 7 0 01-14 0z" />`;
        } else {
          colorClasses = 'text-red-700 border-red-400';
          iconColor = 'text-red-600';
          iconSvg = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />`;
        }

        toast.className = `fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold ${colorClasses} bg-white rounded-xl shadow-lg border-2`;

        toast.innerHTML = `
            <svg class="flex-shrink-0 w-6 h-6 ${iconColor} mr-3" 
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              ${iconSvg}
            </svg>
            <div class="flex-1">${msg}</div>
          `;

        box.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
      },

      // ===== IMPORT EXCEL =====
      showImportModal: false,
      importFile: null,
      importing: false,

      openImportModal() {
        this.showImportModal = true;
        this.importFile = null;
      },

      handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) {
          this.importFile = null;
          return;
        }

        // 1. Kiểm tra định dạng file
        const allowedExtensions = ['.xls', '.xlsx'];
        const fileName = file.name.toLowerCase();
        const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

        if (!isValidExtension) {
          this.showToast('Chỉ chấp nhận file Excel (.xls, .xlsx)');
          e.target.value = '';
          return;
        }

        // 2. Kiểm tra kích thước file (max 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
          this.showToast('File không được vượt quá 5MB');
          e.target.value = '';
          return;
        }

        // 3. Kiểm tra độ dài tên file
        if (file.name.length > 255) {
          this.showToast('Tên file quá dài (tối đa 255 ký tự)');
          e.target.value = '';
          return;
        }

        // 4. Kiểm tra ký tự đặc biệt trong tên file
        const specialCharsRegex = /[<>:"|?*]/;
        if (specialCharsRegex.test(file.name)) {
          this.showToast('Tên file chứa ký tự không hợp lệ (< > : " | ? *)');
          e.target.value = '';
          return;
        }

        this.importFile = file;
      },

      clearFile() {
        this.importFile = null;
        if (this.$refs.fileInput) {
          this.$refs.fileInput.value = '';
        }
      },

      downloadTemplate() {
        window.location.href = '/admin/api/customers/template';
      },

      async submitImport() {
        if (!this.importFile) {
          this.showToast('Vui lòng chọn file Excel');
          return;
        }

        this.importing = true;
        const formData = new FormData();
        formData.append('file', this.importFile);

        try {
          const res = await fetch('/admin/api/customers/import', {
            method: 'POST',
            body: formData
          });

          // Log response for debugging
          console.log('Response status:', res.status);
          console.log('Response headers:', res.headers.get('content-type'));

          // Get text first to see what we're getting
          const text = await res.text();
          console.log('Raw response:', text.substring(0, 500)); // First 500 chars

          let data;
          try {
            data = JSON.parse(text);
          } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            this.showToast('Lỗi: Server trả về dữ liệu không hợp lệ. Kiểm tra console để xem chi tiết.');
            return;
          }

          if (data.success) {
            // Chọn màu toast theo status
            let toastType = 'success';
            if (data.status === 'failed') toastType = 'error';
            else if (data.status === 'partial') toastType = 'warning';

            this.showToast(data.message || 'Nhập Excel thành công!', toastType);
            this.showImportModal = false;
            this.importFile = null;
            await this.init();
          } else {
            let errorMsg = data.message || 'Có lỗi xảy ra';
            if (data.detail) {
              console.error('Error detail:', data.detail);
              errorMsg += ' (Xem console để biết chi tiết)';
            }
            this.showToast(errorMsg, 'error');
          }
        } catch (err) {
          console.error('Fetch error:', err);
          this.showToast('Lỗi kết nối server: ' + err.message);
        } finally {
          this.importing = false;
        }
      }
    };
  }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>