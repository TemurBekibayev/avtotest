import re

json_path = r'D:\projects\avtotest\admin\backend\resources\tests\savollar\all_questions_uz'
with open(json_path, 'r', encoding='utf-8') as f:
    text = f.read()

images_list = re.findall(r'/test_files/img/[^"]+', text)
videos_list = re.findall(r'/test_files/testanswer/[^"]+', text)

print(f"Total Unique Images: {len(set(images_list))}")
print(f"Total Unique Videos: {len(set(videos_list))}")
