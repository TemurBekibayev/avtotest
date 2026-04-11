import json
import os

path = r'admin/backend/resources/tests/savollar/all_questions_uz.json'
if not os.path.exists(path):
    print("File not found")
else:
    with open(path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    total_questions = 0
    all_q_ids = []
    for t in data:
        qs = t.get('data', {}).get('data', {}).get('questions', [])
        total_questions += len(qs)
        for q in qs:
            all_q_ids.append(q['id'])
    
    print(f"Total question slots (including duplicates): {total_questions}")
    print(f"Unique question IDs: {len(set(all_q_ids))}")
    print(f"Template count in JSON: {len(data)}")
