<?php
$path = 'admin/backend/resources/tests/savollar/all_questions_uz.json';
if (!file_exists($path)) {
    echo "File not found: $path\n";
    exit;
}

$data = json_decode(file_get_contents($path), true);
if (!$data) {
    echo "Failed to decode JSON\n";
    exit;
}

$total_questions = 0;
$all_q_ids = [];
foreach ($data as $idx => $t) {
    $qs = $t['data']['data']['questions'] ?? [];
    $total_questions += count($qs);
    $template_ids = [];
    foreach ($qs as $q) {
        $all_q_ids[] = $q['id'];
        $template_ids[] = $q['id'];
    }
    if (count($template_ids) !== count(array_unique($template_ids))) {
        echo "Template index $idx has internal duplicates!\n";
    }
}

echo "Total question slots (including duplicates): " . $total_questions . "\n";
echo "Unique question IDs: " . count(array_unique($all_q_ids)) . "\n";
echo "Number of objects in JSON: " . count($data) . "\n";

// Count questions per template in JSON
$counts = [];
foreach ($data as $idx => $t) {
    $counts[$idx] = count($t['data']['data']['questions'] ?? []);
}
echo "Questions per template in JSON first 5: " . implode(', ', array_slice($counts, 0, 5)) . "\n";
echo "Questions per template in JSON last 5: " . implode(', ', array_slice($counts, -5)) . "\n";
