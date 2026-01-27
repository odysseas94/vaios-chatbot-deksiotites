<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ποσοτικά και ποιοτικά στοιχεία για τις απαιτήσεις σε γνώσεις και δεξιότητες στην αγορά εργασίας αποφοίτων δευτεροβάθμιας εκπαίδευσης - Ψηφιακά σημεία πληροφόρησης</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/favicon/apple-touch-icon.png">
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 700px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            font-size: 28px;
        }

        .logo {
            width: clamp(150px, 30vw, 250px);
            height: auto;
            max-height: 80px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            color: #333;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        select:hover {
            border-color: #1877f2;
        }

        select:focus {
            outline: none;
            border-color: #1877f2;
            background-color: white;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1877f2 0%, #0e5fc4 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(24, 119, 242, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
    </style>
</head>

<body>
    <div class="container">
        <div style="text-align: center;">
            <img src="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/d2a.png" alt="Logo" class="logo">
        </div>
        <h1>Ψηφιακά σημεία πληροφόρησης</h1>
        <p class="subtitle">Επιλέξτε τα κριτήρια φιλτραρίσματος</p>

        <div class="info-box">
            ℹ️ Επιλέξτε τον τύπο σχολείου, το φύλο και την περιφέρεια για να φιλτράρετε τα δεδομένα δεξιοτήτων πριν ξεκινήσετε τη συνομιλία με το chatbot.
        </div>

        <form method="GET" action="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/chat/interface" id="filterForm">
            <div class="form-group">
                <label for="school">Τύπος σχολείου:</label>
                <select name="school" id="school" required>
                    <option value="">-- Επιλέξτε σχολείο --</option>
                    <option value="Γενικό">Γενικό (ΓΕΛ)</option>
                    <option value="ΕΠΑΛ">ΕΠΑΛ</option>
                </select>
            </div>

            <div class="form-group">
                <label for="gender">Φύλο:</label>
                <select name="gender" id="gender" required>
                    <option value="">-- Επιλέξτε φύλο --</option>
                    <option value="Άνδρας">Άνδρας</option>
                    <option value="Γυναίκα">Γυναίκα</option>
                </select>
            </div>

            <div class="form-group">
                <label for="perifereia">Περιφέρεια:</label>
                <select name="perifereia" id="perifereia" required>
                    <option value="">-- Επιλέξτε Περιφέρεια --</option>
                    <?php
                    $perifereiasPath = __DIR__ . '/../../resources/data/perifereia.json';
                    $perifereiasData = json_decode(file_get_contents($perifereiasPath), true);

                    // Iterate through the array
                    foreach ($perifereiasData as $perifereia) {
                        echo '<option value="' . htmlspecialchars($perifereia) . '">'
                            . htmlspecialchars($perifereia) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit">Συνέχεια στο Chat</button>
        </form>
    </div>

    <script>
        // Form validation
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const school = document.getElementById('school').value;
            const gender = document.getElementById('gender').value;
            const perifereia = document.getElementById('perifereia').value;

            if (!school || !gender || !perifereia) {
                e.preventDefault();
                alert('Παρακαλώ επιλέξτε όλα τα πεδία');
            }
        });
    </script>
</body>

</html>