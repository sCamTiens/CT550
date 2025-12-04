<!-- Filter Modal -->
<div x-show="showFilterModal" x-cloak @click.self="showFilterModal = false"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <!-- Header -->
        <div class="sticky top-0 bg-gradient-to-r from-[#002975] to-[#0043ad] 
        text-white p-6 flex items-center justify-between z-10">
            <h3 class="text-2xl font-bold">
                Lọc sản phẩm
            </h3>
            <button @click="showFilterModal = false" class="text-white hover:text-gray-200">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-6">
            <!-- Price Range -->
            <div>
                <h4 class="font-bold text-lg mb-3 text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-dollar-sign text-[#002975]"></i>
                    Khoảng giá
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Từ (₫)</label>
                        <input type="number" x-model.number="filters.min_price" placeholder="0" class="w-full px-4 py-2 border-2 border-gray-300 
                        rounded-lg focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Đến (₫)</label>
                        <input type="number" x-model.number="filters.max_price" placeholder="1000000" class="w-full px-4 py-2 border-2 border-gray-300 
                        rounded-lg focus:border-[#002975] focus:ring-2 focus:ring-[#002975] focus:ring-opacity-20">
                    </div>
                </div>
            </div>

            <!-- Brands -->
            <div>
                <h4 class="font-bold text-lg mb-3 text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-copyright text-[#002975]"></i>
                    Thương hiệu
                </h4>
                <div class="grid grid-cols-2 gap-3 max-h-60 overflow-y-auto p-2 border-2 border-gray-200 rounded-lg">
                    <?php foreach ($allBrands as $brand): ?>
                        <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input type="checkbox" value="<?= $brand->id ?>" @change="toggleBrand(<?= $brand->id ?>)"
                                class="w-5 h-5 text-[#002975] rounded border-gray-300 focus:ring-2 focus:ring-[#002975]">
                            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($brand->name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sort Options -->
            <div>
                <h4 class="font-bold text-lg mb-3 text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-sort text-[#002975]"></i>
                    Sắp xếp
                </h4>
                <div class="space-y-2">
                    <label
                        class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="filters.sort === 'newest' ? 'border-[#002975] bg-blue-50' : 'border-gray-200'">
                        <input type="radio" name="sort" value="newest" x-model="filters.sort"
                            class="w-5 h-5 text-[#002975] focus:ring-2 focus:ring-[#002975]">
                        <span class="font-medium">Mới nhất</span>
                    </label>
                    <label
                        class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="filters.sort === 'price_asc' ? 'border-[#002975] bg-blue-50' : 'border-gray-200'">
                        <input type="radio" name="sort" value="price_asc" x-model="filters.sort"
                            class="w-5 h-5 text-[#002975] focus:ring-2 focus:ring-[#002975]">
                        <span class="font-medium">Giá thấp → cao</span>
                    </label>
                    <label
                        class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="filters.sort === 'price_desc' ? 'border-[#002975] bg-blue-50' : 'border-gray-200'">
                        <input type="radio" name="sort" value="price_desc" x-model="filters.sort"
                            class="w-5 h-5 text-[#002975] focus:ring-2 focus:ring-[#002975]">
                        <span class="font-medium">Giá cao → thấp</span>
                    </label>
                    <label
                        class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="filters.sort === 'best_selling' ? 'border-[#002975] bg-blue-50' : 'border-gray-200'">
                        <input type="radio" name="sort" value="best_selling" x-model="filters.sort"
                            class="w-5 h-5 text-[#002975] focus:ring-2 focus:ring-[#002975]">
                        <span class="font-medium">Bán chạy</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 bg-gray-50 p-6 flex gap-3 border-t">
            <button @click="resetFilters()"
                class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                <i class="fa-solid fa-rotate-left mr-2"></i>
                Đặt lại
            </button>
            <button @click="applyFilters()"
                class="flex-1 px-6 py-3 bg-[#002975] text-white rounded-lg hover:bg-[#001a54] transition-colors font-semibold">
                <i class="fa-solid fa-check mr-2"></i>
                Áp dụng
            </button>
        </div>
    </div>
</div>