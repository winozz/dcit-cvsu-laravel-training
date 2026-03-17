@push('head')
<style>
    h1 { margin:0 0 16px; }
    .form-card { max-width:420px; }
    label { display:block; margin:10px 0 6px; color:#ffd166; font-weight:700; }
    input, textarea { width:100%; padding:10px; border-radius:6px; border:1px solid #2f3a66; background:#0f1735; color:#ecf4ff; }
    textarea { min-height:100px; resize:vertical; }
    .primary-btn { margin-top:14px; padding:10px 14px; background:#2a6df5; color:#fff; border:2px solid #fff; border-radius:6px; box-shadow:0 4px 0 #000; font-weight:700; cursor:pointer; }
    .primary-btn:active { transform:translateY(1px); box-shadow:0 2px 0 #000; }
    a.link { color:#62d0ff; text-decoration:none; }
</style>
@endpush

<x-app title="Create Game">
    <h1>Create Game</h1>
    <x-flash />
    @if ($errors->any())
        <div style="margin:8px 0 16px;padding:10px 12px;border:2px solid #ff5f6d;color:#ff5f6d;background:#1a0f14;border-radius:8px;">
            <strong>Fix the errors below:</strong>
            <ul style="margin:6px 0 0 16px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form class="form-card" method="POST" action="{{ empty($guestMode) ? route('games.store') : route('guest.games.store') }}">
        @csrf
        <label for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Game name" required value="{{ old('name') }}">
        @error('name')
            <div style="color:#ff5f6d;font-size:12px;margin-top:4px;">{{ $message }}</div>
        @enderror

        <label for="slug">Slug (optional, used in URL)</label>
        <input type="text" id="slug" name="slug" placeholder="auto-generated if blank" aria-describedby="slug-help" value="{{ old('slug') }}">
        <small id="slug-help" style="color:#c7d6ff;display:block;margin-bottom:6px;">Leave empty to generate from the name.</small>
        @error('slug')
            <div style="color:#ff5f6d;font-size:12px;margin-top:4px;">{{ $message }}</div>
        @enderror

        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Brief description">{{ old('description') }}</textarea>
        @error('description')
            <div style="color:#ff5f6d;font-size:12px;margin-top:4px;">{{ $message }}</div>
        @enderror

        <button type="submit" class="primary-btn">Save</button>
    </form>
    <p style="margin-top:12px;"><a class="link" href="{{ empty($guestMode) ? route('games.index') : route('guest.games') }}">Back to list</a></p>
</x-app>
