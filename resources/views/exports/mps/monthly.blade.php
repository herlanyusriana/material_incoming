<table>
    <thead>
        <tr>
            <th colspan="{{ 2 + count($months) }}" style="font-size: 14pt; font-weight: bold; text-align: center;">
                MASTER PRODUCTION SCHEDULE (MONTHLY VIEW)
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + count($months) }}" style="text-align: center;">
                Generated at: {{ now()->format('Y-m-d H:i') }}
            </th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f1f5f9;">
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #e2e8f0;">Part GCI</th>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #e2e8f0;">Part Name</th>
            @foreach($months as $m)
                <th style="font-weight: bold; border: 1px solid #000000; background-color: #e2e8f0; text-align: center;">
                    {{ \Carbon\Carbon::parse($m . '-01')->format('M Y') }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($parts as $p)
            <tr>
                <td style="border: 1px solid #000000;">{{ $p->part_no }}</td>
                <td style="border: 1px solid #000000;">{{ $p->part_name }}</td>
                @foreach($months as $m)
                    @php
                        $monthSum = $p->mps->filter(function($item) use ($m) {
                            $y = (int) substr($item->minggu, 0, 4);
                            $w = (int) substr($item->minggu, 6, 2);
                            $d = \Carbon\Carbon::now()->setISODate($y, $w, 1);
                            return $d->format('Y-m') === $m;
                        })->sum('planned_qty');
                    @endphp
                    <td style="border: 1px solid #000000; text-align: right;">
                        {{ (float)$monthSum }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
