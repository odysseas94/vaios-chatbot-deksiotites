<?php

namespace app\controllers;

use flight\Engine;
use OpenAI;

class FileUploadController
{
    protected Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Upload JSON files to OpenAI and create a vector store
     */
    public function uploadFiles()
    {
        $apiKey = $this->app->get('openai_api_key');

        if (empty($apiKey)) {
            $this->app->json(['error' => 'OpenAI API key not configured'], 500);
            return;
        }

        try {
            $client = OpenAI::client($apiKey);

            // Define the files to upload
            $files = [
                'antistixeia' => __DIR__ . '/../../resources/csv/antistixeia.txt',
                'deksiotites' => __DIR__ . '/../../resources/csv/deksiotites.txt',
            ];

            $uploadedFileIds = [];

            // Upload each file
            foreach ($files as $name => $path) {
                if (!file_exists($path)) {
                    throw new \Exception("File not found: {$name}");
                }
       
                $response = $client->files()->upload([
                    'purpose' => 'assistants',
                    'file' => fopen($path, 'r'),
                ]);

                $uploadedFileIds[$name] = $response->id;
            }

            // Create a vector store with these files
            $vectorStore = $client->vectorStores()->create([
                'name' => 'Deksiotites Data Store',
                'file_ids' => array_values($uploadedFileIds),
            ]);

            // Store the vector store ID and file IDs in a config file
            $configPath = __DIR__ . '/../config/openai_files.json';
            $config = [
                'vector_store_id' => $vectorStore->id,
                'file_ids' => $uploadedFileIds,
                'uploaded_at' => date('Y-m-d H:i:s'),
            ];

            file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->app->json([
                'success' => true,
                'vector_store_id' => $vectorStore->id,
                'file_ids' => $uploadedFileIds,
                'message' => 'Files uploaded successfully'
            ]);

        } catch (\Exception $e) {
            $this->app->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get the current vector store and file IDs
     */
    public function getUploadedFiles()
    {
        $configPath = __DIR__ . '/../config/openai_files.json';

        if (!file_exists($configPath)) {
            $this->app->json(['error' => 'No files uploaded yet'], 404);
            return;
        }

        $config = json_decode(file_get_contents($configPath), true);
        $this->app->json($config);
    }

    /**
     * Delete uploaded files and vector store
     */
    public function deleteUploadedFiles()
    {
        $apiKey = $this->app->get('openai_api_key');

        if (empty($apiKey)) {
            $this->app->json(['error' => 'OpenAI API key not configured'], 500);
            return;
        }

        $configPath = __DIR__ . '/../config/openai_files.json';

        if (!file_exists($configPath)) {
            $this->app->json(['error' => 'No files uploaded yet'], 404);
            return;
        }

        try {
            $client = OpenAI::client($apiKey);
            $config = json_decode(file_get_contents($configPath), true);

            // Delete vector store
            if (isset($config['vector_store_id'])) {
                $client->vectorStores()->delete($config['vector_store_id']);
            }

            // Delete individual files
            if (isset($config['file_ids'])) {
                foreach ($config['file_ids'] as $fileId) {
                    $client->files()->delete($fileId);
                }
            }

            // Remove config file
            unlink($configPath);

            $this->app->json(['success' => true, 'message' => 'Files deleted successfully']);

        } catch (\Exception $e) {
            $this->app->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show upload management page
     */
    public function showUploadPage()
    {
        $request = $this->app->request();
        $scheme = $request->scheme;
        $host = $request->host;
        $basePath = $this->app->get('flight.base_url');
        $baseUrl = $scheme . '://' . $host . $basePath;
        
        $this->app->render('file_upload', ['baseUrl' => $baseUrl]);
    }
}
