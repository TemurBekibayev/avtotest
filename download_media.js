const fs = require('fs');
const https = require('https');
const path = require('path');

const baseUrl = 'https://back.eavtotalim.uz';
const jsonPath = 'D:\\projects\\avtotest\\admin\\backend\\resources\\tests\\savollar\\all_uz_template_questions.json';
const baseOutputDir = 'D:\\projects\\avtotest\\Новая папка\\all';
const imgOutputDir = path.join(baseOutputDir, 'images');
const vidOutputDir = path.join(baseOutputDir, 'videos');

// Create dirs
[imgOutputDir, vidOutputDir].forEach(dir => {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
});

const data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

const images = new Set();
const videos = new Set();

function traverse(obj) {
    if (Array.isArray(obj)) {
        obj.forEach(traverse);
    } else if (typeof obj === 'object' && obj !== null) {
        if (obj.value && typeof obj.value === 'string') {
            if (obj.value.endsWith('.jpg') || obj.value.endsWith('.png') || obj.value.endsWith('.gif') || obj.value.startsWith('/test_files/img/')) {
                images.add(obj.value);
            }
        }
        if (obj.answer_video && typeof obj.answer_video === 'string') {
            videos.add(obj.answer_video);
        }
        
        for (const key in obj) {
            traverse(obj[key]);
        }
    }
}

traverse(data);

console.log(`Found ${images.size} images and ${videos.size} videos.`);

async function download(urlPath, outputDir) {
    const fullUrl = baseUrl + encodeURI(urlPath);
    // User URL had some spaces in description: "/test_files/img/ newtest_questions/...", encodeURI handles these but let's trip spaces just in case
    const safeUrlPath = urlPath.replace(/ /g, '%20');
    let properFullUrl = baseUrl + safeUrlPath;

    const fileName = path.basename(urlPath).trim();
    if (!fileName) return;

    const destPath = path.join(outputDir, fileName);
    
    if (fs.existsSync(destPath)) {
        return; // Skip if already exists
    }

    return new Promise((resolve, reject) => {
        const file = fs.createWriteStream(destPath);
        https.get(properFullUrl, {
            rejectUnauthorized: false
        }, (response) => {
            if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
                // handle redirect quickly
                   https.get(response.headers.location, {rejectUnauthorized: false}, (res2) => {
                        res2.pipe(file);
                        file.on('finish', () => { file.close(); resolve(); });
                   });
                   return;
            }

            if (response.statusCode !== 200) {
                fs.unlink(destPath, () => {});
                console.error(`Failed to download ${properFullUrl}: ${response.statusCode}`);
                return resolve(); // Resolve anyway to continue
            }
            response.pipe(file);
            file.on('finish', () => {
                file.close();
                resolve();
            });
        }).on('error', (err) => {
            fs.unlink(destPath, () => {});
            console.error(`Error downloading ${properFullUrl}: ${err.message}`);
            resolve();
        });
    });
}

// Download 10 items concurrently
async function run() {
    let imgCount = 0;
    const imgQueue = Array.from(images);
    
    while(imgQueue.length > 0) {
        const batch = imgQueue.splice(0, 20);
        await Promise.all(batch.map(img => download(img, imgOutputDir)));
        imgCount += batch.length;
        console.log(`Downloaded ${imgCount}/${images.size} images`);
    }
    
    let vidCount = 0;
    const vidQueue = Array.from(videos);
    while(vidQueue.length > 0) {
        const batch = vidQueue.splice(0, 5);
        await Promise.all(batch.map(vid => download(vid, vidOutputDir)));
        vidCount += batch.length;
        console.log(`Downloaded ${vidCount}/${videos.size} videos`);
    }
    console.log('Done!');
}

run();
