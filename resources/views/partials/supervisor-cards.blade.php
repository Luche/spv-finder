@if($supervisors->isEmpty())
    <div class="text-center text-gray-400 py-16">
        <div class="text-5xl mb-3">🔍</div>
        <p>Tidak ada pembimbing yang ditemukan.</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @foreach($supervisors as $supervisor)
        <a href="{{ route('supervisor.show', $supervisor->kddsn) }}"
           class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all block">

            <div class="flex items-start justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <h2 class="font-semibold text-gray-800 text-sm leading-snug truncate">{{ $supervisor->name }}</h2>
                    <div class="text-xs text-gray-400 mt-0.5 truncate">
                        {{ $supervisor->email }}
                    </div>
                </div>
                @if($supervisor->active_titles > 0)
                    <span class="ml-2 text-xs bg-amber-100 text-amber-700 rounded-full px-2 py-0.5 whitespace-nowrap">
                        {{ $supervisor->active_titles }} aktif
                    </span>
                @endif
            </div>

            {{-- Programs --}}
            <div class="flex flex-wrap gap-1 mb-2">
                @foreach($supervisor->programs->take(2) as $prog)
                    <span class="text-xs bg-brand-50 text-brand-700 rounded-full px-2 py-0.5">
                        {{ Str::limit($prog->name, 20) }}
                    </span>
                @endforeach
                @if($supervisor->programs->count() > 2)
                    <span class="text-xs text-gray-400">+{{ $supervisor->programs->count() - 2 }}</span>
                @endif
            </div>

            {{-- Topics --}}
            <div class="flex flex-wrap gap-1 mb-3">
                @foreach($supervisor->topics->take(3) as $topic)
                    <span class="text-xs bg-gray-100 text-gray-600 rounded-full px-2 py-0.5">
                        {{ $topic->name }}
                    </span>
                @endforeach
                @if($supervisor->topics->count() > 3)
                    <span class="text-xs text-gray-400">+{{ $supervisor->topics->count() - 3 }}</span>
                @endif
            </div>

            {{-- Stats --}}
            <div class="flex gap-3 text-xs text-gray-400 border-t pt-2 mt-auto">
                <span title="Dilihat 30 hari terakhir">👁 {{ number_format($supervisor->views_30) }} dilihat</span>
                <span title="Dihubungi 30 hari terakhir">✉️ {{ number_format($supervisor->contacts_30) }} dihubungi</span>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="text-sm text-gray-500">
        {{ $supervisors->links() }}
    </div>
@endif
