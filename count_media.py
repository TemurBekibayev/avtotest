import json
import os
import re

json_path = r'D:\projects\avtotest\admin\backend\resources\tests\savollar\uz-all.json'

with open(json_path, 'r', encoding='utf-8') as f:
    text = f.read()
    
# Find all occurrences of /test_files/img/ and /test_files/testanswer/
images = set(re.findall(r'/test_files/img/[^"]+', text))
videos = set(re.findall(r'/test_files/testanswer/[^"]+', text))

print(f"Parsed directly with regex: Found {len(images)} images and {len(videos)} videos")

for i, img in enumerate(list(images)[:5]):
    print(f"Img {i}: {img}")
    
for i, vid in enumerate(list(videos)[:5]):
    print(f"Vid {i}: {vid}")
