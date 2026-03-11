<x-app title="Join Word Quest Lobby">
    <div class="surface max-w-md mx-auto space-y-4">
        <h2 class="text-xl font-bold">Enter Username</h2>
        @if($errors->any())
            <div class="p-3 bg-red-900/30 border border-red-700 rounded text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('players.login') }}" class="space-y-3">
            @csrf
            <label class="block text-sm font-semibold">Username</label>
            <input type="text" name="username" required minlength="3" maxlength="32" class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2" placeholder="e.g. wordsmith" />
            <button class="btn green w-full" type="submit">Enter Lobby</button>
        </form>
    </div>
</x-app>
