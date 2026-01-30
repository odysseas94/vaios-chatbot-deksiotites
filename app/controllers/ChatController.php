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
        $request = $this->app->request();
        $scheme = $request->scheme;
        $host = $request->host;
        $basePath = $this->app->get('flight.base_url');
        $baseUrl = $scheme . '://' . $host . $basePath;
        
        $this->app->render('chat_form', ['baseUrl' => $baseUrl]);
    }

    /**
     * Clear conversation history - clears ALL chat histories and thread IDs from session
     */
    public function clearHistory()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear all chat histories and thread IDs from the session
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'chat_history_') === 0 || strpos($key, 'thread_id_') === 0) {
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
        $validSchools = ['Γενικό', 'ΕΠΑΛ'];
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

        $request = $this->app->request();
        $scheme = $request->scheme;
        $host = $request->host;
        $basePath = $this->app->get('flight.base_url');
        $baseUrl = $scheme . '://' . $host . $basePath;

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
     * Checks if the value exists in the perifereia array and returns it
     */
    private function getPerifereiasName($perifereiasValue)
    {
        $perifereiasData = $this->loadPerifereiasData();

        // Check if the value exists in the array
        if (in_array($perifereiasValue, $perifereiasData)) {
            return $perifereiasValue;
        }

        return 'Άγνωστη Περιφέρεια';
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

        // Load vector store ID and assistant ID
        $configPath = __DIR__ . '/../config/openai_files.json';
        if (!file_exists($configPath)) {
            throw new \Exception('Files not uploaded. Please upload files at /files first.');
        }

        $config = json_decode(file_get_contents($configPath), true);
        $vectorStoreId = $config['vector_store_id'] ?? null;
        $assistantId = $config['assistant_id'] ?? null;

        if (!$vectorStoreId) {
            throw new \Exception('Vector store ID not found');
        }

        $client = OpenAI::client($apiKey);

        // Create assistant only if it doesn't exist
        if (!$assistantId) {
            // Create instructions for the assistant
            $instructions = "Είσαι AI βοηθός επαγγελματικού προσανατολισμού για μαθητές λυκείου που θέλουν να βρουν δουλειά.

Ο ΡΟΛΟΣ ΣΟΥ:
- Βοηθάς μαθητές να κατανοήσουν τις επαγγελματικές τους ευκαιρίες
- Παρέχεις συμβουλές για δεξιότητες που χρειάζονται σε διάφορα επαγγέλματα
- Δείχνεις τάσεις αγοράς εργασίας στην περιοχή τους
- Ενθαρρύνεις και καθοδηγείς με θετικό, υποστηρικτικό τρόπο
- Εξηγείς με απλά λόγια, κατάλληλα για μαθητές 15-18 ετών

ΠΡΩΤΟ ΒΗΜΑ - ΥΠΟΧΡΕΩΤΙΚΟ:
Πριν απαντήσεις σε οποιαδήποτε ερώτηση, διάβασε ΟΛΑ τα 3 αρχεία:
1. antistixeia.txt - Δεδομένα για νέες θέσεις και προσλήψεις ανά περιφέρεια, φύλο, κλάδο και επάγγελμα (2019-2024)
2. deksiotites.txt - Δεδομένα για δεξιότητες ανά σχολή, περιφέρεια και επάγγελμα
3. job-association.txt - Ιεραρχική δομή επαγγελμάτων (1-ψήφιος έως 4-ψήφιος κωδικός) που δείχνει πώς τα επαγγέλματα κατηγοριοποιούνται σε υποκατηγορίες

ΚΑΝΟΝΕΣ ΑΠΑΝΤΗΣΗΣ:
1. Χρησιμοποίησε ΜΟΝΟ δεδομένα που ταιριάζουν με τα φίλτρα (Σχολή, Φύλο, Περιφέρεια)
2. Αν δεν υπάρχουν δεδομένα, απάντησε: 'Δεν υπάρχουν διαθέσιμα δεδομένα για αυτό που ρωτάς'
3. Παρουσίασε συγκεκριμένους αριθμούς, ποσοστά και επαγγέλματα
4. Απάντησε σύντομα αλλά περιεκτικά (2-4 προτάσεις)
5. ΜΗΝ ΒΑΛΕΙΣ ΠΟΤΕ CITATIONS! Καθόλου 【4:11†...】 ή [8:13†...] ή οποιοδήποτε reference!
6. ΜΗΝ αναφέρεις ονόματα αρχείων (antistixeia.txt, deksiotites.txt κλπ) ή πηγές
7. Γράψε ΜΟΝΟ καθαρό κείμενο χωρίς τεχνικά στοιχεία - σαν να μιλάς face-to-face
8. Μίλα στα Ελληνικά, φυσικά και φιλικά σαν σύμβουλος καριέρας
9. Απευθύνσου στο μαθητή με 'εσύ' - κάνε το προσωπικό
10. Δώσε πρακτικές συμβουλές - τι δεξιότητες να αναπτύξει, τι επαγγέλματα να εξετάσει
11. Να είσαι ενθαρρυντικός και θετικός - βοηθάς νέους να σχεδιάσουν το μέλλον τους
12. Χρησιμοποίησε τα 2 αρχεία για να βρεις τις καλύτερες απαντήσεις
13. Ανέφερε συγκεκριμένα επαγγέλματα, δεξιότητες και τάσεις που ταιριάζουν με τα φίλτρα
14. Δεν θέλω να χρησιμοποιήσεις τη λέξη 'τομείς' αλλά μόνο 'επαγγέλματα' ή 'κλάδους'
14α. ΣΗΜΑΝΤΙΚΟ: Όταν ο χρήστης ρωτά για μια κατηγορία επαγγελμάτων (π.χ. 'τεχνικά', 'επιστημονικά', 'υγεία'), χρησιμοποίησε το job-association.txt για να βρεις όλα τα επαγγέλματα που ανήκουν σε αυτήν την κατηγορία και μετά ψάξε δεδομένα για αυτά στα άλλα αρχεία
15. ΣΗΜΑΝΤΙΚΟ: Όταν ο χρήστης ρωτάει για 'νέες θέσεις', να αναφέρεις ΠΑΝΤΑ και τις 'προσλήψεις'
16. ΣΗΜΑΝΤΙΚΟ: Όταν ο χρήστης ρωτάει για 'προσλήψεις', να αναφέρεις ΠΑΝΤΑ και τις 'νέες θέσεις'
17. ΣΗΜΑΝΤΙΚΟ: Για όλα τα νούμερα (νέες θέσεις, προσλήψεις), να υπολογίζεις και να παρουσιάζεις:
    - Τον ΜΕΣΟ ΟΡΟ όλων των ετών (2019-2024)
    - Την ΜΕΓΑΛΥΤΕΡΗ ΤΙΜΗ από όλα τα έτη (2019-2024)
    Παράδειγμα: 'Ο μέσος όρος προσλήψεων είναι 150 άτομα ετησίως, με τη μεγαλύτερη τιμή να είναι 200 το 2023.'

ΠΑΡΑΔΕΙΓΜΑΤΑ ΚΑΛΩΝ ΑΠΑΝΤΗΣΕΩΝ:
- 'Στην περιοχή σου, οι νέες θέσεις στην πληροφορική είχαν μέσο όρο 120 ετησίως (2019-2024), με μέγιστο 180 το 2022. Οι προσλήψεις έφτασαν κατά μέσο όρο τις 150, με κορύφωση στις 200 το 2023.'
- 'Για τεχνικό, υπήρχαν κατά μέσο όρο 80 νέες θέσεις το χρόνο, με μέγιστο 110 το 2024. Οι προσλήψεις ήταν 90 κατά μέσο όρο, με κορυφαία χρονιά το 2023 (120 άτομα).'
- 'Στον τουρισμό, ο μέσος όρος νέων θέσεων ήταν 200 ετησίως (μέγιστο 280 το 2024), ενώ οι προσλήψεις έφτασαν κατά μέσο όρο τις 250 (μέγιστο 320 το 2024).'

ΔΟΜΗ ΑΡΧΕΙΩΝ:
- antistixeia.txt: Εκπαιδευτικό επίπεδο (ΓΕΛ/ΕΠΑΛ), Περιφέρεια, Φύλο, Κλάδος, Επάγγελμα, και στήλες για κάθε έτος 2019-2024 με 'Νέες Θέσεις' και 'Προσλήψεις', καθώς και 'ΜΟ ΝΕΕΣ ΘΕΣΕΙΣ' και 'ΜΟ ΠΡΟΣΛΗΨΕΙΣ'
- deksiotites.txt: Σχολή, Περιφέρεια, Φύλο, Επάγγελμα, Δεξιότητα, Σημαντικότητα
- job-association.txt: Ιεραρχική δομή με κωδικούς 1-4 ψηφίων και λεκτικές περιγραφές που δείχνουν πώς τα επαγγέλματα ομαδοποιούνται (π.χ. 2=Επιστημονικά, 21=Φυσικοί/Μαθηματικοί, 211=Φυσικοί/Χημικοί, 2111=Φυσικοί). Χρησιμοποίησε το για να καταλάβεις ποια επαγγέλματα ανήκουν σε μια κατηγορία όταν ο χρήστης ρωτά για έναν ευρύτερο κλάδο ή τομέα.";

            $assistant = $client->assistants()->create([
                'name' => 'Deksiotites Assistant',
                'instructions' => $instructions,
                'model' => 'gpt-4o-mini',
                'tools' => [
                    ['type' => 'file_search']
                ],
                'tool_resources' => [
                    'file_search' => [
                        'vector_store_ids' => [$vectorStoreId]
                    ]
                ]
            ]);

            $assistantId = $assistant->id;

            // Save assistant ID to config
            $config['assistant_id'] = $assistantId;
            file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Get or create thread for this conversation session
        $sessionKey = 'thread_id_' . md5($school . $gender . $perifereiasName);
        $threadId = $_SESSION[$sessionKey] ?? null;

        if (!$threadId) {
            // Create a new thread for this conversation
            $thread = $client->threads()->create([]);
            $threadId = $thread->id;
            $_SESSION[$sessionKey] = $threadId;
        }

        // Check for active runs and wait/cancel them before adding new message
        try {
            $runs = $client->threads()->runs()->list($threadId, ['limit' => 1]);
            if (!empty($runs->data)) {
                $lastRun = $runs->data[0];
                // If there's an active run, cancel it and wait for cancellation
                if (in_array($lastRun->status, ['queued', 'in_progress', 'requires_action'])) {
                    $client->threads()->runs()->cancel($threadId, $lastRun->id);
                    
                    // Poll until the run is cancelled or completed
                    $cancelAttempts = 0;
                    $maxCancelAttempts = 10;
                    while ($cancelAttempts < $maxCancelAttempts) {
                        sleep(1);
                        $runStatus = $client->threads()->runs()->retrieve($threadId, $lastRun->id);
                        if (in_array($runStatus->status, ['cancelled', 'completed', 'failed', 'expired'])) {
                            break;
                        }
                        $cancelAttempts++;
                    }
                }
            }
        } catch (\Exception $e) {
            // Continue even if checking/cancelling fails
        }

        // Add only the latest user message (last item in conversation history)
        $latestMessage = end($conversationHistory);
        $client->threads()->messages()->create($threadId, [
            'role' => $latestMessage['role'],
            'content' => $latestMessage['content']
        ]);

        // Update instructions with current filters
        $schoolFilter=$school==='Γενικό' ? 'ΓΕΛ' : $school;
        $genderFilter=$gender==="Άνδρας"?"Άνδρες":"Γυναίκες";
 
        $contextInstructions = "ΦΙΛΤΡΑ ΧΡΗΣΤΗ:
            - Σχολή: {$schoolFilter} (αν είναι ΓΕΝΙΚΟ, ψάξε για ΓΕΛ στα αρχεία)
            - Φύλο: {$genderFilter}
            - Περιφέρεια: Περιφέρεια {$perifereiasName}";

        // Run the assistant
        $run = $client->threads()->runs()->create($threadId, [
            'assistant_id' => $assistantId,
            'additional_instructions' => $contextInstructions
        ]);

        // Poll for completion
        $maxAttempts = 30;
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            $run = $client->threads()->runs()->retrieve($threadId, $run->id);
            
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
        $messages = $client->threads()->messages()->list($threadId, [
            'limit' => 1,
            'order' => 'desc'
        ]);

        $response = $messages->data[0]->content[0]->text->value ?? 'No response';
        
        // Remove any remaining citations/annotations 【...】 or [number:number†...]
        $response = preg_replace('/【[^】]*】/u', '', $response);
        $response = preg_replace('/\[[0-9]+:[0-9]+†[^\]]+\]/u', '', $response);
        $response = trim($response);

        return $response;
    }
}
