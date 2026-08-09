import fs from 'fs';
['resources/views/staff/staff_check_ins.blade.php', 
 'resources/views/staff/staff_reports.blade.php',
 'resources/views/staff/staff_reservations.blade.php',
 'resources/views/staff/staff_records.blade.php'
].forEach(file => {
    const html = fs.readFileSync(file, 'utf8');
    const open = (html.match(/<div/g) || []).length;
    const close = (html.match(/<\/div>/g) || []).length;
    console.log(file, 'OPEN:', open, 'CLOSE:', close);
});
