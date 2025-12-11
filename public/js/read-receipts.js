/**
 * Read Receipts Module for Chat Widget
 * Add this script to enable read receipt icons (✓ and ✓✓)
 */

(function () {
    'use strict';

    // Override or enhance the message rendering
    const originalFetch = window.fetch;

    window.fetch = function (...args) {
        return originalFetch.apply(this, args).then(response => {
            // Clone response to read body
            const clonedResponse = response.clone();

            // If this is a messages API call
            if (args[0] && args[0].includes('/messages')) {
                clonedResponse.json().then(data => {
                    if (data.messages) {
                        // Enhance messages with read receipts
                        enhanceMessagesWithReceipts(data.messages);
                    }
                });
            }

            return response;
        });
    };

    /**
     * Add read receipt icons to message bubbles
     */
    function enhanceMessagesWithReceipts(messages) {
        messages.forEach(msg => {
            if (msg.sender_type === 'customer' && msg.id) {
                // Find the message element
                const messageEl = document.querySelector(`[data-message-id="${msg.id}"]`);

                if (messageEl) {
                    addReceiptIcon(messageEl, msg.status || 'sent');
                }
            }
        });
    }

    /**
     * Add receipt icon based on status
     */
    function addReceiptIcon(messageElement, status) {
        // Remove existing receipt if any
        const existing = messageElement.querySelector('.read-receipt');
        if (existing) existing.remove();

        // Create receipt icon
        const receipt = document.createElement('span');
        receipt.className = 'read-receipt';
        receipt.style.cssText = 'margin-left: 6px; font-size: 12px; vertical-align: middle;';

        switch (status) {
            case 'sent':
                receipt.textContent = '✓';
                receipt.style.color = '#9ca3af'; // Gray
                receipt.title = 'Đã gửi';
                break;
            case 'delivered':
                receipt.textContent = '✓✓';
                receipt.style.color = '#3b82f6'; // Blue
                receipt.title = 'Đã nhận';
                break;
            case 'read':
                receipt.textContent = '✓✓';
                receipt.style.color = '#10b981'; // Green
                receipt.title = 'Đã xem';
                break;
        }

        // Append to message bubble
        const bubble = messageElement.querySelector('.message-bubble, .message-text, .chat-bubble');
        if (bubble) {
            bubble.appendChild(receipt);
        }
    }

    /**
     * MutationObserver to watch for new messages
     */
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1 && node.classList &&
                    (node.classList.contains('message') || node.classList.contains('chat-message'))) {

                    // Check if customer message
                    const dataAttr = node.getAttribute('data-sender');
                    const msgId = node.getAttribute('data-message-id');

                    if (dataAttr === 'customer' && msgId) {
                        // Fetch message status
                        fetchMessageStatus(msgId).then(status => {
                            addReceiptIcon(node, status);
                        });
                    }
                }
            });
        });
    });

    /**
     * Fetch message status from server
     */
    async function fetchMessageStatus(messageId) {
        try {
            // You may need to adjust this endpoint
            const response = await fetch(`/api/chat/message/${messageId}/status`);
            const data = await response.json();
            return data.status || 'sent';
        } catch (error) {
            return 'sent';
        }
    }

    // Start observing chat messages container
    const chatContainer = document.querySelector('#chat-messages, .chat-messages, #messages-container');
    if (chatContainer) {
        observer.observe(chatContainer, {
            childList: true,
            subtree: true
        });

        console.log('✓ Read Receipts Module loaded');
    }

})();
