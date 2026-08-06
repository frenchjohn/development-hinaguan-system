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
</head>
<body class="antialiased">
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($amenities as $amenity)
                                <tr
                                    class="amenity-row"
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
                                    <td>{{ $amenity->amenities_name }}</td>
                                    <td>
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <span style="text-decoration: line-through; color: var(--hp-text-muted); margin-right: 0.5rem;">{{ $amenity->original_daytime_price ?? $amenity->daytime_price }}</span>
                                            <span style="color: var(--hp-green-dark); font-weight: 600;">{{ number_format($amenity->daytime_price, 2) }}</span>
                                        @else
                                            {{ number_format($amenity->daytime_price, 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <span style="text-decoration: line-through; color: var(--hp-text-muted); margin-right: 0.5rem;">{{ $amenity->original_nighttime_price ?? $amenity->nighttime_price }}</span>
                                            <span style="color: var(--hp-green-dark); font-weight: 600;">{{ number_format($amenity->nighttime_price, 2) }}</span>
                                        @else
                                            {{ number_format($amenity->nighttime_price, 2) }}
                                        @endif
                                    </td>
                                    <td>{{ $amenity->minimum_capacity !== null && $amenity->minimum_capacity !== '' ? $amenity->minimum_capacity : 'none' }}</td>
                                    <td>{{ $amenity->maximum_capacity !== null && $amenity->maximum_capacity !== '' ? $amenity->maximum_capacity : 'none' }}</td>
                                    <td>
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <span class="badge badge--sale">{{ $amenity->sale_percentage }}% OFF</span>
                                        @else
                                            <span>—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $amenity->status ? 'badge--enabled' : 'badge--disabled' }}">
                                            {{ $amenity->status ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="table-empty">No amenities found yet.</td>
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
