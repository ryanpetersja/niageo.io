<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Code Reviews']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Code Reviews</h2>
        <p class="mt-1 text-sm text-muted">AI reviews of pull requests, started from a client's page.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="card p-4 sm:p-6">
                @if($reviews->isEmpty())
                    <p class="text-sm text-muted">No reviews yet. Open a client with a linked repository and choose “Review PRs”.</p>
                @else
                    <ul class="divide-hair">
                        @foreach($reviews as $review)
                            @php $counts = array_count_values(array_column($review->sections['pull_requests'] ?? [], 'recommendation')); @endphp
                            <li class="py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('code-reviews.show', $review) }}" class="accent-ink hover:underline font-medium break-words">{{ $review->title }}</a>
                                    <div class="text-xs text-faint">{{ $review->client->company_name }} · {{ $review->repository->full_name }} · {{ $review->created_at->format('M d, Y') }}</div>
                                </div>
                                <div class="flex gap-1.5">
                                    @if(! empty($counts['merge']))<span class="badge badge-good">{{ $counts['merge'] }} merge</span>@endif
                                    @if(! empty($counts['merge_with_caution']))<span class="badge badge-warn">{{ $counts['merge_with_caution'] }} caution</span>@endif
                                    @if(! empty($counts['hold']))<span class="badge badge-danger">{{ $counts['hold'] }} hold</span>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">{{ $reviews->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
