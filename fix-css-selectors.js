import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const cssDir = path.join(__dirname, 'resources', 'css', 'staff_css');

const files = fs.readdirSync(cssDir).filter(f => f.endsWith('.css'));

for (const file of files) {
    const filePath = path.join(cssDir, file);
    let content = fs.readFileSync(filePath, 'utf8');

    // Replace ".s-xxx-page body.staff-portal " with ".s-xxx-page "
    const regex = /\.s-[a-z]{3}-page\s+body\.staff-portal\s+/g;
    
    if (regex.test(content)) {
        content = content.replace(regex, (match) => {
            // match is ".s-xxx-page body.staff-portal "
            const prefix = match.split(' ')[0]; // gets ".s-xxx-page"
            return prefix + ' ';
        });
        
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Fixed selectors in ${file}`);
    }
}
