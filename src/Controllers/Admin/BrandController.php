<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\BrandRepository;

class BrandController extends BaseAdminController
{
    private $brandRepo;

    public function __construct()
    {
        parent::__construct(); // Gọi constructor của BaseAdminController
        $this->brandRepo = new BrandRepository();
    }
    /** GET /admin/brands (view) */
    public function index()
    {
        return $this->view('admin/brands/brand');
    }

    /** GET /admin/api/brands (list JSON) */
    public function apiIndex()
    {
        $rows = $this->brandRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/brands (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        // Validate dữ liệu
        if ($name === '' || mb_strlen($name) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và ≤ 190 ký tự']);
            exit;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        if ($slug !== null && mb_strlen($slug) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Slug không vượt quá 190 ký tự']);
            exit;
        }

        try {
            $brand = $this->brandRepo->create($name, $slug, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->entityToArray($brand), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Lỗi máy chủ khi tạo thương hiệu']);
            }
            exit;
        }
    }

    /** PUT /admin/brands/{id} (update) */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $currentUser = $this->currentUserId();

        // Validate dữ liệu
        if ($name === '' || mb_strlen($name) > 190) {
            http_response_code(422);
            echo json_encode(['error' => 'Tên là bắt buộc và ≤ 190 ký tự']);
            exit;
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        try {
            $brand = $this->brandRepo->update($id, $name, $slug, $currentUser);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->entityToArray($brand), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'Tên hoặc slug đã tồn tại']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Lỗi máy chủ khi cập nhật thương hiệu']);
            }
            exit;
        }
    }

    /** DELETE /admin/brands/{id} */
    public function destroy($id)
    {
        // Kiểm tra ràng buộc: nếu thương hiệu đã có sản phẩm thì không cho xóa
        if ($this->brandHasProducts($id)) {
            http_response_code(409);
            echo json_encode(['error' => 'Không thể xóa, thương hiệu đang bị ràng buộc với sản phẩm.']);
            exit;
        }
        try {
            $this->brandRepo->delete($id);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi máy chủ khi xóa thương hiệu']);
        }
        exit;
    }

    // Helper: fallback nếu chưa có canDelete trong BrandRepository
    private function brandHasProducts($id)
    {
        $pdo = \App\Core\DB::pdo();
        $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
        $count->execute([$id]);
        return $count->fetchColumn() > 0;
    }

    // ====== Helper Methods ======

    /** Convert Brand entity or array to plain array */
    private function entityToArray($brand)
    {
        if (is_array($brand)) {
            return array_map([$this, 'entityToArray'], $brand);
        }
        if (!is_object($brand)) {
            return $brand;
        }
        return get_object_vars($brand);
    }

    /** Chuyển text thành slug */
    private function slugify($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
        $text = preg_replace('~\p{Mn}+~u', '', $text);
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = trim($text, '-');
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);
        return mb_substr($text, 0, 190) ?: null;
    }

    /** Lấy ID user hiện tại từ session */
    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
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
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Ngày xuất file
        $sheet->setCellValue('A2', 'Ngày xuất file: ' . $vietnamTime->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Tiêu đề
        $sheet->setCellValue('A3', 'DANH SÁCH THƯƠNG HIỆU');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['STT', 'Tên thương hiệu', 'Slug', 'Thời gian tạo', 'Người tạo', 'Thời gian cập nhật', 'Người cập nhật'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }
        $sheet->getStyle('A5:G5')->getFont()->setBold(true);
        $sheet->getStyle('A5:G5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2EFDA');

        // Data
        $row = 6;
        $stt = 1;
        foreach ($items as $b) {
            $sheet->setCellValue('A' . $row, $stt++);
            $sheet->setCellValue('B' . $row, $b['name'] ?? '');
            $sheet->setCellValue('C' . $row, $b['slug'] ?? '');
            $sheet->setCellValue('D' . $row, $b['created_at'] ?? '');
            $sheet->setCellValue('E' . $row, $b['created_by_name'] ?? '');
            $sheet->setCellValue('F' . $row, $b['updated_at'] ?? '');
            $sheet->setCellValue('G' . $row, $b['updated_by_name'] ?? '');
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
        $sheet->getStyle('A5:G' . $lastRow)->applyFromArray($styleArray);

        // Auto-size columns
        foreach (range('A', 'G') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="Thuong_hieu.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
