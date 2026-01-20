<?php

namespace app\controllers;

use flight\Engine;
use OpenAI;
use app\services\DataFilterService;

class ChatController
{

    protected Engine $app;
    protected DataFilterService $dataFilterService;

    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->dataFilterService = new DataFilterService();
    }

    /**
     * Display the form for filtering
     */
    public function showForm()
    {
        $this->app->render('chat_form');
    }

    /**
     * Clear conversation history
     */
    public function clearHistory()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $request = $this->app->request();
        $school = $request->data->school ?? '';
        $gender = $request->data->gender ?? '';
        $perifereia = $request->data->perifereia ?? '';

        $sessionKey = 'chat_history_' . md5($school . $gender . $perifereia);
        unset($_SESSION[$sessionKey]);

        $this->app->json(['success' => true]);
    }

    /**
     * Display the chat interface with filtered data
     */
    public function showChat()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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

        // Get perifereia display name (Greek key)
        $perifereiasName = $this->getPerifereiasName($perifereia);

        // Filter the data based on parameters - includes all data sources
        $filteredData = $this->filterAllData($school, $gender, $perifereia);

        // Get conversation history from session
        $sessionKey = 'chat_history_' . md5($school . $gender . $perifereia);
        $conversationHistory = $_SESSION[$sessionKey] ?? [];

        $this->app->render('chat_interface', [
            'school' => $school,
            'gender' => $gender,
            'perifereia' => $perifereia,
            'perifereiasName' => $perifereiasName,
            'filteredData' => json_encode($filteredData, JSON_UNESCAPED_UNICODE),
            'filteredDataArray' => $filteredData,
            'conversationHistory' => json_encode($conversationHistory, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Handle chat messages via AJAX
     */
    public function handleMessage()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $request = $this->app->request();
        $message = $request->data->message ?? '';
        $school = $request->data->school ?? '';
        $gender = $request->data->gender ?? '';
        $perifereia = $request->data->perifereia ?? '';

        if (empty($message) || empty($school) || empty($gender) || empty($perifereia)) {
            $this->app->json(['error' => 'Missing required fields'], 400);
            return;
        }

        // Get perifereia display name (Greek key)
        $perifereiasName = $this->getPerifereiasName($perifereia);

        // Get filtered data - includes all data sources
        $filteredData = $this->filterAllData($school, $gender, $perifereia);

        // Create unique session key for this conversation
        $sessionKey = 'chat_history_' . md5($school . $gender . $perifereia);

        // Initialize conversation history if not exists
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        // Add user message to conversation history
        $_SESSION[$sessionKey][] = ['role' => 'user', 'content' => $message];

        // Call OpenAI API with conversation history
        try {
            $response = $this->callOpenAI($_SESSION[$sessionKey], $filteredData, $school, $gender, $perifereiasName);
            
            // Add assistant response to conversation history
            $_SESSION[$sessionKey][] = ['role' => 'assistant', 'content' => $response];
            
            $this->app->json(['response' => $response]);
        } catch (\Exception $e) {
            $this->app->json(['error' => 'Failed to get response: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Filter all data sources using DataFilterService
     */
    private function filterAllData($school, $gender, $perifereia)
    {
        // Get all filtered data from the service
        return $this->dataFilterService->filterAllData($school, $gender, $perifereia);
    }

    /**
     * Get perifereia display name
     * Searches both keys and values in the perifereia map and returns the first Greek key in uppercase
     */
    private function getPerifereiasName($perifereiasValue)
    {
        $perifereiasData = $this->loadPerifereiasData();

        // Check if the value is a key (Greek name)
        if (isset($perifereiasData[$perifereiasValue])) {
            return mb_strtoupper($perifereiasValue, 'UTF-8'); // Return the Greek key in uppercase
        }

        // Check if the value is an English value - return the first Greek key found
        foreach ($perifereiasData as $greekName => $englishName) {
            if ($englishName === $perifereiasValue) {
                return mb_strtoupper($greekName, 'UTF-8'); // Return the Greek key in uppercase
            }
        }

        return 'ΑΓΝΩΣΤΗ ΠΕΡΙΦΕΡΕΙΑ';
    }

    /**
     * Load perifereia data from JSON
     */
    private function loadPerifereiasData()
    {
        $jsonPath = __DIR__ . '/../../resources/data/perifereia.json';
        $jsonContent = file_get_contents($jsonPath);
        return json_decode($jsonContent, true);
    }

    /**
     * Call OpenAI API with the filtered data and conversation history
     */
    private function callOpenAI($conversationHistory, $filteredData, $school, $gender, $perifereiasName)
    {
        $apiKey = $this->app->get('openai_api_key');

        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $client = OpenAI::client($apiKey);

        // Create a system prompt with context
        $systemPrompt = "Αναλύεις δεδομένα δεξιοτήτων και απασχόλησης αποφοίτων. 
                Σχολή: {$school}, Φύλο: {$gender}, Περιφέρεια: {$perifereiasName}.\n\n
                ΚΑΝΟΝΕΣ:\n
                1. Χρησιμοποίησε ΜΟΝΟ τα δεδομένα που ακολουθούν - μην κάνεις υποθέσεις\n
                2. Αν δεν υπάρχουν δεδομένα για μια ερώτηση, απάντησε 'Δεν υπάρχουν διαθέσιμα δεδομένα'\n
                3. Παρουσίασε αριθμούς και ποσοστά όπου υπάρχουν\n
                4. Απάντησε σύντομα και ουσιαστικά - 2-3 προτάσεις maximum\n
                5. Μην αναφέρεις πηγές, μοντέλα AI, ή τεχνικές λεπτομέρειες\n\n
                ΠΗΓΕΣ ΔΕΔΟΜΕΝΩΝ:\n
                - antistixeia: Αντιστοιχία δεξιοτήτων με κλάδους (importance = Επάρκεια/Ανεπάρκεια, τιμές = συσχετισμός)\n
                - deksiotites: Δεξιότητες ανά σχολή, περιοχή και επάγγελμα (importance = σημαντικότητα)\n
                - hiring_job: Προσλήψεις ανά επάγγελμα, περιοχή και μήνα (pct_diff = % μεταβολή, count = αριθμός προσλήψεων)\n
                - isozygio: Ισοζύγιο απασχόλησης ανά σχολή, κλάδο, έτος (τιμές = αλλαγή απασχόλησης)\n\n
                ΔΕΔΟΜΕΝΑ:\n
                ";


        // Encode with proper UTF-8 handling and validation
        $jsonData = json_encode($filteredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($jsonData === false) {
            throw new \Exception('Failed to encode data: ' . json_last_error_msg());
        }


        $systemPrompt .= $jsonData;

        // // Limit the context size if it's too large
        // if (strlen($systemPrompt) > 120000) {
        //     $systemPrompt = mb_substr($systemPrompt, 0, 120000, 'UTF-8') . "\n... (δεδομένα περικομμένα λόγω μεγέθους)";
        // }
        
        // Build messages array with system prompt and conversation history
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $messages[] = $msg;
        }


        die(json_encode($messages));

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.4,
        ]);

        return $response->choices[0]->message->content;
    }
}
