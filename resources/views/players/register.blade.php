<x-app title="Create Account">
    <div class="surface max-w-md mx-auto space-y-4">
        <h2 class="text-xl font-bold">Create Your Player</h2>
        @if($errors->any())
            <div class="p-3 bg-red-900/30 border border-red-700 rounded text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('players.register') }}" class="space-y-3">
            @csrf
            <label class="block text-sm font-semibold">Username</label>
            <input type="text" name="username" required minlength="3" maxlength="32" pattern="[A-Za-z0-9]+" class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2" placeholder="e.g. wordsmith" />

            <label class="block text-sm font-semibold">Password</label>
            <input type="password" name="password" required minlength="8" class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2" />

            <label class="block text-sm font-semibold">Confirm Password</label>
            <input type="password" name="password_confirmation" required minlength="8" class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2" />

            <button class="btn green w-full" type="submit">Create Account</button>
        </form>
        <p class="text-sm text-white/70">Already registered? <a class="text-[var(--accent)]" href="{{ route('players.login.form') }}">Sign in</a></p>
    </div>
</x-app>
