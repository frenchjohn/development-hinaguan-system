window.AppPage = window.AppPage || {};
window.AppPage['admin_amenitiesmanagement'] = function () {


    const modal = document.getElementById('amenityModal');
    const openButtons = document.querySelectorAll('[data-open-amenity-modal]');
    const closeButtons = document.querySelectorAll('[data-close-amenity-modal]');
    const amenityRows = Array.from(document.querySelectorAll('.amenity-row'));
    const amenitySearch = document.getElementById('amenitySearch');
    const amenitySortColumn = document.getElementById('amenitySortColumn');
    const amenitySortOrder = document.getElementById('amenitySortOrder');
    const amenityStatus = document.getElementById('amenityStatus');
    const form = document.getElementById('amenityForm');
    const modalTitle = document.getElementById('amenityModalTitle');
    const submitButton = document.getElementById('amenitySubmitButton');
    const deleteButton = document.getElementById('amenityDeleteButton');
    const amenityIdInput = document.getElementById('amenityId');

    const fields = {
        amenities_name: document.getElementById('amenities_name'),
        daytime_price: document.getElementById('daytime_price'),
        nighttime_price: document.getElementById('nighttime_price'),
        daytime_aircon_price: document.getElementById('daytime_aircon_price'),
        nighttime_aircon_price: document.getElementById('nighttime_aircon_price'),
        additional_per_head: document.getElementById('additional_per_head'),
        minimum_capacity: document.getElementById('minimum_capacity'),
        maximum_capacity: document.getElementById('maximum_capacity'),
        description: document.getElementById('description'),
        image: document.getElementById('image'),
        status: document.getElementById('status'),
        sale_percentage: document.getElementById('sale_percentage'),
    };

    const dropZone = document.getElementById('imageDropZone');
    const imageInput = document.getElementById('image');
    const imageFileName = document.getElementById('imageFileName');

    const editButton = document.getElementById('amenityEditButton');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewImg = document.getElementById('imagePreviewImg');

    const setFormReadOnly = (readOnly) => {
        Object.values(fields).forEach(field => {
            field.disabled = readOnly;
        });
        if (readOnly) {
            dropZone.classList.add('dropzone--disabled');
        } else {
            dropZone.classList.remove('dropzone--disabled');
        }
    };

    const openModal = () => {
        modal.classList.add('is-open');
    };

    const normalizeValue = (value) => {
        if (value === null || value === undefined || value === '') {
            return Number.POSITIVE_INFINITY;
        }
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : Number.POSITIVE_INFINITY;
    };

    const filterAndSortRows = () => {
        const query = amenitySearch.value.trim().toLowerCase();
        const sortColumn = amenitySortColumn.value;
        const sortOrder = amenitySortOrder.value;
        const tbody = document.querySelector('.amenities-table tbody');

        const activeStatus = amenityStatus.value;
        let filteredRows = amenityRows.filter(row => {
            const name = row.dataset.amenitiesName.toLowerCase();
            const matchesName = name.includes(query);
            if (!matchesName) {
                return false;
            }
            if (activeStatus === 'all') {
                return true;
            }
            return row.dataset.status === activeStatus;
        });

        const compareRow = (a, b, field, direction = 'asc') => {
            const aValue = normalizeValue(a.dataset[field]);
            const bValue = normalizeValue(b.dataset[field]);
            if (aValue === bValue) return 0;
            return direction === 'asc' ? aValue - bValue : bValue - aValue;
        };

        if (sortColumn) {
            if (sortOrder === 'none') {
                filteredRows = filteredRows.filter(row => {
                    const value = row.dataset[sortColumn];
                    return value === '' || value === null || value === undefined;
                });
            } else {
                filteredRows.sort((a, b) => compareRow(a, b, sortColumn, sortOrder));
            }
        }

        tbody.innerHTML = '';
        if (filteredRows.length > 0) {
            filteredRows.forEach(row => tbody.appendChild(row));
        } else {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="8" class="table-empty">No amenities found.</td>';
            tbody.appendChild(emptyRow);
        }
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        form.reset();
        amenityIdInput.value = '';
        document.getElementById('existingImage').value = '';
        modalTitle.textContent = 'Add New Amenity';
        submitButton.textContent = 'Create Amenity';
        submitButton.style.display = 'inline-flex';
        editButton.style.display = 'none';
        deleteButton.style.display = 'none';
        setFormReadOnly(false);
        form.action = form.dataset.storeUrl;
        form.method = 'POST';
        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) methodInput.remove();
        imageFileName.textContent = 'No file chosen';
        dropZone.classList.remove('is-active');
        imagePreview.style.display = 'none';
    };

    const populateFormForEdit = (row) => {
        const amenityId = row.dataset.amenityId;
        const data = {
            amenities_name: row.dataset.amenitiesName,
            daytime_price: row.dataset.originalDaytimePrice || row.dataset.daytimePrice || row.querySelector('td:nth-child(2)').textContent.trim(),
            nighttime_price: row.dataset.originalNighttimePrice || row.dataset.nighttimePrice || row.querySelector('td:nth-child(3)').textContent.trim(),
            daytime_aircon_price: row.dataset.originalDaytimeAirconPrice || row.dataset.daytimeAirconPrice || '',
            nighttime_aircon_price: row.dataset.originalNighttimeAirconPrice || row.dataset.nighttimeAirconPrice || '',
            additional_per_head: row.dataset.additionalPerHead || '',
            minimum_capacity: row.dataset.minimumCapacity || '',
            maximum_capacity: row.dataset.maximumCapacity || '',
            description: row.dataset.description || '',
            imageUrl: row.dataset.imageUrl || '',
            imagePath: row.dataset.imagePath || '',
            status: row.dataset.status || 'enabled',
            sale_percentage: row.dataset.salePercentage || '0',
        };

        const baseUrl = form.dataset.updateBaseUrl;
        form.action = `${baseUrl}/${amenityId}`;
        form.method = 'POST';
        let methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            form.appendChild(methodInput);
        }
        methodInput.value = 'PUT';

        amenityIdInput.value = amenityId;
        fields.amenities_name.value = data.amenities_name;
        fields.daytime_price.value = data.daytime_price;
        fields.nighttime_price.value = data.nighttime_price;
        fields.daytime_aircon_price.value = data.daytime_aircon_price;
        fields.nighttime_aircon_price.value = data.nighttime_aircon_price;
        fields.additional_per_head.value = data.additional_per_head;
        fields.minimum_capacity.value = data.minimum_capacity;
        fields.maximum_capacity.value = data.maximum_capacity;
        fields.description.value = data.description;
        fields.status.value = data.status;
        fields.sale_percentage.value = data.sale_percentage;
        
        document.getElementById('existingImage').value = data.imagePath || '';
        imageFileName.textContent = data.imageUrl ? data.imageUrl.split('/').pop() : 'No file chosen';

        if (data.imageUrl) {
            imagePreviewImg.src = data.imageUrl;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
        }

        modalTitle.textContent = 'Amenity details';
        submitButton.textContent = 'Update Amenity';
        submitButton.style.display = 'none';
        editButton.style.display = 'inline-flex';
        deleteButton.style.display = 'inline-flex';
        setFormReadOnly(true);
        deleteButton.dataset.deleteAmenity = amenityId;
    };

    openButtons.forEach(button => {
        button.addEventListener('click', () => {
            editButton.style.display = 'none';
            submitButton.style.display = 'inline-flex';
            deleteButton.style.display = 'none';
            setFormReadOnly(false);
            imagePreview.style.display = 'none';
            openModal();
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', closeModal);
    });

    amenitySearch.addEventListener('input', filterAndSortRows);
    amenitySortColumn.addEventListener('change', filterAndSortRows);
    amenitySortOrder.addEventListener('change', filterAndSortRows);
    amenityStatus.addEventListener('change', filterAndSortRows);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Add row click listeners (ignore clicks on the action buttons)
    amenityRows.forEach(row => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('[data-action]')) return;
            populateFormForEdit(row);
            openModal();
        });
    });

    // Row-level action buttons
    const deleteAmenityById = (amenityId) => {
        if (!amenityId) return;
        if (confirm('Are you sure you want to delete this amenity?')) {
            const deleteForm = document.createElement('form');
            deleteForm.method = 'POST';
            deleteForm.action = `${form.dataset.updateBaseUrl}/${amenityId}`;
            deleteForm.innerHTML = '<input type="hidden" name="_token" value="' + document.querySelector('[name="_token"]').value + '"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(deleteForm);
            deleteForm.submit();
        }
    };

    amenityRows.forEach(row => {
        row.querySelectorAll('[data-action]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.stopPropagation();
                if (btn.dataset.action === 'edit') {
                    populateFormForEdit(row);
                    openModal();
                } else if (btn.dataset.action === 'delete') {
                    deleteAmenityById(row.dataset.amenityId);
                }
            });
        });
    });

    // Initial filter/sort display
    filterAndSortRows();

    const updateFilePreview = (file) => {
        imageFileName.textContent = file ? file.name : 'No file chosen';
    };

    dropZone.addEventListener('click', () => {
        if (!imageInput.disabled) {
            imageInput.click();
        }
    });

    editButton.addEventListener('click', () => {
        setFormReadOnly(false);
        submitButton.style.display = 'inline-flex';
        editButton.style.display = 'none';
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            event.stopPropagation();
            dropZone.classList.add('is-active');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            event.stopPropagation();
            dropZone.classList.remove('is-active');
        });
    });

    dropZone.addEventListener('drop', (event) => {
        if (imageInput.disabled) {
            return;
        }
        const files = event.dataTransfer.files;
        if (files.length) {
            imageInput.files = files;
            updateFilePreview(files[0]);
            imagePreview.style.display = 'none';
        }
    });

    imageInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        updateFilePreview(file);
        imagePreview.style.display = 'none';
    });

    // Handle numeric-only price inputs
    ['daytime_price', 'nighttime_price', 'daytime_aircon_price', 'nighttime_aircon_price', 'additional_per_head', 'minimum_capacity', 'maximum_capacity'].forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^\d\.]/g, '');
        });
    });

    // Handle delete button (reuses the row-level delete flow)
    deleteButton.addEventListener('click', (e) => {
        e.preventDefault();
        deleteAmenityById(deleteButton.dataset.deleteAmenity);
    });
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_amenitiesmanagement']());