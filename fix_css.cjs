const fs = require('fs');
const path = require('path');

const cssDir = path.join(__dirname, 'resources', 'css', 'staff_css');
const files = fs.readdirSync(cssDir).filter(f => f.startsWith('staff_') && f.endsWith('.css') && f !== 'staff_theme.css' && f !== 'staff_dashboard.css');

const rootVars = `
:root {
    --hp-green-dark: #14402b;
    --hp-green-mid: #2e7d55;
    --hp-green: #1c5c3c;
    --hp-text: #1c2b22;
    --hp-text-muted: #5c6b62;
    --hp-gold: #8a7a4d;
    --hp-cream: #f3f6f3;
    --hp-font-ui: 'Montserrat', system-ui, -apple-system, sans-serif;
    --hp-font-display: 'Poppins', 'Montserrat', sans-serif;
    
    --radius-xl: 16px;
    --radius-lg: 14px;
    
    /* Glassmorphism tokens - Light Mode */
    --glass-bg: rgba(255, 255, 255, 0.4);
    --glass-bg-hover: rgba(255, 255, 255, 0.5);
    --glass-bg-strong: rgba(255, 255, 255, 0.65);
    --glass-border: rgba(255, 255, 255, 0.5);
    --glass-border-strong: rgba(255, 255, 255, 0.8);
    --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    --glass-blur: blur(24px);
}

[data-theme="dark"] {
    --hp-text: #eaf0eb;
    --hp-text-muted: #9baaa1;
    --hp-green-dark: #152619;
    
    /* Glassmorphism tokens - Dark Mode */
    --glass-bg: rgba(0, 0, 0, 0.35);
    --glass-bg-hover: rgba(0, 0, 0, 0.45);
    --glass-bg-strong: rgba(0, 0, 0, 0.55);
    --glass-border: rgba(255, 255, 255, 0.1);
    --glass-border-strong: rgba(255, 255, 255, 0.25);
    --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}
`;

files.forEach(file => {
    const filePath = path.join(cssDir, file);
    let content = fs.readFileSync(filePath, 'utf8');

    // Inject root vars if not already present
    if (!content.includes('--glass-bg:')) {
        content = rootVars + '\n' + content;
    }

    // Remove animation line
    content = content.replace(/animation:\s*bgPan[^;]+;/g, '');

    // Remove @keyframes bgPan block (basic regex for this specific block)
    content = content.replace(/@keyframes\s+bgPan\s*\{[\s\S]*?100%\s*\{[\s\S]*?\}\s*\}/g, '');
    
    // Change opacity: 0.08 to 1 for dash-main::before if it exists
    content = content.replace(/(\.dash-main::before\s*\{[^}]*?)opacity:\s*0\.08;/g, '$1opacity: 1;');

    fs.writeFileSync(filePath, content, 'utf8');
    console.log('Processed', file);
});

// Also replace background image 1 to 3 in blade files
const viewsDir = path.join(__dirname, 'resources', 'views', 'staff');
const bladeFiles = fs.readdirSync(viewsDir).filter(f => f.endsWith('.blade.php'));
bladeFiles.forEach(file => {
    const filePath = path.join(viewsDir, file);
    let content = fs.readFileSync(filePath, 'utf8');
    if (content.includes('background_image1.png')) {
        content = content.replace(/background_image1\.png/g, 'background_image3.png');
        fs.writeFileSync(filePath, content, 'utf8');
        console.log('Updated background in', file);
    }
});
