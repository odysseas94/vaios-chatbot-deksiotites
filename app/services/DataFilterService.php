<?php

namespace app\services;

class DataFilterService
{
    private $perifereiasVariations = [];

    /**
     * Load perifereia data and get all variations
     */
    private function loadPerifereiasData()
    {
        $jsonPath = __DIR__ . '/../../resources/data/perifereia.json';
        $jsonContent = file_get_contents($jsonPath);
        return json_decode($jsonContent, true);
    }

    /**
     * Get all possible names (Greek and English) for a perifereia
     */
    private function getPerifereiasVariations($perifereiasValue)
    {
        if (isset($this->perifereiasVariations[$perifereiasValue])) {
            return $this->perifereiasVariations[$perifereiasValue];
        }

        $perifereiasData = $this->loadPerifereiasData();
        $variations = [];

        // Find the English name for this perifereia
        $englishName = null;
        if (isset($perifereiasData[$perifereiasValue])) {
            $englishName = $perifereiasData[$perifereiasValue];
        } else {
            // Check if the value is an English value
            foreach ($perifereiasData as $greekName => $engName) {
                if ($engName === $perifereiasValue) {
                    $englishName = $engName;
                    break;
                }
            }
        }

        if ($englishName) {
            // Find all Greek names that map to this English name
            foreach ($perifereiasData as $greekName => $engName) {
                if ($engName === $englishName) {
                    $variations[] = $greekName;
                    $variations[] = mb_strtoupper($greekName, 'UTF-8');
                    $variations[] = mb_strtolower($greekName, 'UTF-8');
                }
            }
            // Add the English name
            $variations[] = $englishName;
            $variations[] = strtoupper($englishName);
            $variations[] = strtolower($englishName);
        }

        $this->perifereiasVariations[$perifereiasValue] = array_unique($variations);
        return $this->perifereiasVariations[$perifereiasValue];
    }

    /**
     * Check if a region matches any perifereia variation using fuzzy matching
     * Returns true if similarity is >= 80%
     * 
     * @param string $regionField - The region value from the data
     * @param array $perifereiasVariations - Array of perifereia variations to match against
     * @return bool
     */
    private function regionMatchesFuzzy($regionField, $perifereiasVariations)
    {
        // Clean up the region field before comparison
        $regionField = mb_strtolower($regionField, 'UTF-8');
        
        // Remove common prefixes
        $regionField = preg_replace('/^(περιφέρεια|περιφερεια|region of|region)\s*/iu', '', $regionField);
        
        // Normalize separators and special characters
        $regionField = str_replace(['&', ' και ', ' & '], ' ', $regionField);
        $regionField = preg_replace('/\s+/', ' ', $regionField); // normalize spaces
        $regionField = trim($regionField);
        
        foreach ($perifereiasVariations as $variation) {
            $variation = mb_strtolower($variation, 'UTF-8');
            
            // Apply same cleaning to variation
            $cleanedVariation = preg_replace('/^(περιφέρεια|περιφερεια|region of|region)\s*/iu', '', $variation);
            $cleanedVariation = str_replace(['&', ' και ', ' & '], ' ', $cleanedVariation);
            $cleanedVariation = preg_replace('/\s+/', ' ', $cleanedVariation);
            $cleanedVariation = trim($cleanedVariation);
            
            // Calculate similarity percentage
            similar_text($regionField, $cleanedVariation, $percent);
            
            // Also check reverse to handle cases like "Αττικής" containing "Αττική"
            similar_text($cleanedVariation, $regionField, $percentReverse);
            
            // Use the higher percentage
            $maxPercent = max($percent, $percentReverse);
            
            // Match if similarity is 70% or higher
            if ($maxPercent >= 70.0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Filter antistixeia.json - Filter by school only
     * 
     * @param string $school - ΓΕΝΙΚΟ or ΕΠΑΛ
     * @return array
     */
    public function filterAntistixeia($school)
    {
        $jsonPath = __DIR__ . '/../../resources/data/antistixeia.json';
        $data = $this->loadJsonFile($jsonPath);

        $filteredData = [];

        foreach ($data as $item) {
            if (!isset($item['school'])) {
                continue;
            }

            $schoolField = $item['school'];
            $shouldInclude = false;

            if ($school === 'ΓΕΝΙΚΟ') {
                // Include if contains ΓΕΛ but not pure ΕΠΑΛ
                if (strpos($schoolField, 'ΓΕΛ') !== false) {
                    $shouldInclude = true;
                } elseif (strpos($schoolField, 'ΕΠΑΛ') === false) {
                    // Include neutral entries (no ΓΕΛ and no ΕΠΑΛ)
                    $shouldInclude = true;
                }
            } elseif ($school === 'ΕΠΑΛ') {
                // Include if contains ΕΠΑΛ
                if (strpos($schoolField, 'ΕΠΑΛ') !== false) {
                    $shouldInclude = true;
                } elseif (strpos($schoolField, 'ΓΕΛ') === false) {
                    // Include neutral entries (no ΓΕΛ and no ΕΠΑΛ)
                    $shouldInclude = true;
                }
            }

            if ($shouldInclude) {
                $filteredData[] = $item;
            }
        }

        return $filteredData;
    }

    /**
     * Filter deksiotites.json - Filter by school and location
     * 
     * @param string $school - ΓΕΝΙΚΟ or ΕΠΑΛ
     * @param string $perifereia - Selected perifereia
     * @return array
     */
    public function filterDeksiotites($school, $perifereia)
    {
        $jsonPath = __DIR__ . '/../../resources/data/deksiotites.json';
        $data = $this->loadJsonFile($jsonPath);

        $perifereiasVariations = $this->getPerifereiasVariations($perifereia);
        $filteredData = [];

        foreach ($data as $item) {
            if (!isset($item['school']) || !isset($item['location'])) {
                continue;
            }

            $schoolField = $item['school'];
            $locationField = $item['location'];

            // Check if location matches any perifereia variation
            $locationMatches = false;
            foreach ($perifereiasVariations as $variation) {
                if (stripos($locationField, $variation) !== false || stripos($variation, $locationField) !== false) {
                    $locationMatches = true;
                    break;
                }
            }

            if (!$locationMatches) {
                continue;
            }

            // Check school filter
            $shouldInclude = false;

            if ($school === 'ΓΕΝΙΚΟ') {
                // Include if contains ΓΕΛ but not pure ΕΠΑΛ entries
                if (strpos($schoolField, 'ΓΕΛ') !== false) {
                    $shouldInclude = true;
                } elseif (strpos($schoolField, 'ΕΠΑΛ') === false) {
                    // Neutral entries (like ΗΠΙΕΣ) - include if no ΓΕΛ and no ΕΠΑΛ
                    $shouldInclude = true;
                }
            } elseif ($school === 'ΕΠΑΛ') {
                // Include if contains ΕΠΑΛ
                if (strpos($schoolField, 'ΕΠΑΛ') !== false) {
                    $shouldInclude = true;
                } elseif (strpos($schoolField, 'ΓΕΛ') === false) {
                    // Neutral entries - include if no ΓΕΛ and no ΕΠΑΛ
                    $shouldInclude = true;
                }
            }

            if ($shouldInclude) {
                $filteredData[] = $item;
            }
        }

        return $filteredData;
    }

    /**
     * Filter hiring_job.json - Filter by region and gender
     * 
     * @param string $gender - Άνδρας or Γυναίκα
     * @param string $perifereia - Selected perifereia
     * @return array
     */
    public function filterHiringJob($gender, $perifereia)
    {
        $jsonPath = __DIR__ . '/../../resources/data/hiring_job.json';
        $data = $this->loadJsonFile($jsonPath);

        $perifereiasVariations = $this->getPerifereiasVariations($perifereia);
        $filteredData = [];

        // Normalize gender
        $genderNormalized = $gender === 'Άνδρας' ? 'Άνδρες' : 'Γυναίκες';

        foreach ($data as $item) {
            if (!isset($item['region']) || !isset($item['gender'])) {
                continue;
            }

            $regionField = $item['region'];
            $genderField = $item['gender'];

            // Check if gender matches
            if ($genderField !== $genderNormalized) {
                continue;
            }

            // Check if region matches any perifereia variation using fuzzy matching
            if ($this->regionMatchesFuzzy($regionField, $perifereiasVariations)) {
                $filteredData[] = $item;
            }
        }

        return $filteredData;
    }

    /**
     * Filter isozygio.json - Filter by gender and region, and filter columns by school
     * 
     * @param string $school - ΓΕΝΙΚΟ or ΕΠΑΛ
     * @param string $gender - Άνδρας or Γυναίκα
     * @param string $perifereia - Selected perifereia
     * @return array
     */
    public function filterIsozygio($school, $gender, $perifereia)
    {
        $jsonPath = __DIR__ . '/../../resources/data/isozygio.json';
        $data = $this->loadJsonFile($jsonPath);

        $perifereiasVariations = $this->getPerifereiasVariations($perifereia);
        $filteredData = [];

        // Normalize gender
        $genderNormalized = $gender === 'Άνδρας' ? 'Άνδρες' : 'Γυναίκες';

        foreach ($data as $item) {
            if (!isset($item['region']) || !isset($item['gender'])) {
                continue;
            }

            $regionField = $item['region'];
            $genderField = $item['gender'];

            // Check if gender matches
            if ($genderField !== $genderNormalized) {
                continue;
            }

            // Check if region matches any perifereia variation using fuzzy matching
            if (!$this->regionMatchesFuzzy($regionField, $perifereiasVariations)) {
                continue;
            }

            // Filter columns based on school
            $filteredItem = [
                'gender' => $item['gender'],
                'region' => $item['region']
            ];

            foreach ($item as $key => $value) {
                if ($key === 'gender' || $key === 'region') {
                    continue;
                }

                $shouldIncludeColumn = false;

                if ($school === 'ΓΕΝΙΚΟ') {
                    // Include ΓΕΛ columns and neutral columns (no school prefix)
                    if (strpos($key, 'ΓΕΛ_') === 0) {
                        $shouldIncludeColumn = true;
                    } elseif (strpos($key, 'ΕΠΑΛ_') === false && 
                              strpos($key, 'ΓΥΜΝΑΣΙΟ_') === false && 
                              strpos($key, 'ΔΗΜΟΤΙΚΟ_') === false) {
                        // Neutral column (no school prefix)
                        $shouldIncludeColumn = true;
                    }
                } elseif ($school === 'ΕΠΑΛ') {
                    // Include ΕΠΑΛ columns and neutral columns
                    if (strpos($key, 'ΕΠΑΛ_') === 0) {
                        $shouldIncludeColumn = true;
                    } elseif (strpos($key, 'ΓΕΛ_') === false && 
                              strpos($key, 'ΓΥΜΝΑΣΙΟ_') === false && 
                              strpos($key, 'ΔΗΜΟΤΙΚΟ_') === false) {
                        // Neutral column (no school prefix)
                        $shouldIncludeColumn = true;
                    }
                }

                if ($shouldIncludeColumn) {
                    $filteredItem[$key] = $value;
                }
            }

            $filteredData[] = $filteredItem;
        }

        return $filteredData;
    }

    /**
     * Load and decode JSON file
     */
    private function loadJsonFile($path)
    {
        if (!file_exists($path)) {
            return [];
        }

        $jsonContent = file_get_contents($path);
        $jsonContent = mb_convert_encoding($jsonContent, 'UTF-8', 'UTF-8');
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error for ' . basename($path) . ': ' . json_last_error_msg());
        }

        return $data ?: [];
    }

    /**
     * Get all filtered data for all sources
     * 
     * @param string $school - ΓΕΝΙΚΟ or ΕΠΑΛ
     * @param string $gender - Άνδρας or Γυναίκα
     * @param string $perifereia - Selected perifereia
     * @return array
     */
    public function filterAllData($school, $gender, $perifereia)
    {
        return [
            'antistixeia' => $this->filterAntistixeia($school),
            'deksiotites' => $this->filterDeksiotites($school, $perifereia),
            'hiring_job' => $this->filterHiringJob($gender, $perifereia),
            'isozygio' => $this->filterIsozygio($school, $gender, $perifereia)
        ];
    }
}
