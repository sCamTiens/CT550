# ===================================================================
# RASA CUSTOM ACTIONS V2.1 - FIXED với product_images JOIN
# ===================================================================

from typing import Any, Text, Dict, List
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet
import mysql.connector
import os
from dotenv import load_dotenv
from datetime import datetime

load_dotenv()

# ===================================================================
# DATABASE CONNECTION
# ===================================================================

def get_db_connection():
    try:
        conn = mysql.connector.connect(
            host=os.getenv("DB_HOST", "localhost"),
            user=os.getenv("DB_USER", "root"),
            password=os.getenv("DB_PASSWORD", ""),
            database=os.getenv("DB_NAME", "mini_market"),
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        return conn
    except Exception as e:
        print(f"Database error: {str(e)}")
        return None

def format_currency(amount):
    if amount is None:
        return "0đ"
    return f"{int(amount):,}đ".replace(',', '.')

def format_date(date_obj):
    if date_obj is None:
        return "Chưa rõ"
    if isinstance(date_obj, str):
        return date_obj
    return date_obj.strftime("%d/%m/%Y %H:%M")

def get_order_status_text(status):
    status_map = {
        'pending': 'Chờ xác nhận',
        'confirmed': 'Đã xác nhận',
        'processing': 'Đang chuẩn bị',
        'shipping': 'Đang giao hàng',
        'delivered': 'Đã giao hàng',
        'cancelled': 'Đã hủy',
        'completed': 'Hoàn thành'
    }
    return status_map.get(status, status)

# ===================================================================
# ACTION: CHECK ORDER
# ===================================================================

class ActionCheckOrder(Action):
    def name(self) -> Text:
        return "action_check_order"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
        order_code = next(tracker.get_latest_entity_values("order_code"), None)
        
        metadata = tracker.latest_message.get('metadata', {})
        user_id = metadata.get('user_id') or tracker.sender_id
        
        try:
            user_id = int(user_id) if user_id and str(user_id).isdigit() else None
        except:
            user_id = None
        
        print(f"🔍 [DEBUG] order_code={order_code}, user_id={user_id}, sender={tracker.sender_id}")
        
        if not order_code:
            dispatcher.utter_message(text="Bạn vui lòng cho mình biết mã đơn hàng nhé! Ví dụ: **DH001** hoặc **ORD...**")
            return []
        
        conn = get_db_connection()
        if not conn:
            dispatcher.utter_message(text="Xin lỗi, hệ thống đang bảo trì. Vui lòng thử lại! 🔧")
            return []
        
        try:
            cursor = conn.cursor(dictionary=True)
            
            if user_id:
                query = """
                    SELECT 
                        o.id,
                        o.code,
                        o.status,
                        o.grand_total,
                        o.payment_status,
                        o.payment_method,
                        o.created_at,
                        u.full_name,
                        u.phone,
                        u.email,
                        COUNT(oi.id) as total_items,
                        SUM(oi.qty) as total_quantity
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.code = %s AND o.user_id = %s
                    GROUP BY o.id
                """
                cursor.execute(query, (order_code, user_id))
            else:
                query = """
                    SELECT 
                        o.id,
                        o.code,
                        o.status,
                        o.grand_total,
                        o.payment_status,
                        o.payment_method,
                        o.created_at,
                        u.full_name,
                        u.phone,
                        u.email,
                        COUNT(oi.id) as total_items,
                        SUM(oi.qty) as total_quantity
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.code = %s
                    GROUP BY o.id
                """
                cursor.execute(query, (order_code,))
            
            order = cursor.fetchone()
            
            print(f"🔍 [DEBUG] Found order: {order is not None}")
            
            if not order:
                if user_id:
                    message = f"Không tìm thấy đơn hàng **{order_code}** của bạn.\n\n"
                    message += "Vui lòng kiểm tra lại mã đơn hàng hoặc liên hệ hỗ trợ!"
                else:
                    message = f"Không tìm thấy đơn hàng **{order_code}**.\n\n"
                    message += "Vui lòng đăng nhập để xem đơn hàng của bạn!"
                dispatcher.utter_message(text=message)
                return []
            
            base_url = os.getenv("APP_URL", "http://localhost")
            order_url = f"{base_url}/profile?view=orders&id={order['id']}"
            
            message = f"**Thông tin đơn hàng #{order['code']}**\n\n"
            message += f"Khách hàng: {order['full_name']}\n"
            message += f"SĐT: {order['phone']}\n"
            message += f"Trạng thái: {get_order_status_text(order['status'])}\n"
            message += f"Tổng tiền: {format_currency(order['grand_total'])}\n"
            message += f"Thanh toán: {order['payment_status']}\n"
            message += f"Ngày đặt: {format_date(order['created_at'])}\n"
            message += f"Số mặt hàng: {order['total_items']} ({order['total_quantity']} sản phẩm)\n\n"
            message += f"[Xem chi tiết đơn hàng]({order_url})\n\n"
            
            metadata = {
                'order_id': order['id'],
                'order_code': order['code'],
                'order_url': order_url,
                'status': order['status']
            }
            
            dispatcher.utter_message(text=message, metadata=metadata)
            
        except Exception as e:
            print(f"Error checking order: {str(e)}")
            dispatcher.utter_message(text="Có lỗi khi kiểm tra đơn hàng. Vui lòng thử lại!")
        finally:
            if conn.is_connected():
                cursor.close()
                conn.close()
        
        return [SlotSet("order_code", order_code)]

# ===================================================================
# ACTION: SEARCH PRODUCT
# ===================================================================

class ActionSearchProduct(Action):
    def name(self) -> Text:
        return "action_search_product"
    
    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
        product_name = next(tracker.get_latest_entity_values("product_name"), None)
        
        if not product_name:
            message_text = tracker.latest_message.get('text', '')
            words = message_text.lower().split()
            skip_words = ['tìm', 'kiếm', 'xem', 'có', 'sản', 'phẩm', 'hàng', 'gì']
            product_name = ' '.join([w for w in words if w not in skip_words])
        
        if not product_name or len(product_name) < 2:
            dispatcher.utter_message(text="Bạn muốn tìm sản phẩm gì? Ví dụ: **nước giặt**, **sữa**, **cá**...")
            return []
        
        conn = get_db_connection()
        if not conn:
            dispatcher.utter_message(text="Hệ thống đang bảo trì. Vui lòng thử lại! 🔧")
            return []
        
        try:
            cursor = conn.cursor(dictionary=True)
            
            query = """
                SELECT 
                    p.id,
                    p.name,
                    p.slug,
                    p.sale_price,
                    pi.image_url,
                    p.is_active,
                    b.name as brand_name,
                    c.name as category_name,
                    COALESCE(s.qty, 0) as stock_qty
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN stocks s ON p.id = s.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
                WHERE p.name LIKE %s AND p.is_active = TRUE
                ORDER BY p.name
                LIMIT 5
            """
            cursor.execute(query, (f"%{product_name}%",))
            products = cursor.fetchall()
            
            if not products:
                message = f"Không tìm thấy sản phẩm nào với từ khóa \"{product_name}\".\n\n"
                message += "Thử tìm kiếm với từ khóa khác hoặc liên hệ hỗ trợ!"
                dispatcher.utter_message(text=message)
                return [SlotSet("product_name", product_name)]
            
            base_url = os.getenv("APP_URL", "http://localhost")
            
            message = f"Tìm thấy **{len(products)} sản phẩm** liên quan đến \"{product_name}\":\n\n"
            
            products_metadata = []
            
            for idx, p in enumerate(products, 1):
                product_url = f"{base_url}/products/{p['slug']}"
                image_url = f"{base_url}/storage/products/{p['image_url']}" if p['image_url'] else None
                
                stock_status = "Còn hàng" if p['stock_qty'] > 0 else "Hết hàng"
                
                message += f"**{idx}. {p['name']}**\n"
                message += f"   Giá: {format_currency(p['sale_price'])}\n"
                message += f"   {stock_status} ({p['stock_qty']} sản phẩm)\n"
                if p['brand_name']:
                    message += f"   Thương hiệu: {p['brand_name']}\n"
                message += f"   [Xem chi tiết]({product_url})\n\n"
                
                products_metadata.append({
                    'id': p['id'],
                    'name': p['name'],
                    'slug': p['slug'],
                    'sale_price': p['sale_price'],
                    'price': p['sale_price'],
                    'image_url': image_url,
                    'image': image_url,
                    'url': product_url,
                    'stock': p['stock_qty']
                })
            
            metadata = {
                'type': 'product_list',
                'products': products_metadata
            }
            
            dispatcher.utter_message(text=message, metadata=metadata)
            
        except Exception as e:
            print(f"Error searching products: {str(e)}")
            dispatcher.utter_message(text="Có lỗi khi tìm kiếm. Vui lòng thử lại!")
        finally:
            if conn.is_connected():
                cursor.close()
                conn.close()
        
        return [SlotSet("product_name", product_name)]
