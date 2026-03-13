{{-- Correspondence Log --}}
<div class="surface-1 p-6">
    <div class="flex justify-between items-center mb-3">
        <h2 class="text-lg font-semibold text-gray-300">Correspondence Log</h2>
        @if ($serviceRequest->customer)
            <button type="button"
                    @click="showCorrespondenceForm = !showCorrespondenceForm"
                    class="text-sm text-cyan-400 hover:text-cyan-300 font-medium">
                + Log Entry
            </button>
        @endif
    </div>

    {{-- Manual entry form (hidden by default) --}}
    @if ($serviceRequest->customer)
    <div x-show="showCorrespondenceForm" x-cloak class="mb-4 border border-white/10 rounded-lg p-4 bg-white/5">
        <form action="{{ route('service-requests.correspondence.store', $serviceRequest) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Channel</label>
                    <select name="channel" required
                            class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal">
                        <option value="phone">Phone Call</option>
                        <option value="email">Email</option>
                        <option value="in_person">In Person</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Direction</label>
                    <select name="direction" required
                            class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal">
                        <option value="outbound">Outbound (we contacted)</option>
                        <option value="inbound">Inbound (they contacted)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Subject</label>
                    <input type="text" name="subject" maxlength="255" placeholder="Brief topic…"
                           class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" min="0" max="9999" placeholder="0"
                           class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal">
                </div>
            </div>

            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-400 mb-1">Notes</label>
                <textarea name="body" rows="2" maxlength="5000" placeholder="Describe the interaction…"
                          class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal resize-none"></textarea>
            </div>

            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-400 mb-1">Outcome</label>
                <input type="text" name="outcome" maxlength="100" placeholder="e.g. Scheduled for tomorrow, Left voicemail…"
                       class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="showCorrespondenceForm = false"
                        class="text-sm text-gray-500 hover:text-gray-300 px-3 py-1.5">
                    Cancel
                </button>
                <button type="submit"
                        class="btn-crystal text-sm font-medium px-4 py-1.5 rounded-md  transition">
                    Log Entry
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Timeline --}}
    @if ($serviceRequest->correspondences->isNotEmpty())
        <div class="space-y-3 max-h-80 overflow-y-auto">
            @foreach ($serviceRequest->correspondences->sortByDesc('logged_at') as $entry)
                <div class="flex items-start gap-3 text-sm border-b border-gray-50 pb-2">
                    <div class="flex-shrink-0 mt-1 text-base" title="{{ $entry->channel_label }}">
                        @switch($entry->channel)
                            @case('sms')
                                <span class="text-blue-500">&#x1F4F1;</span>
                                @break
                            @case('phone')
                                <span class="text-green-500">&#x1F4DE;</span>
                                @break
                            @case('email')
                                <span class="text-yellow-500">&#x1F4E7;</span>
                                @break
                            @case('in_person')
                                <span class="text-purple-500">&#x1F91D;</span>
                                @break
                            @default
                                <span class="text-gray-400">&#x1F4AC;</span>
                        @endswitch
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-gray-300">{{ $entry->channel_label }}</span>
                            <span @class([
                                'inline-block text-xs px-1.5 py-0.5 rounded-full',
                                'bg-blue-100 text-cyan-400' => $entry->direction === 'outbound',
                                'bg-green-100 text-green-700' => $entry->direction === 'inbound',
                            ])>
                                {{ ucfirst($entry->direction) }}
                            </span>
                            @if ($entry->subject)
                                <span class="text-gray-500">&mdash; {{ $entry->subject }}</span>
                            @endif
                        </div>

                        @if ($entry->body)
                            <p class="text-gray-400 text-xs mt-0.5 line-clamp-2">{{ $entry->body }}</p>
                        @endif

                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                            <span>{{ $entry->logged_at->format('M j g:i A') }}</span>
                            @if ($entry->logger)
                                <span>by {{ $entry->logger->name }}</span>
                            @endif
                            @if ($entry->duration_minutes)
                                <span>{{ $entry->duration_minutes }} min</span>
                            @endif
                            @if ($entry->outcome)
                                <span class="text-gray-500 font-medium">{{ $entry->outcome }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-400 italic">No correspondence logged yet.</p>
    @endif
</div>
