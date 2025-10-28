<?php
namespace App\Controllers\Admin;

use App\Models\Repositories\ProductRepository;

use App\Controllers\Admin\AuthController;
class ProductController extends BaseAdminController
{
    private $productRepo;

    public function __construct()
    {
        AuthController::requirePasswordChanged();
        $this->productRepo = new ProductRepository();
    }
    /** GET /admin/products (trả về view) */
    public function index()
    {
        return $this->view('admin/products/product');
    }

    /** GET /admin/api/products (list) */
    public function apiIndex()
    {
        $items = $this->productRepo->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/products (create) */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');
        try {
            $id = $this->productRepo->create($data, $currentUser, $slug);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->productRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi tạo sản phẩm'
            ]);
            exit;
        }
    }

    /** PUT /admin/api/products/{id} */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $currentUser = $this->currentUserId();
        $slug = $data['slug'] ?? $this->slugify($data['name'] ?? '');
        try {
            $this->productRepo->update($id, $data, $currentUser, $slug);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->productRepo->findOne($id), JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\PDOException $e) {
            http_response_code($e->getCode() === '23000' ? 409 : 500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => $e->getCode() === '23000'
                    ? 'SKU hoặc slug đã tồn tại'
                    : 'Lỗi máy chủ khi cập nhật sản phẩm'
            ]);
            exit;
        }
    }

    /** DELETE /admin/api/products/{id} */
    public function destroy($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->productRepo->delete($id);
            echo json_encode(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // findOne now in ProductRepository

    private function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-') ?: uniqid('sp-');
    }

    /** GET /admin/api/products/stock-list - Danh sách sản phẩm với tồn kho cho kiểm kê */
    public function apiStockList()
    {
        $pdo = \App\Core\DB::pdo();
        $sql = "SELECT 
                    p.id, 
                    p.name,
                    COALESCE(SUM(pb.current_qty), 0) AS stock_quantity
                FROM products p
                LEFT JOIN product_batches pb ON pb.product_id = p.id
                WHERE p.is_active = 1
                GROUP BY p.id, p.name
                ORDER BY p.name ASC";
        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['products' => $products], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /admin/api/products/upload-images - Upload ảnh sản phẩm */
    public function uploadImages()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!isset($_POST['product_id'])) {
                throw new \Exception('Thiếu product_id');
            }
            
            $productId = (int)$_POST['product_id'];
            $uploadDir = __DIR__ . '/../../../public/assets/images/products/' . $productId;
            
            // Tạo thư mục nếu chưa có
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadedFiles = [];
            
            // Upload ảnh chính (1.png)
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $mainImage = $_FILES['main_image'];
                $ext = strtolower(pathinfo($mainImage['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    throw new \Exception('Ảnh chính chỉ chấp nhận PNG, JPG, JPEG');
                }
                
                if ($mainImage['size'] > 2 * 1024 * 1024) {
                    throw new \Exception('Ảnh chính không được vượt quá 2MB');
                }
                
                $mainPath = $uploadDir . '/1.png';
                
                // Convert to PNG if needed
                $this->convertAndSaveImage($mainImage['tmp_name'], $mainPath, $ext);
                $uploadedFiles[] = '1.png';
            }
            
            // Upload ảnh phụ (2.png, 3.png, ...)
            if (isset($_FILES['sub_images']) && is_array($_FILES['sub_images']['name'])) {
                $subImages = $_FILES['sub_images'];
                $count = count($subImages['name']);
                
                for ($i = 0; $i < $count && $i < 5; $i++) {
                    if ($subImages['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($subImages['name'][$i], PATHINFO_EXTENSION));
                        
                        if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                            continue;
                        }
                        
                        if ($subImages['size'][$i] > 2 * 1024 * 1024) {
                            continue;
                        }
                        
                        $subPath = $uploadDir . '/' . ($i + 2) . '.png';
                        $this->convertAndSaveImage($subImages['tmp_name'][$i], $subPath, $ext);
                        $uploadedFiles[] = ($i + 2) . '.png';
                    }
                }
            }
            
            // Lưu thông tin ảnh vào database
            $pdo = \App\Core\DB::pdo();
            $currentUser = $_SESSION['user']['id'] ?? null;
            
            $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            foreach ($uploadedFiles as $idx => $filename) {
                $stmt = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_url, is_primary, created_by, updated_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $productId,
                    "/assets/images/products/{$productId}/{$filename}",
                    $idx === 0 ? 1 : 0,
                    $currentUser,
                    $currentUser
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'uploaded' => $uploadedFiles,
                'message' => 'Upload ảnh thành công'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    private function convertAndSaveImage($sourcePath, $destPath, $sourceExt)
    {
        // Load image based on type
        switch ($sourceExt) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $image = imagecreatefrompng($sourcePath);
                break;
            default:
                throw new \Exception('Định dạng ảnh không được hỗ trợ');
        }
        
        if (!$image) {
            throw new \Exception('Không thể đọc file ảnh');
        }
        
        // Convert to PNG and save
        imagepng($image, $destPath, 9);
        imagedestroy($image);
    }
}
