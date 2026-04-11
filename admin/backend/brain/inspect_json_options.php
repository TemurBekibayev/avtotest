<?php
$f = 'uz-lat';
$path = "admin/backend/resources/tests/savollar/all_questions_uz.json";
$content = file_get_contents($path);
$data = json_decode($content, true);

foreach ($data as $template) {
    if (isset($template['data']['data']['questions'])) {
        foreach ($template['data']['data']['questions'] as $q) {
            $text = '';
            foreach ($q['body'] as $part) if ($part['type'] == 1) $text = $part['value'];
            
            if (strpos($text, 'Ko‘rsatilgan yo‘nalishlar bo‘yi') !== false) {
                echo "JSON Question ID: {$q['id']}\n";
                if (isset($q['answers'])) {
                    echo "Options count: " . count($q['answers']) . "\n";
                    foreach ($q['answers'] as $idx => $ans) {
                        $optText = '';
                        foreach ($ans['body'] as $p) if ($p['type'] == 1) $optText = $p['value'];
                        echo "  Option $idx: '$optText'\n";
                    }
                }
                break 2;
            }
        }
    }
}
echo "Checking all_questions_ru.json for same pattern...\n";
$pathRu = "admin/backend/resources/tests/savollar/all_questions_ru.json";
$contentRu = file_get_contents($pathRu);
$dataRu = json_decode($contentRu, true);
// (simplified, assuming order)
// Wait, I should find by index if I can't find by text.
