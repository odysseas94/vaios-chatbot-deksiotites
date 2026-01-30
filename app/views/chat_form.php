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
        
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 20px;
            max-width: 90vw;

            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            font-size: 28px;
        }

        .logo {
            width: clamp(150px, 20vw, 170px);
            height: auto;
            max-height: 60px;
            object-fit: contain;
            margin-bottom: 20px;
        }



        .logo-espa {
            width: clamp(250px, 40vw, 550px);
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
        .parent-images {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
  
                width: 90vw;
            }

            .parent-images {
                gap: 10px;
            }

            .logo {
                width: 100px;
                max-height: 45px;
            }

            .logo-espa {
                width: 180px;
                max-height: 55px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px 15px;
                
                width: 70vw;
            }

            .parent-images {
                gap: 5px;
                padding: 0 5px;
            }

            .logo {
                width: 70px;
                max-height: 35px;
            }

            .logo-espa {
                width: 120px;
                max-height: 40px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div style="text-align: center;">
            <img src="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/espa.jpg" alt="Logo" class="logo-espa">
        </div>
        <h1>Ψηφιακά σημεία πληροφόρησης</h1>
        <p class="subtitle">Επιλέξτε τα κριτήρια φιλτραρίσματος</p>

        <div class="info-box">
            ℹ️ Επιλέξτε τον τύπο σχολείου, το φύλο και την Περιφέρεια για να φιλτράρετε τα δεδομένα δεξιοτήτων πριν ξεκινήσετε τη συνομιλία με το chatbot.
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
        <div class="parent-images">
               <img src="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/d2a.png" alt="Logo" class="logo">
                <img src="<?php echo htmlspecialchars($baseUrl ?? ''); ?>/images/eee_group.jpeg" alt="Logo" class="logo">

        </div>
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