<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Δεξιοτήτων - Συνομιλία</title>
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
            max-width: 800px;
            width: 100%;
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .chat-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
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
    </style>
</head>
<body>
    <a href="/chat" class="back-button">← Πίσω</a>

    <div class="chat-container">
        <div class="chat-header">
            <h1>🤖 Chatbot Δεξιοτήτων</h1>
            <div class="filter-info">
                <span class="filter-badge">📚 <?php echo htmlspecialchars($school); ?></span>
                <span class="filter-badge">👤 <?php echo htmlspecialchars($gender); ?></span>
                <span class="filter-badge">📍 <?php echo htmlspecialchars($perifereiasName); ?></span>
                <span class="filter-badge">💼 <?php echo htmlspecialchars($kladosName); ?></span>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message bot">
                <div class="message-bubble">
                    Γεια σου! Είμαι ο βοηθός σου για ερωτήσεις σχετικά με τις δεξιότητες των αποφοίτων.
                    Ρώτησέ με οτιδήποτε σχετικά με τα δεδομένα για <?php echo htmlspecialchars($school); ?>, <?php echo htmlspecialchars($gender); ?> στην περιφέρεια <?php echo htmlspecialchars($perifereiasName); ?>, κλάδος <?php echo htmlspecialchars($kladosName); ?>.
                </div>
            </div>
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
        const klados = <?php echo json_encode($klados); ?>;

        function addMessage(content, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
            
            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = 'message-bubble';
            bubbleDiv.textContent = content;
            
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

            // Add user message to chat
            addMessage(message, true);
            messageInput.value = '';
            
            // Disable input while processing
            sendButton.disabled = true;
            messageInput.disabled = true;
            showTyping();

            try {
                const response = await fetch('/chat/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        school: school,
                        gender: gender,
                        perifereia: perifereia,
                        klados: klados
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

        // Event listeners
        sendButton.addEventListener('click', sendMessage);
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Focus input on load
        messageInput.focus();
    </script>
</body>
</html>
