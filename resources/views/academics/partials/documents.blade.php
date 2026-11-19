{{-- Downloadable documents. --}}
<ul class="ac-docs">
    @foreach ($documents as $document)
        <li>
            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file) }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M12 12v6M9 15l3 3 3-3"/></svg>
                <span><strong>{{ $document->title }}</strong>
                <small>{{ \App\Models\Term::label('document_category', $document->category) }} · {{ strtoupper(pathinfo($document->file, PATHINFO_EXTENSION)) }}</small></span>
            </a>
        </li>
    @endforeach
</ul>
