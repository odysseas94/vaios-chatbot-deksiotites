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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .chat-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            height: 700px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chat-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .filter-info {
            font-size: 13px;
            opacity: 0.9;
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 8px;
        }

        .filter-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 15px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
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

        .message.user {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.user .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.bot .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .message-bubble h1, .message-bubble h2, .message-bubble h3 {
            margin-top: 12px;
            margin-bottom: 8px;
            color: #667eea;
        }

        .message-bubble h1 {
            font-size: 20px;
        }

        .message-bubble h2 {
            font-size: 18px;
        }

        .message-bubble h3 {
            font-size: 16px;
        }

        .message-bubble ul, .message-bubble ol {
            margin: 10px 0;
            padding-left: 25px;
        }

        .message-bubble li {
            margin: 5px 0;
            line-height: 1.6;
        }

        .message-bubble p {
            margin: 8px 0;
            line-height: 1.6;
        }

        .message-bubble code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .message-bubble pre {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }

        .message-bubble pre code {
            background: none;
            padding: 0;
        }

        .message-bubble strong {
            font-weight: 600;
            color: #667eea;
        }

        .message-bubble em {
            font-style: italic;
        }

        .message-bubble blockquote {
            border-left: 4px solid #667eea;
            padding-left: 12px;
            margin: 10px 0;
            color: #666;
        }

        .message-bubble table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }

        .message-bubble th, .message-bubble td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .message-bubble th {
            background: #667eea;
            color: white;
        }

        .message-bubble tr:nth-child(even) {
            background: #f9f9f9;
        }

        .typing-indicator {
            display: none;
            padding: 12px 16px;
            background: white;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            width: fit-content;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: #667eea;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
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
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 10px;
        }

        #messageInput {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        #messageInput:focus {
            border-color: #667eea;
        }

        #sendButton {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        #sendButton:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        #sendButton:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease;
        }

        .back-button:hover {
            transform: translateY(-2px);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 10px;
            margin: 10px 0;
            text-align: center;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        #clearHistoryBtn {
            margin-top: 10px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            color: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        #clearHistoryBtn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        #clearHistoryBtn:active {
            transform: translateY(0);
        }

        .welcome-section {
            background: linear-gradient(135deg, #f5f7ff 0%, #fef5ff 100%);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            border: 2px solid #e8ecff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.08);
        }

        .welcome-section h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sample-questions {
            display: grid;
            gap: 10px;
        }

        .sample-question {
            background: white;
            padding: 12px 16px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-size: 14px;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .sample-question::before {
            content: '💬';
            font-size: 18px;
            flex-shrink: 0;
        }

        .sample-question:hover {
            border-color: #667eea;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            color: #667eea;
        }

        .sample-question:active {
            transform: translateX(3px);
        }
    </style>
</head>
<body>
    <a href="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/chat" class="back-button">← Πίσω</a>

    <div class="chat-container">
        <div class="chat-header">
            <h1>🤖 Chatbot Δεξιοτήτων</h1>
            <div class="filter-info">
                <span class="filter-badge">📚 <?php echo htmlspecialchars($school); ?></span>
                <span class="filter-badge">👤 <?php echo htmlspecialchars($gender); ?></span>
                <span class="filter-badge">📍 <?php echo htmlspecialchars($perifereiasName); ?></span>
            </div>
            <button id="clearHistoryBtn">
                <span>🗑️</span>
                <span>Νέα Συνομιλία</span>
            </button>
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

            } catch (error) {
                hideTyping();
                showError('Σφάλμα: ' + error.message);
                console.error('Error:', error);
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
            const perifereiasFormatted = <?php echo json_encode($perifereiasName); ?>.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
            
            // Always show welcome message first
            addMessage(`Γεια σου! Είμαι ο βοηθός σου για ερωτήσεις σχετικά με δεξιότητες και απασχόληση αποφοίτων. Μπορείς να με ρωτήσεις οτιδήποτε για ${schoolName}, ${genderFormatted}, στην περιφέρεια ${perifereiasFormatted}.`, false);
            
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
            const welcomeSection = document.createElement('div');
            welcomeSection.className = 'welcome-section';
            welcomeSection.id = 'welcomeSection';
            
            const sampleQuestions = [
                'Ποια επαγγέλματα είναι δημοφιλή στην περιοχή μου;',
                'Τι δεξιότητες χρειάζομαι για να δουλέψω σε γραφείο;',
                'Ποιοι κλάδοι έχουν περισσότερες προσλήψεις;',
                'Τι δεξιότητες αναζητούν οι εργοδότες στην περιοχή μου;'
            ];
            
            welcomeSection.innerHTML = `
                <h3><span>💡</span> Προτεινόμενες ερωτήσεις</h3>
                <div class="sample-questions">
                    ${sampleQuestions.map(q => `<div class="sample-question" data-question="${q}">${q}</div>`).join('')}
                </div>
            `;
            
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

        // Load conversation history on page load
        loadConversationHistory();

        // Focus input on load
        messageInput.focus();
    </script>
</body>
</html>
