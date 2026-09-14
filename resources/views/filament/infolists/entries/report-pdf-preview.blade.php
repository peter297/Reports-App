<div class="w-full min-w-0" wire:ignore>
    <iframe
        src="{{ route('reports.pdf', $entry->getRecord()) }}"
        title="Report PDF preview"
        style="display: block; width: 100%; height: calc(100vh - 120px); min-height: 1200px;"
        class="rounded-lg border border-gray-200 dark:border-gray-700"
    ></iframe>

    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        If the preview does not load,
        <a
            href="{{ route('reports.pdf', $entry->getRecord()) }}"
            target="_blank"
            class="text-primary-600 underline"
        >
            open the PDF in a new tab
        </a>.
    </p>
</div>
