@extends('layout')

@section('title', 'Cari Pembimbing Skripsi')

@section('content')
{{-- Hero --}}
<div class="text-center mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Cari Pembimbing Skripsi</h1>
    <p class="text-gray-500">Temukan dosen pembimbing yang sesuai dengan topik skripsimu</p>
</div>

<script>
try {
    window.__spvState = JSON.parse(sessionStorage.getItem('spv_back_state') || 'null');
    if (window.__spvState) sessionStorage.removeItem('spv_back_state');
} catch(e) { window.__spvState = null; }
</script>

{{-- Search + Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6"
     x-data="{
         q: '',

         sorts: (function() {
             const s = window.__spvState;
             if (!s || !s.sorts) return [];
             const valid = ['views','contacts','active','alpha','relevance'];
             return s.sorts.split(',').map(function(str) {
                 const parts = str.split(':');
                 return { val: parts[0], dir: parts[1] === 'asc' ? 'asc' : 'desc' };
             }).filter(function(item) { return valid.includes(item.val); });
         })(),

         init() {
             const s = window.__spvState;
             if (!s) return;
             const qi = document.getElementById('search-input');
             if (qi && s.q) { qi.value = s.q; this.q = s.q; }
             const fp = document.getElementById('filter-program');
             if (fp && s.program) fp.value = s.program;
             const ft = document.getElementById('filter-topic');
             if (ft && s.topic) ft.value = s.topic;
             this.$nextTick(() => htmx.trigger(document.getElementById('sort-trigger'), 'click'));
         },

         toggle(val) {
             const idx = this.sorts.findIndex(s => s.val === val);
             if (idx === -1) {
                 this.sorts.push({ val, dir: val === 'alpha' ? 'asc' : 'desc' });
             } else if (this.sorts[idx].dir === 'desc') {
                 this.sorts[idx].dir = 'asc';
             } else {
                 this.sorts.splice(idx, 1);
             }
             this.$nextTick(() => htmx.trigger(document.getElementById('sort-trigger'), 'click'));
         },

         state(val) {
             const s = this.sorts.find(s => s.val === val);
             return s ? s.dir : null;
         },

         rank(val) {
             const idx = this.sorts.findIndex(s => s.val === val);
             return this.sorts.length > 1 && idx !== -1 ? idx + 1 : null;
         },

         sortsParam() {
             return this.sorts.length ? this.sorts.map(s => s.val + ':' + s.dir).join(',') : 'alpha:asc';
         }
     }">

    <div class="flex flex-col md:flex-row gap-3">
        {{-- Search bar --}}
        <div class="flex-1 relative">
            <input
                id="search-input"
                type="text"
                name="q"
                x-model="q"
                placeholder="Cari nama dosen, topik, atau judul skripsi yang pernah dibimbing..."
                class="w-full border rounded-xl px-4 py-3 pr-16 focus:outline-none focus:ring-2 focus:ring-brand-400 text-sm"
                hx-get="{{ route('search') }}"
                hx-trigger="input changed delay:350ms, search"
                hx-target="#results"
                hx-swap="innerHTML"
                hx-include="#filter-program,#filter-topic,#sort-hidden"
                hx-indicator="#search-spinner"
            >
            <span id="search-spinner" class="htmx-indicator absolute right-8 top-3.5 text-gray-400 text-sm">⏳</span>
            <button type="button" x-show="q" x-cloak
                @click="q = ''; $nextTick(() => htmx.trigger(document.getElementById('search-input'), 'search'))"
                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Program filter --}}
        <select id="filter-program" name="program"
            class="border rounded-xl px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 bg-white"
            hx-get="{{ route('search') }}"
            hx-trigger="change"
            hx-target="#results"
            hx-swap="innerHTML"
            hx-include="#search-input,#filter-topic,#sort-hidden">
            <option value="">Semua Program</option>
            @foreach($programs as $program)
                <option value="{{ $program->slug }}">{{ $program->name }}</option>
            @endforeach
            <option value="global-class">Global Class</option>
        </select>

        {{-- Topic filter --}}
        <select id="filter-topic" name="topic"
            class="border rounded-xl px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 bg-white"
            hx-get="{{ route('search') }}"
            hx-trigger="change"
            hx-target="#results"
            hx-swap="innerHTML"
            hx-include="#search-input,#filter-program,#sort-hidden">
            <option value="">Semua Topik</option>
            @foreach($programs as $program)
                <optgroup label="{{ $program->name }}">
                    @foreach($program->topics as $topic)
                        <option value="{{ $topic->slug }}">{{ $topic->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    {{-- Sort chips --}}
    <div id="sort-chips" class="flex flex-wrap items-center gap-2 mt-3">
        <span class="text-xs text-gray-400 mr-1">Urutkan:</span>

        @foreach([
            ['views',     'Paling Dilihat'],
            ['contacts',  'Paling Dihubungi'],
            ['active',    'Judul Aktif'],
            ['relevance', 'Relevansi'],
            ['alpha',     'A–Z'],
        ] as [$val, $label])
        <button type="button" @click="toggle('{{ $val }}')"
            :class="state('{{ $val }}')
                ? 'bg-brand-600 text-white border-brand-600'
                : 'bg-white text-gray-600 border-gray-300 hover:border-brand-400 hover:text-brand-600'"
            class="text-xs border rounded-full px-3 py-1.5 transition-colors font-medium select-none">
            <template x-if="rank('{{ $val }}')">
                <span x-text="rank('{{ $val }}')" class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-white/30 text-[10px] font-bold mr-1"></span>
            </template>
            {{ $label }}<span x-text="state('{{ $val }}') === 'desc' ? ' ↓' : state('{{ $val }}') === 'asc' ? ' ↑' : ''"></span>
        </button>
        @endforeach

        <button type="button" x-show="sorts.length > 0" @click="sorts = []; $nextTick(() => htmx.trigger(document.getElementById('sort-trigger'), 'click'))"
            class="text-xs text-gray-400 hover:text-red-500 transition-colors px-1">
            ✕ reset
        </button>

        {{-- Single hidden input carries all sort state --}}
        <input type="hidden" id="sort-hidden" name="sorts" :value="sortsParam()">

        {{-- Ghost trigger for chip clicks --}}
        <button id="sort-trigger" type="button" class="hidden"
            hx-get="{{ route('search') }}"
            hx-trigger="click"
            hx-target="#results"
            hx-swap="innerHTML"
            hx-include="#search-input,#filter-program,#filter-topic,#sort-hidden">
        </button>
    </div>
</div>

@include('partials.tour', [
    'tourKey' => 'home',
    'steps'   => [
        [
            'target'   => '#search-input',
            'title'    => 'Kotak Pencarian',
            'desc'     => 'Ketik nama dosen, topik spesifik, atau judul skripsi yang pernah dibimbing. Pencarian berjalan otomatis saat kamu mengetik — tidak perlu tekan Enter.',
            'optional' => false,
        ],
        [
            'target'   => '#filter-program',
            'title'    => 'Filter Program Studi',
            'desc'     => 'Pilih program studimu agar dosen yang ditampilkan hanya yang relevan dengan jurusanmu.',
            'optional' => false,
        ],
        [
            'target'   => '#filter-topic',
            'title'    => 'Filter Topik',
            'desc'     => 'Saring lebih lanjut berdasarkan topik skripsi. Kombinasikan dengan filter program untuk hasil yang lebih spesifik.',
            'optional' => false,
        ],
        [
            'target'   => '#sort-chips',
            'title'    => 'Pengurutan',
            'desc'     => 'Klik chip untuk mengurutkan. Klik pertama untuk urutan terbanyak, klik lagi untuk tersedikit, klik ketiga untuk menghapus. Bisa pilih beberapa sekaligus untuk urutan bertingkat.',
            'optional' => false,
        ],
        [
            'target'   => '#results a',
            'title'    => 'Kartu Dosen',
            'desc'     => 'Klik kartu dosen untuk melihat profil lengkap: topik spesifik, judul skripsi yang pernah dibimbing, dan tombol untuk mengirim email permohonan.',
            'optional' => false,
        ],
        [
            'target'   => '#nim-form',
            'title'    => 'Simpan NIM',
            'desc'     => 'Isi NIM kamu di sini agar data aktivitas (dilihat, dihubungi) bisa dikenali sebagai milikmu, bukan sekadar cookie anonim. Ini opsional — kamu tetap bisa menggunakan semua fitur tanpa mengisinya.',
            'optional' => false,
        ],
    ],
])

{{-- Results --}}
<div id="results">
    @include('partials.supervisor-cards', ['supervisors' => $supervisors])
</div>

<script>
function _spvCurrentState() {
    const sorts = document.getElementById('sort-hidden')?.value || '';
    return {
        q:       document.getElementById('search-input')?.value   || '',
        program: document.getElementById('filter-program')?.value || '',
        topic:   document.getElementById('filter-topic')?.value   || '',
        sorts:   sorts === 'alpha:asc' ? '' : sorts,
    };
}

document.addEventListener('click', function(e) {
    // Save state when navigating to a supervisor profile
    if (e.target.closest('#results a[href*="/supervisor/"]')) {
        sessionStorage.setItem('spv_back_state', JSON.stringify(_spvCurrentState()));
        return;
    }

    // Intercept pagination links to keep filter state
    const link = e.target.closest('#results a[href*="page="]');
    if (!link) return;
    e.preventDefault();
    const url = new URL(link.href);
    const s = _spvCurrentState();
    if (s.q)       url.searchParams.set('q',       s.q);
    if (s.program) url.searchParams.set('program', s.program);
    if (s.topic)   url.searchParams.set('topic',   s.topic);
    if (s.sorts)   url.searchParams.set('sorts',   s.sorts);
    htmx.ajax('GET', url.toString(), { target: '#results', swap: 'innerHTML' });
});
</script>
@endsection
