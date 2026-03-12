<x-app title="Join Word Quest Lobby">
    <div class="surface max-w-md mx-auto space-y-4">
        <h2 class="text-xl font-bold">Sign In</h2>
        <x-flash />
        @if($errors->any())
            <div class="p-3 bg-red-900/30 border border-red-700 rounded text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('players.login') }}" class="space-y-3">
            @csrf
            <label class="block text-sm font-semibold">Username</label>
            <input type="text" name="username" required minlength="3" maxlength="32" class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2" placeholder="e.g. wordsmith" />

            <label class="block text-sm font-semibold">Password</label>
            <input type="password" name="password" required minlength="8" class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2" />

            <button class="btn green w-full" type="submit">Enter Lobby</button>
        </form>
        <p class="text-sm text-white/70">New here? <a class="text-[var(--accent)]" href="{{ route('players.register.form') }}">Create an account</a></p>
        <p class="text-sm text-white/70">Just want to try it? <a class="text-[var(--accent)]" href="{{ route('guest.games') }}">Continue as guest</a></p>
    </div>
</x-app>
