<x-filament-panels::page>
    {{-- Templates Grid --}}
    @php
        $templates = $this->getTemplates();
    @endphp

    @if ($templates->isEmpty())
        <div class="flex items-center justify-center py-16">
            <div class="text-center">
                <x-heroicon-o-document-arrow-up class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No templates</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No report templates have been uploaded yet.</p>
            </div>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($templates as $template)
                <div class="relative flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                    <div class="mb-4 flex items-center gap-3">
                        @if ($template->file_type === 'word')
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                <x-heroicon-o-document-text class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                                <x-heroicon-o-table-cells class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $template->name }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ strtoupper($template->extension) }} • {{ $template->formatted_size }}
                            </p>
                        </div>
                    </div>

                    @if ($template->description)
                        <p class="mb-4 flex-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ $template->description }}
                        </p>
                    @else
                        <div class="mb-4 flex-1"></div>
                    @endif

                    <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                        <span class="text-xs text-gray-400">
                            {{ $template->created_at->diffForHumans() }}
                        </span>
                        <div class="flex items-center gap-2">
                            @if ($this->isSuperAdmin())
                                <button
                                    wire:click="deleteTemplate({{ $template->id }})"
                                    wire:confirm="Are you sure you want to delete this template?"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-danger-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-danger-500 dark:bg-danger-500 dark:hover:bg-danger-400"
                                >
                                    <x-heroicon-o-trash class="h-3.5 w-3.5" />
                                    Delete
                                </button>
                            @endif
                            <a
                                href="{{ $template->download_url }}"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
                            >
                                <x-heroicon-o-arrow-down-tray class="h-3.5 w-3.5" />
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
