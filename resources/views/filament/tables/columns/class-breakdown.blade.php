@php
    $classes = $getState() ?? [];
@endphp

@if ($classes)
    <div class="min-w-[32rem] overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 my-2">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800">
                    <th class="whitespace-nowrap px-3 py-2 font-semibold text-white-700 dark:text-gray-300">Grade</th>
                    <th class="px-3 py-2 text-right font-semibold text-blue-700 dark:text-gray-300">Boys</th>
                    <th class="px-3 py-2 text-right font-semibold text-blue-700 dark:text-gray-300">Girls</th>
                    <th class="px-3 py-2 text-right font-semibold text-blue-700 dark:text-gray-300">Total</th>
                    <th class="px-3 py-2 text-right font-semibold text-blue-700 dark:text-gray-300">Admitted</th>
                    <th class="px-3 py-2 text-right font-semibold text-blue-700 dark:text-gray-300">Left</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @php
                    $totalBoys = 0;
                    $totalGirls = 0;
                    $totalStudents = 0;
                    $totalAdmitted = 0;
                    $totalLeft = 0;
                @endphp

                @foreach ($classes as $class)
                    @php
                        $boys = (int) ($class['boys'] ?? 0);
                        $girls = (int) ($class['girls'] ?? 0);
                        $total = (int) ($class['total'] ?? $boys + $girls);
                        $admitted = (int) ($class['new_admissions'] ?? 0);
                        $left = (int) ($class['departures'] ?? 0);

                        $totalBoys += $boys;
                        $totalGirls += $girls;
                        $totalStudents += $total;
                        $totalAdmitted += $admitted;
                        $totalLeft += $left;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="whitespace-nowrap px-3 py-1.5 font-medium text-gray-900 dark:text-gray-100">
                            {{ $class['class_name'] ?? $class['class_id'] ?? 'Class' }}
                        </td>
                        <td class="px-3 py-1.5 text-right tabular-nums">{{ $boys }}</td>
                        <td class="px-3 py-1.5 text-right tabular-nums">{{ $girls }}</td>
                        <td class="px-3 py-1.5 text-right font-semibold tabular-nums">{{ $total }}</td>
                        <td class="px-3 py-1.5 text-right tabular-nums text-success-600 dark:text-success-400">
                            {{ $admitted }}
                        </td>
                        <td class="px-3 py-1.5 text-right tabular-nums text-danger-600 dark:text-danger-400">
                            {{ $left }}
                        </td>
                    </tr>
                @endforeach
            </tbody>

            @if (count($classes) > 1)
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <td class="whitespace-nowrap px-3 py-2 font-bold text-gray-900 dark:text-gray-100">
                            Total
                        </td>
                        <td class="px-3 py-2 text-right font-bold tabular-nums">{{ $totalBoys }}</td>
                        <td class="px-3 py-2 text-right font-bold tabular-nums">{{ $totalGirls }}</td>
                        <td class="px-3 py-2 text-right font-bold tabular-nums">{{ $totalStudents }}</td>
                        <td class="px-3 py-2 text-right font-bold tabular-nums text-success-600 dark:text-success-400">
                            {{ $totalAdmitted }}
                        </td>
                        <td class="px-3 py-2 text-right font-bold tabular-nums text-danger-600 dark:text-danger-400">
                            {{ $totalLeft }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@else
    <span class="text-gray-500 dark:text-gray-400 text-xs italic">No class data</span>
@endif
