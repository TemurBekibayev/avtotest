<?php
$files = ['uz', 'ru', 'kiril'];
$data = [];
foreach ($files as $f) {
    $path = "admin/backend/resources/tests/savollar/all_questions_$f.json";
    $content = file_get_contents($path);
    $data[$f] = json_decode($content, true);
}

// Flat questions for each
$flattened = [];
foreach ($files as $f) {
    $flattened[$f] = [];
    foreach ($data[$f] as $template) {
        $qs = $template['data']['data']['questions'] ?? [];
        foreach ($qs as $q) {
            $flattened[$f][] = $q;
        }
    }
}

$sampleIndices = [0, 100, 500, 1000];
foreach ($sampleIndices as $idx) {
    echo "--- Index $idx ---\n";
    foreach ($files as $f) {
        $q = $flattened[$f][$idx] ?? null;
        if ($q) {
            $text = '';
            foreach ($q['body'] as $part) if ($part['type'] == 1) $text = substr($part['value'], 0, 40) . '...';
            echo "[$f] ID: {$q['id']} - Text: $text\n";
        }
    }
}
