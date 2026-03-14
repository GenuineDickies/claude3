<section>
    <header>
        <h2 class="text-lg font-medium text-white">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-300">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-400 hover:text-white rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" :value="$user->requiresMobilePhone() ? __('Mobile Phone *') : __('Mobile Phone')" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->getAttributes()['phone'] ?? null)" autocomplete="tel" placeholder="5551234567" :required="$user->requiresMobilePhone()" />
            @if ($user->requiresMobilePhone())
                <p class="mt-2 text-sm text-gray-400">{{ __('Required for your current role.') }}</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        @if ($user->requiresSmsConsent())
            <div>
                <x-input-label for="grant_sms_consent" :value="__('SMS Consent')" />

                @if ($user->hasSmsConsent())
                    <p class="mt-2 text-sm text-gray-400">{{ __('SMS consent is already on file for this account.') }}</p>
                @else
                    <label for="grant_sms_consent" class="mt-2 flex items-start gap-3 rounded-lg border border-white/10 bg-white/5 p-4 text-sm text-gray-300">
                        <input id="grant_sms_consent" name="grant_sms_consent" type="checkbox" value="1" class="mt-1 rounded border-white/10 text-cyan-400 focus:ring-cyan-500" @checked(old('grant_sms_consent'))>
                        <span>{{ __('I agree to receive SMS messages related to my role. Reply STOP to opt out and HELP for help.') }}</span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('grant_sms_consent')" />
                @endif
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
