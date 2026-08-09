import fs from 'fs';
import path from 'path';
import postcss from 'postcss';
import prefixSelector from 'postcss-prefix-selector';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const fileMap = {
    'staff_check_ins.css': '.s-che-page',
    'staff_dashboard.css': '.s-das-page',
    'staff_occupancy_monitor.css': '.s-occ-page',
    'staff_reports.css': '.s-rep-page',
    'staff_records.css': '.s-rec-page',
    'staff_settings.css': '.s-set-page',
    'staff_reservations.css': '.s-res-page',
};

const dir = path.join(__dirname, 'resources/css/staff_css');

async function processCss() {
    for (const [filename, prefix] of Object.entries(fileMap)) {
        const filePath = path.join(dir, filename);
        if (!fs.existsSync(filePath)) {
            console.log(`Skipping ${filename} - not found`);
            continue;
        }

        const css = fs.readFileSync(filePath, 'utf8');

        // Postcss prefix plugin config
        const prefixer = prefixSelector({
            prefix: prefix,
            transform: function (prefix, selector, prefixedSelector) {
                if (selector === ':root' || selector === 'body' || selector === 'html') {
                    return prefix; // Replace :root/body with the prefix class instead of prepending
                }
                return prefixedSelector;
            }
        });

        try {
            const result = await postcss([prefixer]).process(css, { from: filePath, to: filePath });
            fs.writeFileSync(filePath, result.css);
            console.log(`Successfully prefixed ${filename} with ${prefix}`);
        } catch (err) {
            console.error(`Error processing ${filename}:`, err);
        }
    }
}

processCss();
