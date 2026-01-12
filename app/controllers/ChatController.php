<?php

namespace app\controllers;

use flight\Engine;
use OpenAI;

class ChatController
{

    protected Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Display the form for filtering
     */
    public function showForm()
    {
        $this->app->render('chat_form');
    }

    /**
     * Display the chat interface with filtered data
     */
    public function showChat()
    {
        $school = $this->app->request()->query['school'] ?? '';
        $gender = $this->app->request()->query['gender'] ?? '';
        $perifereia = $this->app->request()->query['perifereia'] ?? '';
        $klados = $this->app->request()->query['klados'] ?? '';

        // Validate inputs
        $validSchools = ['ΓΕΝΙΚΟ', 'ΕΠΑΛ'];
        $validGenders = ['Άνδρας', 'Γυναίκα'];

        if (!in_array($school, $validSchools) || !in_array($gender, $validGenders) || empty($perifereia) || $klados === '') {
            $this->app->redirect('/chat');
            return;
        }

        // Get perifereia name
        $perifereiasName = $this->getPerifereiasName($perifereia);

        // Get klados name
        $kladosName = $this->getKladosName($klados);

        // Filter the data based on all parameters
        $filteredData = $this->filterData($school, $gender, $perifereia, $klados);
    

        $this->app->render('chat_interface', [
            'school' => $school,
            'gender' => $gender,
            'perifereia' => $perifereia,
            'perifereiasName' => $perifereiasName,
            'klados' => $klados,
            'kladosName' => $kladosName,
            'filteredData' => json_encode($filteredData, JSON_UNESCAPED_UNICODE),
            'filteredDataArray' => $filteredData
        ]);
    }

    /**
     * Handle chat messages via AJAX
     */
    public function handleMessage()
    {
        $request = $this->app->request();
        $message = $request->data->message ?? '';
        $school = $request->data->school ?? '';
        $gender = $request->data->gender ?? '';
        $perifereia = $request->data->perifereia ?? '';
        $klados = $request->data->klados ?? '';

        if (empty($message) || empty($school) || empty($gender) || empty($perifereia) || $klados === '') {
            $this->app->json(['error' => 'Missing required fields'], 400);
            return;
        }

        // Get perifereia name
        $perifereiasName = $this->getPerifereiasName($perifereia);

        // Get klados name
        $kladosName = $this->getKladosName($klados);

        // Get filtered data
        $filteredData = $this->filterData($school, $gender, $perifereia, $klados);
  

        // Call OpenAI API
        try {
            $response = $this->callOpenAI($message, $filteredData, $school, $gender, $perifereiasName, $kladosName);
            $this->app->json(['response' => $response]);
        } catch (\Exception $e) {
            $this->app->json(['error' => 'Failed to get response: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Filter the JSON data based on school, gender, perifereia and klados
     */
    private function filterData($school, $gender, $perifereia = null, $klados = null)
    {
        $jsonPath = __DIR__ . '/../../resources/data/combined_deksiotites.json';
        $jsonContent = file_get_contents($jsonPath);

        // Ensure UTF-8 encoding
        $jsonContent = mb_convert_encoding($jsonContent, 'UTF-8', 'UTF-8');

        $data = json_decode($jsonContent, true);


        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        // Get klados name if index provided
        $kladosName = null;
        if ($klados !== null) {
            $kladosName = $this->getKladosName($klados);
        }

        $filteredData = [];

        // Determine which sections to include based on school type
        foreach ($data as $sectionName => $sectionData) {
            $shouldInclude = false;

            if ($school === 'ΓΕΝΙΚΟ') {
                // For ΓΕΝΙΚΟ (ΓΕΛ): Exclude any section that contains "ΕΠΑΛ" (unless it's "ΓΕΛ - ΕΠΑΛ")
                if (strpos($sectionName, 'ΓΕΛ - ΕΠΑΛ') !== false) {
                    // Include shared ΓΕΛ - ΕΠΑΛ sections
                    $shouldInclude = true;
                } elseif (strpos($sectionName, 'ΕΠΑΛ') === false) {
                    // Include sections that don't contain "ΕΠΑΛ" at all
                    $shouldInclude = true;
                }
            } elseif ($school === 'ΕΠΑΛ') {
                // For ΕΠΑΛ: Exclude any section that contains "ΓΕΛ" (unless it's "ΓΕΛ - ΕΠΑΛ")
                if (strpos($sectionName, 'ΓΕΛ - ΕΠΑΛ') !== false) {
                    // Include shared ΓΕΛ - ΕΠΑΛ sections
                    $shouldInclude = true;
                } elseif (strpos($sectionName, 'ΓΕΛ') === false || strpos($sectionName, 'ΕΠΑΛ') !== false) {
                    // Include sections that contain "ΕΠΑΛ" or don't contain "ΓΕΛ"
                    if (strpos($sectionName, 'ΕΠΑΛ') !== false || strpos($sectionName, 'ΓΕΛ') === false) {
                        $shouldInclude = true;
                    }
                }
            }

            if ($shouldInclude && is_array($sectionData)) {
                $filteredSection = [];

                foreach ($sectionData as $item) {

                    $found = false;
                    if (!is_array($item)) {
                        continue;
                    }

                    // If klados is specified, filter columns to only include the specific klados
                    if ($kladosName !== null) {


                        foreach ($item as $key => $value) {
                            if (is_string($value) && ($kladosName === $key || $value === $kladosName)) {
                                $found = true;
                            }
                        }

                        if ($found)
                            $filteredSection[] = $this->cleanArrayUtf8($item);
                    } else {
                        // No klados filter, include all data but clean it
                        $filteredSection[] = $this->cleanArrayUtf8($item);
                    }
                }

                if (!empty($filteredSection)) {
                    $filteredData[$sectionName] = $filteredSection;
                }
            }
        }

        return $filteredData;
    }

    /**
     * Get perifereia name by ID
     */
    private function getPerifereiasName($perifereiasId)
    {
        $jsonPath = __DIR__ . '/../../resources/data/perifereia.json';
        $jsonContent = file_get_contents($jsonPath);
        $perifereiasData = json_decode($jsonContent, true);

        foreach ($perifereiasData as $perifereia) {
            if ($perifereia['id'] === $perifereiasId) {
                return $perifereia['name'];
            }
        }

        return 'Άγνωστη Περιφέρεια';
    }

    /**
     * Get klados name by index
     */
    private function getKladosName($kladosIndex)
    {
        $jsonPath = __DIR__ . '/../../resources/data/klados.json';
        $jsonContent = file_get_contents($jsonPath);
        $kladosData = json_decode($jsonContent, true);

        if (isset($kladosData[$kladosIndex])) {
            return $kladosData[$kladosIndex];
        }

        return 'Άγνωστος Κλάδος';
    }

    /**
     * Call OpenAI API with the filtered data
     */
    private function callOpenAI($message, $filteredData, $school, $gender, $perifereiasName, $kladosName)
    {
        $apiKey = $this->app->get('openai_api_key');

        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $client = OpenAI::client($apiKey);

        // Create a system prompt with context
        $systemPrompt = "Είσαι ένας βοηθός που βοηθά να αναλύσει δεδομένα δεξιοτήτων για αποφοίτους. ";
        $systemPrompt .= "Τα δεδομένα αφορούν αποφοίτους από {$school}, φύλο {$gender}, στην περιφέρεια {$perifereiasName}, κλάδος {$kladosName}. ";
        $systemPrompt .= "Χρησιμοποίησε τα παρακάτω δεδομένα για να απαντήσεις στις ερωτήσεις:\n\n";

        // Encode with proper UTF-8 handling and validation
        $jsonData = json_encode($filteredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($jsonData === false) {
            throw new \Exception('Failed to encode data: ' . json_last_error_msg());
        }

        $systemPrompt .= $jsonData;

        // Limit the context size if it's too large
        if (strlen($systemPrompt) > 12000) {
            $systemPrompt = mb_substr($systemPrompt, 0, 12000, 'UTF-8') . "\n... (δεδομένα περικομμένα λόγω μεγέθους)";
        }

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);

        return $response->choices[0]->message->content;
    }

    /**
     * Clean a string to ensure valid UTF-8 encoding
     */
    private function cleanUtf8($string)
    {
        if (!is_string($string)) {
            return $string;
        }

        // Remove invalid UTF-8 characters
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remove null bytes and other control characters except newlines and tabs
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        return $string;
    }

    /**
     * Clean all strings in an array recursively
     */
    private function cleanArrayUtf8($array)
    {
        if (!is_array($array)) {
            return $this->cleanUtf8($array);
        }

        $cleaned = [];
        foreach ($array as $key => $value) {
            $cleanKey = $this->cleanUtf8($key);
            if (is_array($value)) {
                $cleaned[$cleanKey] = $this->cleanArrayUtf8($value);
            } else {
                $cleaned[$cleanKey] = $this->cleanUtf8($value);
            }
        }

        return $cleaned;
    }
}
