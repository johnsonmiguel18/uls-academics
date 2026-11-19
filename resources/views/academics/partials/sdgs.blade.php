{{-- Small SDG badges in each goal's color (Admin → Lists and categories → SDGs): @include('academics.partials.sdgs', ['sdgs' => [4, 9]]) --}}
@if (! empty($sdgs))
    @php($goals = \App\Support\Sdg::goals())
    <ul class="ac-sdgs" aria-label="{{ \App\Support\Site::text('a11y.sdgs') }}">
        @foreach (collect($sdgs)->map(fn ($n) => (int) $n)->unique()->sort() as $n)
            @isset($goals[$n])
                <li style="--sdg: {{ $goals[$n][1] }}; --sdg-ink: {{ $goals[$n][2] }}" title="{{ $goals[$n][0] }}">SDG {{ $n }}<span class="sr-only">: {{ $goals[$n][0] }}</span></li>
            @endisset
        @endforeach
    </ul>
@endif
