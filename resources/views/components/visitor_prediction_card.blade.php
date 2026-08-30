@props(['predictionReport', 'isStaff' => false])

@if (!empty($predictionReport))
@php
    $dt = $predictionReport['daytime'];
    $nt = $predictionReport['nighttime'];
@endphp
<section class="mb-5 rounded-xl border border-glass-border bg-glass p-3.5 shadow-glass backdrop-blur-[6px] transition-all">
    {{-- Header Strip --}}
    <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2 border-b border-glass-border pb-2">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-hp-green-dark dark:text-[#f3f4f6]">
                    Visitor Traffic Prediction
                </span>
                <span class="rounded bg-black/5 px-2 py-0.5 text-[0.68rem] font-semibold text-hp-text-muted dark:bg-white/10">
                    {{ $predictionReport['day_name'] }}
                </span>
                @if ($predictionReport['is_holiday'])
                    <span class="rounded bg-amber-500/15 px-2 py-0.5 text-[0.68rem] font-bold text-amber-800 dark:text-amber-300">
                        Holiday: {{ $predictionReport['holiday_name'] }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1.5 text-xs">
            <span class="text-[0.7rem] text-hp-text-muted">Total Projected:</span>
            <strong class="font-display text-sm font-bold text-hp-green-dark dark:text-emerald-300">
                ~{{ $predictionReport['total_day_predicted'] }} guests
            </strong>
        </div>
    </div>

    {{-- Clean 2-Column Daytime vs Nighttime --}}
    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
        {{-- 1. DAYTIME SHIFT --}}
        <div class="rounded-lg border border-black/5 bg-black/[0.02] p-2.5 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="mb-1.5 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <strong class="text-xs font-bold text-hp-text">Daytime (8:00 AM – 6:00 PM)</strong>
                </div>
                <span class="rounded px-1.5 py-0.5 text-[0.65rem] font-semibold" style="background-color: {{ $dt['crowd_color'] }}15; color: {{ $dt['crowd_color'] }};">
                    {{ $dt['crowd_level'] }}
                </span>
            </div>

            <div class="flex items-baseline justify-between gap-2">
                <div class="flex items-baseline gap-1">
                    <span class="font-display text-xl font-bold text-hp-green-dark dark:text-[#f3f4f6]">~{{ $dt['predicted_guests'] }}</span>
                    <span class="text-[0.68rem] text-hp-text-muted">guests (range: {{ $dt['range_min'] }}–{{ $dt['range_max'] }})</span>
                </div>
                <div class="text-right text-[0.68rem] text-hp-text-muted">
                    <span class="font-medium text-hp-text">{{ $dt['weather'] }} ({{ round($dt['temperature']) }}°C)</span>
                    <span class="block text-[0.62rem]">Rain prob: {{ $dt['precipitation_probability'] }}%</span>
                </div>
            </div>

            <div class="mt-1.5 flex items-center justify-between border-t border-black/5 pt-1.5 text-[0.68rem] text-hp-text-muted dark:border-white/5">
                <span>Check-ins start: <strong class="text-hp-text">{{ $dt['earliest_arrival'] }}</strong></span>
                <span>Peak rush: <strong class="text-hp-text">{{ $dt['peak_arrival_window'] }}</strong></span>
            </div>
        </div>

        {{-- 2. NIGHTTIME SHIFT --}}
        <div class="rounded-lg border border-black/5 bg-black/[0.02] p-2.5 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="mb-1.5 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <strong class="text-xs font-bold text-hp-text">Nighttime (6:00 PM – 11:00 PM)</strong>
                </div>
                <span class="rounded px-1.5 py-0.5 text-[0.65rem] font-semibold" style="background-color: {{ $nt['crowd_color'] }}15; color: {{ $nt['crowd_color'] }};">
                    {{ $nt['crowd_level'] }}
                </span>
            </div>

            <div class="flex items-baseline justify-between gap-2">
                <div class="flex items-baseline gap-1">
                    <span class="font-display text-xl font-bold text-hp-green-dark dark:text-[#f3f4f6]">~{{ $nt['predicted_guests'] }}</span>
                    <span class="text-[0.68rem] text-hp-text-muted">guests (range: {{ $nt['range_min'] }}–{{ $nt['range_max'] }})</span>
                </div>
                <div class="text-right text-[0.68rem] text-hp-text-muted">
                    <span class="font-medium text-hp-text">{{ $nt['weather'] }} ({{ round($nt['temperature']) }}°C)</span>
                    <span class="block text-[0.62rem]">Rain prob: {{ $nt['precipitation_probability'] }}%</span>
                </div>
            </div>

            <div class="mt-1.5 flex items-center justify-between border-t border-black/5 pt-1.5 text-[0.68rem] text-hp-text-muted dark:border-white/5">
                <span>Check-ins start: <strong class="text-hp-text">{{ $nt['earliest_arrival'] }}</strong></span>
                <span>Peak rush: <strong class="text-hp-text">{{ $nt['peak_arrival_window'] }}</strong></span>
            </div>
        </div>
    </div>

    {{-- Explanatory note --}}
    <div class="mt-2 flex items-center justify-between text-[0.68rem] text-hp-text-muted">
        <span class="truncate">{{ $dt['explanation'] }}</span>
        <span class="shrink-0 ml-2 font-medium">[{{ $dt['confidence_badge'] }}]</span>
    </div>
</section>
@endif
