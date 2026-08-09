import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const fileMap = {
    'staff_check_ins.blade.php': 's-che-page',
    'staff_dashboard.blade.php': 's-das-page',
    'staff_occupancy_monitor.blade.php': 's-occ-page',
    'staff_reports.blade.php': 's-rep-page',
    'staff_records.blade.php': 's-rec-page',
    'staff_settings.blade.php': 's-set-page',
    'staff_reservations.blade.php': 's-res-page',
};

const dir = path.join(__dirname, 'resources/views/staff');

function processBlade() {
    for (const [filename, newClass] of Object.entries(fileMap)) {
        const filePath = path.join(dir, filename);
        if (!fs.existsSync(filePath)) {
            console.log(`Skipping ${filename} - not found`);
            continue;
        }

        let content = fs.readFileSync(filePath, 'utf8');

        // Remove the class from the body tag
        if (content.includes('<body ')) {
            content = content.replace(new RegExp(`(<body[^>]*class="[^"]*)\\s+${newClass}([^"]*"[^>]*>)`), '$1$2');
        }

        // Add the class to <div class="dash-layout">
        if (content.includes('<div class="dash-layout')) {
            content = content.replace(/<div\s+class="dash-layout([^"]*)"/, (match, after) => {
                if (!after.includes(newClass)) {
                    return `<div class="dash-layout ${newClass}"`;
                }
                return match;
            });
        }
        
        fs.writeFileSync(filePath, content);
        console.log(`Successfully moved class ${newClass} to dash-layout in ${filename}`);
    }
}

processBlade();
