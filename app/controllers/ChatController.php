<?php

namespace app\controllers;

use flight\Engine;
use OpenAI;

class ChatController {

    protected Engine $app;
    
    public function __construct(Engine $app) {
        $this->app = $app;
    }

    /**
     * Display the form for filtering
     */
    public function showForm() {
        $this->app->render('chat_form');
    }

    /**
     * Display the chat interface with filtered data
     */
    public function showChat() {
        $school = $this->app->request()->query['school'] ?? '';
        $gender = $this->app->request()->query['gender'] ?? '';
        $perifereia = $this->app->request()->query['perifereia'] ?? '';

        // Validate inputs
        $validSchools = ['ΓΕΝΙΚΟ', 'ΕΠΑΛ'];
        $validGenders = ['Άνδρας', 'Γυναίκα'];

        if (!in_array($school, $validSchools) || !in_array($gender, $validGenders) || empty($perifereia)) {
            $this->app->redirect('/chat');
            return;
        }

        // Get perifereia name
        $perifereiasName = $this->getPerifereiasName($perifereia);

        // Filter the data based on school and gender
        $filteredData = $this->filterData($school, $gender);

        $this->app->render('chat_interface', [
            'school' => $school,
            'gender' => $gender,
            'perifereia' => $perifereia,
            'perifereiasName' => $perifereiasName,
            'filteredData' => json_encode($filteredData, JSON_UNESCAPED_UNICODE),
            'filteredDataArray' => $filteredData
        ]);
    }

    /**
     * Handle chat messages via AJAX
     */
    public function handleMessage() {
        $request = $this->app->request();
        $message = $request->data->message ?? '';
        $school = $request->data->school ?? '';
        $gender = $request->data->gender ?? '';
        $perifereia = $request->data->perifereia ?? '';

        if (empty($message) || empty($school) || empty($gender) || empty($perifereia)) {
            $this->app->json(['error' => 'Missing required fields'], 400);
            return;
        }

        // Get perifereia name
        $perifereiasName = $this->getPerifereiasName($perifereia);

        // Get filtered data
        $filteredData = $this->filterData($school, $gender);

        // Call OpenAI API
        try {
            $response = $this->callOpenAI($message, $filteredData, $school, $gender, $perifereiasName);
            $this->app->json(['response' => $response]);
        } catch (\Exception $e) {
            $this->app->json(['error' => 'Failed to get response: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Filter the JSON data based on school and gender
     */
    private function filterData($school, $gender) {
        $jsonPath = __DIR__ . '/../../resources/data/combined_deksiotites.json';
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        $filteredData = [];

        // Determine which sections to include based on school type
        foreach ($data as $sectionName => $sectionData) {
            $shouldInclude = false;

            if ($school === 'ΓΕΝΙΚΟ') {
                // Include sections for ΓΕΛ (General High School)
                if (strpos($sectionName, 'ΓΕΛ') !== false || 
                    strpos($sectionName, 'ΓΕΝΙΚΟ') !== false ||
                    (strpos($sectionName, 'ΕΠΑΛ') === false && strpos($sectionName, 'δεξιοτήτων') !== false)) {
                    $shouldInclude = true;
                }
            } elseif ($school === 'ΕΠΑΛ') {
                // Include sections for ΕΠΑΛ (Vocational High School)
                if (strpos($sectionName, 'ΕΠΑΛ') !== false ||
                    (strpos($sectionName, 'ΓΕΛ') === false && strpos($sectionName, 'δεξιοτήτων') !== false)) {
                    $shouldInclude = true;
                }
            }

            // If the section should be included, filter by gender if applicable
            if ($shouldInclude) {
                // For now, we include all data as the JSON doesn't seem to have gender-specific fields
                // You can enhance this later if gender-specific data exists
                $filteredData[$sectionName] = $sectionData;
            }
        }

        return $filteredData;
    }

    /**
     * Get perifereia name by ID
     */
    private function getPerifereiasName($perifereiasId) {
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
     * Call OpenAI API with the filtered data
     */
    private function callOpenAI($message, $filteredData, $school, $gender, $perifereiasName) {
        $apiKey = $this->app->get('openai_api_key');
        
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $client = OpenAI::client($apiKey);

        // Create a system prompt with context
        $systemPrompt = "Είσαι ένας βοηθός που βοηθά να αναλύσει δεδομένα δεξιοτήτων για αποφοίτους. ";
        $systemPrompt .= "Τα δεδομένα αφορούν αποφοίτους από {$school}, φύλο {$gender}, στην περιφέρεια {$perifereiasName}. ";
        $systemPrompt .= "Χρησιμοποίησε τα παρακάτω δεδομένα για να απαντήσεις στις ερωτήσεις:\n\n";
        $systemPrompt .= json_encode($filteredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Limit the context size if it's too large
        if (strlen($systemPrompt) > 12000) {
            $systemPrompt = substr($systemPrompt, 0, 12000) . "\n... (δεδομένα περικομμένα λόγω μεγέθους)";
        }

        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);

        return $response->choices[0]->message->content;
    }
}
