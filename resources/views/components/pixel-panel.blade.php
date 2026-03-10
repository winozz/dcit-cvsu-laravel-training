@props(['title' => null])

<div {{ $attributes->merge(['class' => 'relative bg-[#1a2142] border-4 border-white shadow-[0_0_0_4px_#000,0_12px_0_#000] p-4 overflow-hidden']) }}>
    <div class="pointer-events-none absolute inset-0 opacity-30 bg-[linear-gradient(to_bottom,rgba(255,255,255,0.08)_0,rgba(255,255,255,0.08)_2px,transparent_2px,transparent_6px)]"></div>

    @if($title)
        <h2 class="relative z-10 text-[var(--warn,theme(colors.amber.300))] text-lg sm:text-xl font-bold drop-shadow-[2px_2px_0_#000] mb-3">
            {{ $title }}
        </h2>
    @endif

    <div class="relative z-10 space-y-3 text-[var(--ink,#ecf4ff)] text-sm leading-6">
        {{ $slot }}
    </div>
</div>
