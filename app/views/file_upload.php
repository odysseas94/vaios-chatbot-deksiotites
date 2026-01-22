<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenAI File Upload - Διαχείριση</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8eaf0 0%, #f0f2f5 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
            font-size: 13px;
            color: #1976d2;
        }

        .status-box {
            background-color: #f5f5f5;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .status-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-label {
            font-weight: 600;
            color: #555;
        }

        .status-value {
            color: #333;
            font-family: monospace;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-upload {
            background: linear-gradient(135deg, #1877f2 0%, #0e5fc4 100%);
            color: white;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(24, 119, 242, 0.4);
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #e0e0e0;
            color: #333;
        }

        .btn-back:hover {
            background: #d0d0d0;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #1877f2;
        }

        .loading.active {
            display: block;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .message.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 OpenAI File Upload</h1>
        <p class="subtitle">Διαχείριση αρχείων δεδομένων για το Chatbot</p>

        <div class="info-box">
            ℹ️ Ανεβάστε τα JSON αρχεία (antistixeia, deksiotites, hiring_job, isozygio) στο OpenAI για να τα χρησιμοποιήσει το chatbot με file search αντί να στέλνει όλα τα δεδομένα σε κάθε μήνυμα.
        </div>

        <div class="message" id="message"></div>

        <div class="loading" id="loading">
            <div>⏳ Φόρτωση...</div>
        </div>

        <div class="status-box" id="statusBox" style="display: none;">
            <h3>Κατάσταση Ανεβασμένων Αρχείων</h3>
            <div class="status-item">
                <span class="status-label">Vector Store ID:</span>
                <span class="status-value" id="vectorStoreId">-</span>
            </div>
            <div class="status-item">
                <span class="status-label">Ημερομηνία Ανεβάσματος:</span>
                <span class="status-value" id="uploadedAt">-</span>
            </div>
            <div class="status-item">
                <span class="status-label">Αρχεία:</span>
                <span class="status-value" id="fileCount">-</span>
            </div>
        </div>

        <div class="button-group">
            <button class="btn-upload" id="uploadBtn">📤 Ανέβασμα Αρχείων</button>
            <button class="btn-delete" id="deleteBtn" style="display: none;">🗑️ Διαγραφή Αρχείων</button>
            <button class="btn-back" onclick="window.location.href='<?php echo htmlspecialchars($baseUrl ?? ''); ?>/chat'">← Πίσω</button>
        </div>
    </div>

    <script>
        const baseUrl = <?php echo json_encode($baseUrl ?? ''); ?>;
        const uploadBtn = document.getElementById('uploadBtn');
        const deleteBtn = document.getElementById('deleteBtn');
        const loading = document.getElementById('loading');
        const message = document.getElementById('message');
        const statusBox = document.getElementById('statusBox');

        function showMessage(text, type) {
            message.textContent = text;
            message.className = `message ${type} active`;
            setTimeout(() => {
                message.classList.remove('active');
            }, 5000);
        }

        function showLoading(show) {
            loading.className = show ? 'loading active' : 'loading';
            uploadBtn.disabled = show;
            deleteBtn.disabled = show;
        }

        async function checkStatus() {
            try {
                const response = await fetch(baseUrl + '/files/status');
                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('vectorStoreId').textContent = data.vector_store_id;
                    document.getElementById('uploadedAt').textContent = data.uploaded_at;
                    document.getElementById('fileCount').textContent = Object.keys(data.file_ids).length;
                    statusBox.style.display = 'block';
                    deleteBtn.style.display = 'block';
                    uploadBtn.textContent = '🔄 Επαναφόρτωση Αρχείων';
                }
            } catch (error) {
                // No files uploaded yet
            }
        }

        uploadBtn.addEventListener('click', async () => {
            if (!confirm('Θέλεις να ανεβάσεις τα αρχεία στο OpenAI? Αυτό μπορεί να πάρει λίγο χρόνο.')) {
                return;
            }

            showLoading(true);
            
            try {
                const response = await fetch(baseUrl + '/files/upload', {
                    method: 'POST'
                });

                const data = await response.json();

                if (response.ok) {
                    showMessage('✅ Τα αρχεία ανέβηκαν επιτυχώς!', 'success');
                    await checkStatus();
                } else {
                    showMessage('❌ Σφάλμα: ' + data.error, 'error');
                }
            } catch (error) {
                showMessage('❌ Σφάλμα δικτύου: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        });

        deleteBtn.addEventListener('click', async () => {
            if (!confirm('Είσαι σίγουρος ότι θέλεις να διαγράψεις όλα τα ανεβασμένα αρχεία από το OpenAI;')) {
                return;
            }

            showLoading(true);

            try {
                const response = await fetch(baseUrl + '/files/delete', {
                    method: 'POST'
                });

                const data = await response.json();

                if (response.ok) {
                    showMessage('✅ Τα αρχεία διαγράφηκαν επιτυχώς!', 'success');
                    statusBox.style.display = 'none';
                    deleteBtn.style.display = 'none';
                    uploadBtn.textContent = '📤 Ανέβασμα Αρχείων';
                } else {
                    showMessage('❌ Σφάλμα: ' + data.error, 'error');
                }
            } catch (error) {
                showMessage('❌ Σφάλμα δικτύου: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        });

        // Check status on load
        checkStatus();
    </script>
</body>
</html>
