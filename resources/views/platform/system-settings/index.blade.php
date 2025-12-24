<x-app-layout>
    @section('title', 'System Settings')
    @section('page-title', 'System Settings')

    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Settings</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configure system-wide settings for your PBX
            </p>
        </div>
    </x-slot>

    <form action="{{ route('system-settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- AMI Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Asterisk Manager Interface (AMI)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Connection settings for AMI</p>
                </div>
                <a href="{{ route('system-settings.test-ami') }}" class="btn-secondary text-sm">Test Connection</a>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="ami_host" class="form-label">Host</label>
                        <input type="text" name="ami_host" id="ami_host" 
                               value="{{ old('ami_host', $settings['ami_host'] ?? '127.0.0.1') }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="ami_port" class="form-label">Port</label>
                        <input type="number" name="ami_port" id="ami_port" 
                               value="{{ old('ami_port', $settings['ami_port'] ?? 5038) }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="ami_username" class="form-label">Username</label>
                        <input type="text" name="ami_username" id="ami_username" 
                               value="{{ old('ami_username', $settings['ami_username'] ?? '') }}" 
                               class="form-input">
                    </div>
                    <div class="md:col-span-3">
                        <label for="ami_secret" class="form-label">Secret</label>
                        <input type="password" name="ami_secret" id="ami_secret" 
                               value="{{ old('ami_secret', $settings['ami_secret'] ?? '') }}" 
                               class="form-input" placeholder="Leave blank to keep current">
                    </div>
                </div>
            </div>
        </div>

        <!-- ARI Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Asterisk REST Interface (ARI)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Connection settings for ARI</p>
                </div>
                <a href="{{ route('system-settings.test-ari') }}" class="btn-secondary text-sm">Test Connection</a>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <label for="ari_url" class="form-label">ARI URL</label>
                        <input type="url" name="ari_url" id="ari_url" 
                               value="{{ old('ari_url', $settings['ari_url'] ?? 'http://127.0.0.1:8088/ari') }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="ari_app" class="form-label">Application Name</label>
                        <input type="text" name="ari_app" id="ari_app" 
                               value="{{ old('ari_app', $settings['ari_app'] ?? 'minipbx') }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="ari_username" class="form-label">Username</label>
                        <input type="text" name="ari_username" id="ari_username" 
                               value="{{ old('ari_username', $settings['ari_username'] ?? '') }}" 
                               class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label for="ari_password" class="form-label">Password</label>
                        <input type="password" name="ari_password" id="ari_password" 
                               class="form-input" placeholder="Leave blank to keep current">
                    </div>
                </div>
            </div>
        </div>

        <!-- WebRTC / STUN/TURN Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">WebRTC Settings</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Configure STUN/TURN servers for WebRTC</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stun_server" class="form-label">STUN Server</label>
                        <input type="text" name="stun_server" id="stun_server" 
                               value="{{ old('stun_server', $settings['stun_server'] ?? 'stun:stun.l.google.com:19302') }}" 
                               class="form-input" placeholder="stun:server:port">
                    </div>
                    <div>
                        <label for="turn_server" class="form-label">TURN Server</label>
                        <input type="text" name="turn_server" id="turn_server" 
                               value="{{ old('turn_server', $settings['turn_server'] ?? '') }}" 
                               class="form-input" placeholder="turn:server:port">
                    </div>
                    <div>
                        <label for="turn_username" class="form-label">TURN Username</label>
                        <input type="text" name="turn_username" id="turn_username" 
                               value="{{ old('turn_username', $settings['turn_username'] ?? '') }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="turn_credential" class="form-label">TURN Credential</label>
                        <input type="password" name="turn_credential" id="turn_credential" 
                               class="form-input" placeholder="Leave blank to keep current">
                    </div>
                </div>
            </div>
        </div>

        <!-- General Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">General Settings</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="timezone" class="form-label">Timezone</label>
                        <select name="timezone" id="timezone" class="form-select">
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ old('timezone', $settings['timezone'] ?? 'UTC') === $tz ? 'selected' : '' }}>
                                    {{ $tz }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                        <input type="number" name="session_timeout" id="session_timeout" 
                               value="{{ old('session_timeout', $settings['session_timeout'] ?? 120) }}" 
                               class="form-input" min="5">
                    </div>
                    <div>
                        <label for="data_retention_days" class="form-label">Data Retention (days)</label>
                        <input type="number" name="data_retention_days" id="data_retention_days" 
                               value="{{ old('data_retention_days', $settings['data_retention_days'] ?? 365) }}" 
                               class="form-input" min="30">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Call logs older than this will be archived</p>
                    </div>
                    <div>
                        <label for="recording_retention_days" class="form-label">Recording Retention (days)</label>
                        <input type="number" name="recording_retention_days" id="recording_retention_days" 
                               value="{{ old('recording_retention_days', $settings['recording_retention_days'] ?? 90) }}" 
                               class="form-input" min="7">
                    </div>
                </div>
            </div>
        </div>

        <!-- SMTP Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Email Settings (SMTP)</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" id="smtp_host" 
                               value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="smtp_port" class="form-label">Port</label>
                        <input type="number" name="smtp_port" id="smtp_port" 
                               value="{{ old('smtp_port', $settings['smtp_port'] ?? 587) }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="smtp_username" class="form-label">Username</label>
                        <input type="text" name="smtp_username" id="smtp_username" 
                               value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="smtp_password" class="form-label">Password</label>
                        <input type="password" name="smtp_password" id="smtp_password" 
                               class="form-input" placeholder="Leave blank to keep current">
                    </div>
                    <div>
                        <label for="smtp_encryption" class="form-label">Encryption</label>
                        <select name="smtp_encryption" id="smtp_encryption" class="form-select">
                            <option value="" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? '') === '' ? 'selected' : '' }}>None</option>
                            <option value="tls" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="mail_from_address" class="form-label">From Address</label>
                        <input type="email" name="mail_from_address" id="mail_from_address" 
                               value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}" 
                               class="form-input">
                    </div>
                    <div>
                        <label for="mail_from_name" class="form-label">From Name</label>
                        <input type="text" name="mail_from_name" id="mail_from_name" 
                               value="{{ old('mail_from_name', $settings['mail_from_name'] ?? 'Mini PBX') }}" 
                               class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Settings
            </button>
        </div>
    </form>
</x-app-layout>

