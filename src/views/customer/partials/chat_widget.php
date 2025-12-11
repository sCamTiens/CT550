<!-- Chat Widget & Go to Top Button -->
<style>
    /* Go to Top Button */
    #go-to-top {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #002975 0%, #004bb5 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 41, 117, 0.3);
        z-index: 999;
        transition: all 0.3s ease;
    }

    #go-to-top:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 41, 117, 0.4);
    }

    #go-to-top i {
        font-size: 20px;
    }

    /* Chat Widget */
    #chat-widget {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    /* Chat Toggle Button */
    #chat-toggle {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #002975 0%, #004bb5 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(0, 41, 117, 0.4);
        transition: all 0.3s ease;
        position: relative;
    }

    #chat-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 24px rgba(0, 41, 117, 0.5);
    }

    #chat-toggle i {
        color: white;
        font-size: 28px;
    }

    #chat-toggle .unread-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        border: 2px solid white;
    }

    /* Chat Box */
    #chat-box {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 380px;
        height: 600px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        overflow: hidden;
        transition: all 0.3s ease;
        user-select: none;
    }

    #chat-box.open {
        display: flex;
    }

    /* Chat Header */
    #chat-header {
        background: linear-gradient(135deg, #002975 0%, #004bb5 100%);
        color: white;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: move;
        user-select: none;
    }

    #chat-header .header-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    #chat-header .avatar {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    #chat-header .status {
        display: flex;
        flex-direction: column;
    }

    #chat-header .status-name {
        font-weight: 600;
        font-size: 16px;
    }

    #chat-header .status-text {
        font-size: 12px;
        opacity: 0.9;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    #chat-header .status-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    #chat-header .header-actions {
        display: flex;
        gap: 10px;
    }

    #chat-header .header-actions button {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    #chat-header .header-actions button:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Chat Messages */
    #chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f9fafb;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    #chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    #chat-messages::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #chat-messages::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    #chat-messages::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Message Bubble */
    .message {
        display: flex;
        gap: 8px;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.customer {
        flex-direction: row-reverse;
    }

    .message .message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .message.customer .message-avatar {
        background: #002975;
        color: white;
    }

    .message .message-content {
        max-width: 70%;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .message .message-bubble {
        padding: 12px 16px;
        border-radius: 16px;
        background: white;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        word-wrap: break-word;
    }

    .message.customer .message-bubble {
        background: #002975;
        color: white;
    }

    .message .message-time {
        font-size: 11px;
        color: #9ca3af;
        padding: 0 8px;
    }

    .message.customer .message-time {
        text-align: right;
    }

    /* Order Card */
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
        color: #002975;
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
        background: #002975;
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
        background: #001a54;
    }

    /* Typing Indicator */
    .typing-indicator {
        display: none;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        background: white;
        border-radius: 16px;
        width: fit-content;
    }

    .typing-indicator.active {
        display: flex;
    }

    .typing-indicator span {
        width: 8px;
        height: 8px;
        background: #cbd5e1;
        border-radius: 50%;
        animation: typing 1.4s infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {

        0%,
        60%,
        100% {
            transform: translateY(0);
        }

        30% {
            transform: translateY(-10px);
        }
    }

    /* Quick Actions */
    #quick-actions {
        padding: 12px 20px;
        background: white;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 8px;
        overflow-x: auto;
    }

    #quick-actions::-webkit-scrollbar {
        height: 4px;
    }

    #quick-actions button {
        padding: 8px 16px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        font-size: 13px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }

    #quick-actions button:hover {
        background: #e5e7eb;
        border-color: #002975;
        color: #002975;
    }

    /* Chat Input */
    #chat-input-area {
        padding: 16px 20px;
        background: white;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    #chat-input {
        flex: 1;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
    }

    #chat-input:focus {
        border-color: #002975;
    }

    #chat-send-btn {
        width: 40px;
        height: 40px;
        background: #002975;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    #chat-send-btn:hover {
        background: #001a54;
        transform: scale(1.05);
    }

    #chat-send-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: scale(1);
    }

    /* Responsive */
    @media (max-width: 768px) {
        #chat-box {
            width: calc(100vw - 40px);
            height: calc(100vh - 120px);
            bottom: 80px;
            right: 20px;
        }

        #chat-widget {
            right: 20px;
            bottom: 20px;
        }

        #go-to-top {
            right: 20px;
            bottom: 90px;
        }
    }
</style>

<!-- Go to Top Button -->
<button id="go-to-top" title="Lên đầu trang">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- Chat Widget -->
<div id="chat-widget">
    <!-- Chat Toggle Button -->
    <div id="chat-toggle">
        <i class="fa-solid fa-comments"></i>
        <span class="unread-badge" style="display: none;">0</span>
    </div>

    <!-- Chat Box -->
    <div id="chat-box">
        <!-- Chat Header -->
        <div id="chat-header">
            <div class="header-info">
                <div class="avatar">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <div class="status">
                    <div class="status-name">Hỗ trợ khách hàng</div>
                    <div class="status-text">
                        <span class="status-dot"></span>
                        <span id="support-status">Đang trực tuyến</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button id="minimize-chat" title="Thu nhỏ">
                    <i class="fa-solid fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Chat Messages -->
        <div id="chat-messages">
            <div class="message">
                <div class="message-avatar">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-bubble">
                        Xin chào! Tôi là trợ lý ảo của MiniGo. Tôi có thể giúp gì cho bạn?
                    </div>
                    <div class="message-time">Vừa xong</div>
                </div>
            </div>

            <!-- Typing Indicator -->
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <!-- Quick Actions -->
        <div id="quick-actions">
            <button onclick="sendQuickMessage('Xem đơn hàng của tôi')">
                <i class="fa-solid fa-box mr-1"></i> Đơn hàng
            </button>
            <button onclick="sendQuickMessage('Danh sách sản phẩm')">
                <i class="fa-solid fa-list mr-1"></i> Sản phẩm
            </button>
            <button onclick="sendQuickMessage('Liên hệ nhân viên')">
                <i class="fa-solid fa-user mr-1"></i> Nhân viên
            </button>
        </div>

        <!-- Chat Input -->
        <div id="chat-input-area">
            <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." />
            <button id="chat-send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script>
    // =====================================================================
    // GO TO TOP BUTTON
    // =====================================================================
    const goToTopBtn = document.getElementById('go-to-top');

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            goToTopBtn.style.display = 'flex';
        } else {
            goToTopBtn.style.display = 'none';
        }
    });

    goToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // =====================================================================
    // CHAT WIDGET
    // =====================================================================
    const chatToggle = document.getElementById('chat-toggle');
    const chatBox = document.getElementById('chat-box');
    const minimizeChat = document.getElementById('minimize-chat');
    const chatInput = document.getElementById('chat-input');
    const chatSendBtn = document.getElementById('chat-send-btn');
    const chatMessages = document.getElementById('chat-messages');
    const typingIndicator = document.querySelector('.typing-indicator');

    let sessionId = null;
    let isAIMode = true;
    let userData = null; // Lưu user info và avatar

    // Toggle chat box
    chatToggle.addEventListener('click', () => {
        chatBox.classList.toggle('open');
        if (chatBox.classList.contains('open')) {
            chatInput.focus();
            initChatSession();
        }
    });

    minimizeChat.addEventListener('click', () => {
        chatBox.classList.remove('open');
    });

    // Draggable chat header
    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;

    const chatHeader = document.getElementById('chat-header');

    chatHeader.addEventListener('mousedown', dragStart);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', dragEnd);

    function dragStart(e) {
        if (e.target.closest('.header-actions')) return;

        initialX = e.clientX - chatBox.offsetLeft;
        initialY = e.clientY - chatBox.offsetTop;
        isDragging = true;
        chatHeader.style.cursor = 'grabbing';
    }

    function drag(e) {
        if (!isDragging) return;

        e.preventDefault();
        currentX = e.clientX - initialX;
        currentY = e.clientY - initialY;

        // Giới hạn trong viewport
        const maxX = window.innerWidth - chatBox.offsetWidth;
        const maxY = window.innerHeight - chatBox.offsetHeight;

        currentX = Math.max(0, Math.min(currentX, maxX));
        currentY = Math.max(0, Math.min(currentY, maxY));

        chatBox.style.right = 'auto';
        chatBox.style.bottom = 'auto';
        chatBox.style.left = currentX + 'px';
        chatBox.style.top = currentY + 'px';
    }

    function dragEnd() {
        isDragging = false;
        chatHeader.style.cursor = 'move';
    }

    // Send message
    chatSendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        console.log('🚀 [Send] Sending message with sessionId:', sessionId);

        // Add customer message
        addMessage(message, 'customer');
        chatInput.value = '';

        // Show typing indicator
        typingIndicator.classList.add('active');
        scrollToBottom();

        // Send to server
        const token = localStorage.getItem('jwt_token');
        fetch('/api/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...(token ? {
                        'Authorization': `Bearer ${token}`
                    } : {})
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    message: message
                })
            })
            .then(res => res.json())
            .then(data => {
                typingIndicator.classList.remove('active');

                if (data.success) {
                    // Only show response if not empty (staff mode doesn't send response)
                    if (data.response && data.response.trim()) {
                        addMessage(data.response, data.sender_type || 'ai', data.metadata, data.created_at || data.timestamp);
                    }
                } else {
                    addMessage('Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.', 'ai');
                }
            })
            .catch(err => {
                console.error('Chat error:', err);
                typingIndicator.classList.remove('active');
                addMessage('Không thể kết nối. Vui lòng kiểm tra kết nối mạng.', 'ai');
            });
    }

    function sendQuickMessage(message) {
        chatInput.value = message;
        sendMessage();
    }

    function addMessage(text, sender, metadata = null, timestamp = null, status = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;

        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';

        // Hiển thị avatar của user nếu có
        if (sender === 'customer' && userData && userData.avatar) {
            avatar.innerHTML = `<img src="${userData.avatar}" alt="${userData.name}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
        } else {
            avatar.innerHTML = sender === 'customer' ?
                '<i class="fa-solid fa-user"></i>' :
                sender === 'ai' ?
                '<i class="fa-solid fa-robot"></i>' :
                '<i class="fa-solid fa-headset"></i>';
        }

        const content = document.createElement('div');
        content.className = 'message-content';

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.textContent = text;

        // Add metadata (order link, product link, etc.)
        if (metadata) {
            // Check for JSON string
            if (typeof metadata === 'string') {
                try {
                    metadata = JSON.parse(metadata);
                } catch (e) {}
            }

            if (metadata.type === 'order_card') {
                const card = document.createElement('div');
                card.className = 'order-card';
                card.innerHTML = `
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
                `;
                bubble.appendChild(card);
            } else if (metadata.type === 'product_list' && metadata.products) {
                const grid = document.createElement('div');
                grid.className = 'product-grid';

                metadata.products.forEach(product => {
                    const productCard = document.createElement('a');
                    productCard.className = 'product-card';
                    productCard.href = `/products/${product.slug || product.id}`;
                    productCard.target = '_blank';

                    const imageUrl = product.image_url || '/uploads/products/default.jpg';
                    const price = new Intl.NumberFormat('vi-VN', {
                        style: 'currency',
                        currency: 'VND'
                    }).format(product.sale_price || 0);

                    productCard.innerHTML = `
                        <img src="${imageUrl}" alt="${product.name}" />
                        <div class="product-info">
                            <div class="product-name">${product.name}</div>
                            <div class="product-price">${price}</div>
                        </div>
                    `;

                    grid.appendChild(productCard);
                });

                bubble.appendChild(grid);
            } else if (metadata.order_id) {
                // Legacy simple link
                const link = document.createElement('a');
                link.href = `/profile?tab=orders&view_order=${metadata.order_id}`;
                link.className = 'text-blue-600 underline block mt-2 font-medium';
                link.textContent = 'Xem chi tiết đơn hàng →';
                bubble.appendChild(link);
            }
        }

        const time = document.createElement('div');
        time.className = 'message-time';

        // Use provided timestamp or current time
        const timeToDisplay = timestamp ? new Date(timestamp) : new Date();
        time.textContent = timeToDisplay.toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });

        // Add read receipts for customer messages
        if (sender === 'customer') {
            const receipt = document.createElement('span');
            receipt.className = 'read-receipt';
            receipt.style.cssText = 'margin-left: 6px; font-size: 11px;';

            const msgStatus = status || 'sent';

            switch (msgStatus) {
                case 'sent':
                    receipt.textContent = 'Đã gửi';
                    receipt.style.color = '#9ca3af';
                    receipt.title = 'Đã gửi';
                    break;
                case 'delivered':
                    receipt.textContent = 'Đã nhận';
                    receipt.style.color = '#3b82f6';
                    receipt.title = 'Đã nhận';
                    break;
                case 'read':
                    receipt.textContent = 'Đã xem';
                    receipt.style.color = '#10b981';
                    receipt.title = 'Đã xem';
                    break;
            }

            time.appendChild(receipt);
        }

        content.appendChild(bubble);
        content.appendChild(time);
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);

        chatMessages.insertBefore(messageDiv, typingIndicator);
        scrollToBottom();
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function initChatSession() {
        // Kiểm tra sessionId trong localStorage
        const savedSessionId = localStorage.getItem('chat_session_id');

        if (sessionId && sessionId === savedSessionId) {
            // Đã có session và đang load rồi, skip
            return;
        }

        // Nếu có saved session, dùng lại
        if (savedSessionId) {
            sessionId = savedSessionId;
            console.log('📂 Restored session:', sessionId);
        }

        const token = localStorage.getItem('jwt_token');

        fetch('/api/chat/init', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...(token ? {
                        'Authorization': `Bearer ${token}`
                    } : {})
                },
                body: JSON.stringify({
                    session_id: sessionId // Gửi session cũ nếu có
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionId = data.session_id;
                    userData = data.user; // Lưu user info và avatar

                    // Lưu vào localStorage
                    localStorage.setItem('chat_session_id', sessionId);
                    console.log('💾 Saved session to localStorage:', sessionId);
                    console.log('👤 User data:', userData);

                    isAIMode = data.is_ai_mode;

                    // Update status
                    const statusText = document.getElementById('support-status');
                    statusText.textContent = isAIMode ?
                        'Trợ lý AI' :
                        'Nhân viên hỗ trợ';

                    // Clear old messages (giữ lại welcome message)
                    const firstMessage = chatMessages.querySelector('.message');
                    chatMessages.innerHTML = '';
                    if (firstMessage) {
                        chatMessages.appendChild(firstMessage);
                    }
                    chatMessages.appendChild(typingIndicator);

                    // Load chat history from DB
                    if (data.messages && data.messages.length > 0) {
                        console.log(`📜 Loading ${data.messages.length} messages from history`);
                        data.messages.forEach(msg => {
                            addMessage(msg.message, msg.sender_type, msg.metadata ? JSON.parse(msg.metadata) : null, msg.created_at, msg.status);
                            // Track last message ID
                            if (msg.id) {
                                lastMessageId = Math.max(lastMessageId, msg.id);
                            }
                        });
                    } else {
                        console.log('📭 No previous messages');
                    }
                }
            })
            .catch(err => {
                console.error('Init chat error:', err);
            });
    }

    // Track last message ID to avoid duplicates
    let lastMessageId = 0;

    // Check for new messages periodically
    setInterval(() => {
        if (sessionId && chatBox.classList.contains('open')) {
            checkNewMessages();
        }
    }, 2000); // Poll every 2 seconds when chat is open

    function checkNewMessages() {
        const token = localStorage.getItem('jwt_token');

        fetch('/api/chat/init', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...(token ? {
                        'Authorization': `Bearer ${token}`
                    } : {})
                },
                body: JSON.stringify({
                    session_id: sessionId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.history) {
                    // Filter out messages we've already displayed
                    const newMessages = data.history.filter(msg => {
                        const msgId = msg.id || 0;
                        return msgId > lastMessageId && msg.sender_type !== 'customer';
                    });

                    if (newMessages.length > 0) {
                        console.log('📩 New messages received:', newMessages.length);
                        newMessages.forEach(msg => {
                            addMessage(msg.message, msg.sender_type, msg.metadata, msg.created_at, msg.status);
                            lastMessageId = Math.max(lastMessageId, msg.id || 0);
                        });
                    }
                }
            })
            .catch(err => console.error('Check messages error:', err));
    }
</script>