<?php
$items = $items ?? [];
?>
<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<nav class="text-sm text-slate-500 mb-4">
    Admin / Danh mục hệ thống / <span class="text-slate-800 font-medium">Đơn vị tính</span>
</nav>

<div x-data="unitPage()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#002975]">Quản lý đơn vị tính</h1>
        <button
            class="px-3 py-2 rounded-lg text-[#002975] hover:bg-[#002975] hover:text-white font-semibold border border-[#002975]"
            @click="openCreate()">+ Thêm đơn vị</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto pb-40">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-slate-600">
                    <th class="py-2 px-4">Thao tác</th>
                    <th class="py-2 px-4">Tên</th>
                    <th class="py-2 px-4">Slug</th>
                    <th class="py-2 px-4">Ngày tạo</th>
                    <th class="py-2 px-4">Người tạo</th>
                    <th class="py-2 px-4">Cập nhật</th>
                    <th class="py-2 px-4">Người cập nhật</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="u in items" :key="u.id">
                    <tr class="border-t">
                        <td class="py-2 px-4 space-x-2">
                            <button @click="openEdit(u)" class="p-2 rounded hover:bg-gray-100 text-[#002975]">✎</button>
                            <button @click="remove(u.id)" class="p-2 rounded hover:bg-gray-100 text-red-600">🗑</button>
                        </td>
                        <td class="py-2 px-4" x-text="u.name"></td>
                        <td class="py-2 px-4" x-text="u.slug"></td>
                        <td class="py-2 px-4" x-text="u.created_at"></td>
                        <td class="py-2 px-4" x-text="u.created_by || '—'"></td>
                        <td class="py-2 px-4" x-text="u.updated_at || '—'"></td>
                        <td class="py-2 px-4" x-text="u.updated_by || '—'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Modal Create -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openCreateModal"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-lg rounded-xl shadow p-5" @click.outside="openCreateModal=false">
            <h3 class="font-semibold text-xl mb-4">Thêm đơn vị</h3>
            <form @submit.prevent="submitCreate">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm mb-1">Tên đơn vị <span class="text-red-500">*</span></label>
                        <input x-model="createForm.name" @input="onCreateNameInput"
                            class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Slug <span class="text-red-500">*</span></label>
                        <input x-model="createForm.slug" class="w-full border rounded px-3 py-2" required>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 border rounded" @click="openCreateModal=false">Đóng</button>
                    <button class="px-4 py-2 bg-[#002975] text-white rounded" :disabled="submitting"
                        x-text="submitting ? 'Đang lưu...' : 'Lưu'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" x-show="openEditModal"
        x-transition.opacity style="display:none">
        <div class="bg-white w-full max-w-lg rounded-xl shadow p-5" @click.outside="openEditModal=false">
            <h3 class="font-semibold text-xl mb-4">Sửa đơn vị</h3>
            <form @submit.prevent="submitEdit">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm mb-1">Tên đơn vị <span class="text-red-500">*</span></label>
                        <input x-model="editForm.name" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Slug <span class="text-red-500">*</span></label>
                        <input x-model="editForm.slug" class="w-full border rounded px-3 py-2" required>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 border rounded" @click="openEditModal=false">Đóng</button>
                    <button class="px-4 py-2 bg-[#002975] text-white rounded" :disabled="submitting"
                        x-text="submitting ? 'Đang lưu...' : 'Lưu thay đổi'"></button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>
</div>

<script>
    function unitPage() {
        const api = {
            list: '/admin/api/units',
            create: '/admin/units',
            update: id => `/admin/units/${id}`,
            remove: id => `/admin/units/${id}`
        };

        return {
            items: <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>,
            submitting: false,

            // Modal state
            openCreateModal: false,
            openEditModal: false,

            // Forms
            createForm: { name: '', slug: '' },
            editForm: { id: null, name: '', slug: '' },

            init() { this.fetchAll(); },
            async fetchAll() {
                const r = await fetch(api.list);
                if (r.ok) {
                    const data = await r.json();
                    this.items = data.items || [];
                }
            },

            // Auto slug cho create
            onCreateNameInput() {
                this.createForm.slug = this.createForm.name.toLowerCase().replace(/\s+/g, '-');
            },

            // Open modals
            openCreate() {
                this.createForm = { name: '', slug: '' };
                this.openCreateModal = true;
            },
            openEdit(u) {
                this.editForm = { ...u };
                this.openEditModal = true;
            },

            // Submit Create
            async submitCreate() {
                this.submitting = true;
                try {
                    const r = await fetch(api.create, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.createForm)
                    });
                    const res = await r.json();
                    if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ');
                    this.items.unshift(res);
                    this.openCreateModal = false;
                    this.showToast('Thêm đơn vị thành công!', 'success');
                } catch (e) {
                    this.showToast(e.message, 'error');
                } finally { this.submitting = false; }
            },

            // Submit Edit
            async submitEdit() {
                this.submitting = true;
                try {
                    const r = await fetch(api.update(this.editForm.id), {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.editForm)
                    });
                    const res = await r.json();
                    if (!r.ok) throw new Error(res.error || 'Lỗi máy chủ');

                    const i = this.items.findIndex(x => x.id == res.id);
                    if (i > -1) this.items[i] = res;
                    this.openEditModal = false;
                    this.showToast('Cập nhật thành công!', 'success');
                } catch (e) {
                    this.showToast(e.message, 'error');
                } finally { this.submitting = false; }
            },

            async remove(id) {
                if (!confirm('Xóa đơn vị này?')) return;
                try {
                    const r = await fetch(api.remove(id), { method: 'DELETE' });
                    const res = await r.json();
                    if (!r.ok) throw new Error(res.error || 'Lỗi khi xóa');
                    this.items = this.items.filter(x => x.id != id);
                    this.showToast('Xóa đơn vị thành công!', 'success');
                } catch (e) {
                    this.showToast(e.message, 'error');
                }
            },

            showToast(msg, type = 'success') {
                const box = document.getElementById('toast-container');
                box.innerHTML = `
        <div class="fixed top-5 right-5 bg-white border-2 ${type === 'success'
                        ? 'border-green-400 text-green-700'
                        : 'border-red-400 text-red-700'} 
        px-4 py-3 rounded shadow">${msg}</div>`;
                setTimeout(() => box.innerHTML = '', 3000);
            }
        }
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>