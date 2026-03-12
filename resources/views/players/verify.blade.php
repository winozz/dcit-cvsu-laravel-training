<x-app title="Verify Email">
    <div class="surface max-w-md mx-auto space-y-4">
        <h2 class="text-xl font-bold">Verify Your Email</h2>
        <x-flash />

        @if($errors->any())
            <div class="p-3 bg-red-900/30 border border-red-700 rounded text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <p class="text-sm text-white/70">
            We sent a 6-digit OTP to <strong>{{ $player->email }}</strong>. The code expires in {{ $ttlMinutes }} minutes.
        </p>

        <form method="POST" action="{{ route('players.verification.verify') }}" class="space-y-3">
            @csrf
            <label class="block text-sm font-semibold">Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email', $player->email) }}"
                readonly
                class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2 opacity-80"
            />

            <label class="block text-sm font-semibold">OTP Code</label>
            <input
                type="text"
                name="otp"
                value="{{ old('otp') }}"
                required
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                class="w-full rounded bg-[#0f142a] border border-[#2f3a66] p-2 tracking-[0.35em]"
                placeholder="123456"
            />

            <button class="btn green w-full" type="submit">Verify and Enter Lobby</button>
        </form>

        <form method="POST" action="{{ route('players.verification.resend') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="email" value="{{ $player->email }}" />
            <button class="btn secondary w-full" type="submit">Resend OTP</button>
        </form>

        <p class="text-sm text-white/70">Need another account? <a class="text-[var(--accent)]" href="{{ route('players.register.form') }}">Register again</a></p>
    </div>
</x-app>
