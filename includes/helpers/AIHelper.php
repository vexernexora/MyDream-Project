<?php
/**
 * AIHelper - Integracja z OpenAI do analizy filmów
 */

declare(strict_types=1);

class AIHelper
{
    /**
     * Analizuje film i generuje metadata (tytuł, opis, tagi)
     */
    public static function analyzeVideo(array $fileInfo, ?array $metadata = null): ?array
    {
        $apiKey = OPENAI_API_KEY;

        if (empty($apiKey)) {
            debug_log("Brak klucza API OpenAI - używam domyślnych wartości");
            return self::generateDefaultMetadata($fileInfo, $metadata);
        }

        // Przygotuj kontekst dla AI
        $context = self::prepareVideoContext($fileInfo, $metadata);

        // Wywołaj OpenAI API
        $prompt = self::buildAnalysisPrompt($context);

        try {
            $response = self::callOpenAI($prompt, $apiKey);

            if ($response) {
                return self::parseAIResponse($response);
            }
        } catch (Exception $e) {
            debug_log("Błąd AI: " . $e->getMessage());
        }

        // Fallback do domyślnych wartości
        return self::generateDefaultMetadata($fileInfo, $metadata);
    }

    /**
     * Przygotowuje kontekst o filmie dla AI
     */
    private static function prepareVideoContext(array $fileInfo, ?array $metadata): array
    {
        return [
            'filename' => $fileInfo['filename'] ?? 'unknown',
            'size' => $fileInfo['size'] ?? 0,
            'duration' => $metadata['duration_formatted'] ?? 'unknown',
            'resolution' => isset($metadata['width'], $metadata['height'])
                ? "{$metadata['width']}x{$metadata['height']}"
                : 'unknown',
            'codec' => $metadata['codec'] ?? 'unknown',
        ];
    }

    /**
     * Buduje prompt dla OpenAI
     */
    private static function buildAnalysisPrompt(array $context): string
    {
        $filename = $context['filename'];
        $duration = $context['duration'];
        $resolution = $context['resolution'];

        return <<<PROMPT
Jesteś ekspertem od analizy filmów. Na podstawie podanych informacji o pliku wideo, wygeneruj atrakcyjne metadata.

Informacje o filmie:
- Nazwa pliku: {$filename}
- Czas trwania: {$duration}
- Rozdzielczość: {$resolution}

Zadanie:
1. Wymyśl kreatywny, chwytliwy tytuł (po polsku), który brzmi profesjonalnie jak na YouTube
2. Napisz krótki, ciekawy opis (2-3 zdania, po polsku)
3. Wygeneruj 5-8 trafnych tagów opisujących potencjalną zawartość (po polsku)
4. Określ prawdopodobną kategorię/typ filmu (np. vlog, tutorial, gaming, krajobraz, muzyka, sport, itp.)

Odpowiedz TYLKO w formacie JSON:
{
  "title": "Tytuł filmu",
  "description": "Opis filmu",
  "tags": ["tag1", "tag2", "tag3", "tag4", "tag5"],
  "category": "kategoria"
}

Bądź kreatywny i dopasuj metadata do nazwy pliku i charakterystyki technicznej.
PROMPT;
    }

    /**
     * Wywołuje OpenAI API
     */
    private static function callOpenAI(string $prompt, string $apiKey): ?string
    {
        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => OPENAI_MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Jesteś pomocnym asystentem specjalizującym się w analizie i kategoryzacji filmów wideo. Zawsze odpowiadasz w formacie JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
            'response_format' => ['type' => 'json_object']
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            debug_log("OpenAI API błąd: HTTP $httpCode", ['response' => $response]);
            return null;
        }

        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? null;
    }

    /**
     * Parsuje odpowiedź AI
     */
    private static function parseAIResponse(string $response): array
    {
        $data = json_decode($response, true);

        if (!$data) {
            debug_log("Nie udało się sparsować odpowiedzi AI");
            return [];
        }

        return [
            'title' => $data['title'] ?? 'Bez tytułu',
            'description' => $data['description'] ?? '',
            'tags' => $data['tags'] ?? [],
            'category' => $data['category'] ?? 'Inne',
            'ai_generated' => true,
        ];
    }

    /**
     * Generuje domyślne metadata bez AI (fallback)
     */
    private static function generateDefaultMetadata(array $fileInfo, ?array $metadata): array
    {
        $filename = pathinfo($fileInfo['filename'], PATHINFO_FILENAME);

        // Prosta heurystyka na podstawie nazwy pliku
        $title = self::beautifyFilename($filename);
        $tags = self::extractTagsFromFilename($filename);

        return [
            'title' => $title,
            'description' => "Film wideo: {$title}",
            'tags' => $tags,
            'category' => 'Wideo',
            'ai_generated' => false,
        ];
    }

    /**
     * Ładnie formatuje nazwę pliku na tytuł
     */
    private static function beautifyFilename(string $filename): string
    {
        // Usuń znaki specjalne, zamień na spacje
        $title = preg_replace('/[_\-\.]+/', ' ', $filename);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);

        // Kapitalizuj
        $title = mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');

        return $title ?: 'Bez tytułu';
    }

    /**
     * Wyciąga potencjalne tagi z nazwy pliku
     */
    private static function extractTagsFromFilename(string $filename): array
    {
        $tags = [];
        $filename = strtolower($filename);

        // Słowa kluczowe
        $keywords = [
            'tutorial' => 'Tutorial',
            'vlog' => 'Vlog',
            'gaming' => 'Gaming',
            'gameplay' => 'Gameplay',
            'review' => 'Recenzja',
            'test' => 'Test',
            'unboxing' => 'Unboxing',
            'music' => 'Muzyka',
            'live' => 'Live',
            'concert' => 'Koncert',
            'travel' => 'Podróże',
            'food' => 'Jedzenie',
            'sport' => 'Sport',
            'nature' => 'Natura',
            'timelapse' => 'Timelapse',
            '4k' => '4K',
            'hd' => 'HD',
        ];

        foreach ($keywords as $key => $tag) {
            if (stripos($filename, $key) !== false) {
                $tags[] = $tag;
            }
        }

        // Jeśli nie znaleziono żadnych tagów, dodaj domyślne
        if (empty($tags)) {
            $tags = ['Wideo', 'Film'];
        }

        return array_unique($tags);
    }

    /**
     * Sugeruje najlepszy timestamp dla miniatury na podstawie analizy AI
     * (uproszczona wersja - zwraca heurystyczny timestamp)
     */
    public static function suggestThumbnailTimestamp(array $fileInfo, ?array $metadata): float
    {
        if (!$metadata || !isset($metadata['duration'])) {
            return 0;
        }

        // Używamy VideoHelper do znajdowania najlepszego momentu
        return VideoHelper::findBestThumbnailTimestamp($fileInfo['path']);
    }
}
