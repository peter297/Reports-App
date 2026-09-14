
<div class="min-w-[34rem] overflow-x-auto">
    <table class="w-full text-left text-xs">
        <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="px-2 py-1 font-medium">Grade</th>
                <th class="px-2 py-1 text-right font-medium">Boys</th>
                <th class="px-2 py-1 text-right font-medium">Girls</th>
                <th class="px-2 py-1 text-right font-medium">Total</th>
                <th class="px-2 py-1 text-right font-medium">Admitted</th>
                <th class="px-2 py-1 text-right font-medium">Left</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($getState() ?? [] as $class)
                @php
                    $boys = (int) ($class['boys'] ?? 0);
                    $girls = (int) ($class['girls'] ?? 0);
                @endphp
                <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                    <td class="whitespace-nowrap px-2 py-1">
                        {{ $class['class_name'] ?? $class['class_id'] ?? 'Class' }}
                    </td>
                    <td class="px-2 py-1 text-right">{{ $boys }}</td>
                    <td class="px-2 py-1 text-right">{{ $girls }}</td>
                    <td class="px-2 py-1 text-right font-medium">
                        {{ $class['total'] ?? $boys + $girls }}
                    </td>
                    <td class="px-2 py-1 text-right">{{ $class['new_admissions'] ?? 0 }}</td>
                    <td class="px-2 py-1 text-right">{{ $class['departures'] ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-2 py-1 text-gray-500">No class data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
