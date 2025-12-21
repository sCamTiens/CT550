<?php
$pageTitle = $pageTitle ?? 'Chat Support';
?>
<?php require __DIR__ . '/../partials/layout-start.php'; ?>

<style>
    /* Chat Support Styles */
    .chat-support-container {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 20px;
        height: calc(100vh - 140px);
    }

    .sessions-panel {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 15px;
        border-bottom: 1px solid #e5e7eb;
    }

    .stat-card {
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        text-align: center;
    }

    .stat-card.ai {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.unread {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card.staff {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-number {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 11px;
        opacity: 0.9;
    }

    .sessions-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }

    .session-item {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }

    .session-item:hover {
        background: #f3f4f6;
    }

    .session-item.active {
        background: #eff6ff;
        border-color: #3b82f6;
    }

    .session-item.has-unread {
        background: #fef3c7;
    }

    .session-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }

    .customer-name {
        font-weight: 600;
        font-size: 14px;
    }

    .session-time {
        font-size: 11px;
        color: #6b7280;
    }

    .last-message {
        font-size: 13px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .session-badges {
        display: flex;
        gap: 6px;
        margin-top: 6px;
    }

    .badge {
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 12px;
        font-weight: 500;
    }

    .badge.ai {
        background: #fef3c7;
        color: #92400e;
    }

    .badge.staff {
        background: #d1fae5;
        color: #065f46;
    }

    .badge.unread {
        background: #fee2e2;
        color: #991b1b;
    }

    .chat-panel {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .chat-header {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .customer-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .customer-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f9fafb;
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
    }

    .chat-messages * {
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
    }

    .message {
        margin-bottom: 16px;
        display: flex;
        gap: 10px;
    }

    .message.customer {
        flex-direction: row;
    }

    .message.staff,
    .message.ai {
        flex-direction: row-reverse;
    }

    .message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        user-select: none !important;
        /* Don't select avatar icons */
        -webkit-user-select: none !important;
        -moz-user-select: none !important;
        -ms-user-select: none !important;
    }

    .message-content {
        max-width: 70%;
    }

    .message-bubble {
        padding: 10px 14px;
        border-radius: 16px;
        background: white;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        user-select: text;
        /* Allow text selection */
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
    }

    .message.customer .message-bubble {
        background: #eff6ff;
    }

    .message.staff .message-bubble {
        background: #f0fdf4;
    }

    .message.ai .message-bubble {
        background: #fef3c7;
    }

    .message-time {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 4px;
        padding: 0 4px;
        user-select: text;
        /* Allow text selection */
    }

    .chat-input-area {
        padding: 16px;
        border-top: 1px solid #e5e7eb;
        background: white;
    }

    .chat-input-container {
        display: flex;
        gap: 10px;
    }

    #chat-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 24px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
    }

    #chat-input:focus {
        border-color: #3b82f6;
    }

    #send-btn {
        padding: 0 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 24px;
        cursor: pointer;
        font-weight: 500;
        transition: transform 0.2s;
    }

    #send-btn:hover {
        transform: scale(1.05);
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #9ca3af;
        text-align: center;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }

    .quick-replies-bar {
        padding: 12px 16px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        background: #f9fafb;
    }

    .quick-reply-btn {
        padding: 6px 12px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }

    .quick-reply-btn:hover {
        background: #eff6ff;
        border-color: #3b82f6;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }

    .action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        border: none;
        font-weight: 500;
        transition: all 0.2s;
    }

    .action-btn.close {
        background: #fee2e2;
        color: #991b1b;
    }

    .action-btn.assign {
        background: #dbeafe;
        color: #1e40af;
    }

    /* Order Card Styles */
    .order-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px;
        background: #f8fafc;
        margin-top: 8px;
        min-width: 200px;
    }

    .order-card .order-header {
        font-weight: 600;
        color: #667eea;
        margin-bottom: 8px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 4px;
    }

    .order-card .order-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        margin-bottom: 4px;
        color: #4b5563;
    }

    .order-card .order-btn {
        display: block;
        width: 100%;
        text-align: center;
        background: #667eea;
        color: white;
        padding: 8px;
        border-radius: 6px;
        margin-top: 10px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        border: none;
    }

    .order-card .order-btn:hover {
        background: #5568d3;
    }

    /* Product List/Grid Styles */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    .product-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        transition: all 0.3s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .product-card:hover {
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        transform: translateY(-2px);
    }

    .product-card img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        background: #f3f4f6;
    }

    .product-card .product-info {
        padding: 10px;
    }

    .product-card .product-name {
        font-size: 13px;
        font-weight: 500;
        color: #1f2937;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.3;
    }

    .product-card .product-price {
        font-size: 14px;
        font-weight: 700;
        color: #ef4444;
    }

    /* Text Selection Styling */
    .chat-messages ::selection {
        background: #bfdbfe;
        color: #1e40af;
    }

    .chat-messages ::-moz-selection {
        background: #bfdbfe;
        color: #1e40af;
    }
</style>

<!-- Confirm Modal -->
<div id="confirm-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeConfirmModal()"></div>

    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all scale-95 opacity-0" id="confirm-modal-content">
            <!-- Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-full">
                        <i class="fa-solid fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Xác nhận</h3>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6">
                <p class="text-gray-700 text-base leading-relaxed" id="confirm-message"></p>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex gap-3 justify-end">
                <button onclick="closeConfirmModal()"
                    class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                    <i class="fa-solid fa-times mr-2"></i>Hủy
                </button>
                <button id="confirm-ok-btn"
                    class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
                    <i class="fa-solid fa-check mr-2"></i>Đồng ý
                </button>
            </div>
        </div>
    </div>
</div>

<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fa-solid fa-headset mr-2"></i> Hỗ Trợ Trực Tuyến</h1>
    </div>

    <div class="chat-support-container">
        <!-- Sessions Panel -->
        <div class="sessions-panel">
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" id="stat-active">0</div>
                    <div class="stat-label">Phiên Active</div>
                </div>
                <div class="stat-card unread">
                    <div class="stat-number" id="stat-unread">0</div>
                    <div class="stat-label">Chưa Đọc</div>
                </div>
                <div class="stat-card ai">
                    <div class="stat-number" id="stat-ai">0</div>
                    <div class="stat-label">AI Mode</div>
                </div>
                <div class="stat-card staff">
                    <div class="stat-number" id="stat-staff">0</div>
                    <div class="stat-label">Nhân Viên</div>
                </div>
            </div>

            <div class="sessions-list" id="sessions-list">
                <!-- Sessions will be loaded here -->
            </div>
        </div>

        <!-- Chat Panel -->
        <div class="chat-panel">
            <div id="chat-header" class="chat-header" style="display: none;">
                <div class="customer-info">
                    <div class="customer-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <div class="customer-name" id="customer-name">Customer</div>
                        <div class="session-time" id="session-start-time">--</div>
                    </div>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <div>Chọn một cuộc trò chuyện để bắt đầu</div>
                </div>
            </div>

            <div class="quick-replies-bar" id="quick-replies" style="display: none;">
                <button class="quick-reply-btn" onclick="insertQuickReply('Xin chào! Tôi có thể giúp gì cho bạn?')">
                    👋 Chào hỏi
                </button>
                <button class="quick-reply-btn" onclick="insertQuickReply('Vui lòng cung cấp mã đơn hàng để tôi kiểm tra')">
                    📦 Hỏi mã đơn
                </button>
                <button class="quick-reply-btn" onclick="insertQuickReply('Cảm ơn bạn đã liên hệ!')">
                    🙏 Cảm ơn
                </button>
                <button class="quick-reply-btn" onclick="insertQuickReply('!product ')">
                    🔍 Tìm SP
                </button>
            </div>

            <div class="chat-input-area" id="chat-input-area" style="display: none;">
                <div class="chat-input-container">
                    <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." />
                    <button id="send-btn" onclick="sendMessage()">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentSessionId = null;
    let sessions = [];
    let pollingInterval = null;
    let currentPollingSpeed = 5000; // Default 5s

    // Modal confirmation functions
    let confirmResolve = null;

    function showConfirmModal(message) {
        return new Promise((resolve) => {
            confirmResolve = resolve;
            const modal = document.getElementById('confirm-modal');
            const modalContent = document.getElementById('confirm-modal-content');
            const messageEl = document.getElementById('confirm-message');

            messageEl.textContent = message;
            modal.classList.remove('hidden');

            // Trigger animation
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);

            // Handle OK button
            const okBtn = document.getElementById('confirm-ok-btn');
            okBtn.onclick = () => {
                closeConfirmModal();
                resolve(true);
            };

            // Handle ESC key
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    closeConfirmModal();
                    resolve(false);
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
        });
    }

    function closeConfirmModal() {
        const modal = document.getElementById('confirm-modal');
        const modalContent = document.getElementById('confirm-modal-content');

        // Animate out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            if (confirmResolve) {
                confirmResolve(false);
                confirmResolve = null;
            }
        }, 200);
    }

    // Load sessions on page load
    document.addEventListener('DOMContentLoaded', () => {
        loadStats();
        loadSessions();
        startPolling();
    });

    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);

        pollingInterval = setInterval(() => {
            loadStats();
            loadSessions();
            if (currentSessionId) {
                loadMessages(currentSessionId);
            }
        }, currentPollingSpeed);
    }

    async function loadStats() {
        try {
            const res = await fetch('/admin/api/chat-support/stats');
            const data = await res.json();

            if (data.success) {
                document.getElementById('stat-active').textContent = data.stats.active_sessions;
                document.getElementById('stat-unread').textContent = data.stats.unread_messages;
                document.getElementById('stat-ai').textContent = data.stats.ai_sessions;
                document.getElementById('stat-staff').textContent = data.stats.staff_sessions;
            }
        } catch (err) {
            console.error('Load stats error:', err);
        }
    }

    async function loadSessions() {
        try {
            const res = await fetch('/admin/api/chat-support/sessions?status=active');
            const data = await res.json();

            if (data.success) {
                sessions = data.sessions;
                renderSessions();
            }
        } catch (err) {
            console.error('Load sessions error:', err);
        }
    }

    function renderSessions() {
        const container = document.getElementById('sessions-list');

        if (sessions.length === 0) {
            container.innerHTML = '<div class="empty-state"><div>Không có phiên chat nào</div></div>';
            return;
        }

        container.innerHTML = sessions.map(session => `
        <div class="session-item ${session.id === currentSessionId ? 'active' : ''} ${session.unread_count > 0 ? 'has-unread' : ''}"
             onclick="selectSession(${session.id})">
            <div class="session-header">
                <div class="customer-name">${session.customer_name || 'Guest'}</div>
                <div class="session-time">${formatTime(session.last_message_time || session.created_at)}</div>
            </div>
            <div class="last-message">${session.last_message || 'No messages yet'}</div>
            <div class="session-badges">
                ${session.is_ai_mode ? '<span class="badge ai">AI</span>' : '<span class="badge staff">Staff</span>'}
                ${session.unread_count > 0 ? `<span class="badge unread">${session.unread_count} mới</span>` : ''}
            </div>
        </div>
    `).join('');
    }

    async function selectSession(sessionId) {
        currentSessionId = sessionId;
        renderSessions(); // Highlight selected

        // Switch to faster polling (1s) when active
        currentPollingSpeed = 1000;
        startPolling();

        const session = sessions.find(s => s.id === sessionId);
        if (session) {
            document.getElementById('customer-name').textContent = session.customer_name || 'Guest';
            document.getElementById('session-start-time').textContent = formatDateTime(session.created_at);
        }

        document.getElementById('chat-header').style.display = 'block';
        document.getElementById('quick-replies').style.display = 'flex';
        document.getElementById('chat-input-area').style.display = 'block';

        await loadMessages(sessionId);
    }

    async function loadMessages(sessionId) {
        try {
            const res = await fetch(`/admin/api/chat-support/messages/${sessionId}`);
            const data = await res.json();

            if (data.success) {
                renderMessages(data.messages);
            }
        } catch (err) {
            console.error('Load messages error:', err);
        }
    }

    function renderMessages(messages) {
        const container = document.getElementById('chat-messages');

        if (messages.length === 0) {
            container.innerHTML = '<div class="empty-state"><div>Chưa có tin nhắn nào</div></div>';
            return;
        }

        container.innerHTML = messages.map(msg => {
            let bubble = `<div class="message-bubble">${escapeHtml(msg.message)}`;

            // Check for metadata (order card, product list)
            if (msg.metadata) {
                let metadata;
                try {
                    metadata = typeof msg.metadata === 'string' ? JSON.parse(msg.metadata) : msg.metadata;
                } catch (e) {
                    metadata = null;
                }

                // Order Card
                if (metadata && metadata.type === 'order_card') {
                    bubble += `
                        <div class="order-card">
                            <div class="order-header">
                                <i class="fa-solid fa-box"></i> Đơn hàng #${metadata.order_number}
                            </div>
                            <div class="order-row">
                                <span>Trạng thái:</span>
                                <span class="font-medium">${metadata.status}</span>
                            </div>
                            <div class="order-row">
                                <span>Tổng tiền:</span>
                                <span class="font-bold text-red-500">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(metadata.total_price)}</span>
                            </div>
                            <a href="/profile?tab=orders&view_order=${metadata.order_id}" class="order-btn" target="_blank">
                                Xem chi tiết <i class="fa-solid fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    `;
                }
                // Product List
                else if (metadata && metadata.type === 'product_list' && metadata.products) {
                    bubble += '<div class="product-grid">';
                    metadata.products.forEach(product => {
                        const imageUrl = product.image_url || '/uploads/products/default.jpg';
                        const productUrl = `/products/${product.slug || product.id}`;
                        const price = new Intl.NumberFormat('vi-VN', {
                            style: 'currency',
                            currency: 'VND'
                        }).format(product.sale_price || 0);

                        bubble += `
                            <a href="${productUrl}" class="product-card" target="_blank">
                                <img src="${imageUrl}" alt="${escapeHtml(product.name)}" />
                                <div class="product-info">
                                    <div class="product-name">${escapeHtml(product.name)}</div>
                                    <div class="product-price">${price}</div>
                                </div>
                            </a>
                        `;
                    });
                    bubble += '</div>';
                }
            }

            bubble += '</div>';

            return `
            <div class="message ${msg.sender_type}">
                <div class="message-avatar">
                    <i class="fa-solid fa-${msg.sender_type === 'customer' ? 'user' : msg.sender_type === 'ai' ? 'robot' : 'headset'}"></i>
                </div>
                <div class="message-content">
                    ${bubble}
                    <div class="message-time">${formatTime(msg.created_at)}</div>
                </div>
            </div>
        `;
        }).join('');

        // Only auto-scroll if user is near the bottom (within 100px)
        const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;

        if (isNearBottom || messages.length === 1) {
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 50);
        }
    }

    async function sendMessage() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();

        if (!message || !currentSessionId) return;

        try {
            const res = await fetch('/admin/api/chat-support/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: currentSessionId,
                    message: message
                })
            });

            const data = await res.json();

            if (data.success) {
                input.value = '';
                await loadMessages(currentSessionId);
                await loadSessions(); // Refresh to update last message
            }
        } catch (err) {
            console.error('Send error:', err);
        }
    }

    function insertQuickReply(text) {
        document.getElementById('chat-input').value = text;
        document.getElementById('chat-input').focus();
    }

    async function closeSession() {
        if (!currentSessionId) return;

        const confirmed = await showConfirmModal('Đóng cuộc trò chuyện này?');
        if (!confirmed) return;

        try {
            const res = await fetch('/admin/api/chat-support/close', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: currentSessionId
                })
            });

            if (res.ok) {
                currentSessionId = null;
                window.location.reload();
            }
        } catch (err) {
            console.error('Close error:', err);
        }
    }

    // Enter to send
    document.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && document.activeElement.id === 'chat-input') {
            sendMessage();
        }
    });

    function formatTime(datetime) {
        if (!datetime) return '--';
        const date = new Date(datetime);

        // Format: DD/MM/YYYY HH:MM
        return date.toLocaleString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatDateTime(datetime) {
        if (!datetime) return '--';
        const date = new Date(datetime);
        return date.toLocaleString('vi-VN');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php require __DIR__ . '/../partials/layout-end.php'; ?>