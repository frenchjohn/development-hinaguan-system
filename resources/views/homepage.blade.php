<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hinaguan Nature Park — A riverside sanctuary in Jasaan, Misamis Oriental. Discover pristine trails, crystal-clear waters, and unforgettable outdoor experiences.">

    <title>Hinaguan Nature Park</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|dancing-script:700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/css/chatbot.css', 'resources/js/homepage.js', 'resources/js/guest_chatbot.js'])
</head>
<body class="bg-[#06190f] text-[#1c2b22] font-['Montserrat',sans-serif] antialiased overflow-x-hidden selection:bg-[#a3e635] selection:text-[#06190f]">

    {{-- Fixed Site Header --}}
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-transparent border-b border-transparent" id="hpSiteHeader">
        @if (($parkSettings->park_status ?? 'open') === 'closed')
            {{-- Park Closed Notice Bar --}}
            <div class="bg-red-900/90 text-red-100 text-xs py-1 px-4 text-center border-b border-red-700/50 flex items-center justify-center gap-2">
                <span class="inline-flex items-center gap-1 bg-red-600 text-white font-bold text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Park Closed
                </span>
                <span>{{ $parkSettings->close_description ?: 'The park is temporarily closed today for maintenance or weather safety.' }}</span>
                <span class="opacity-75">| Inquiries: {{ $parkSettings->contact_number ?? '0917 861 8383' }}</span>
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 h-20 flex items-center justify-between">
            {{-- Logo --}}
            <a href="#home" class="flex items-center gap-3 group no-underline">
                <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-emerald-400/50 shadow-[0_0_12px_rgba(74,222,128,0.25)] shrink-0 group-hover:border-emerald-300 transition-colors">
                    <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park Logo" class="w-full h-full object-cover">
                </div>
                <div class="flex flex-col">
                    <span class="font-serif text-lg sm:text-xl font-bold text-white tracking-wide leading-tight group-hover:text-emerald-200 transition-colors">Hinaguan Nature Park</span>
                    <span class="text-[9px] sm:text-[10px] font-bold tracking-[0.2em] text-emerald-400 uppercase">JASAAN, MISAMIS ORIENTAL</span>
                </div>
            </a>

            {{-- Desktop Nav Links (Ordered to match section order down the page) --}}
            <nav class="hidden lg:flex items-center gap-6 xl:gap-7 relative py-1" aria-label="Main Navigation" id="hpDesktopNav">
                <a href="#about" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">About</a>
                <a href="#activities" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">Activities</a>
                <a href="#gallery" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">Gallery</a>
                <a href="#rates" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">Rates</a>
                <a href="#amenities" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">Amenities</a>
                <a href="#events" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">Events</a>
                <a href="#reviews" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">Reviews</a>
                <a href="#directions" data-nav-link class="text-xs font-semibold uppercase tracking-wider text-white/80 hover:text-[#a3e635] transition-colors duration-200 py-1 relative">Directions</a>
            </nav>

            {{-- Action / Book Now --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('reservation') }}" class="inline-flex items-center justify-center bg-[#a3e635] hover:bg-[#bef264] text-[#082214] font-extrabold text-xs tracking-wider uppercase px-6 py-2.5 rounded-full shadow-[0_2px_15px_rgba(163,230,53,0.35)] transition-all transform hover:scale-105 active:scale-95 no-underline">
                    BOOK NOW
                </a>

                {{-- Mobile Menu Button --}}
                <button type="button" class="lg:hidden p-2 rounded-lg text-white/90 hover:text-white hover:bg-white/10 transition hp-menu-toggle" aria-label="Toggle navigation">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>
        </div>
    </header>

    {{-- Mobile Navigation Drawer --}}
    <div id="hpMobileNav" class="hp-mobile-nav fixed inset-y-0 right-0 w-72 bg-[#061a10]/95 backdrop-blur-xl border-l border-white/10 z-50 p-6 flex flex-col justify-between translate-x-full transition-transform duration-300 lg:hidden" aria-hidden="true">
        <div>
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" class="w-8 h-8 rounded-full border border-emerald-400" alt="Logo">
                    <span class="font-serif font-bold text-white text-sm">Hinaguan Park</span>
                </div>
                <button type="button" id="hpMobileNavClose" class="text-white/70 hover:text-white p-1" aria-label="Close menu">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <div class="flex flex-col gap-4">
                <a href="#about" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">About</a>
                <a href="#activities" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">Activities</a>
                <a href="#gallery" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">Gallery</a>
                <a href="#rates" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">Rates</a>
                <a href="#amenities" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">Amenities</a>
                <a href="#events" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">Events</a>
                <a href="#reviews" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">Reviews</a>
                <a href="#directions" data-nav-link class="text-sm font-semibold uppercase tracking-wider text-white/90 hover:text-[#a3e635] py-2 border-b border-white/5 transition-all duration-300">Directions</a>
            </div>
        </div>

        <div class="pt-6 border-t border-white/10">
            <a href="{{ route('reservation') }}" class="block text-center bg-[#a3e635] text-[#082214] font-extrabold text-xs tracking-wider uppercase py-3 rounded-full shadow-lg">
                BOOK NOW
            </a>
        </div>
    </div>

    {{-- SECTION 1: HERO (Design 1: Modern Nature - Image Hero) --}}
    <section class="relative min-h-screen pt-28 pb-20 flex items-center overflow-hidden bg-[#06190f]" id="home" data-section>
        {{-- Hero Background Image --}}
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/background.jpeg') }}" alt="Hinaguan Nature Park Riverside Cabins" class="w-full h-full object-cover object-center scale-105 transform">
            {{-- Dark Nature Gradient Overlays --}}
            <div class="absolute inset-0 bg-gradient-to-r from-[#061a10]/95 via-[#061a10]/80 to-[#061a10]/40"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#061a10] via-transparent to-black/40"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_40%,rgba(16,185,129,0.12),transparent_70%)]"></div>
        </div>

        {{-- Floating Left Quick Contact Circles (As seen in Reference Image 1) --}}
        <div class="hidden lg:flex fixed left-6 top-1/2 -translate-y-1/2 z-40 flex-col gap-3">
            <a href="#directions" class="w-10 h-10 rounded-full bg-[#0a2517]/80 hover:bg-[#1b5e3a] border border-emerald-500/30 text-emerald-300 hover:text-white flex items-center justify-center transition-all duration-300 shadow-lg backdrop-blur-sm" title="Directions to Park">
                <i class="bi bi-geo-alt text-base"></i>
            </a>
            <a href="tel:+63{{ preg_replace('/[^0-9]/', '', $parkSettings->contact_number ?? '0917 861 8383') }}" class="w-10 h-10 rounded-full bg-[#0a2517]/80 hover:bg-[#1b5e3a] border border-emerald-500/30 text-emerald-300 hover:text-white flex items-center justify-center transition-all duration-300 shadow-lg backdrop-blur-sm" title="Call Us">
                <i class="bi bi-telephone text-base"></i>
            </a>
        </div>

        {{-- Hero Content Grid --}}
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10 w-full grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            {{-- Left Column: Hero Typography & Actions --}}
            <div class="lg:col-span-7 flex flex-col items-start pt-4 lg:pt-0">
                <h1 class="font-serif text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight leading-[1.08] mb-5 text-white drop-shadow-sm">
                    Hinaguan<br>
                    <span class="text-[#84cc16] lg:text-[#a3e635]">Nature Park</span>
                </h1>

                <p class="text-emerald-100/85 text-base sm:text-lg max-w-xl leading-relaxed mb-7 font-light">
                    Where the river sings and the forest breathes — an enchanting riverside escape owned by celebrity Brenda Mage.
                </p>

                {{-- Currently In The Park Pill (Matches Design 1 screenshot) --}}
                <div class="mb-8">
                    <a href="{{ route('amenities') }}" class="inline-flex items-center gap-3.5 bg-[#0a2718]/90 hover:bg-[#0f3d26] border border-emerald-500/40 px-5 py-2.5 rounded-full shadow-xl transition-all duration-200 group no-underline">
                        <span class="w-8 h-8 rounded-full border border-emerald-400 bg-emerald-950/80 flex items-center justify-center text-emerald-400 text-sm shadow-[0_0_12px_rgba(74,222,128,0.35)] shrink-0">
                            <i class="bi bi-people-fill"></i>
                        </span>
                        <div class="flex flex-col text-left">
                            <span class="text-[9px] font-extrabold tracking-[0.2em] text-emerald-300 uppercase">CURRENTLY IN THE PARK</span>
                            <span class="text-sm font-bold text-white leading-tight">
                                <span id="activeGuestCount" data-count="{{ $activeGuestCount ?? 3 }}">{{ $activeGuestCount ?? 3 }}</span> guests
                            </span>
                        </div>
                    </a>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('reservation') }}" class="inline-flex items-center justify-center bg-[#a3e635] hover:bg-[#bef264] text-[#082214] font-extrabold text-xs sm:text-sm tracking-wider uppercase px-8 py-3.5 rounded-full transition-all transform hover:scale-105 active:scale-95 shadow-[0_4px_20px_rgba(163,230,53,0.35)] no-underline">
                        RESERVE NOW
                    </a>
                    <a href="#about" class="inline-flex items-center justify-center border border-white/40 hover:border-white text-white bg-white/5 hover:bg-white/15 backdrop-blur-sm font-medium text-xs sm:text-sm tracking-wider uppercase px-8 py-3.5 rounded-full transition-all transform hover:scale-105 active:scale-95 no-underline">
                        EXPLORE THE PARK
                    </a>
                </div>
            </div>

            {{-- Right Column: Weather Card & Brenda Celebrity Card (Matches Design 1 screenshot) --}}
            <div class="lg:col-span-5 flex flex-col items-center lg:items-end w-full">
                {{-- Weather Card --}}
                <div class="w-full max-w-sm bg-[#0c2618]/90 backdrop-blur-md border border-emerald-500/30 rounded-3xl p-5 sm:p-6 shadow-2xl text-white relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                    {{-- Top row: Weather condition & location --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl text-amber-300">
                                <i class="bi bi-sun-fill"></i>
                            </span>
                            <span class="font-medium text-sm text-emerald-100">
                                {{ $weather['condition'] ?? 'Patchy rain nearby' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-emerald-300/90 font-medium">
                            <i class="bi bi-geo-alt-fill text-emerald-400"></i>
                            <span>{{ $weather['location'] ?? 'Jasaan' }}</span>
                        </div>
                    </div>

                    {{-- Metric chips row --}}
                    <div class="grid grid-cols-3 gap-2 mb-5">
                        <div class="bg-[#071d12]/80 border border-emerald-500/20 px-2.5 py-1.5 rounded-full text-center text-xs font-medium text-emerald-200 flex items-center justify-center gap-1">
                            <i class="bi bi-thermometer-half text-emerald-400"></i>
                            <span>Feels {{ round($weather['feelslike_c'] ?? 29) }}°</span>
                        </div>
                        <div class="bg-[#071d12]/80 border border-emerald-500/20 px-2.5 py-1.5 rounded-full text-center text-xs font-medium text-emerald-200 flex items-center justify-center gap-1">
                            <i class="bi bi-droplet-half text-emerald-400"></i>
                            <span>{{ $weather['humidity'] ?? 79 }}%</span>
                        </div>
                        <div class="bg-[#071d12]/80 border border-emerald-500/20 px-2.5 py-1.5 rounded-full text-center text-xs font-medium text-emerald-200 flex items-center justify-center gap-1">
                            <i class="bi bi-wind text-emerald-400"></i>
                            <span>{{ round($weather['wind_kph'] ?? 4) }} km/h</span>
                        </div>
                    </div>

                    {{-- Next Hours Forecast --}}
                    <div class="pt-3 border-t border-emerald-500/20">
                        <div class="flex items-center gap-1.5 text-[10px] font-extrabold tracking-widest text-emerald-400 uppercase mb-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>NEXT HOURS</span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center">
                            @if (!empty($weather['next_3_hours']))
                                @foreach ($weather['next_3_hours'] as $hour)
                                    <div class="flex flex-col items-center">
                                        <span class="text-[11px] text-emerald-200/80 mb-1">{{ $hour['time_label'] }}</span>
                                        <div class="h-7 flex items-center justify-center mb-1">
                                            @if (!empty($hour['icon']))
                                                <img src="{{ $hour['icon'] }}" class="w-6 h-6 object-contain" alt="">
                                            @else
                                                <i class="bi bi-cloud-sun text-emerald-300"></i>
                                            @endif
                                        </div>
                                        <span class="text-xs font-bold text-white">{{ round($hour['temp_c']) }}°</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="flex flex-col items-center">
                                    <span class="text-[11px] text-emerald-200/80 mb-1">8 PM</span>
                                    <i class="bi bi-cloud-rain text-emerald-300 text-lg mb-1"></i>
                                    <span class="text-xs font-bold text-white">26°</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <span class="text-[11px] text-emerald-200/80 mb-1">9 PM</span>
                                    <i class="bi bi-cloud-moon text-emerald-300 text-lg mb-1"></i>
                                    <span class="text-xs font-bold text-white">26°</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <span class="text-[11px] text-emerald-200/80 mb-1">10 PM</span>
                                    <i class="bi bi-cloud-drizzle text-emerald-300 text-lg mb-1"></i>
                                    <span class="text-xs font-bold text-white">26°</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Celebrity In The Park Card (Brenda Mage) --}}
                <div class="w-full max-w-sm bg-[#0c2618]/90 backdrop-blur-md border border-emerald-500/30 rounded-2xl p-3.5 flex items-center gap-3.5 shadow-xl mt-4 relative group">
                    <div class="relative shrink-0">
                        <img src="{{ asset('images/brendamageishere.jpeg') }}" alt="Brenda Mage in the park" class="w-13 h-13 rounded-full object-cover border-2 border-emerald-400 shadow-md">
                        <span class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full bg-emerald-400 border-2 border-[#0c2618]"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block text-[10px] font-bold text-emerald-300 uppercase tracking-wide">Celebrity in the park!</span>
                        <h4 class="text-xs sm:text-sm font-bold text-white truncate">Brenda is in the park!</h4>
                        <p class="text-[11px] text-emerald-100/70 truncate">Damngo</p>
                    </div>
                    <div class="text-emerald-400 text-sm pl-1">
                        <i class="bi bi-stars"></i>
                    </div>
                </div>

                {{-- Cursive signature flourish (Hinaguan Farm) as seen in Design 1 --}}
                <div class="w-full max-w-sm text-right mt-2 pr-3">
                    <span class="font-['Dancing_Script',cursive] text-2xl sm:text-3xl text-[#d4b06a] italic tracking-wide">Hinaguan Farm</span>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 2: ABOUT (Design 2: Clean & Minimal) --}}
    <section class="bg-[#f7faf7] py-20 lg:py-28 relative overflow-hidden" id="about" data-section>
        {{-- Faint Botanical Leaf Background Accent --}}
        <div class="absolute top-0 right-0 w-64 h-64 opacity-5 pointer-events-none text-[#1b5e3a]">
            <svg viewBox="0 0 200 200" fill="currentColor" class="w-full h-full">
                <path d="M45,-60C58,-52,68,-39,73,-24C78,-9,78,8,73,23C67,39,56,53,42,63C28,73,12,79,-4,83C-19,87,-34,88,-46,80C-58,71,-68,54,-73,37C-79,20,-80,3,-76,-13C-72,-28,-63,-42,-50,-51C-38,-59,-19,-63,-1,-61C17,-60,33,-69,45,-60Z" transform="translate(100 100)" />
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            {{-- Left: A-frame Cottage Photo with Organic Curve --}}
            <div class="lg:col-span-6">
                <div class="relative rounded-[2.5rem] overflow-hidden shadow-2xl border border-emerald-950/10 group">
                    <img src="{{ asset('images/About_Image.jpeg') }}" alt="A-frame cottages at Hinaguan Nature Park" class="w-full h-[380px] sm:h-[460px] object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 flex items-center justify-between text-white">
                        <span class="text-xs uppercase tracking-widest font-semibold bg-black/40 backdrop-blur-md px-3.5 py-1.5 rounded-full border border-white/20">
                            A-Frame Riverside Cottages
                        </span>
                    </div>
                </div>
            </div>

            {{-- Right: Clean & Minimal Text + 4 Feature Badges --}}
            <div class="lg:col-span-6 flex flex-col items-start">
                <span class="text-xs font-bold tracking-[0.22em] text-[#1b5e3a] uppercase mb-3">
                    ABOUT HINAGUAN NATURE PARK
                </span>

                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-[#0f2d1e] tracking-tight leading-[1.15] mb-6">
                    Where Nature Feels Like Home
                </h2>

                <p class="text-gray-600 text-base sm:text-lg leading-relaxed mb-10">
                    Nestled beside the beautiful river of Jasaan, Misamis Oriental, Hinaguan Nature Park is a peaceful escape where nature, adventure, and unforgettable moments come together.
                </p>

                {{-- 4 Feature Badges as shown in Design 2 --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 w-full pt-4 border-t border-emerald-900/10">
                    {{-- 1. Riverside Escape --}}
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-12 h-12 rounded-full bg-[#e8f3ec] text-[#1b5e3a] group-hover:bg-[#1b5e3a] group-hover:text-white transition-colors duration-300 flex items-center justify-center text-xl mb-2.5">
                            <i class="bi bi-flower1"></i>
                        </div>
                        <span class="text-xs font-bold text-[#0f2d1e]">Riverside Escape</span>
                    </div>

                    {{-- 2. Nature Views --}}
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-12 h-12 rounded-full bg-[#e8f3ec] text-[#1b5e3a] group-hover:bg-[#1b5e3a] group-hover:text-white transition-colors duration-300 flex items-center justify-center text-xl mb-2.5">
                            <i class="bi bi-triangle-half"></i>
                        </div>
                        <span class="text-xs font-bold text-[#0f2d1e]">Nature Views</span>
                    </div>

                    {{-- 3. Family Friendly --}}
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-12 h-12 rounded-full bg-[#e8f3ec] text-[#1b5e3a] group-hover:bg-[#1b5e3a] group-hover:text-white transition-colors duration-300 flex items-center justify-center text-xl mb-2.5">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <span class="text-xs font-bold text-[#0f2d1e]">Family Friendly</span>
                    </div>

                    {{-- 4. Great Memories --}}
                    <div class="flex flex-col items-center text-center group">
                        <div class="w-12 h-12 rounded-full bg-[#e8f3ec] text-[#1b5e3a] group-hover:bg-[#1b5e3a] group-hover:text-white transition-colors duration-300 flex items-center justify-center text-xl mb-2.5">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <span class="text-xs font-bold text-[#0f2d1e]">Great Memories</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 3: ACTIVITIES (Design 5: Activities Page) --}}
    <section class="py-20 lg:py-28 relative overflow-hidden bg-[#06190f] text-white" id="activities" data-section>
        {{-- Background Image with Same Dark Green Overlays as Section 1 --}}
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('storage/design_images/activities_background.png') }}" alt="Hinaguan Nature Park Activities Background" class="w-full h-full object-cover object-center scale-105 transform">
            {{-- Dark Nature Gradient Overlays (Matching Section 1 Hero) --}}
            <div class="absolute inset-0 bg-gradient-to-r from-[#061a10]/95 via-[#061a10]/85 to-[#061a10]/60"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#061a10] via-transparent to-[#061a10]/70"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_40%,rgba(16,185,129,0.14),transparent_70%)]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10">
            {{-- Header (Things To Do / Activities & Experiences) --}}
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold tracking-[0.22em] text-[#a3e635] uppercase inline-block mb-2">
                    THINGS TO DO
                </span>
                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-white tracking-tight mb-4 drop-shadow-sm">
                    Activities &amp; Experiences
                </h2>
                <p class="text-emerald-100/80 text-base sm:text-lg leading-relaxed">
                    From peaceful riverside walks to fun-filled group activities, there's something for every visitor.
                </p>
            </div>

            {{-- Activity cards become a carousel when more than four records exist. --}}
            <div class="hp-activities-carousel overflow-hidden">
            <div class="hp-activities-track" id="hpActivitiesTrack">
                @foreach ($activities as $activity)
                    @php
                        $activityImage = $activity->image
                            ? (str_starts_with($activity->image, 'images/')
                                ? asset($activity->image)
                                : asset('storage/' . $activity->image))
                            : '';
                    @endphp
                    <div class="hp-activity-card cursor-pointer bg-[#102d20]/90 rounded-xl p-4 shadow-lg hover:bg-[#163b29] transition-colors duration-300 border border-emerald-200/20 flex flex-col group" data-activity-card data-activity-title="{{ $activity->activity }}" data-activity-description="{{ $activity->description }}" data-activity-image="{{ $activityImage }}" role="button" aria-label="View details for {{ $activity->activity }}">
                        <div class="rounded-2xl overflow-hidden h-44 sm:h-48 w-full relative mb-6 bg-emerald-950/5">
                            @if ($activity->image)
                                <img src="{{ $activityImage }}" alt="{{ $activity->activity }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full bg-[#163b29]"></div>
                            @endif
                        </div>
                        <div class="flex flex-col flex-grow text-center px-2">
                            <h3 class="text-lg font-bold text-white font-serif mb-2">{{ $activity->activity }}</h3>
                            <p class="text-xs text-emerald-100/75 leading-relaxed mb-4 flex-grow">{{ $activity->description }}</p>
                            <button type="button" class="text-[11px] font-bold tracking-wider text-emerald-300 hover:text-white uppercase flex items-center justify-center gap-1.5 pt-2 border-t border-emerald-200/15 transition-colors">
                                <span>VIEW DETAILS</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
                @if (false)
                {{-- Card 1: River Trekking --}}
                <div class="hp-activity-card bg-[#102d20]/90 rounded-xl p-4 shadow-lg hover:bg-[#163b29] transition-colors duration-300 border border-emerald-200/20 flex flex-col group">
                    <div class="rounded-2xl overflow-hidden h-44 sm:h-48 w-full relative mb-6 bg-emerald-950/5">
                        <img src="{{ asset('images/River_Trecking.jpg') }}" alt="River Trekking" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <div class="flex flex-col flex-grow text-center px-2">
                        <h3 class="text-lg font-bold text-white font-serif mb-2">River Trekking</h3>
                        <p class="text-xs text-emerald-100/75 leading-relaxed mb-4 flex-grow">Explore the river trails and discover hidden spots.</p>
                        <button type="button" class="text-[11px] font-bold tracking-wider text-emerald-300 hover:text-white uppercase flex items-center justify-center gap-1.5 pt-2 border-t border-emerald-200/15 transition-colors" data-activity-card data-activity-title="River Trekking" data-activity-description="Explore the pristine river trails along the clear waters of Jasaan. Discover natural boulder paths, hidden rapids, and peaceful riverside viewpoints." data-activity-image="{{ asset('images/River_Trecking.jpg') }}">
                            <span>VIEW DETAILS</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Card 2: Swimming & Wading --}}
                <div class="hp-activity-card bg-[#102d20]/90 rounded-xl p-4 shadow-lg hover:bg-[#163b29] transition-colors duration-300 border border-emerald-200/20 flex flex-col group">
                    <div class="rounded-2xl overflow-hidden h-44 sm:h-48 w-full relative mb-6 bg-emerald-950/5">
                        <img src="{{ asset('images/swimming_and_wading.jpg') }}" alt="Swimming & Wading" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <div class="flex flex-col flex-grow text-center px-2">
                        <h3 class="text-lg font-bold text-white font-serif mb-2">Swimming &amp; Wading</h3>
                        <p class="text-xs text-emerald-100/75 leading-relaxed mb-4 flex-grow">Cool off in the natural pool or wade in the shallow river.</p>
                        <button type="button" class="text-[11px] font-bold tracking-wider text-emerald-300 hover:text-white uppercase flex items-center justify-center gap-1.5 pt-2 border-t border-emerald-200/15 transition-colors" data-activity-card data-activity-title="Swimming &amp; Wading" data-activity-description="Enjoy natural spring waters and clean river swimming pools. Perfect for refreshing swims on sunny days, family wading, and relaxing in calm currents." data-activity-image="{{ asset('images/swimming_and_wading.jpg') }}">
                            <span>VIEW DETAILS</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Card 3: Picnic & Bonding --}}
                <div class="hp-activity-card bg-[#102d20]/90 rounded-xl p-4 shadow-lg hover:bg-[#163b29] transition-colors duration-300 border border-emerald-200/20 flex flex-col group">
                    <div class="rounded-2xl overflow-hidden h-44 sm:h-48 w-full relative mb-6 bg-emerald-950/5">
                        <img src="{{ asset('images/picnic_and_bonding.jpg') }}" alt="Picnic & Bonding" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <div class="flex flex-col flex-grow text-center px-2">
                        <h3 class="text-lg font-bold text-white font-serif mb-2">Picnic &amp; Bonding</h3>
                        <p class="text-xs text-emerald-100/75 leading-relaxed mb-4 flex-grow">Enjoy meals with loved ones in the fresh air.</p>
                        <button type="button" class="text-[11px] font-bold tracking-wider text-emerald-300 hover:text-white uppercase flex items-center justify-center gap-1.5 pt-2 border-t border-emerald-200/15 transition-colors" data-activity-card data-activity-title="Picnic &amp; Bonding" data-activity-description="Gather with friends and family over delicious food. Choose from our comfortable native huts and tables with sweeping views of the lush trees and flowing waters." data-activity-image="{{ asset('images/picnic_and_bonding.jpg') }}">
                            <span>VIEW DETAILS</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Card 4: Photography --}}
                <div class="hp-activity-card bg-[#102d20]/90 rounded-xl p-4 shadow-lg hover:bg-[#163b29] transition-colors duration-300 border border-emerald-200/20 flex flex-col group">
                    <div class="rounded-2xl overflow-hidden h-44 sm:h-48 w-full relative mb-6 bg-emerald-950/5">
                        <img src="{{ asset('images/photography.jpg') }}" alt="Photography" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <div class="flex flex-col flex-grow text-center px-2">
                        <h3 class="text-lg font-bold text-white font-serif mb-2">Photography</h3>
                        <p class="text-xs text-emerald-100/75 leading-relaxed mb-4 flex-grow">Capture stunning shots of the riverside and nature.</p>
                        <button type="button" class="text-[11px] font-bold tracking-wider text-emerald-300 hover:text-white uppercase flex items-center justify-center gap-1.5 pt-2 border-t border-emerald-200/15 transition-colors" data-activity-card data-activity-title="Photography" data-activity-description="Every corner of Hinaguan Nature Park is picture-perfect. From the A-frame cabins to the lush canopy and cascading river stones, create unforgettable memories." data-activity-image="{{ asset('images/photography.jpg') }}">
                            <span>VIEW DETAILS</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                @foreach ($activities->slice(4) as $activity)
                    <div class="hp-activity-card bg-[#102d20]/90 rounded-xl p-4 shadow-lg hover:bg-[#163b29] transition-colors duration-300 border border-emerald-200/20 flex flex-col group">
                        <div class="rounded-2xl overflow-hidden h-44 sm:h-48 w-full relative mb-6 bg-emerald-950/5">
                            @if ($activity->image)
                                <img src="{{ asset($activity->image) }}" alt="{{ $activity->activity }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full bg-[#163b29]"></div>
                            @endif
                        </div>
                        <div class="flex flex-col flex-grow text-center px-2">
                            <h3 class="text-lg font-bold text-white font-serif mb-2">{{ $activity->activity }}</h3>
                            <p class="text-xs text-emerald-100/75 leading-relaxed mb-4 flex-grow">{{ $activity->description }}</p>
                            <button type="button" class="text-[11px] font-bold tracking-wider text-emerald-300 hover:text-white uppercase flex items-center justify-center gap-1.5 pt-2 border-t border-emerald-200/15 transition-colors" data-activity-card data-activity-title="{{ $activity->activity }}" data-activity-description="{{ $activity->description }}" data-activity-image="{{ $activity->image ? asset($activity->image) : '' }}">
                                <span>VIEW DETAILS</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
                @endif
            </div>
            @if ($activities->count() > 4)
                <div class="hp-activities-carousel__controls" aria-label="Activity navigation">
                    <button type="button" class="hp-carousel-btn" id="hpActivitiesPrev" aria-label="Previous activities" disabled>
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <span class="hp-activities-carousel__count" id="hpActivitiesCount" aria-live="polite">1 / 2</span>
                    <button type="button" class="hp-carousel-btn" id="hpActivitiesNext" aria-label="Next activities">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            @endif
        </div>
    </section>

    {{-- SECTION 4: GALLERY (Design 4: Gallery Page) --}}
    <section class="bg-[#f7faf7] py-20 lg:py-28 relative overflow-hidden" id="gallery" data-section>
        {{-- Botanical leaves corner graphics as seen in Design 4 --}}
        <div class="absolute top-4 left-4 w-40 h-40 opacity-20 pointer-events-none text-emerald-800">
            <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M10,90 Q40,40 90,10 M40,40 Q60,30 75,15 M30,55 Q50,45 65,35 M20,70 Q40,65 50,50"/>
            </svg>
        </div>
        <div class="absolute top-4 right-4 w-40 h-40 opacity-20 pointer-events-none text-emerald-800 transform scale-x-[-1]">
            <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M10,90 Q40,40 90,10 M40,40 Q60,30 75,15 M30,55 Q50,45 65,35 M20,70 Q40,65 50,50"/>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10">
            {{-- Header (Our Gallery / Moments at Hinaguan) --}}
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-bold tracking-[0.22em] text-[#1b5e3a] uppercase block mb-2">
                    OUR GALLERY
                </span>
                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-[#0f2d1e] tracking-tight mb-3">
                    Moments at Hinaguan
                </h2>
                <p class="text-gray-600 text-base sm:text-lg">
                    Beautiful places, happy faces, and unforgettable memories.
                </p>
            </div>

            {{-- 4x2 Photos Grid as seen in Design 4 --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                @foreach (range(1, 7) as $i)
                    <div class="rounded-2xl overflow-hidden h-40 sm:h-48 w-full shadow-md group cursor-pointer relative bg-emerald-950/10">
                        <img src="{{ asset('images/image_' . $i . '.jpg') }}" alt="Moments at Hinaguan {{ $i }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <span class="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm text-emerald-900 flex items-center justify-center shadow-lg">
                                <i class="bi bi-zoom-in text-lg"></i>
                            </span>
                        </div>
                    </div>
                @endforeach

                {{-- 8th Item: View More Photos Card as seen in Design 4 --}}
                <div class="rounded-2xl border-2 border-dashed border-[#1b5e3a]/30 bg-white/70 hover:bg-white hover:border-[#1b5e3a] h-40 sm:h-48 w-full flex items-center justify-center p-6 transition duration-300 group cursor-pointer shadow-sm">
                    <a href="#about" class="text-center flex flex-col items-center gap-2 text-[#1b5e3a] group-hover:text-[#0f2d1e] no-underline">
                        <span class="w-10 h-10 rounded-full bg-[#e8f3ec] group-hover:bg-[#1b5e3a] group-hover:text-white transition-colors flex items-center justify-center text-lg">
                            <i class="bi bi-images"></i>
                        </span>
                        <span class="text-sm font-bold flex items-center gap-1.5">
                            View More Photos <i class="bi bi-arrow-right"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 5: PRICING (Design 6: Pricing Page) --}}
    <section class="py-20 lg:py-28 relative overflow-hidden bg-[#06190f] text-white" id="rates" data-section>
        {{-- Background Image with Same Dark Green Overlays as Section 1 --}}
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('storage/design_images/background_image2.png') }}" alt="Hinaguan Nature Park Rates Background" class="w-full h-full object-cover object-center scale-105 transform">
            {{-- Dark Nature Gradient Overlays (Matching Section 1 Hero) --}}
            <div class="absolute inset-0 bg-gradient-to-r from-[#061a10]/95 via-[#061a10]/80 to-[#061a10]/55"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#061a10] via-transparent to-[#061a10]/70"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_40%,rgba(16,185,129,0.14),transparent_70%)]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10">
            {{-- Header (Pricing / Affordable Rates for Everyone) --}}
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold tracking-[0.22em] text-[#a3e635] uppercase inline-block mb-2">
                    PRICING
                </span>
                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-white tracking-tight mb-3 drop-shadow-sm">
                    Affordable Rates for Everyone
                </h2>
                <p class="text-emerald-100/80 text-base sm:text-lg">
                    Transparent pricing with no hidden fees. Choose the visit type that suits your adventure.
                </p>
            </div>

            {{-- 2 Side-by-Side Comparison Cards as seen in Design 6 --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto mb-10">
                {{-- Card 1: Daytime Visit (Light Card) --}}
                <div class="bg-[#102d20]/90 rounded-xl p-8 sm:p-10 border border-emerald-200/20 shadow-lg relative text-white flex flex-col justify-between hover:bg-[#163b29] transition-colors duration-300">
                    <div>
                        <div class="flex items-center gap-3.5 mb-2">
                            <span class="text-3xl text-emerald-300">
                                <i class="bi bi-sun-fill"></i>
                            </span>
                            <h3 class="text-2xl sm:text-3xl font-serif font-bold text-white">Daytime Visit</h3>
                        </div>
                        <p class="text-[10px] font-extrabold tracking-[0.15em] text-emerald-300 uppercase mb-8">
                            ENTRANCE FEE &bull; FULL PARK ACCESS DURING THE DAY
                        </p>

                        <div class="space-y-4">
                            {{-- Adult Rate --}}
                            <div class="flex items-center justify-between py-2 border-b border-emerald-200/15">
                                <span class="bg-emerald-100/15 text-emerald-100 font-bold text-xs tracking-wider px-5 py-2 rounded-md uppercase">
                                    ADULT
                                </span>
                                <div class="text-right">
                                    <span class="font-bold text-lg sm:text-xl text-white">&#8369;{{ number_format((float) ($parkSettings->daytime_adult_entrance_fee ?? 20), 2) }}</span>
                                    <span class="text-xs text-emerald-100/60 font-normal">per person</span>
                                </div>
                            </div>

                            {{-- Child Rate --}}
                            <div class="flex items-center justify-between py-2">
                                <span class="bg-emerald-100/15 text-emerald-100 font-bold text-xs tracking-wider px-5 py-2 rounded-md uppercase">
                                    CHILD
                                </span>
                                <div class="text-right">
                                    <span class="font-bold text-lg sm:text-xl text-white">&#8369;{{ number_format((float) ($parkSettings->daytime_child_entrance_fee ?? 0), 2) }}</span>
                                    <span class="text-xs text-emerald-100/60 font-normal">per person</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Overnight Stay (Deep Forest Green Card) --}}
                <div class="bg-[#102d20]/90 rounded-xl p-8 sm:p-10 border border-emerald-300/30 shadow-lg relative text-white flex flex-col justify-between hover:bg-[#163b29] transition-colors duration-300">
                    <div>
                        <div class="flex items-center gap-3.5 mb-2">
                            <span class="text-3xl text-[#a3e635]">
                                <i class="bi bi-moon-stars-fill"></i>
                            </span>
                            <h3 class="text-2xl sm:text-3xl font-serif font-bold text-white">Overnight Stay</h3>
                        </div>
                        <p class="text-[10px] font-extrabold tracking-[0.15em] text-emerald-300 uppercase mb-8">
                            ENTRANCE FEE &bull; CHECK-IN 6:00 PM &bull; CHECK-OUT 8:00 AM
                        </p>

                        <div class="space-y-4">
                            {{-- Adult Rate --}}
                            <div class="flex items-center justify-between py-2 border-b border-emerald-800/60">
                                <span class="bg-emerald-100/15 text-emerald-100 font-bold text-xs tracking-wider px-5 py-2 rounded-md uppercase">
                                    ADULT
                                </span>
                                <div class="text-right">
                                    <span class="font-bold text-lg sm:text-xl text-white">&#8369;{{ number_format((float) ($parkSettings->nighttime_adult_entrance_fee ?? 50), 2) }}</span>
                                    <span class="text-xs text-emerald-200/80 font-normal">per person</span>
                                </div>
                            </div>

                            {{-- Child Rate --}}
                            <div class="flex items-center justify-between py-2">
                                <span class="bg-emerald-100/15 text-emerald-100 font-bold text-xs tracking-wider px-5 py-2 rounded-md uppercase">
                                    CHILD
                                </span>
                                <div class="text-right">
                                    <span class="font-bold text-lg sm:text-xl text-white">&#8369;{{ number_format((float) ($parkSettings->nighttime_child_entrance_fee ?? 0), 2) }}</span>
                                    <span class="text-xs text-emerald-200/80 font-normal">per person</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom Disclaimer as seen in Design 6 --}}
            <div class="text-center max-w-xl mx-auto bg-[#102d20]/90 border border-emerald-200/20 rounded-xl p-6 shadow-lg">
                <p class="text-xs sm:text-sm text-emerald-100/85 font-medium mb-5">
                    Entrance fee of &#8369;{{ number_format((float) ($parkSettings->daytime_adult_entrance_fee ?? 20), 2) }} applies to all visitors. Cottage and amenity rentals are priced separately.
                </p>
                <a href="{{ route('reservation') }}" class="inline-flex items-center gap-2 bg-[#a3e635] hover:bg-[#bef264] text-[#082214] font-extrabold text-xs tracking-wider uppercase px-8 py-3.5 rounded-full transition-all shadow-[0_4px_20px_rgba(163,230,53,0.35)] no-underline">
                    <span>BOOK YOUR VISIT</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- SECTION 6: AMENITIES --}}
    <section class="bg-white py-20 lg:py-28 relative overflow-hidden" id="amenities" data-section>
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold tracking-[0.22em] text-[#1b5e3a] uppercase block mb-2">
                    WHAT WE OFFER
                </span>
                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-[#0f2d1e] tracking-tight mb-4">
                    Park Amenities &amp; Highlights
                </h2>
                <p class="text-gray-600 text-base sm:text-lg">
                    Everything you need for a comfortable and memorable visit, surrounded by nature's finest offerings.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                {{-- Amenity 1 --}}
                <div class="bg-[#f8faf8] hover:bg-white border border-emerald-950/10 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-[#1b5e3a]/40 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100/70 group-hover:bg-[#1b5e3a] group-hover:text-white text-[#1b5e3a] flex items-center justify-center text-2xl mb-4 transition-colors duration-300 shadow-sm">
                        <i class="bi bi-house-door-fill"></i>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-[#0f2d1e] mb-2">Cottages &amp; Huts</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Rustic cottages and open huts perfect for day visits or overnight stays with family and friends.</p>
                </div>

                {{-- Amenity 2 --}}
                <div class="bg-[#f8faf8] hover:bg-white border border-emerald-950/10 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-[#1b5e3a]/40 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100/70 group-hover:bg-[#1b5e3a] group-hover:text-white text-[#1b5e3a] flex items-center justify-center text-2xl mb-4 transition-colors duration-300 shadow-sm">
                        <i class="bi bi-water"></i>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-[#0f2d1e] mb-2">Natural Pool</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Refresh in our clean, spring-fed swimming pool surrounded by towering trees and tropical foliage.</p>
                </div>

                {{-- Amenity 3 --}}
                <div class="bg-[#f8faf8] hover:bg-white border border-emerald-950/10 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-[#1b5e3a]/40 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100/70 group-hover:bg-[#1b5e3a] group-hover:text-white text-[#1b5e3a] flex items-center justify-center text-2xl mb-4 transition-colors duration-300 shadow-sm">
                        <i class="bi bi-image"></i>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-[#0f2d1e] mb-2">Scenic Views</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Panoramic river views, lush natural landscapes, and photo-worthy spots at every turn of the park.</p>
                </div>

                {{-- Amenity 4 --}}
                <div class="bg-[#f8faf8] hover:bg-white border border-emerald-950/10 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-[#1b5e3a]/40 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100/70 group-hover:bg-[#1b5e3a] group-hover:text-white text-[#1b5e3a] flex items-center justify-center text-2xl mb-4 transition-colors duration-300 shadow-sm">
                        <i class="bi bi-tsunami"></i>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-[#0f2d1e] mb-2">Natural River</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Relax by crystal-clear river waters with gentle currents and shallow wading zones.</p>
                </div>

                {{-- Amenity 5 --}}
                <div class="bg-[#f8faf8] hover:bg-white border border-emerald-950/10 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-[#1b5e3a]/40 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100/70 group-hover:bg-[#1b5e3a] group-hover:text-white text-[#1b5e3a] flex items-center justify-center text-2xl mb-4 transition-colors duration-300 shadow-sm">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-[#0f2d1e] mb-2">Affordable Rates</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">Enjoy nature, fun, and relaxation without breaking the bank — exceptional value for everyone.</p>
                </div>

                {{-- Amenity 6 --}}
                <div class="bg-[#f8faf8] hover:bg-white border border-emerald-950/10 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-[#1b5e3a]/40 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100/70 group-hover:bg-[#1b5e3a] group-hover:text-white text-[#1b5e3a] flex items-center justify-center text-2xl mb-4 transition-colors duration-300 shadow-sm">
                        <i class="bi bi-cup-hot-fill"></i>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-[#0f2d1e] mb-2">Food &amp; Refreshments</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">On-site refreshment points and grill areas so you can stay energized throughout your adventure.</p>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('amenities') }}" class="inline-flex items-center gap-2 rounded-full border-2 border-[#1b5e3a] text-[#1b5e3a] hover:bg-[#1b5e3a] hover:text-white px-8 py-3 text-xs font-bold uppercase tracking-wider transition-all duration-300 shadow-sm no-underline">
                    <span>View All Amenities</span>
                    <i class="bi bi-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- SECTION 7: EVENTS --}}
    <section class="py-20 lg:py-28 relative overflow-hidden bg-[#06190f] text-white border-t border-white/5" id="events" data-section>
        {{-- Background Image with Same Dark Green Overlays as Section 1 --}}
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('storage/design_images/event_background.png') }}" alt="Hinaguan Nature Park Events Background" class="w-full h-full object-cover object-center scale-105 transform">
            {{-- Dark Nature Gradient Overlays (Matching Section 1 Hero) --}}
            <div class="absolute inset-0 bg-gradient-to-r from-[#061a10]/95 via-[#061a10]/85 to-[#061a10]/60"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#061a10] via-transparent to-[#061a10]/70"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_40%,rgba(16,185,129,0.14),transparent_70%)]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold tracking-[0.22em] text-[#a3e635] uppercase inline-block mb-2">
                    WHAT'S HAPPENING
                </span>
                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-white tracking-tight mb-4 drop-shadow-sm">
                    Park Events &amp; Experiences
                </h2>
                <p class="text-emerald-100/80 text-base sm:text-lg">
                    Discover exciting gatherings, seasonal celebrations, and outdoor activities scheduled at Hinaguan Nature Park.
                </p>
            </div>

            @if ($nearEvent)
                {{-- Near Event Spotlight Card --}}
                <div class="max-w-4xl mx-auto mb-12 bg-[#102d20]/90 border border-emerald-300/30 rounded-xl p-6 sm:p-8 shadow-lg relative overflow-hidden">
                    <div class="inline-flex items-center gap-2 bg-[#a3e635] text-[#082214] font-extrabold text-[10px] tracking-widest uppercase px-3.5 py-1 rounded-full mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#082214] animate-pulse"></span>
                        <span>FEATURED NEAR EVENT &bull; HAPPENING SOON</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                        <div class="sm:col-span-3 text-center sm:text-left sm:border-r sm:border-emerald-500/20 pr-4">
                            <span class="block text-emerald-300 font-bold text-xs uppercase tracking-wider">{{ \Carbon\Carbon::parse($nearEvent->date)->format('M') }}</span>
                            <span class="block text-4xl sm:text-5xl font-extrabold text-white leading-none my-1">{{ \Carbon\Carbon::parse($nearEvent->date)->format('d') }}</span>
                            <span class="block text-xs text-emerald-200/70">{{ $nearEvent->day ?: \Carbon\Carbon::parse($nearEvent->date)->format('l') }}</span>
                        </div>

                        <div class="sm:col-span-6">
                            <div class="flex items-center gap-3 text-xs text-emerald-300 mb-2">
                                <span><i class="bi bi-clock"></i> {{ $nearEvent->time ?: 'All Day Event' }}</span>
                                <span>&bull;</span>
                                <span><i class="bi bi-geo-alt"></i> Hinaguan Nature Park</span>
                            </div>
                            <h3 class="text-2xl font-serif font-bold text-white mb-2">{{ $nearEvent->title }}</h3>
                            <p class="text-sm text-emerald-100/75 leading-relaxed">{{ $nearEvent->event }}</p>
                        </div>

                        <div class="sm:col-span-3 text-center sm:text-right">
                            <a href="{{ route('reservation') }}" class="inline-block bg-[#a3e635] hover:bg-[#bef264] text-[#082214] font-bold text-xs uppercase tracking-wider px-6 py-3 rounded-full transition-all shadow-md no-underline">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if ($allEvents->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($allEvents as $event)
                        @php
                            $eventDate = \Carbon\Carbon::parse($event->date);
                            $isToday = $eventDate->isToday();
                        @endphp
                        <article class="bg-[#102d20]/90 border {{ $isToday ? 'border-emerald-400' : 'border-emerald-200/20' }} rounded-xl p-5 flex gap-4">
                            <div class="w-16 h-16 rounded-lg bg-[#0a2417] border border-emerald-300/25 flex flex-col items-center justify-center shrink-0 text-center">
                                <span class="text-[10px] uppercase font-bold text-emerald-400">{{ $eventDate->format('M') }}</span>
                                <span class="text-xl font-bold text-white leading-none">{{ $eventDate->format('d') }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    @if ($isToday)
                                        <span class="bg-emerald-500 text-black text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full">Today</span>
                                    @endif
                                    @if ($event->time)
                                        <span class="text-xs text-emerald-300"><i class="bi bi-clock"></i> {{ $event->time }}</span>
                                    @endif
                                </div>
                                <h4 class="font-serif text-lg font-bold text-white truncate">{{ $event->title }}</h4>
                                <p class="text-xs text-emerald-100/70 line-clamp-2 mt-1">{{ $event->event }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 border border-dashed border-emerald-300/25 rounded-xl max-w-xl mx-auto bg-[#102d20]/70">
                    <i class="bi bi-calendar4-event text-3xl text-emerald-400/60 mb-3 block"></i>
                    <h3 class="font-serif text-lg text-white font-semibold">Open Daily for Regular Visits</h3>
                    <p class="text-xs text-emerald-100/60 mt-1">Check back soon for upcoming festivals and outdoor weekend events.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- SECTION 8: REVIEWS (Running Carousel of Reviews) --}}
    <section class="bg-[#f7faf7] py-20 lg:py-28 relative overflow-hidden" id="reviews" data-section>
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10 mb-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-bold tracking-[0.22em] text-[#1b5e3a] uppercase block mb-2">
                    GUEST REVIEWS
                </span>
                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-[#0f2d1e] tracking-tight mb-4">
                    What Visitors Say
                </h2>
                <p class="text-gray-600 text-base sm:text-lg">
                    Real experiences from guests who explored Hinaguan Nature Park.
                </p>
            </div>
        </div>

        @if ($featuredFeedbacks->isNotEmpty())
            @php
                $rawCount = $featuredFeedbacks->count();
                // Repeat reviews so the marquee is long enough to run smoothly and infinitely
                $repeatMultiplier = $rawCount >= 8 ? 1 : (int) ceil(8 / max(1, $rawCount));
            @endphp
            {{-- Infinite Running Carousel Track --}}
            <div class="hp-reviews-running py-4">
                <div class="hp-reviews-running__track">
                    @foreach (range(1, 2) as $trackGroup)
                        <div class="hp-reviews-running__group" {{ $loop->last ? 'aria-hidden=true' : '' }}>
                            @for ($rep = 0; $rep < $repeatMultiplier; $rep++)
                                @foreach ($featuredFeedbacks as $feedback)
                                    <article class="w-80 sm:w-96 shrink-0 bg-white rounded-2xl p-6 shadow-sm border border-emerald-950/10 flex flex-col justify-between hover:shadow-xl hover:border-[#1b5e3a]/40 transition-all duration-300">
                                        <div>
                                            <div class="flex items-center gap-3.5 mb-4">
                                                <div class="w-10 h-10 rounded-full bg-[#1b5e3a] text-white flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
                                                    {{ $feedback->initials }}
                                                </div>
                                                <div class="min-w-0">
                                                    <h4 class="font-bold text-sm text-[#0f2d1e] truncate">{{ $feedback->full_name }}</h4>
                                                    <time class="text-xs text-gray-400 block">{{ $feedback->created_at->format('M j, Y') }}</time>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1 text-amber-400 text-sm mb-3">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    <i class="bi {{ $s <= $feedback->stars ? 'bi-star-fill' : 'bi-star' }}"></i>
                                                @endfor
                                            </div>

                                            <p class="text-sm text-gray-600 leading-relaxed italic line-clamp-4">
                                                &ldquo;{{ $feedback->description }}&rdquo;
                                            </p>
                                        </div>
                                    </article>
                                @endforeach
                            @endfor
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="text-center mt-10 relative z-10">
                <a href="{{ route('feedback') }}" class="inline-flex items-center gap-2 border-2 border-[#1b5e3a] text-[#1b5e3a] hover:bg-[#1b5e3a] hover:text-white px-8 py-3 rounded-full text-xs font-bold uppercase tracking-wider transition-all duration-300 shadow-sm no-underline">
                    <span>See Other Reviews</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @else
            <div class="text-center py-12 max-w-md mx-auto">
                <p class="text-gray-500 mb-4">No guest reviews yet. Be the first to share your Hinaguan experience!</p>
                <a href="{{ route('feedback') }}" class="inline-block bg-[#1b5e3a] text-white px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider">
                    Write a Review
                </a>
            </div>
        @endif
    </section>

    {{-- SECTION 9: DIRECTIONS & CONTACT --}}
    <section class="bg-[#06190f] py-20 lg:py-28 text-white relative overflow-hidden border-t border-white/5" id="directions" data-section>
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold tracking-[0.22em] text-[#a3e635] uppercase block mb-2">
                    FIND US
                </span>
                <h2 class="text-4xl sm:text-5xl font-serif font-bold text-white tracking-tight mb-4">
                    Directions &amp; Contact
                </h2>
                <p class="text-emerald-100/80 text-base sm:text-lg">
                    Plan your trip to Hinaguan Nature Park in Jasaan, Misamis Oriental.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-stretch">
                {{-- Left details --}}
                <div class="lg:col-span-5 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-[#0c2419]/80 border border-emerald-500/20">
                            <span class="w-10 h-10 rounded-xl bg-emerald-950/80 text-[#a3e635] flex items-center justify-center shrink-0 text-xl">
                                <i class="bi bi-geo-alt-fill"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300">Location</h4>
                                <p class="text-sm text-white font-medium mt-0.5">Hinaguan, Jasaan, Misamis Oriental, Philippines</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-[#0c2419]/80 border border-emerald-500/20">
                            <span class="w-10 h-10 rounded-xl bg-emerald-950/80 text-[#a3e635] flex items-center justify-center shrink-0 text-xl">
                                <i class="bi bi-telephone-fill"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300">Phone</h4>
                                <p class="text-sm text-white font-medium mt-0.5">
                                    <a href="tel:+63{{ preg_replace('/[^0-9]/', '', $parkSettings->contact_number ?? '0917 861 8383') }}" class="hover:text-emerald-300 transition-colors">
                                        {{ $parkSettings->contact_number ?? '0917 861 8383' }}
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-[#0c2419]/80 border border-emerald-500/20">
                            <span class="w-10 h-10 rounded-xl bg-emerald-950/80 text-[#a3e635] flex items-center justify-center shrink-0 text-xl">
                                <i class="bi bi-envelope-fill"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300">Email</h4>
                                <p class="text-sm text-white font-medium mt-0.5">
                                    <a href="mailto:{{ $parkSettings->email ?? 'info@hinaguannaturepark.com' }}" class="hover:text-emerald-300 transition-colors">
                                        {{ $parkSettings->email ?? 'info@hinaguannaturepark.com' }}
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-[#0c2419]/80 border border-emerald-500/20">
                            <span class="w-10 h-10 rounded-xl bg-emerald-950/80 text-[#a3e635] flex items-center justify-center shrink-0 text-xl">
                                <i class="bi bi-clock-fill"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300">Park Hours</h4>
                                <p class="text-sm text-white font-medium mt-0.5 leading-relaxed">
                                    Daily: {{ $parkSettings?->opening_time ? date('g:i A', strtotime($parkSettings->opening_time)) : '6:00 AM' }} – {{ $parkSettings?->closing_time ? date('g:i A', strtotime($parkSettings->closing_time)) : '6:00 PM' }}<br>
                                    Overnight check-in from {{ $parkSettings?->nighttime_start ? date('g:i A', strtotime($parkSettings->nighttime_start)) : '6:00 PM' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 rounded-2xl bg-[#0a2015] border border-emerald-500/10">
                        <h4 class="text-sm font-bold text-white mb-2">How to Get Here</h4>
                        <ol class="text-xs text-emerald-100/70 space-y-1.5 list-decimal pl-4 leading-relaxed">
                            <li>From Cagayan de Oro City, take a bus or van bound for Jasaan.</li>
                            <li>Ask the driver to drop you off at Hinaguan, Jasaan.</li>
                            <li>Follow the park signage — approximately 5 minutes from the national highway.</li>
                        </ol>
                    </div>
                </div>

                {{-- Right map --}}
                <div class="lg:col-span-7 rounded-3xl overflow-hidden border border-emerald-500/30 shadow-2xl min-h-[380px] relative">
                    <iframe
                        title="Hinaguan Nature Park Location"
                        src="https://maps.google.com/maps?q=Jasaan%20Misamis%20Oriental%20Philippines&output=embed"
                        class="w-full h-full min-h-[380px] border-0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="bg-[#030d07] py-10 border-t border-white/5 text-white">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div class="flex items-center gap-3">
                <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" class="w-8 h-8 rounded-full border border-emerald-400" alt="Hinaguan Logo">
                <span class="font-serif font-bold text-sm tracking-wide">Hinaguan Nature Park &bull; Jasaan, Misamis Oriental</span>
            </div>
            <p class="text-xs text-emerald-100/50">
                &copy; {{ date('Y') }} Hinaguan Nature Park. All rights reserved.
            </p>
        </div>
    </footer>

    {{-- Activity Detail Modal --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 hp-activity-modal" id="hpActivityModal" aria-hidden="true" role="dialog">
        <div class="fixed inset-0" data-activity-modal-close></div>
        <div class="bg-white rounded-2xl max-w-3xl w-full overflow-hidden shadow-2xl relative z-10 p-5 sm:p-7 transform transition-transform duration-300 scale-95" role="document">
            <button type="button" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors" data-activity-modal-close aria-label="Close modal">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div class="min-h-56 overflow-hidden rounded-xl bg-[#e8f0ea]">
                    <img class="w-full h-full min-h-56 object-cover" id="hpActivityModalImage" alt="">
                </div>
                <div class="pr-2">
                    <span class="text-[10px] font-bold tracking-[0.2em] text-[#356748] uppercase block mb-2">PARK EXPERIENCE</span>
                    <h2 class="text-2xl font-serif font-bold text-[#0f2d1e] mb-3" id="hpActivityModalTitle"></h2>
                    <p class="text-sm text-gray-600 leading-relaxed" id="hpActivityModalDescription"></p>
                </div>
            </div>
        </div>
    </div>

    @if (($parkSettings->park_status ?? 'open') === 'closed')
        {{-- Notice: Park is Closed Today Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm modal fade hp-park-closed-modal" id="parkClosedModal" tabindex="-1" aria-hidden="true" role="dialog">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl text-center relative z-10">
                <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h3 class="font-serif text-2xl font-bold text-gray-900 mb-2">Notice: Park is Closed Today</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-6">
                    {{ $parkSettings->close_description ?: 'Hinaguan Nature Park is temporarily closed today for maintenance or weather safety. We apologize for any inconvenience caused.' }}
                </p>
                <button type="button" class="w-full bg-[#1b5e3a] hover:bg-[#14472b] text-white font-bold text-xs uppercase tracking-wider py-3 rounded-full transition-colors" data-bs-dismiss="modal">
                    Understood
                </button>
            </div>
        </div>
    @endif

    <x-guest_chatbot />

</body>
</html>
