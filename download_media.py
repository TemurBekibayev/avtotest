import os
import re
import urllib.request
import urllib.parse
import ssl
from concurrent.futures import ThreadPoolExecutor

base_url = 'https://back.eavtotalim.uz'
json_path = r'D:\projects\avtotest\admin\backend\resources\tests\savollar\all_questions_uz'
base_output_dir = r'D:\projects\avtotest\Новая папка\all'
img_output_dir = os.path.join(base_output_dir, 'images')
vid_output_dir = os.path.join(base_output_dir, 'videos')

# Create directories if they don't exist
os.makedirs(img_output_dir, exist_ok=True)
os.makedirs(vid_output_dir, exist_ok=True)

# Ignore SSL certificate verification
ssl_context = ssl._create_unverified_context()

def download_file(url_path, output_dir):
    try:
        # url_path might have spaces, e.g., "/test_files/img/ newtest_questions/..."
        clean_path = url_path.strip()
        
        # Proper URL encoding to handle spaces and other special characters
        encoded_path = urllib.parse.quote(clean_path)
        # Restore forward slashes
        encoded_path = encoded_path.replace('%2F', '/')
        
        full_url = base_url + encoded_path
        
        file_name = os.path.basename(clean_path).strip()
        if not file_name:
            return
            
        dest_path = os.path.join(output_dir, file_name)
        
        if os.path.exists(dest_path):
            return  # Skip if already downloaded
            
        req = urllib.request.Request(full_url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, context=ssl_context) as response, open(dest_path, 'wb') as out_file:
            data = response.read()
            out_file.write(data)
            
    except Exception as e:
        print(f"Error downloading {full_url}: {e}")

if __name__ == '__main__':
    print(f"Reading file: {json_path}")
    
    if not os.path.exists(json_path) or os.path.getsize(json_path) == 0:
        print("\nERROR: The file is empty (0 bytes) or does not exist.")
        print("Please save the file and run again.")
        exit(1)
        
    with open(json_path, 'r', encoding='utf-8') as f:
        text = f.read()

    # Extract all unique image and video URLs directly from the file text
    images = set(re.findall(r'/test_files/img/[^"]+', text))
    videos = set(re.findall(r'/test_files/testanswer/[^"]+', text))
    
    print(f"Found {len(images)} NEW unique images and {len(videos)} NEW unique videos in this file.")
    
    if len(images) > 0 or len(videos) > 0:
        print("Starting download. Please wait...")
        with ThreadPoolExecutor(max_workers=10) as executor:
            # Queue up all images for download
            for img in images:
                executor.submit(download_file, img, img_output_dir)
                    
            # Queue up all videos for download
            for vid in videos:
                executor.submit(download_file, vid, vid_output_dir)
                    
        print("Download finished!")
    else:
        print("No media paths found in the file.")
