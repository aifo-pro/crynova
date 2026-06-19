{{-- Crynova payment gateway settings (Flute admin panel). --}}
<div class="form-group">
    <label class="form-label" for="crynova-api-key">API Key</label>
    <input type="text" id="crynova-api-key" name="apiKey" class="form-control"
           value="{{ $settings['apiKey'] ?? '' }}" placeholder="sk_live_...">
    <small class="form-text text-muted">Crynova: Project → Integration → API keys.</small>
</div>

<div class="form-group">
    <label class="form-label" for="crynova-webhook-secret">Webhook Secret</label>
    <input type="text" id="crynova-webhook-secret" name="webhookSecret" class="form-control"
           value="{{ $settings['webhookSecret'] ?? '' }}">
    <small class="form-text text-muted">Project webhook secret — used to verify notifications.</small>
</div>

<div class="form-group">
    <label class="form-label" for="crynova-api-base">API Base URL</label>
    <input type="text" id="crynova-api-base" name="apiBase" class="form-control"
           value="{{ $settings['apiBase'] ?? 'https://crynova.io/api/v1' }}">
    <small class="form-text text-muted">Change only for a self-hosted Crynova instance.</small>
</div>

<div class="form-group form-check">
    <input type="checkbox" id="crynova-test-mode" name="testMode" value="1" class="form-check-input"
           {{ !empty($settings['testMode']) ? 'checked' : '' }}>
    <label class="form-check-label" for="crynova-test-mode">Test mode</label>
</div>
