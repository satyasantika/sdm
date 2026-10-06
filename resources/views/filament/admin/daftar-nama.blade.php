<div class="text-sm">
    @forelse ($nama as $n)
        <div class="border-b border-gray-100 py-1 dark:border-white/10">{{ $n }}</div>
    @empty
        <p class="text-gray-500">Semua dosen tetap sudah memiliki data BKD.</p>
    @endforelse
</div>
