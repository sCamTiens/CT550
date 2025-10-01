<?php
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('view_path')) {
    function view_path(string $rel): string
    {
        return dirname(__DIR__, 2) . '/views/' . ltrim($rel, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): void
    {
        header('Location: ' . $to);
        exit;
    }
}

/**
 * Helper: thêm thuộc tính maxlength cho input/textarea
 * @param int $len giới hạn ký tự (mặc định 255)
 * @return string ví dụ: maxlength="255"
 */
if (!function_exists('input_attr_maxlength')) {
    function input_attr_maxlength(int $len = 255): string
    {
        return 'maxlength="' . $len . '"';
    }
}


// Helper: render filter text input (ví dụ: name, slug, created_by...)
if (!function_exists('textFilterPopover')) {
    /**
     * Render filter text input (ví dụ: name, slug, created_by...)
     */
    function textFilterPopover(string $key, string $label): string
    {
        return <<<HTML
<th class="py-2 px-4 relative">
  <div class="flex items-center gap-2">
    <span>{$label}</span>
    <button @click.stop="toggleFilter('{$key}')" class="p-1 rounded hover:bg-gray-100" title="Tìm theo {$label}">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
      </svg>
    </button>
  </div>

  <div x-show="openFilter.{$key}" x-transition @click.outside="openFilter.{$key}=false"
       class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow border p-3">
    <div class="font-semibold mb-2">Tìm kiếm theo "{$label}"</div>
    <input x-model.trim="filters.{$key}" class="w-full border rounded px-3 py-2" placeholder="Nhập {$label}">
    <div class="mt-3 flex gap-2">
      <button @click="applyFilter('{$key}')" class="px-3 py-1 rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
      <button @click="resetFilter('{$key}')" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm mới</button>
      <button @click="openFilter.{$key}=false" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
    </div>
  </div>
</th>
HTML;
    }
}

if (!function_exists('numberFilterPopover')) {
    /**
     * Render filter số (ví dụ sort_order)
     */
    function numberFilterPopover(string $key, string $label): string
    {
        return <<<HTML
<th class="py-2 px-4 relative">
  <div class="flex items-center gap-2">
    <span>{$label}</span>
    <button @click.stop="toggleFilter('{$key}')" class="p-1 rounded hover:bg-gray-100" title="Tìm theo {$label}">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
      </svg>
    </button>
  </div>

  <div x-show="openFilter.{$key}" x-transition @click.outside="openFilter.{$key}=false"
       class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow border p-3">
    <div class="font-semibold mb-2">Tìm kiếm theo "{$label}"</div>
    <input type="number" x-model.number="filters.{$key}" class="w-full border rounded px-3 py-2" placeholder="Nhập số">
    <div class="mt-3 flex gap-2">
      <button @click="applyFilter('{$key}')" class="px-3 py-1 rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
      <button @click="resetFilter('{$key}')" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm mới</button>
      <button @click="openFilter.{$key}=false" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
    </div>
  </div>
</th>
HTML;
    }
}

// Helper: render filter theo ngày (có dropdown loại lọc + input date)
if (!function_exists('dateFilterPopover')) {
    /**
     * Render filter theo ngày (có dropdown loại lọc + input date)
     *
     * @param string $key    Tên filter (vd: created_at, updated_at)
     * @param string $label  Label hiển thị
     * @return string
     */
    function dateFilterPopover($key, $label)
    {
        return <<<HTML
<th class="py-2 px-4 relative">
  <div class="flex items-center gap-2">
    <span>{$label}</span>
    <button @click.stop="toggleFilter('{$key}')" 
            class="p-1 rounded hover:bg-gray-100" title="Lọc theo {$label}">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
      </svg>
    </button>
  </div>

  <div x-show="openFilter.{$key}" x-transition @click.outside="openFilter.{$key}=false"
       class="absolute z-40 mt-2 w-72 bg-white rounded-lg shadow border p-3 space-y-3">
    <div class="font-semibold mb-1">Tìm theo "{$label}"</div>

    <select x-model="filters.{$key}_type" class="w-full border rounded px-3 py-2">
      <option value="">-- Chọn kiểu lọc --</option>
      <option value="eq">Ngày</option>
      <option value="between">Từ ngày đến ngày</option>
      <option value="lt">Nhỏ hơn</option>
      <option value="gt">Lớn hơn</option>
      <option value="lte">Nhỏ hơn hoặc bằng</option>
      <option value="gte">Lớn hơn hoặc bằng</option>
    </select>

    <div x-show="filters.{$key}_type==='eq'">
      <input type="date" x-model="filters.{$key}_value" class="w-full border rounded px-3 py-2">
    </div>

    <div x-show="filters.{$key}_type==='between'" class="flex gap-2">
      <input type="date" x-model="filters.{$key}_from" :max="filters.{$key}_to || null"
             class="flex-1 border rounded px-3 py-2">
      <input type="date" x-model="filters.{$key}_to" :min="filters.{$key}_from || null"
             class="flex-1 border rounded px-3 py-2">
    </div>

    <div x-show="['lt','gt','lte','gte'].includes(filters.{$key}_type)">
      <input type="date" x-model="filters.{$key}_value" class="w-full border rounded px-3 py-2">
    </div>

    <div class="flex gap-2 justify-end">
      <button @click="applyFilter('{$key}')" class="px-3 py-1 rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
      <button @click="resetFilter('{$key}')" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm mới</button>
      <button @click="openFilter.{$key}=false" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
    </div>
  </div>
</th>
HTML;
    }
}

// Helper: render filter dạng select (dropdown)
if (!function_exists('selectFilterPopover')) {
    /**
     * Render filter dạng select (dropdown)
     *
     * @param string $key    Tên filter (vd: status, role_id)
     * @param string $label  Label hiển thị
     * @param array  $options Mảng option (value => text)
     * @return string
     */
    function selectFilterPopover(string $key, string $label, array $options): string
    {
        // build options html
        $optHtml = '';
        foreach ($options as $val => $text) {
            $optHtml .= "<option value=\"{$val}\">{$text}</option>";
        }

        return <<<HTML
<th class="py-2 px-4 relative">
  <div class="flex items-center gap-2">
    <span>{$label}</span>
    <button @click.stop="toggleFilter('{$key}')"
            class="p-1 rounded hover:bg-gray-100" title="Lọc theo {$label}">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
      </svg>
    </button>
  </div>

  <div x-show="openFilter.{$key}" x-transition @click.outside="openFilter.{$key}=false"
       class="absolute z-40 mt-2 w-64 bg-white rounded-lg shadow border p-3 space-y-3">
    <div class="font-semibold mb-1">Tìm theo "{$label}"</div>

    <select x-model="filters.{$key}" class="w-full border rounded px-3 py-2">
      {$optHtml}
    </select>

    <div class="flex gap-2 justify-end">
      <button @click="applyFilter('{$key}')" class="px-3 py-1 rounded bg-[#002975] text-white hover:opacity-90">Tìm</button>
      <button @click="resetFilter('{$key}')" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Làm mới</button>
      <button @click="openFilter.{$key}=false" class="px-3 py-1 rounded border border-[#002975] text-[#002975] hover:bg-[#002975] hover:text-white">Đóng</button>
    </div>
  </div>
</th>
HTML;
    }
}

// Phân trang
if (!function_exists('pagination')) {
    /**
     * Render phân trang
     *
     * @param int $total Tổng số bản ghi
     * @param int $perPage Số bản ghi mỗi trang
     * @param int $current Trang hiện tại
     * @param string $baseUrl URL cơ bản (vd: /admin/categories?page=)
     * @return string HTML phân trang
     */
    function pagination(int $total, int $perPage, int $current = 1, string $baseUrl = '?page='): string
    {
        $totalPages = max(1, ceil($total / $perPage));
        if ($totalPages <= 1)
            return ''; // không cần phân trang

        $html = '<nav class="mt-4 flex justify-center">';
        $html .= '<ul class="inline-flex items-center -space-x-px">';

        // Nút prev
        $prev = max(1, $current - 1);
        $disabledPrev = $current <= 1 ? 'opacity-50 pointer-events-none' : '';
        $html .= '<li><a href="' . $baseUrl . $prev . '" 
            class="px-3 py-1 border rounded-l ' . $disabledPrev . '">«</a></li>';

        // Trang số
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i == $current ? 'bg-[#002975] text-white font-semibold' : 'hover:bg-gray-100';
            $html .= '<li><a href="' . $baseUrl . $i . '" 
                class="px-3 py-1 border ' . $active . '">' . $i . '</a></li>';
        }

        // Nút next
        $next = min($totalPages, $current + 1);
        $disabledNext = $current >= $totalPages ? 'opacity-50 pointer-events-none' : '';
        $html .= '<li><a href="' . $baseUrl . $next . '" 
            class="px-3 py-1 border rounded-r ' . $disabledNext . '">»</a></li>';

        $html .= '</ul></nav>';

        return $html;
    }
}
