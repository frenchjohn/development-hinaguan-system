import sys

file = 'resources/views/staff/staff_reservations.blade.php'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

start_token = '                <div class="guest-modal" id="reservationModal" aria-hidden="true">'
if start_token not in content:
    print('Start token not found')
    sys.exit(1)

start_index = content.find(start_token)
end_index = content.find('            </main>', start_index)
if end_index == -1:
    print('End token not found')
    sys.exit(1)

modals = content[start_index:end_index]
new_content = content[:start_index] + content[end_index:]

body_end_index = new_content.find('    <x-staff_chatbot />')
new_content = new_content[:body_end_index] + '    <!-- Modals (Direct children of body) -->\n' + modals + '\n' + new_content[body_end_index:]

with open(file, 'w', encoding='utf-8') as f:
    f.write(new_content)
print('Modals moved successfully.')
