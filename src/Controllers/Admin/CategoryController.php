<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\CategoryRepository;

class CategoryController extends BaseAdminController
{
    private $categoryRepo;

    public function __construct()
    {
        parent::__construct();
        $this->categoryRepo = new CategoryRepository();
    }
    /** GET /admin/categories (view) */
    public function index()
    {
        return $this->view('admin/categories/category');
    }

    /** GET /admin/api/categories (list JSON) */
    public function apiIndex()
    {
    header('Content-Type: application/json; charset=utf-8');
    $rows = $this->categoryRepo->all();
    echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
    }

    /** POST /admin/categories (create) */
    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $parent_id = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $sort_order = (int) ($data['sort_order'] ?? 0);
        $is_active = !empty($data['is_active']) ? 1 : 0;
        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();

        if ($name === '' || mb_strlen($name) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và không vượt quá 250 ký tự']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        if ($slug !== null && mb_strlen($slug) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Slug không vượt quá 250 ký tự']);
            exit;
        }

        try {
            $id = $this->categoryRepo->create([
                'name' => $name,
                'slug' => $slug ?: null,
                'parent_id' => $parent_id,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
                'created_by' => $currentUserId,
                'updated_by' => $currentUserId,
            ]);
            $row = $this->categoryRepo->find($id);
            $row['created_by_name'] = $currentUserName;
            $row['updated_by_name'] = $currentUserName;
            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi tạo loại sản phẩm']);
            exit;
        }
    }

    /** PUT /admin/categories/{id} (update) */
    public function update($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $parent_id = array_key_exists('parent_id', $data) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $sort_order = (int) ($data['sort_order'] ?? 0);
        $is_active = !empty($data['is_active']) ? 1 : 0;
        $currentUserId = $this->currentUserId();
        $currentUserName = $this->currentUserName();

        if ($name === '' || mb_strlen($name) > 250) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và không vượt quá 250 ký tự']);
            exit;
        }
        if ((string) $parent_id === (string) $id) {
            http_response_code(422);
            echo json_encode(['error' => 'Loại cha không thể là chính nó']);
            exit;
        }
        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $this->categoryRepo->update($id, [
                'name' => $name,
                'slug' => $slug ?: null,
                'parent_id' => $parent_id,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
                'updated_by' => $currentUserId,
            ]);
            $row = $this->categoryRepo->find($id);
            $row['updated_by_name'] = $currentUserName;
            echo json_encode($row, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Dữ liệu bị trùng (slug đã tồn tại)']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật loại sản phẩm']);
            exit;
        }
    }

    /** DELETE /admin/categories/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $canDelete = $this->categoryRepo->canDelete($id);
        if ($canDelete === 'parent') {
            http_response_code(409);
            echo json_encode(['error' => 'Không thể xoá: đang là loại cha của mục khác']);
            exit;
        }
        if ($canDelete === 'product') {
            http_response_code(409);
            echo json_encode(['error' => 'Không thể xoá: loại sản phẩm đang ràng buộc với sản phẩm']);
            exit;
        }
        try {
            $this->categoryRepo->delete($id);
            echo json_encode(['ok' => true]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Lỗi máy chủ khi xoá',
                'detail' => $e->getMessage(),
                'code' => $e->getCode(),
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** Helper: lấy 1 bản ghi */
    private function findOne($id)
    {
        $pdo = \App\Core\DB::pdo();
        $st = $pdo->prepare("SELECT c.id, c.name, c.slug, c.parent_id, c.sort_order, c.is_active, 
                                    c.created_at, c.updated_at,
                                    c.created_by, cu.full_name AS created_by_name,
                                    c.updated_by, uu.full_name AS updated_by_name
                             FROM categories c
                             LEFT JOIN users cu ON cu.id = c.created_by
                             LEFT JOIN users uu ON uu.id = c.updated_by
                             WHERE c.id=?");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC);
    }

    private function slugify($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        if (class_exists('\Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
        }
        $text = preg_replace('~\p{Mn}+~u', '', $text);
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);
        return mb_substr($text, 0, 250) ?: null;
    }

    private function currentUserId(): ?int
    {
        return $_SESSION['admin_user']['id'] ?? null;
    }

    private function currentUserName(): ?string
    {
        return $_SESSION['admin_user']['full_name'] ?? null;
    }

    public function export()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $items = $data['items'] ?? [];

        require_once __DIR__ . '/../../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set timezone to Vietnam
        $vietnamTime = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        // Header MINIGO
        $sheet->setCellValue('A1', 'MINIGO');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A3', 'DANH SÁCH LOẠI SẢN PHẨM');
        $sheet->mergeCells('A3:I3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Tên loại', 'Slug', 'Loại cha', 'Trạng thái', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }
        $sheet->getStyle('A5:I5')->getFont()->setBold(true);
        $sheet->getStyle('A5:I5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 6;
        $stt = 1;
        foreach ($items as $c) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $c['name'] ?? '');
            $sheet->setCellValue('C' . $row, $c['slug'] ?? '');
            $sheet->setCellValue('D' . $row, $c['parent'] ?? '');
            $sheet->setCellValue('E' . $row, $c['is_active'] ?? '');
            $sheet->setCellValue('F' . $row, $c['created_at'] ?? '');
            $sheet->setCellValue('G' . $row, $c['created_by_name'] ?? '');
            $sheet->setCellValue('H' . $row, $c['updated_at'] ?? '');
            $sheet->setCellValue('I' . $row, $c['updated_by_name'] ?? '');
            $row++;
        }

        $lastRow = $row - 1;

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A5:I' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Loai_san_pham.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

}
