{{--
    Tour overlay component.
    Usage: @include('partials.tour', ['tourKey' => 'home', 'steps' => [...]])
    Each step: ['target' => '#css-selector', 'title' => '...', 'desc' => '...', 'optional' => false]
--}}
<div
    x-data="{
        active:  false,
        moving:  false,
        step:    0,
        box:     { top: 0, left: 0, width: 0, height: 0 },
        tip:     { top: 0, left: 0, place: 'below' },
        steps:   {{ Js::from($steps) }},
        storageKey: 'spv_tour_{{ $tourKey }}',

        init() {
            this._onScroll = () => { if (this.active) this.reposition(); };
            window.addEventListener('scroll', this._onScroll, { passive: true });
            this.$watch('active', v => {
                if (!v) window.removeEventListener('scroll', this._onScroll);
            });
            if (!localStorage.getItem(this.storageKey)) {
                this.$nextTick(() => { this.active = true; this.go(0); });
            }
        },

        go(idx) {
            if (idx >= this.steps.length) { this.finish(); return; }
            const s  = this.steps[idx];
            const el = document.querySelector(s.target);
            if (!el) { this.go(idx + 1); return; }

            // Hide tooltip instantly (no transition), then move box, then reveal
            this.moving = true;
            this.step   = idx;
            el.scrollIntoView({ block: 'center', behavior: 'instant' });
            this.position(el);
            // Reveal tooltip AFTER the box CSS transition finishes (260ms)
            setTimeout(() => { this.moving = false; }, 260);
        },

        reposition() {
            const s = this.steps[this.step];
            if (!s) return;
            const el = document.querySelector(s.target);
            if (el) this.position(el);
        },

        position(el) {
            const r   = el.getBoundingClientRect();
            const pad = 10;
            const h   = Math.min(r.height, 260);
            this.box  = {
                top:    r.top    - pad,
                left:   r.left   - pad,
                width:  r.width  + pad * 2,
                height: h        + pad * 2,
            };
            const tipW      = 300;
            const tipH      = 210;
            const boxBottom = this.box.top + this.box.height;
            const place     = (window.innerHeight - boxBottom - 16) >= tipH ? 'below' : 'above';
            this.tip = {
                top:  place === 'below' ? boxBottom + 12 : this.box.top - tipH - 12,
                left: Math.min(Math.max(r.left + r.width / 2 - tipW / 2, 12), window.innerWidth - tipW - 12),
                place,
            };
        },

        next()   { this.go(this.step + 1); },
        skip()   { this.finish(); },
        finish() {
            localStorage.setItem(this.storageKey, '1');
            this.active = false;
        },
    }"
    x-cloak
>
    {{-- Click-catcher --}}
    <div x-show="active" class="fixed inset-0 z-[9990]"></div>

    {{-- Spotlight box — smooth CSS transition on position/size --}}
    <div
        x-show="active"
        class="fixed z-[9995] rounded-xl pointer-events-none"
        :style="`
            top:        ${box.top}px;
            left:       ${box.left}px;
            width:      ${box.width}px;
            height:     ${box.height}px;
            box-shadow: 0 0 0 3px #3b82f6, 0 0 0 9999px rgba(0,0,0,0.65);
            transition: top 240ms cubic-bezier(0.4,0,0.2,1),
                        left 240ms cubic-bezier(0.4,0,0.2,1),
                        width 240ms cubic-bezier(0.4,0,0.2,1),
                        height 240ms cubic-bezier(0.4,0,0.2,1);
        `"
    ></div>

    {{--
        Tooltip — instantly hidden (no leave transition so it never blips at
        the old position), smooth fade-in only after box has arrived.
    --}}
    <div
        x-show="active && !moving"
        x-transition:enter="transition-opacity duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed z-[9999] w-[300px] bg-white rounded-2xl shadow-2xl pointer-events-auto"
        :style="`top: ${tip.top}px; left: ${tip.left}px;`"
    >
        <div class="absolute left-6 w-3 h-3 bg-white rotate-45"
             :class="tip.place === 'below' ? '-top-1.5' : '-bottom-1.5'"></div>

        <div class="p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex gap-1">
                    <template x-for="(s, i) in steps" :key="i">
                        <div class="h-1.5 rounded-full transition-all duration-200"
                             :class="i === step ? 'w-4 bg-brand-500' : 'w-1.5 bg-gray-200'"></div>
                    </template>
                </div>
                <button @click.stop="skip" class="text-gray-400 hover:text-gray-600 text-xs">Lewati</button>
            </div>

            <h3 class="font-bold text-gray-800 text-sm mb-1" x-text="steps[step]?.title"></h3>
            <p class="text-gray-500 text-xs leading-relaxed" x-text="steps[step]?.desc"></p>

            <div class="flex items-center justify-between mt-4">
                <span class="text-xs text-gray-400" x-text="`${step + 1} / ${steps.length}`"></span>
                <button
                    @click.stop="next"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg px-4 py-2 transition-colors"
                    x-text="step === steps.length - 1 ? 'Selesai' : 'Selanjutnya'"
                ></button>
            </div>
        </div>
    </div>

    {{-- Trigger button --}}
    <button
        @click="active = true; go(0)"
        title="Tampilkan tutorial"
        class="fixed bottom-5 right-5 z-[9980] bg-white border border-gray-200 shadow-lg rounded-full w-11 h-11 flex items-center justify-center text-brand-600 hover:bg-brand-50 hover:border-brand-300 transition-colors"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>
</div>

<style>[x-cloak]{display:none!important}</style>
