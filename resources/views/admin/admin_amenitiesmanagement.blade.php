<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amenities Management — Hinaguan Nature Park</title>
    <script>
        // Prevent flash of wrong theme by setting theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/admin_sidemenu.css',
        'resources/css/admin_css/admin_amenitiesmanagement.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_amenitiesmanagement.js',
    ])
    <style>
        .dash-main::before {
            background-image: url('{{ asset('storage/design_images/background_image3.png') }}');
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="amenities" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content">
                <x-header
                    title="Amenities Management"
                    subtitle="Create, edit, and maintain park amenities"
                />
                <section class="amenities-head">
                    <div>
                        <p class="amenities-head__eyebrow">Manage Amenities</p>
                        <h2 class="amenities-head__title">All amenities</h2>
                        <p class="amenities-head__text">View amenity details, enable or disable availability, and add new park services.</p>
                    </div>
                    <button type="button" class="btn btn--primary" data-open-amenity-modal>New Amenity</button>
                </section>

                <div class="amenities-stats">
                    <article class="amenity-stat">
                        <span class="amenity-stat__icon amenity-stat__icon--total">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </span>
                        <div class="amenity-stat__body">
                            <p class="amenity-stat__value">{{ $totalAmenities }}</p>
                            <p class="amenity-stat__label">Total Amenities</p>
                        </div>
                    </article>
                    <article class="amenity-stat">
                        <span class="amenity-stat__icon amenity-stat__icon--enabled">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="amenity-stat__body">
                            <p class="amenity-stat__value">{{ $enabledAmenities }}</p>
                            <p class="amenity-stat__label">Enabled</p>
                        </div>
                    </article>
                    <article class="amenity-stat">
                        <span class="amenity-stat__icon amenity-stat__icon--disabled">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </span>
                        <div class="amenity-stat__body">
                            <p class="amenity-stat__value">{{ $disabledAmenities }}</p>
                            <p class="amenity-stat__label">Disabled</p>
                        </div>
                    </article>
                    <article class="amenity-stat">
                        <span class="amenity-stat__icon amenity-stat__icon--sale">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                        </span>
                        <div class="amenity-stat__body">
                            <p class="amenity-stat__value">{{ $onSaleAmenities }}</p>
                            <p class="amenity-stat__label">On Sale</p>
                        </div>
                    </article>
                </div>

                @if(session('success'))
                    <div class="alert alert--success">{{ session('success') }}</div>
                @endif

                <section class="amenities-table-wrap">
                    <div class="amenities-table-toolbar">
                        <div class="amenities-table-toolbar__item">
                            <label for="amenitySearch">Search amenities</label>
                            <input id="amenitySearch" type="search" placeholder="Search by name..." autocomplete="off">
                        </div>
                        <div class="amenities-table-toolbar__item">
                            <label for="amenitySortColumn">Sort column</label>
                            <select id="amenitySortColumn">
                                <option value="">None</option>
                                <option value="daytimePrice">Day price</option>
                                <option value="nighttimePrice">Night price</option>
                                <option value="daytimeAirconPrice">Daytime aircon price</option>
                                <option value="nighttimeAirconPrice">Nighttime aircon price</option>
                                <option value="additionalPerHead">Additional per head</option>
                                <option value="minimumCapacity">Minimum capacity</option>
                                <option value="maximumCapacity">Maximum capacity</option>
                            </select>
                        </div>
                        <div class="amenities-table-toolbar__item">
                            <label for="amenitySortOrder">Sort order</label>
                            <select id="amenitySortOrder">
                                <option value="none">None</option>
                                <option value="asc">Low to high</option>
                                <option value="desc">High to low</option>
                            </select>
                        </div>
                        <div class="amenities-table-toolbar__item">
                            <label for="amenityStatus">Status</label>
                            <select id="amenityStatus">
                                <option value="all">All statuses</option>
                                <option value="enabled">Enabled only</option>
                                <option value="disabled">Disabled only</option>
                            </select>
                        </div>
                    </div>
                    <table class="amenities-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Day price</th>
                                <th>Night price</th>
                                <th>Min cap</th>
                                <th>Max cap</th>
                                <th>Sale %</th>
                                <th>Status</th>
                                <th class="amenities-table__actions-head">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($amenities as $amenity)
                                <tr
                                    data-amenity-id="{{ $amenity->id }}"
                                    data-amenities-name="{{ e($amenity->amenities_name) }}"
                                    data-daytime-price="{{ $amenity->daytime_price }}"
                                    data-nighttime-price="{{ $amenity->nighttime_price }}"
                                    data-daytime-aircon-price="{{ $amenity->daytime_aircon_price }}"
                                    data-nighttime-aircon-price="{{ $amenity->nighttime_aircon_price }}"
                                    data-additional-per-head="{{ $amenity->additional_per_head }}"
                                    data-minimum-capacity="{{ $amenity->minimum_capacity }}"
                                    data-maximum-capacity="{{ $amenity->maximum_capacity }}"
                                    data-description="{{ e($amenity->description) }}"
                                    data-image-url="{{ $amenity->image ? e(asset('storage/' . $amenity->image)) : '' }}"
                                    data-image-path="{{ $amenity->image ?? '' }}"
                                    data-status="{{ $amenity->status ? 'enabled' : 'disabled' }}"
                                    data-sale-percentage="{{ $amenity->sale_percentage ?? 0 }}"
                                    data-original-daytime-price="{{ $amenity->original_daytime_price ?? $amenity->daytime_price }}"
                                    data-original-nighttime-price="{{ $amenity->original_nighttime_price ?? $amenity->nighttime_price }}"
                                    data-original-daytime-aircon-price="{{ $amenity->original_daytime_aircon_price ?? $amenity->daytime_aircon_price }}"
                                    data-original-nighttime-aircon-price="{{ $amenity->original_nighttime_aircon_price ?? $amenity->nighttime_aircon_price }}"
                                    class="amenity-row {{ $amenity->sale_percentage && $amenity->sale_percentage > 0 ? 'amenity-row--sale' : '' }}"
                                >
                                    <td>
                                        <span class="amenity-cell-name">
                                            <span class="amenity-cell-name__chip">{{ strtoupper(mb_substr($amenity->amenities_name, 0, 1)) }}</span>
                                            <span class="amenity-cell-name__text">{{ $amenity->amenities_name }}</span>
                                        </span>
                                    </td>
                                    <td class="num-cell">
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <span class="price-old">₱{{ number_format($amenity->original_daytime_price ?? $amenity->daytime_price, 2) }}</span>
                                            <span class="price-now">₱{{ number_format($amenity->daytime_price, 2) }}</span>
                                        @else
                                            <span>₱{{ number_format($amenity->daytime_price, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="num-cell">
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <span class="price-old">₱{{ number_format($amenity->original_nighttime_price ?? $amenity->nighttime_price, 2) }}</span>
                                            <span class="price-now">₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                                        @else
                                            <span>₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="num-cell">{{ $amenity->minimum_capacity !== null && $amenity->minimum_capacity !== '' ? $amenity->minimum_capacity : '—' }}</td>
                                    <td class="num-cell">{{ $amenity->maximum_capacity !== null && $amenity->maximum_capacity !== '' ? $amenity->maximum_capacity : '—' }}</td>
                                    <td>
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <span class="badge badge--sale">{{ $amenity->sale_percentage }}% OFF</span>
                                        @else
                                            <span class="num-cell">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-pill {{ $amenity->status ? 'status-pill--enabled' : 'status-pill--disabled' }}">
                                            {{ $amenity->status ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="amenities-table__actions">
                                        <button type="button" class="row-action row-action--edit" data-action="edit" title="Edit amenity">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button" class="row-action row-action--delete" data-action="delete" title="Delete amenity">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="table-empty">No amenities found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </main>
        </div>
    </div>

    <div class="modal" id="amenityModal" aria-hidden="true">
        <div class="modal__backdrop" data-close-amenity-modal></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h3 id="amenityModalTitle">Add New Amenity</h3>
                <button type="button" class="modal__close" data-close-amenity-modal>&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.amenities.store') }}" class="modal-form" id="amenityForm" enctype="multipart/form-data" data-store-url="{{ route('admin.amenities.store') }}" data-update-base-url="{{ url('admin/amenities') }}">
                @csrf
                <input type="hidden" name="amenity_id" id="amenityId">
                <input type="hidden" name="existing_image" id="existingImage">

                <div class="modal-form__row modal-form__row--full">
                    <div id="imagePreview" class="image-preview" style="display:none;">
                        <img id="imagePreviewImg" src="" alt="Amenity preview">
                    </div>
                </div>

                <div class="modal-form__row">
                    <label for="amenities_name">Name <span>*</span></label>
                    <input id="amenities_name" name="amenities_name" type="text" required>
                </div>

                <div class="modal-form__row">
                    <label for="daytime_price">Original Daytime Price <span>*</span></label>
                    <input id="daytime_price" name="daytime_price" type="number" step="0.01" min="0" inputmode="decimal" required>
                    <small style="color: var(--hp-text-muted); font-size: 0.8rem;">Base price before sale discount</small>
                </div>

                <div class="modal-form__row">
                    <label for="nighttime_price">Original Nighttime Price <span>*</span></label>
                    <input id="nighttime_price" name="nighttime_price" type="number" step="0.01" min="0" inputmode="decimal" required>
                    <small style="color: var(--hp-text-muted); font-size: 0.8rem;">Base price before sale discount</small>
                </div>

                <div class="modal-form__row">
                    <label for="daytime_aircon_price">Original Daytime Aircon Price</label>
                    <input id="daytime_aircon_price" name="daytime_aircon_price" type="number" step="0.01" min="0" inputmode="decimal">
                    <small style="color: var(--hp-text-muted); font-size: 0.8rem;">Base price before sale discount</small>
                </div>

                <div class="modal-form__row">
                    <label for="nighttime_aircon_price">Original Nighttime Aircon Price</label>
                    <input id="nighttime_aircon_price" name="nighttime_aircon_price" type="number" step="0.01" min="0" inputmode="decimal">
                    <small style="color: var(--hp-text-muted); font-size: 0.8rem;">Base price before sale discount</small>
                </div>

                <div class="modal-form__row">
                    <label for="additional_per_head">Additional Per Head</label>
                    <input id="additional_per_head" name="additional_per_head" type="number" step="0.01" min="0" inputmode="decimal">
                </div>

                <div class="modal-form__row">
                    <label for="minimum_capacity">Minimum Capacity</label>
                    <input id="minimum_capacity" name="minimum_capacity" type="number" step="1" min="0" inputmode="numeric">
                </div>

                <div class="modal-form__row">
                    <label for="maximum_capacity">Maximum Capacity</label>
                    <input id="maximum_capacity" name="maximum_capacity" type="number" step="1" min="0" inputmode="numeric">
                </div>

                <div class="modal-form__row modal-form__row--full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="modal-form__row modal-form__row--full">
                    <label>Amenity Image</label>
                    <div class="dropzone" id="imageDropZone">
                        <span class="dropzone__text">Drag & drop an image here, or click to browse</span>
                        <span class="dropzone__filename" id="imageFileName">No file chosen</span>
                    </div>
                    <input id="image" name="image" type="file" accept="image/*" hidden>
                </div>

                <div class="modal-form__row">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="enabled">Enabled</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>

                <div class="modal-form__row">
                    <label for="sale_percentage">Sale Percentage (%)</label>
                    <input id="sale_percentage" name="sale_percentage" type="number" step="0.01" min="0" max="100" inputmode="decimal" placeholder="0 = no sale">
                </div>

                <div class="modal-form__actions">
                    <button type="button" class="btn btn--ghost" data-close-amenity-modal>Cancel</button>
                    <button type="button" class="btn btn--ghost" id="amenityEditButton" style="display:none;">Edit</button>
                    <button type="submit" class="btn btn--primary" id="amenitySubmitButton">Create Amenity</button>
                    <button type="button" class="btn btn--danger" id="amenityDeleteButton" style="display:none;" data-delete-amenity>Delete</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
