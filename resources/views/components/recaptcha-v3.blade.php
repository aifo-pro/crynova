@props(['action' => 'submit'])

@php
    $enabled = (bool) \App\Models\Setting::get('recaptcha_enabled', false);
    $siteKey = trim((string) \App\Models\Setting::get('recaptcha_site_key', ''));
    $inputId = 'recaptcha-token-'.uniqid();
@endphp

@if($enabled && $siteKey !== '')
    <input type="hidden" name="recaptcha_token" id="{{ $inputId }}">

    @once
        <script src="https://www.google.com/recaptcha/api.js?render={{ urlencode($siteKey) }}" async defer></script>
    @endonce

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById(@json($inputId));
            if (!input) return;

            const form = input.closest('form');
            const fillToken = function (done) {
                if (!window.grecaptcha) {
                    if (done) done(false);
                    return;
                }

                window.grecaptcha.ready(function () {
                    window.grecaptcha.execute(@json($siteKey), { action: @json($action) }).then(function (token) {
                        input.value = token;
                        if (done) done(true);
                    });
                });
            };

            fillToken();

            if (form) {
                form.addEventListener('submit', function (event) {
                    if (input.value) return;

                    event.preventDefault();
                    fillToken(function (ready) {
                        if (!ready) {
                            form.submit();
                            return;
                        }

                        if (form.requestSubmit) {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    });
                });
            }
        });
    </script>
@endif
