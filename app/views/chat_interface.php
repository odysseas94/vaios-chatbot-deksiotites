<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Δεξιοτήτων - Συνομιλία</title>
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #e8eaf0 0%, #f0f2f5 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: clamp(10px, 2vh, 20px);
            position: relative;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(24, 119, 242, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(24, 119, 242, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(0, 0, 0, 0.015) 0%, transparent 50%);
            animation: drift 20s ease-in-out infinite alternate;
            z-index: 0;
        }

        @keyframes drift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .chat-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            width: 100%;
            max-width: 1200px;
            height: 95vh;
            max-height: 900px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .chat-header {
            background: linear-gradient(135deg, #1877f2 0%, #0e5fc4 100%);
            color: white;
            padding: 20px 30px;
            border-bottom: none;
            position: relative;
            z-index: 10;
            border-radius: 24px 24px 0 0;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .chat-header h1 {
            font-size: clamp(20px, 4vw, 28px);
            font-weight: 700;
            color: white;
            flex: 1;
            text-align: center;
        }

        .filter-info {
            font-size: clamp(11px, 2vw, 13px);
            opacity: 0.95;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-badge {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            white-space: nowrap;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: clamp(15px, 3vw, 30px);
            background: #f8f9fa;
            scroll-behavior: smooth;
        }

        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(24, 119, 242, 0.5);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(24, 119, 242, 0.7);
        }

        .message {
            margin-bottom: 20px;
            display: flex;
            animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            opacity: 0;
            animation-fill-mode: forwards;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: clamp(250px, 75%, 600px);
            padding: 14px 18px;
            border-radius: 20px;
            word-wrap: break-word;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .message.user .message-bubble {
            background: linear-gradient(135deg, #1877f2 0%, #0e5fc4 100%);
            color: white;
            border-bottom-right-radius: 6px;
        }

        .message.bot .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .message-bubble h1, .message-bubble h2, .message-bubble h3 {
            margin-top: 12px;
            margin-bottom: 8px;
            color: #1877f2;
        }

        .message-bubble h1 {
            font-size: clamp(18px, 3vw, 20px);
        }

        .message-bubble h2 {
            font-size: clamp(16px, 2.5vw, 18px);
        }

        .message-bubble h3 {
            font-size: clamp(14px, 2vw, 16px);
        }

        .message-bubble ul, .message-bubble ol {
            margin: 10px 0;
            padding-left: 20px;
        }

        .message-bubble li {
            margin: 5px 0;
            line-height: 1.7;
        }

        .message-bubble p {
            margin: 8px 0;
            line-height: 1.7;
        }

        .message-bubble code {
            background: rgba(24, 119, 242, 0.08);
            padding: 3px 8px;
            border-radius: 6px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: clamp(11px, 2vw, 13px);
            border: 1px solid rgba(24, 119, 242, 0.15);
        }

        .message-bubble pre {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 10px;
            overflow-x: auto;
            margin: 10px 0;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .message-bubble pre code {
            background: none;
            padding: 0;
            border: none;
        }

        .message-bubble strong {
            font-weight: 600;
            color: #1877f2;
        }

        .message-bubble em {
            font-style: italic;
            color: #666;
        }

        .message-bubble blockquote {
            border-left: 4px solid #1877f2;
            padding-left: 12px;
            margin: 10px 0;
            color: #666;
        }

        .message-bubble table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
            font-size: clamp(11px, 2vw, 13px);
        }

        .message-bubble th, .message-bubble td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .message-bubble th {
            background: #1877f2;
            color: white;
        }

        .message-bubble tr:nth-child(even) {
            background: #f9f9f9;
        }

        .typing-indicator {
            display: none;
            padding: 14px 18px;
            background: white;
            border-radius: 20px;
            border-bottom-left-radius: 6px;
            width: fit-content;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-indicator span {
            height: 10px;
            width: 10px;
            background: linear-gradient(135deg, #1877f2 0%, #0e5fc4 100%);
            border-radius: 50%;
            display: inline-block;
            margin: 0 3px;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .typing-indicator span:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }

        .chat-input-container {
            padding: clamp(15px, 3vw, 25px);
            background: white;
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 24px 24px;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        #messageInput {
            flex: 1;
            padding: clamp(12px, 2vw, 16px);
            background: #f5f5f5;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: clamp(14px, 2vw, 16px);
            color: #333;
            outline: none;
            transition: all 0.3s ease;
        }

        #messageInput::placeholder {
            color: #999;
        }

        #messageInput:focus {
            border-color: #1877f2;
            background: white;
            box-shadow: 0 0 20px rgba(24, 119, 242, 0.15);
        }

        #sendButton {
            padding: clamp(12px, 2vw, 16px) clamp(20px, 3vw, 28px);
            background: linear-gradient(135deg, #1877f2 0%, #0e5fc4 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: clamp(13px, 2vw, 15px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 15px rgba(24, 119, 242, 0.3);
        }

        #sendButton:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(24, 119, 242, 0.5);
        }

        #sendButton:active:not(:disabled) {
            transform: translateY(0);
        }

        #sendButton:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .back-button {
            padding: clamp(8px, 1.5vw, 10px) clamp(14px, 2vw, 18px);
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: clamp(12px, 2vw, 14px);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateX(-3px);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 12px;
            margin: 10px clamp(15px, 3vw, 30px);
            text-align: center;
            display: none;
            border: 1px solid #ffcdd2;
        }

        .error-message.show {
            display: block;
        }

        .header-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        #clearHistoryBtn, #showQuestionsBtn {
            padding: clamp(8px, 1.5vw, 10px) clamp(14px, 2vw, 18px);
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            color: white;
            cursor: pointer;
            font-size: clamp(12px, 2vw, 14px);
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        #clearHistoryBtn:hover, #showQuestionsBtn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        #clearHistoryBtn:active, #showQuestionsBtn:active {
            transform: translateY(0);
        }

        .welcome-section {
            background: linear-gradient(135deg, #f0f5ff 0%, #fafbff 100%);
            border-radius: 20px;
            padding: clamp(20px, 3vw, 30px);
            margin-bottom: 20px;
            border: 2px solid #e3efff;
            box-shadow: 0 4px 20px rgba(24, 119, 242, 0.08);
        }

        .welcome-section h3 {
            color: #1877f2;
            font-size: clamp(16px, 2.5vw, 20px);
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sample-questions {
            display: grid;
            gap: 12px;
        }

        .sample-question {
            background: white;
            padding: clamp(12px, 2vw, 16px);
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 2px solid transparent;
            font-size: clamp(13px, 2vw, 15px);
            color: #555;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .sample-question::before {
            content: '💬';
            font-size: clamp(18px, 3vw, 22px);
            flex-shrink: 0;
        }

        .sample-question:hover {
            border-color: #1877f2;
            transform: translateX(8px);
            box-shadow: 0 4px 20px rgba(24, 119, 242, 0.2);
            color: #1877f2;
        }

        .sample-question:active {
            transform: translateX(4px);
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            body {
                padding: 5px;
            }

            .chat-container {
                height: 98vh;
                border-radius: 16px;
            }

            .chat-header {
                border-radius: 16px 16px 0 0;
            }

            .chat-input-container {
                border-radius: 0 0 16px 16px;
            }

            .header-top {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .back-button {
                order: -1;
                justify-content: center;
            }

            .header-buttons {
                align-items: center;
            }

            .filter-info {
                flex-direction: row;
                align-items: center;
                gap: 8px;
            }

            .message-bubble {
                max-width: 85%;
            }

            .chat-input-wrapper {
                flex-direction: row;
            }

            #sendButton {
                flex-shrink: 0;
            }
        }

        @media (max-width: 480px) {
            .message-bubble {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <div class="header-top">
                <a href="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/chat" class="back-button">← Πίσω</a>
                <h1>🤖 Chatbot Δεξιοτήτων</h1>
                <div class="header-buttons">
                    <button id="clearHistoryBtn">
                        <span>🗑️</span>
                        <span>Νέα Συνομιλία</span>
                    </button>
                    <button id="showQuestionsBtn">
                        <span>💡</span>
                        <span>Προτεινόμενες</span>
                    </button>
                </div>
            </div>
            <div class="filter-info">
                <span class="filter-badge">📚 <?php echo htmlspecialchars($school); ?></span>
                <span class="filter-badge">👤 <?php echo htmlspecialchars($gender); ?></span>
                <span class="filter-badge">📍 <?php echo htmlspecialchars($perifereiasName); ?></span>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="typing-indicator" id="typingIndicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="error-message" id="errorMessage"></div>

        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <input 
                    type="text" 
                    id="messageInput" 
                    placeholder="Γράψε το μήνυμά σου εδώ..."
                    autocomplete="off"
                >
                <button id="sendButton">Αποστολή</button>
            </div>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const typingIndicator = document.getElementById('typingIndicator');
        const errorMessage = document.getElementById('errorMessage');

        const school = <?php echo json_encode($school); ?>;
        const gender = <?php echo json_encode($gender); ?>;
        const perifereia = <?php echo json_encode($perifereia); ?>;
        const conversationHistory = <?php echo $conversationHistory ?? '[]'; ?>;
        const baseUrl = <?php echo json_encode($baseUrl ?? ''); ?>;

        function addMessage(content, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
            
            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = 'message-bubble';
            
            if (isUser) {
                // User messages are plain text
                bubbleDiv.textContent = content;
            } else {
                // Bot messages are markdown, parse and render as HTML
                bubbleDiv.innerHTML = marked.parse(content);
            }
            
            messageDiv.appendChild(bubbleDiv);
            
            // Insert before typing indicator
            chatMessages.insertBefore(messageDiv, typingIndicator);
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
            setTimeout(() => {
                errorMessage.classList.remove('show');
            }, 5000);
        }

        function showTyping() {
            typingIndicator.classList.add('active');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTyping() {
            typingIndicator.classList.remove('active');
        }

        async function sendMessage() {
            const message = messageInput.value.trim();
            
            if (!message) return;

            // Remove welcome section if it exists
            const welcomeSection = document.getElementById('welcomeSection');
            if (welcomeSection) {
                welcomeSection.remove();
            }

            // Add user message to chat
            addMessage(message, true);
            
            // Update conversation history with user message
            conversationHistory.push({ role: 'user', content: message });
            
            messageInput.value = '';
            
            // Disable input while processing
            sendButton.disabled = true;
            messageInput.disabled = true;
            showTyping();

            try {
                const response = await fetch(baseUrl + '/chat/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        school: school,
                        gender: gender,
                        perifereia: perifereia
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Σφάλμα κατά την επικοινωνία με τον server');
                }

                hideTyping();
                addMessage(data.response, false);
                
                // Update conversation history with assistant response
                conversationHistory.push({ role: 'assistant', content: data.response });

            } catch (error) {
                hideTyping();
                showError('Σφάλμα: ' + error.message);
                console.error('Error:', error);
                
                // Remove the user message from history since it failed
                conversationHistory.pop();
            } finally {
                sendButton.disabled = false;
                messageInput.disabled = false;
                messageInput.focus();
            }
        }

        // Load previous conversation history
        function loadConversationHistory() {
            // Format school name properly
            const schoolName = school === 'ΓΕΝΙΚΟ' ? 'Γενικό Λύκειο (ΓΕΛ)' : school;
            
            // Format gender properly
            const genderFormatted = gender.toLowerCase();
            
            // Format perifereia - capitalize first letter of each word
            const perifereiasFormatted = "<?=   $perifereiasName ?>";
            
            // Always show welcome message first
            addMessage(`Γεια σου! Είμαι ο βοηθός σου για ερωτήσεις σχετικά με δεξιότητες και απασχόληση αποφοίτων. Μπορείς να με ρωτήσεις οτιδήποτε για ${schoolName}, ${genderFormatted}, στην Περιφέρεια ${perifereiasFormatted}.`, false);
            
            // Show sample questions only if no conversation history
            if (conversationHistory.length === 0) {
                showSampleQuestions();
            } else {
                // Load all previous messages if they exist
                conversationHistory.forEach(msg => {
                    addMessage(msg.content, msg.role === 'user');
                });
            }
            
            // Scroll to bottom after loading
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showSampleQuestions() {
            // Remove existing welcome section if it exists
            const existingSection = document.getElementById('welcomeSection');
            if (existingSection) {
                existingSection.remove();
            }

            const welcomeSection = document.createElement('div');
            welcomeSection.className = 'welcome-section';
            welcomeSection.id = 'welcomeSection';
            
            const allSampleQuestions = [
                'Ποια επαγγέλματα είναι δημοφιλή στην περιοχή μου;',
                'Τι δεξιότητες χρειάζομαι για να δουλέψω σε γραφείο;',
                'Ποιοι κλάδοι έχουν περισσότερες προσλήψεις;',
                'Τι δεξιότητες αναζητούν οι εργοδότες στην περιοχή μου;'
            ];
            
            // Filter out questions that have already been asked
            const askedQuestions = conversationHistory
                .filter(msg => msg.role === 'user')
                .map(msg => msg.content.trim());
            
            const availableQuestions = allSampleQuestions.filter(q => 
                !askedQuestions.includes(q.trim())
            );
            
            // If no questions are available, show a message
            if (availableQuestions.length === 0) {
                welcomeSection.innerHTML = `
                    <h3><span>💡</span> Προτεινόμενες ερωτήσεις</h3>
                    <div style="color: #666; font-size: 14px; padding: 10px;">
                        Έχεις ήδη κάνει όλες τις προτεινόμενες ερωτήσεις! Κάνε τη δική σου ερώτηση παρακάτω.
                    </div>
                `;
            } else {
                welcomeSection.innerHTML = `
                    <h3><span>💡</span> Προτεινόμενες ερωτήσεις</h3>
                    <div class="sample-questions">
                        ${availableQuestions.map(q => `<div class="sample-question" data-question="${q}">${q}</div>`).join('')}
                    </div>
                `;
            }
            
            chatMessages.insertBefore(welcomeSection, typingIndicator);
            
            // Add click event listeners to sample questions
            document.querySelectorAll('.sample-question').forEach(btn => {
                btn.addEventListener('click', function() {
                    const question = this.getAttribute('data-question');
                    messageInput.value = question;
                    
                    // Remove welcome section
                    document.getElementById('welcomeSection').remove();
                    
                    // Send the message
                    sendMessage();
                });
            });

            // Scroll to show the questions
            welcomeSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Event listeners
        sendButton.addEventListener('click', sendMessage);
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Clear history button - no confirmation
        document.getElementById('clearHistoryBtn').addEventListener('click', async () => {
            try {
                await fetch(baseUrl + '/chat/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        school: school,
                        gender: gender,
                        perifereia: perifereia
                    })
                });

                // Reload the page to start fresh
                location.reload();
            } catch (error) {
                showError('Σφάλμα κατά την εκκαθάριση ιστορικού');
            }
        });

        // Show questions button - shows recommended questions without clearing conversation
        document.getElementById('showQuestionsBtn').addEventListener('click', () => {
            showSampleQuestions();
        });

        // Load conversation history on page load
        loadConversationHistory();

        // Focus input on load
        messageInput.focus();
    </script>
</body>
</html>
