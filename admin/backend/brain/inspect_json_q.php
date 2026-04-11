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
            
            // Look for the specific question text from the screenshot
            if (strpos($text, 'Ko‘rsatilgan yo‘nalishlar bo‘yi') !== false) {
                echo "Found Question ID: {$q['id']}\n";
                echo "Body Parts:\n";
                foreach ($q['body'] as $pIdx => $part) {
                    echo " Part $pIdx [Type {$part['type']}]: " . (is_string($part['value']) ? substr($part['value'], 0, 50) : "Complex value") . "\n";
                    if ($part['type'] == 2) {
                        echo "  - IMAGE PATH: {$part['value']}\n";
                    }
                }
                
                if (isset($q['answers'])) {
                    echo "Answers count: " . count($q['answers']) . "\n";
                    foreach ($q['answers'] as $aIdx => $ans) {
                        echo " Answer $aIdx [check: {$ans['check']}]:\n";
                        foreach ($ans['body'] as $bIdx => $bp) {
                            echo "  - Body Part $bIdx [Type {$bp['type']}]: " . (is_string($bp['value']) ? $bp['value'] : "Complex value") . "\n";
                        }
                    }
                } else {
                    echo "NO ANSWERS (options) found in JSON for this question!\n";
                }
                break 2;
            }
        }
    }
}
