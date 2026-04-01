<?php
$req = request();
$req->merge(['name' => 'Group 1 BC', 'category' => 'BC', 'organization_id' => 1]);
$validator = validator($req->all(), [
    'organization_id' => 'required|exists:organizations,id',
    'name' => 'required|string|max:255',
    'category' => 'required|string|max:255'
]);

if ($validator->fails()) {
    dump($validator->errors()->all());
}
else {
    echo "VALID\n";
    try {
        App\Models\Group::create($req->all());
        echo "SAVED\n";
    }
    catch (\Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
}
