{{-- Admin brand: the same seal + traced wordmark as the public website header. --}}
<span class="uls-admin-brand">
    <img src="{{ \App\Support\Site::logo() }}" alt="" class="uls-admin-brand-seal">
    @include('partials.wordmark', ['class' => 'uls-admin-brand-wordmark'])
    @if (\App\Support\Site::text('admin.badge'))<span class="uls-admin-brand-label">{{ \App\Support\Site::text('admin.badge') }}</span>@endif
</span>
