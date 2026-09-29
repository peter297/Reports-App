<table>
    <thead>
        <tr class="band-head">
            <th>{{ $headLabel }}</th>
            <th>AVERAGE</th>
            <th colspan="2">EXCEEDING EXPECTATIONS</th>
            <th colspan="2">MEETING EXPECTATIONS</th>
            <th colspan="2">APPROACHING EXPECTATIONS</th>
            <th colspan="2">BELOW EXPECTATIONS</th>
            <th colspan="2">SUCCESS RATE</th>
        </tr>
        <tr>
            <th>LEARNING AREA (L.A.)</th>
            <th class="text-right">L.A. AVERAGE</th>
            <th class="text-right">NO. OF LEARNERS</th>
            <th class="text-right">%</th>
            <th class="text-right">NO. OF LEARNERS</th>
            <th class="text-right">%</th>
            <th class="text-right">NO. OF LEARNERS</th>
            <th class="text-right">%</th>
            <th class="text-right">NO. OF LEARNERS</th>
            <th class="text-right">%</th>
            <th class="text-right">TOTAL LEARNERS</th>
            <th class="text-right">% Meeting Expectation</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['subject'] }}</td>
                <td class="text-right">{{ number_format($row['average'], 2) }}</td>
                <td class="text-right">{{ $row['exceeding'] }}</td>
                <td class="text-right">{{ number_format($row['exceeding_pct'], 2) }}</td>
                <td class="text-right">{{ $row['meeting'] }}</td>
                <td class="text-right">{{ number_format($row['meeting_pct'], 2) }}</td>
                <td class="text-right">{{ $row['approaching'] }}</td>
                <td class="text-right">{{ number_format($row['approaching_pct'], 2) }}</td>
                <td class="text-right">{{ $row['below'] }}</td>
                <td class="text-right">{{ number_format($row['below_pct'], 2) }}</td>
                <td class="text-right font-bold">{{ $row['total'] }}</td>
                <td class="text-right success font-bold">{{ number_format($row['success_rate'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="12" style="text-align: center; color: #6b7280; font-style: italic;">No subject data.</td></tr>
        @endforelse
    </tbody>
    @if (count($rows) > 0)
        <tfoot>
            <tr class="total-row">
                <td class="font-bold">{{ $avgLabel }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['average'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['exceeding'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['exceeding_pct'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['meeting'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['meeting_pct'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['approaching'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['approaching_pct'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['below'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['below_pct'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($avgRow['total'], 2) }}</td>
                <td class="text-right success font-bold">{{ number_format($avgRow['success_rate'], 2) }}</td>
            </tr>
        </tfoot>
    @endif
</table>
