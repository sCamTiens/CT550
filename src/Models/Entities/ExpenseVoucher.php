<?php
namespace App\Models\Entities;

class ExpenseVoucher
{
    public $id;
    public $code;
    public $type;                // Loại phiếu chi: 'Nhà cung cấp' hoặc 'Lương nhân viên'
    public $purchase_order_id;
    public $supplier_id;
    public $payroll_id;          // ID bảng lương (nếu chi trả lương)
    public $staff_user_id;       // ID nhân viên (nếu chi trả lương)
    public $method;
    public $txn_ref;      // Mã giao dịch ngân hàng (nếu có)
    public $amount;
    public $paid_by;
    public $paid_at;
    public $bank_time;    // Thời gian giao dịch ngân hàng (nếu có)
    public $note;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    
    // JOIN fields
    public $created_by_name;
    public $updated_by_name;
    public $supplier_name;
    public $purchase_order_code;
    public $paid_by_name;
    public $staff_name;           // Tên nhân viên (JOIN với users)
    public $payment_status;       // Trạng thái thanh toán của phiếu nhập (0: chưa trả, 1: trả 1 phần, 2: đã trả hết)

    public function __construct(array $data)
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }
}
