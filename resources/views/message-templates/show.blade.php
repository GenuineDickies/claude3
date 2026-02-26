@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('message-templates.index') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to Templates</a>
        <div class="flex items-center justify-between mt-2">
            <h1 class="text-2xl font-bold text-gray-900">{{ $messageTemplate->name }}</h1>
            <a href="{{ route('message-templates.edit', $messageTemplate) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Edit Template
            </a>
        </div>
    </div>

    {{-- Template Info --}}
    <div class="bg-white rounded-lg shadow-xs p-6 mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-5">
            <div>
                <span class="text-xs text-gray-500 block">ID</span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-sm bg-gray-200 text-sm font-bold text-gray-700 font-mono">{{ $messageTemplate->id }}</span>
            </div>
            <div>
                <span class="text-xs text-gray-500 block">Slug</span>
                <code class="text-sm font-mono text-gray-800">{{ $messageTemplate->slug }}</code>
            </div>
            <div>
                <span class="text-xs text-gray-500 block">Category</span>
                <span class="text-sm text-gray-800">{{ \App\Models\MessageTemplate::categories()[$messageTemplate->category] ?? $messageTemplate->category }}</span>
            </div>
            <div>
                <span class="text-xs text-gray-500 block">Status</span>
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $messageTemplate->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                    {{ $messageTemplate->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div>
                <span class="text-xs text-gray-500 block">Char Count</span>
                <span class="text-sm text-gray-800">{{ mb_strlen($messageTemplate->body) }} chars (~{{ (int) ceil(mb_strlen($messageTemplate->body) / 160) }} SMS segments)</span>
            </div>
        </div>

        {{-- Raw Template --}}
        <div class="mb-5">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Template Body (raw)</h3>
            <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm text-gray-800 whitespace-pre-wrap border border-gray-200">{{ $messageTemplate->body }}</div>
        </div>

        {{-- Preview with sample labels --}}
        <div>
            <h3 class="text-sm font-medium text-gray-700 mb-2">Preview (with sample labels)</h3>
            <div class="bg-blue-50 rounded-lg p-4 text-sm text-gray-800 whitespace-pre-wrap border border-blue-200">{{ $preview }}</div>
        </div>
    </div>

    {{-- Variables Used --}}
    <div class="bg-white rounded-lg shadow-xs p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">Variables Used in This Template</h2>

        @if(count($placeholders) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-2 pr-4">Variable</th>
                            <th class="pb-2 pr-4">Label</th>
                            <th class="pb-2 pr-4">Source</th>
                            <th class="pb-2">Field</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($placeholders as $var)
                            @php $def = $availableVariables[$var] ?? null; @endphp
                            <tr class="border-b last:border-0">
                                <td class="py-2 pr-4">
                                    <code class="text-xs bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded-sm font-mono">@{{ {{ e($var) }} }}</code>
                                </td>
                                <td class="py-2 pr-4 text-gray-700">{{ $def['label'] ?? 'Custom' }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $def['source'] ?? '—' }}</td>
                                <td class="py-2 text-gray-500 font-mono text-xs">{{ $def['field'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No variables — this is a static template.</p>
        @endif
    </div>

    {{-- Live Preview Tool --}}
    <div class="bg-white rounded-lg shadow-xs p-6" x-data="templatePreview()">
        <h2 class="text-base font-semibold text-gray-900 mb-3">Live Preview</h2>
        <p class="text-xs text-gray-500 mb-4">Fill in sample values below and see the rendered message in real-time.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
            @foreach($placeholders as $var)
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">{{ $availableVariables[$var]['label'] ?? $var }}</label>
                    <input type="text"
                           x-model="vars.{{ $var }}"
                           @input="renderPreview()"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="{{ $availableVariables[$var]['label'] ?? $var }}">
                </div>
            @endforeach
        </div>

        <div class="bg-green-50 rounded-lg p-4 text-sm text-gray-800 whitespace-pre-wrap border border-green-200" x-text="rendered"></div>
        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
            <span x-text="rendered.length + ' chars'"></span>
            <span x-text="Math.ceil(rendered.length / 160) + ' SMS segment(s)'"></span>
        </div>
    </div>
</div>

<script>
function templatePreview() {
    const body = @json($messageTemplate->body);
    const placeholders = @json($placeholders);
    const defaultVars = {};
    placeholders.forEach(v => defaultVars[v] = '');

    return {
        vars: { ...defaultVars },
        rendered: body,
        renderPreview() {
            let text = body;
            for (const [key, val] of Object.entries(this.vars)) {
                const re = new RegExp('\\{\\{\\s*' + key + '\\s*\\}\\}', 'g');
                text = text.replace(re, val || '{{ ' + key + ' }}');
            }
            this.rendered = text;
        }
    };
}
</script>
@endsection
