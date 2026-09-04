<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_amenitiesmanagement.js',
        'resources/js/admin_chatbot.js',
    ])
    <style>
        body.admin-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.admin-portal {
            background-color: #0f1110 !important;
        }
        body.admin-portal .dash-layout,
        body.admin-portal .dash-main,
        body.admin-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.admin-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.admin-portal .dash-main::before {
            content: '' !important;
            display: block !important;
            position: fixed !important;
            top: 0 !important;
            left: var(--dash-sidebar-w, 10rem) !important;
            right: 0 !important;
            bottom: 0 !important;
            width: auto !important;
            height: 100vh !important;
            z-index: -1 !important;
            pointer-events: none !important;
            background-color: #ebf3ec !important;
            background-image: url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            background-size: 100% 100% !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
            transition: left 0.25s ease !important;
        }
        .dash-layout.sidebar-collapsed .dash-main::before {
            left: 0 !important;
        }
        @media (max-width: 992px) {
            body.admin-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.admin-portal .dash-main::before {
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.admin-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.admin-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="amenities" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <x-header
                title="Amenities Management"
                subtitle="Create, edit, and maintain park amenities"
            />
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content p-6">
                <section class="mb-7 flex flex-wrap items-start justify-between gap-6">
                    <div>
                        <p class="mb-3 inline-flex rounded-full bg-[rgba(200,164,93,0.12)] px-[0.95rem] py-[0.45rem] text-[0.85rem] font-bold uppercase tracking-[0.14em] text-[var(--hp-gold-dark)]">Manage Amenities</p>
                        <h2 class="m-0 text-[1.85rem] font-bold text-[var(--hp-text)]">All amenities</h2>
                        <p class="m-0 mt-[0.65rem] max-w-[38rem] leading-[1.75] text-[var(--hp-text-muted)]">View amenity details, enable or disable availability, and add new park services.</p>
                    </div>
                    <button type="button" class="btn btn--primary" data-open-amenity-modal>New Amenity</button>
                </section>

                <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $totalAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Total Amenities</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $enabledAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Enabled</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--danger-soft)] text-[var(--danger)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $disabledAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Disabled</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--warn-soft)] text-[var(--warn)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $onSaleAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">On Sale</p>
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
                                <th>Benefits</th>
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
                                    data-is-aircon="{{ $amenity->benefits?->is_aircon ? '1' : '0' }}"
                                    data-free-entrance="{{ $amenity->benefits?->free_entrance ? '1' : '0' }}"
                                    data-free-pool="{{ $amenity->benefits?->free_pool ? '1' : '0' }}"
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
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @if($amenity->benefits?->is_aircon)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-cyan-500/15 border border-cyan-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold text-cyan-700 dark:text-cyan-300">
                                                    <i class="bi bi-snow"></i> Aircon
                                                </span>
                                            @endif
                                            @if($amenity->benefits?->free_entrance)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/15 border border-emerald-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold text-emerald-700 dark:text-emerald-300">
                                                    <i class="bi bi-ticket-perforated-fill"></i> Free Entrance
                                                </span>
                                            @endif
                                            @if($amenity->benefits?->free_pool)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-500/15 border border-blue-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold text-blue-700 dark:text-blue-300">
                                                    <i class="bi bi-water"></i> Free Pool
                                                </span>
                                            @endif
                                            @if(!$amenity->benefits?->is_aircon && !$amenity->benefits?->free_entrance && !$amenity->benefits?->free_pool)
                                                <span class="text-xs text-hp-text-muted">—</span>
                                            @endif
                                        </div>
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
                                    <td colspan="9" class="table-empty">No amenities found yet.</td>
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

                <div class="modal-form__row modal-form__row--full">
                    <label class="block mb-2 font-semibold">Included Benefits & Perks</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 rounded-xl border border-glass-border bg-glass">
                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="is_aircon" name="is_aircon" value="1" class="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-snow text-cyan-600 dark:text-cyan-400"></i> Has Aircon</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="free_entrance" name="free_entrance" value="1" class="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-ticket-perforated-fill text-emerald-600 dark:text-emerald-400"></i> Free Entrance</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="free_pool" name="free_pool" value="1" class="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-water text-blue-600 dark:text-blue-400"></i> Free Pool Access</span>
                        </label>
                    </div>
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

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
