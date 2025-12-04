<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer\Repositories\AddressRepository;

class AddressController extends Controller
{
    private AddressRepository $addressRepo;

    public function __construct()
    {
        $this->addressRepo = new AddressRepository();
    }

    /**
     * Display address management page
     */
    public function index(Request $request): mixed
    {
        $customerId = null;
        
        // Try JWT first (if middleware was applied)
        if (isset($request->user) && is_array($request->user) && isset($request->user['id'])) {
            $customerId = $request->user['id'];
        }
        
        // Fallback to session
        if (!$customerId && !empty($_SESSION['customer']['id'])) {
            $customerId = $_SESSION['customer']['id'];
        }
        
        if (!$customerId) {
            header('Location: /login');
            exit;
        }
        
        $addresses = $this->addressRepo->getCustomerAddresses($customerId);

        return $this->view('customer.address.address', [
            'addresses' => $addresses
        ]);
    }

    /**
     * Get addresses as JSON (for checkout page)
     */
    public function getAddresses(Request $request, $id = null): mixed
    {
        header('Content-Type: application/json');
        
        // Get customer ID from JWT (middleware injects it)
        $customerId = $request->user['id'] ?? null;
        if (!$customerId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }
        
        if ($id) {
            // Get single address
            $address = $this->addressRepo->getAddressById($id, $customerId);
            
            if (!$address) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy địa chỉ'
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'address' => $address
            ]);
        } else {
            // Get all addresses
            $addresses = $this->addressRepo->getCustomerAddresses($customerId);
            
            echo json_encode([
                'success' => true,
                'addresses' => $addresses
            ]);
        }
        exit;
    }

    /**
     * Create new address
     */
    public function store(Request $request): mixed
    {
        header('Content-Type: application/json');
        
        try {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            $customerId = $_SESSION['customer']['id'];

            // Validation
            $errors = $this->validateAddress($data);
            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $errors
                ]);
                exit;
            }

            // Check if user wants to set as default when there's already a default address
            if (isset($data['is_default']) && $data['is_default']) {
                $existingDefault = $this->addressRepo->getDefaultAddress($customerId);
                if ($existingDefault) {
                    // Inform user that existing default will be replaced
                    // But still proceed with the operation
                    $data['replace_default'] = true;
                }
            }

            // If this is the first address, set as default
            $addressCount = $this->addressRepo->countAddresses($customerId);
            if ($addressCount === 0) {
                $data['is_default'] = true;
            }

            $addressId = $this->addressRepo->createAddress($customerId, $data);

            $message = 'Thêm địa chỉ thành công';
            if (isset($data['replace_default'])) {
                $message = 'Thêm địa chỉ thành công. Địa chỉ mặc định đã được cập nhật.';
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'address_id' => $addressId
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Update address
     */
    public function update(Request $request, $id): mixed
    {
        header('Content-Type: application/json');
        
        try {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            
            // Get customer ID from JWT
            $customerId = $request->user['id'] ?? null;
            if (!$customerId) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
                exit;
            }

            // Verify ownership
            $address = $this->addressRepo->getAddressById($id, $customerId);
            if (!$address) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy địa chỉ'
                ]);
                exit;
            }

            // Validation
            $errors = $this->validateAddress($data);
            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $errors
                ]);
                exit;
            }

            // Check if user wants to set as default when there's already another default address
            if (isset($data['is_default']) && $data['is_default'] && $address['is_default'] != 1) {
                $existingDefault = $this->addressRepo->getDefaultAddress($customerId);
                if ($existingDefault && $existingDefault['id'] != $id) {
                    $data['replace_default'] = true;
                }
            }

            $this->addressRepo->updateAddress($id, $customerId, $data);

            $message = 'Cập nhật địa chỉ thành công';
            if (isset($data['replace_default'])) {
                $message = 'Cập nhật địa chỉ thành công. Địa chỉ mặc định đã được thay đổi.';
            }

            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Delete address
     */
    public function delete(Request $request, $id): mixed
    {
        header('Content-Type: application/json');
        
        try {
            // Get customer ID from JWT
            $customerId = $request->user['id'] ?? null;
            if (!$customerId) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
                exit;
            }

            // Verify ownership
            $address = $this->addressRepo->getAddressById($id, $customerId);
            if (!$address) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy địa chỉ'
                ]);
                exit;
            }

            // Prevent deleting default address
            if ($address['is_default']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không thể xóa địa chỉ mặc định. Vui lòng đặt địa chỉ khác làm mặc định trước khi xóa.'
                ]);
                exit;
            }

            $this->addressRepo->deleteAddress($id, $customerId);

            echo json_encode([
                'success' => true,
                'message' => 'Xóa địa chỉ thành công'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Set address as default
     */
    public function setDefault(Request $request, $id): mixed
    {
        header('Content-Type: application/json');
        
        try {
            // Get customer ID from JWT
            $customerId = $request->user['id'] ?? null;
            if (!$customerId) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
                exit;
            }

            // Verify ownership
            $address = $this->addressRepo->getAddressById($id, $customerId);
            if (!$address) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy địa chỉ'
                ]);
                exit;
            }

            $this->addressRepo->setAsDefault($id, $customerId);

            echo json_encode([
                'success' => true,
                'message' => 'Đã đặt làm địa chỉ mặc định'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Validate address data
     */
    private function validateAddress(array $data): array
    {
        $errors = [];

        if (empty($data['recipient_name'])) {
            $errors['recipient_name'] = 'Vui lòng nhập tên người nhận';
        }

        if (empty($data['phone_number'])) {
            $errors['phone_number'] = 'Vui lòng nhập số điện thoại';
        } elseif (!preg_match('/^0\d{9}$/', $data['phone_number'])) {
            $errors['phone_number'] = 'Số điện thoại không hợp lệ';
        }

        if (empty($data['address_line'])) {
            $errors['address_line'] = 'Vui lòng nhập địa chỉ cụ thể';
        }

        if (empty($data['ward_code'])) {
            $errors['ward_code'] = 'Vui lòng chọn phường/xã';
        }

        if (empty($data['province_code'])) {
            $errors['province_code'] = 'Vui lòng chọn tỉnh/thành phố';
        }

        return $errors;
    }
}
