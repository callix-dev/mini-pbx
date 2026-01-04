<x-app-layout>
    @section('title', 'SIP Log Details')
    @section('page-title', 'SIP Log Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('sip-logs.index') }}" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">SIP Log Details</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Event ID: #{{ $sipLog->id }}
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @php
                    $statusColors = [
                        'ALLOWED' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'REJECTED' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        'FAILED' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                        'UNKNOWN' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
                    ];
                @endphp
                <span class="px-4 py-2 rounded-full text-sm font-bold {{ $statusColors[$sipLog->status] ?? $statusColors['UNKNOWN'] }}">
                    {{ $sipLog->status }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Event Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Event Information
                    </h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Time</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $sipLog->event_time->format('F d, Y H:i:s') }}
                                <span class="text-gray-500">({{ $sipLog->event_time->diffForHumans() }})</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ $sipLog->event_type ?? 'INVITE' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Direction</dt>
                            <dd class="mt-1">
                                @if($sipLog->direction === 'inbound')
                                    <span class="inline-flex items-center text-green-600 dark:text-green-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                        </svg>
                                        Inbound
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-blue-600 dark:text-blue-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                        </svg>
                                        Outbound
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Endpoint</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $sipLog->endpoint ?: '-' }}
                            </dd>
                        </div>
                        @if($sipLog->call_id)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Call ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all bg-gray-50 dark:bg-gray-900 px-3 py-2 rounded">
                                {{ $sipLog->call_id }}
                            </dd>
                        </div>
                        @endif
                        @if($sipLog->uniqueid)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unique ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $sipLog->uniqueid }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Network Details Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        Network Details
                    </h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <!-- Source -->
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-2">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Source</p>
                            <a href="{{ route('sip-logs.index', ['source_ip' => $sipLog->source_ip]) }}" 
                               class="font-mono text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                {{ $sipLog->source_ip }}
                            </a>
                            @if($sipLog->source_port)
                                <p class="text-xs text-gray-500">Port: {{ $sipLog->source_port }}</p>
                            @endif
                        </div>

                        <!-- Arrow -->
                        <div class="flex-1 px-4">
                            <div class="border-t-2 border-dashed border-gray-300 dark:border-gray-600 relative">
                                <svg class="w-6 h-6 text-gray-400 absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-1/2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M10 17l5-5-5-5v10z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Destination -->
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-2">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Destination</p>
                            <p class="font-mono text-sm text-gray-900 dark:text-white">
                                {{ $sipLog->destination_ip ?: 'Local' }}
                            </p>
                            @if($sipLog->destination_port)
                                <p class="text-xs text-gray-500">Port: {{ $sipLog->destination_port }}</p>
                            @endif
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        @if($sipLog->from_uri)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">From URI</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all bg-gray-50 dark:bg-gray-900 px-3 py-2 rounded">
                                {{ $sipLog->from_uri }}
                            </dd>
                        </div>
                        @endif
                        @if($sipLog->to_uri)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">To URI</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all bg-gray-50 dark:bg-gray-900 px-3 py-2 rounded">
                                {{ $sipLog->to_uri }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Call Details Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Call Details
                    </h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Caller ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $sipLog->caller_id ?: '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Caller Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $sipLog->caller_name ?: '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Called Number (DID)</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $sipLog->callee_id ?: '-' }}
                            </dd>
                        </div>
                        @if($sipLog->carrier)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Carrier</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $sipLog->carrier->name }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Metadata Card (if exists) -->
            @if($sipLog->metadata && count($sipLog->metadata) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                        Additional Metadata
                    </h3>
                </div>
                <div class="p-6">
                    <pre class="text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">{{ json_encode($sipLog->metadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-{{ $sipLog->status === 'REJECTED' ? 'red' : 'green' }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($sipLog->status === 'REJECTED')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                        Status Details
                    </h3>
                </div>
                <div class="p-6">
                    <div class="text-center mb-4">
                        <div class="w-20 h-20 mx-auto rounded-full flex items-center justify-center {{ $sipLog->status === 'REJECTED' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30' }}">
                            @if($sipLog->status === 'REJECTED')
                                <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @else
                                <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </div>
                        <p class="mt-2 text-lg font-bold {{ $sipLog->status === 'REJECTED' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $sipLog->status }}
                        </p>
                    </div>

                    @if($sipLog->reject_reason)
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-xs font-medium text-red-600 dark:text-red-400 uppercase mb-1">Rejection Reason</p>
                        <p class="text-sm text-red-800 dark:text-red-200">{{ $sipLog->reject_reason }}</p>
                    </div>
                    @endif

                    @if($sipLog->sip_response_code)
                    <div class="mt-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">SIP Response Code</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sipLog->sip_response_code }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @php
                                $sipCodes = [
                                    200 => 'OK',
                                    400 => 'Bad Request',
                                    401 => 'Unauthorized',
                                    403 => 'Forbidden',
                                    404 => 'Not Found',
                                    408 => 'Request Timeout',
                                    480 => 'Temporarily Unavailable',
                                    486 => 'Busy Here',
                                    487 => 'Request Terminated',
                                    488 => 'Not Acceptable Here',
                                    500 => 'Server Internal Error',
                                    503 => 'Service Unavailable',
                                    603 => 'Decline',
                                ];
                            @endphp
                            {{ $sipCodes[$sipLog->sip_response_code] ?? 'Unknown' }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('sip-logs.index', ['source_ip' => $sipLog->source_ip]) }}" 
                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        View all from this IP
                    </a>
                    @if($sipLog->callee_id)
                    <a href="{{ route('sip-logs.index', ['search' => $sipLog->callee_id]) }}" 
                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        View all to this DID
                    </a>
                    @endif
                    <a href="{{ route('sip-logs.index', ['status' => 'REJECTED']) }}" 
                       class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        View all rejected
                    </a>
                </div>
            </div>

            <!-- Timeline (same source IP, recent) -->
            @php
                $relatedLogs = \App\Models\SipSecurityLog::where('source_ip', $sipLog->source_ip)
                    ->where('id', '!=', $sipLog->id)
                    ->latest('event_time')
                    ->limit(5)
                    ->get();
            @endphp
            @if($relatedLogs->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent from Same IP</h3>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        @foreach($relatedLogs as $related)
                        <a href="{{ route('sip-logs.show', $related) }}" 
                           class="block p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $related->status === 'REJECTED' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                                    {{ $related->status }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $related->event_time->diffForHumans() }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white truncate">
                                → {{ $related->callee_id ?: 'Unknown' }}
                            </p>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

