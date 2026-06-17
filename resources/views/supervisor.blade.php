@extends('layout')

@section('title', $supervisor->name . ' — Profil Pembimbing')

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Back --}}
    <a href="{{ route('home') }}" class="text-sm text-brand-600 hover:underline mb-4 inline-block">← Kembali ke daftar</a>

    {{-- Header card --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $supervisor->name }}</h1>
                <a href="mailto:{{ $supervisor->email }}" class="text-brand-600 text-sm hover:underline">
                    {{ $supervisor->email }}
                </a>
                @if($supervisor->scholar_url)
                    <br>
                    <a href="{{ $supervisor->scholar_url }}" target="_blank"
                       class="text-sm text-gray-500 hover:text-brand-600 inline-flex items-center gap-1 mt-1">
                        🎓 Google Scholar ↗
                    </a>
                @endif
            </div>
            @if($supervisor->is_global_class)
                <span class="text-xs bg-indigo-100 text-indigo-700 rounded-full px-3 py-1 whitespace-nowrap">Global Class</span>
            @endif
        </div>

        {{-- Stats row --}}
        <div id="stats-row" class="flex flex-wrap gap-4 mt-5 text-sm">
            <div class="bg-blue-50 rounded-xl px-4 py-3 text-center min-w-[100px]">
                <div class="text-2xl font-bold text-blue-600">{{ $views30 }}</div>
                <div class="text-xs text-gray-500 mt-0.5">dilihat (30 hari)</div>
            </div>
            <div class="bg-green-50 rounded-xl px-4 py-3 text-center min-w-[100px]">
                <div class="text-2xl font-bold text-green-600">{{ $contacts30 }}</div>
                <div class="text-xs text-gray-500 mt-0.5">dihubungi (30 hari)</div>
            </div>
            @if($supervisor->active_titles > 0)
            <div class="bg-amber-50 rounded-xl px-4 py-3 text-center min-w-[100px]">
                <div class="text-2xl font-bold text-amber-600">{{ $supervisor->active_titles }}</div>
                <div class="text-xs text-gray-500 mt-0.5">judul aktif</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Programs & Topics --}}
    <div id="programs-topics" class="bg-white rounded-2xl shadow-sm p-5 mb-4">
        <h2 class="font-semibold text-gray-700 mb-2">Program</h2>
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($supervisor->programs as $prog)
                <span class="text-sm bg-brand-50 text-brand-700 rounded-full px-3 py-1">{{ $prog->name }}</span>
            @endforeach
        </div>

        <h2 class="font-semibold text-gray-700 mb-2">Topik Skripsi</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($supervisor->topics as $topic)
                <span class="text-sm bg-gray-100 text-gray-700 rounded-full px-3 py-1">{{ $topic->name }}</span>
            @endforeach
        </div>

        @if($supervisor->specific_topics)
        <div id="specific-topics" class="mt-4">
            <h2 class="font-semibold text-gray-700 mb-2">Topik Spesifik</h2>
            <div class="flex flex-wrap gap-2">
                @foreach(array_filter(array_map('trim', explode(';', $supervisor->specific_topics))) as $st)
                    <span class="text-sm bg-yellow-100 text-yellow-800 rounded-full px-3 py-1">{{ $st }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Last 5 theses --}}
    @if($supervisor->theses->isNotEmpty())
    <div id="thesis-list" class="bg-white rounded-2xl shadow-sm p-5 mb-4">
        <h2 class="font-semibold text-gray-700 mb-3">5 Judul Skripsi Terakhir yang Dibimbing</h2>
        <ol class="list-decimal list-inside space-y-2">
            @foreach($supervisor->theses as $thesis)
                <li class="text-sm text-gray-700">{{ $thesis->title }}</li>
            @endforeach
        </ol>
    </div>
    @endif

    {{-- Email button --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-4 text-center">
        <p class="text-sm text-gray-500 mb-4">
            Tertarik dengan pembimbing ini? Klik tombol di bawah untuk mengirim email permohonan bimbingan.
            Template sudah disiapkan — kamu hanya perlu mengisi bagian yang ada tanda <em>[kurung siku]</em>.
        </p>
        <button
            id="email-btn"
            class="bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl px-8 py-3 transition-colors"
            onclick="sendContact()"
        >
            ✉️ Kirim Email Permohonan
        </button>
    </div>

</div>

@include('partials.tour', [
    'tourKey' => 'supervisor',
    'steps'   => [
        [
            'target'   => '#stats-row',
            'title'    => 'Statistik Dosen',
            'desc'     => 'Lihat berapa kali profil ini dilihat dan berapa mahasiswa yang sudah menghubungi dosen ini dalam 30 hari terakhir. Judul aktif menunjukkan bimbingan yang sedang berjalan.',
            'optional' => false,
        ],
        [
            'target'   => '#programs-topics',
            'title'    => 'Program dan Topik',
            'desc'     => 'Program studi dan topik skripsi resmi yang dapat dibimbing dosen ini, sesuai data dari jurusan.',
            'optional' => false,
        ],
        [
            'target'   => '#specific-topics',
            'title'    => 'Topik Spesifik',
            'desc'     => 'Topik-topik yang lebih detail dan spesifik dari dosen ini. Bisa jadi acuan saat kamu mengajukan ide skripsi.',
            'optional' => true,
        ],
        [
            'target'   => '#thesis-list',
            'title'    => 'Judul Skripsi Terdahulu',
            'desc'     => 'Referensi judul skripsi yang pernah dibimbing dosen ini. Perhatikan gaya dan topiknya sebagai inspirasi untuk proposalmu.',
            'optional' => true,
        ],
        [
            'target'   => '#email-btn',
            'title'    => 'Kirim Email Permohonan',
            'desc'     => 'Klik tombol ini untuk membuka aplikasi emailmu dengan template permohonan yang sudah disiapkan. Cukup isi bagian dalam [kurung siku] sesuai datamu.',
            'optional' => false,
        ],
    ],
])

<script>
function sendContact() {
    fetch('{{ route('supervisor.contact', $supervisor->kddsn) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        window.location.href = data.mailto;
        // Optimistically update button label
        document.getElementById('email-btn').textContent = '✅ Email dibuka!';
    })
    .catch(() => {
        // Fallback: direct mailto without recording
        window.location.href = 'mailto:{{ $supervisor->email }}';
    });
}
</script>
@endsection
