<table>
    <thead>
        <tr>
            <th colspan="{{ 2 + count($weeks) }}" style="font-size: 14pt; font-weight: bold; text-align: center;">
                MASTER PRODUCTION SCHEDULE (WEEKLY VIEW)
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + count($weeks) }}" style="text-align: center;">
                Generated at: {{ now()->format('Y-m-d H:i') }}
            </th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f1f5f9;">
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #e2e8f0;">Part GCI</th>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #e2e8f0;">Part Name</th>
            @foreach($weeks as $w)
                @php
                    preg_match('/^(\d{4})-W(\d{2})$/', $w, $wm);
                    $label = $wm ? ('W' . $wm[2] . '-' . $wm[1]) : $w;
                @endphp
                <th style="font-weight: bold; border: 1px solid #000000; background-color: #e2e8f0; text-align: center;">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($parts as $p)
            @php $byWeek = $p->mps->keyBy('minggu'); @endphp
            <tr>
                <td style="border: 1px solid #000000;">{{ $p->part_no }}</td>
                <td style="border: 1px solid #000000;">{{ $p->part_name }}</td>
                @foreach($weeks as $w)
                    @php $cell = $byWeek->get($w); @endphp
                    <td style="border: 1px solid #000000; text-align: right;">
                        {{ $cell ? (float)$cell->planned_qty : 0 }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
