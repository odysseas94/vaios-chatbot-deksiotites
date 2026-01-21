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
        $baseUrl = $this->app->get('flight.base_url');
        $this->app->render('chat_form', ['baseUrl' => $baseUrl]);
    }

    /**
     * Clear conversation history - clears ALL chat histories from session
     */
    public function clearHistory()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear all chat histories from the session
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'chat_history_') === 0) {
                unset($_SESSION[$key]);
            }
        }

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

        // Get conversation history from session
        $sessionKey = 'chat_history_' . md5($school . $gender . $perifereia);
        $conversationHistory = $_SESSION[$sessionKey] ?? [];

        $baseUrl = $this->app->get('flight.base_url');

        $this->app->render('chat_interface', [
            'school' => $school,
            'gender' => $gender,
            'perifereia' => $perifereia,
            'perifereiasName' => $perifereiasName,
            'conversationHistory' => json_encode($conversationHistory, JSON_UNESCAPED_UNICODE),
            'baseUrl' => $baseUrl
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
            $response = $this->callOpenAI($_SESSION[$sessionKey], $school, $gender, $perifereiasName);
            
            // Add assistant response to conversation history
            $_SESSION[$sessionKey][] = ['role' => 'assistant', 'content' => $response];
            
            $this->app->json(['response' => $response]);
        } catch (\Exception $e) {
            $this->app->json(['error' => 'Failed to get response: ' . $e->getMessage()], 500);
        }
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
     * Call OpenAI API with vector store file search using Assistants API
     */
    private function callOpenAI($conversationHistory, $school, $gender, $perifereiasName)
    {
        $apiKey = $this->app->get('openai_api_key');

        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        // Load vector store ID
        $configPath = __DIR__ . '/../config/openai_files.json';
        if (!file_exists($configPath)) {
            throw new \Exception('Files not uploaded. Please upload files at /files first.');
        }

        $config = json_decode(file_get_contents($configPath), true);
        $vectorStoreId = $config['vector_store_id'] ?? null;

        if (!$vectorStoreId) {
            throw new \Exception('Vector store ID not found');
        }

        $client = OpenAI::client($apiKey);

        // Create instructions for the assistant
        $instructions = "Είσαι AI βοηθός επαγγελματικού προσανατολισμού για μαθητές λυκείου που θέλουν να βρουν δουλειά.

Ο ΡΟΛΟΣ ΣΟΥ:
- Βοηθάς μαθητές να κατανοήσουν τις επαγγελματικές τους ευκαιρίες
- Παρέχεις συμβουλές για δεξιότητες που χρειάζονται σε διάφορα επαγγέλματα
- Δείχνεις τάσεις αγοράς εργασίας στην περιοχή τους
- Ενθαρρύνεις και καθοδηγείς με θετικό, υποστηρικτικό τρόπο
- Εξηγείς με απλά λόγια, κατάλληλα για μαθητές 15-18 ετών

ΠΡΩΤΟ ΒΗΜΑ - ΥΠΟΧΡΕΩΤΙΚΟ:
Πριν απαντήσεις σε οποιαδήποτε ερώτηση, διάβασε ΟΛΑ τα 4 αρχεία:
1. antistixeia.json - Αντιστοιχία δεξιοτήτων με κλάδους
2. deksiotites.json - Δεξιότητες ανά σχολή, περιοχή, επάγγελμα
3. hiring_job.json - Προσλήψεις ανά περιοχή, φύλο, επάγγελμα
4. isozygio.json - Ισοζύγιο απασχόλησης ανά σχολή, κλάδο, έτος

ΦΙΛΤΡΑ ΧΡΗΣΤΗ:
- Σχολή: {$school} (αν είναι ΓΕΝΙΚΟ, ψάξε για ΓΕΛ στα αρχεία)
- Φύλο: {$gender}
- Περιφέρεια: {$perifereiasName}

ΚΑΝΟΝΕΣ ΑΠΑΝΤΗΣΗΣ:
1. Χρησιμοποίησε ΜΟΝΟ δεδομένα που ταιριάζουν με τα φίλτρα
2. Αν δεν υπάρχουν δεδομένα, απάντησε: 'Δεν υπάρχουν διαθέσιμα δεδομένα για αυτό που ρωτάς'
3. Παρουσίασε συγκεκριμένους αριθμούς, ποσοστά και επαγγέλματα
4. Απάντησε σύντομα αλλά περιεκτικά (2-4 προτάσεις)
5. ΜΗΝ ΒΑΛΕΙΣ ΠΟΤΕ CITATIONS! Καθόλου 【4:11†...】 ή [8:13†...] ή οποιοδήποτε reference!
6. ΜΗΝ αναφέρεις ονόματα αρχείων (antistixeia.json, deksiotites.json κλπ) ή πηγές
7. Γράψε ΜΟΝΟ καθαρό κείμενο χωρίς τεχνικά στοιχεία - σαν να μιλάς face-to-face
8. Μίλα στα Ελληνικά, φυσικά και φιλικά σαν σύμβουλος καριέρας
9. Απευθύνσου στο μαθητή με 'εσύ' - κάνε το προσωπικό
10. Δώσε πρακτικές συμβουλές - τι δεξιότητες να αναπτύξει, τι επαγγέλματα να εξετάσει
11. Να είσαι ενθαρρυντικός και θετικός - βοηθάς νέους να σχεδιάσουν το μέλλον τους

ΠΑΡΑΔΕΙΓΜΑΤΑ ΚΑΛΩΝ ΑΠΑΝΤΗΣΕΩΝ:
- 'Στην περιοχή σου υπάρχουν πολλές ευκαιρίες στον τομέα της πληροφορικής. Συγκεκριμένα, δουλειές σαν προγραμματιστής έχουν αυξηθεί κατά 15% φέτος.'
- 'Για να δουλέψεις ως τεχνικός, θα χρειαστείς δεξιότητες σε μηχανολογία και ηλεκτρολογία. Υπάρχουν 120 θέσεις εργασίας διαθέσιμες.'
- 'Τα επαγγέλματα στον τουρισμό είναι δημοφιλή στην περιοχή σου. Θα ήταν καλό να αναπτύξεις ξένες γλώσσες και επικοινωνία.'

ΔΟΜΗ ΑΡΧΕΙΩΝ:
- antistixeia: [school, skill, occupation, importance, values...]
- deksiotites: [school, location, occupation, skill, importance]
- hiring_job: [occupation, region, gender, month, count, pct_diff]
- isozygio: [school, industry, year, ΓΕΛ_* columns, ΕΠΑΛ_* columns]";

        // Create or get assistant
        $assistant = $client->assistants()->create([
            'name' => 'Deksiotites Assistant',
            'instructions' => $instructions,
            'model' => 'gpt-4o',
            'tools' => [
                ['type' => 'file_search']
            ],
            'tool_resources' => [
                'file_search' => [
                    'vector_store_ids' => [$vectorStoreId]
                ]
            ]
        ]);

        // Create a thread
        $thread = $client->threads()->create([]);

        // Add all conversation history to thread
        foreach ($conversationHistory as $msg) {
            $client->threads()->messages()->create($thread->id, [
                'role' => $msg['role'],
                'content' => $msg['content']
            ]);
        }

        // Run the assistant
        $run = $client->threads()->runs()->create($thread->id, [
            'assistant_id' => $assistant->id
        ]);

        // Poll for completion
        $maxAttempts = 30;
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            $run = $client->threads()->runs()->retrieve($thread->id, $run->id);
            
            if ($run->status === 'completed') {
                break;
            } elseif ($run->status === 'failed' || $run->status === 'cancelled' || $run->status === 'expired') {
                throw new \Exception('Assistant run failed: ' . $run->status);
            }
            
            sleep(1);
            $attempts++;
        }

        if ($attempts >= $maxAttempts) {
            throw new \Exception('Assistant run timed out');
        }

        // Get the assistant's response
        $messages = $client->threads()->messages()->list($thread->id, [
            'limit' => 1,
            'order' => 'desc'
        ]);

        $response = $messages->data[0]->content[0]->text->value ?? 'No response';
        
        // Remove any remaining citations/annotations 【...】 or [number:number†...]
        $response = preg_replace('/【[^】]*】/u', '', $response);
        $response = preg_replace('/\[[0-9]+:[0-9]+†[^\]]+\]/u', '', $response);
        $response = trim($response);

        // Clean up - delete thread and assistant
        $client->threads()->delete($thread->id);
        $client->assistants()->delete($assistant->id);

        return $response;
    }
}
