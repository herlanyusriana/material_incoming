<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report</title>
    <style>
        @page { size: A4 landscape; margin: 7mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9.5px; color: #111; }
        .container { width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4mm; }
        .title { font-size: 14px; font-weight: bold; }
        .meta { font-size: 9.5px; line-height: 1.25; text-align: right; }
        .meta b { font-weight: bold; }
        .layout { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 3mm 3mm; }
        .layout td { vertical-align: top; }
        .card { border: 0.35mm solid #333; padding: 2.5mm; position: relative; page-break-inside: avoid; overflow: hidden; }
        .card.h78 { height: 78mm; }
        .card.h24 { height: 24mm; }
        .card.h44 { height: 44mm; }
        .label { position: absolute; top: 2mm; left: 2mm; background: rgba(255,255,255,0.92); padding: 1mm 2mm; font-weight: bold; font-size: 9.5px; }
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            image-orientation: from-image;
            background: #f2f2f2;
        }
        .empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #777; font-size: 11px; padding-top: 6mm; text-align: center; }
        .notes-body { padding-top: 6mm; line-height: 1.25; }
        .notes-text { max-height: 20mm; overflow: hidden; }
        .badges { margin-top: 1.5mm; max-height: 16mm; overflow: hidden; }
        .badge-row { margin-top: 1.2mm; }
        .badge { display: inline-block; border: 0.25mm solid #333; padding: 0.6mm 1.6mm; margin: 0 1.4mm 1.4mm 0; font-size: 9px; }
        .kvs { width: 100%; border-collapse: collapse; margin-top: 5mm; }
        .kvs td { padding: 1mm 0; vertical-align: top; }
        .k { width: 28mm; font-weight: bold; }
        .v { }
    </style>
</head>
<body>
    <div class="container">
        @php
            $firstContainer = '-';
            if (!empty($arrival->container_numbers)) {
                $lines = preg_split('/\r\n|\r|\n/', $arrival->container_numbers);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $firstContainer = strtoupper($line);
                        break;
                    }
                }
            }
        @endphp
        <!-- <div class="header">
            <div>
                <div class="title">CONTAINER INSPECTION REPORT</div>
                <div style="margin-top:2mm; font-size:10px;">
                    <b>Invoice</b>: {{ $arrival->invoice_no }} &nbsp; • &nbsp;
                    <b>Arrival</b>: {{ $arrival->arrival_no }} &nbsp; • &nbsp;
                    <b>Vendor</b>: {{ $arrival->vendor->vendor_name ?? '-' }}
                </div>
            </div>
            <div class="meta">
                <div><b>Status</b>: {{ strtoupper($inspection->status) }}</div>
                <div><b>Inspector</b>: {{ $inspection->inspector->name ?? '-' }}</div>
                <div><b>Tanggal</b>: {{ $inspection->updated_at?->format('Y-m-d H:i') ?? '-' }}</div>
                <div><b>No. Container</b>: {{ $firstContainer }}</div>
                <div><b>Seal Code</b>: {{ $arrival->seal_code ?? '-' }}</div>
            </div>
        </div> -->

        <table class="layout">
            <tr>
                <td style="width: 35%;">
                    <div class="card h78">
                        <div class="label">Foto Depan</div>
                        @if (!empty($photos['front']))
                            <img class="photo" src="{{ $photos['front'] }}" alt="Foto Depan">
                        @else
                            <div class="empty">Foto Depan belum tersedia</div>
                        @endif
                    </div>
                </td>
                <td style="width: 30%;">
                    <div class="card h24">
                        <div class="label">Kiri</div>
                        @if (!empty($photos['left']))
                            <img class="photo" src="{{ $photos['left'] }}" alt="Kiri">
                        @else
                            <div class="empty">Foto Kiri belum tersedia</div>
                        @endif
                    </div>
                    <div style="height:3mm;"></div>
                    <div class="card h24">
                        <div class="label">Kanan</div>
                        @if (!empty($photos['right']))
                            <img class="photo" src="{{ $photos['right'] }}" alt="Kanan">
                        @else
                            <div class="empty">Foto Kanan belum tersedia</div>
                        @endif
                    </div>
                    <div style="height:3mm;"></div>
                    <div class="card h24">
                        <div class="label">Dalam</div>
                        @if (!empty($photos['inside']))
                            <img class="photo" src="{{ $photos['inside'] }}" alt="Dalam">
                        @else
                            <div class="empty">Foto Dalam belum tersedia</div>
                        @endif
                    </div>
                </td>
                <td style="width: 35%;">
                    <div class="card h78">
                        <div class="label">Belakang</div>
                        @if (!empty($photos['back']))
                            <img class="photo" src="{{ $photos['back'] }}" alt="Belakang">
                        @else
                            <div class="empty">Foto Belakang belum tersedia</div>
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="card h44">
                        <div class="label">No. Seal</div>
                        <div style="padding-top: 10mm; font-size: 18px; font-weight: bold; letter-spacing: 0.5px; text-align: center;">
                            {{ $arrival->seal_code ? strtoupper(trim($arrival->seal_code)) : '-' }}
                        </div>
                    </div>
                </td>
                <td colspan="2">
                    <div class="card h44">
                        <div class="label">Keterangan</div>
                        <div class="notes-body">
                            <table class="kvs">
                                <tr>
                                    <td class="k">No Invoice</td>
                                    <td class="v">: {{ $arrival->invoice_no }}</td>
                                </tr>
                                <tr>
                                    <td class="k">No Container</td>
                                    <td class="v">: {{ $firstContainer }}</td>
                                </tr>
                                <tr>
                                    <td class="k">Tanggal</td>
                                    <td class="v">: {{ $inspection->updated_at?->format('Y-m-d') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="k">Status</td>
                                    <td class="v">: {{ strtoupper($inspection->status) }}</td>
                                </tr>
                            </table>
                            <div class="notes-text"><b>Catatan</b>: {{ $inspection->notes ?: '-' }}</div>
                            <div class="badges">
                                @foreach (['issues_left' => 'Left', 'issues_right' => 'Right', 'issues_front' => 'Front', 'issues_back' => 'Back'] as $field => $side)
                                    @php $issues = $inspection->{$field} ?? []; @endphp
                                    @if (!empty($issues))
                                        <div class="badge-row">
                                            <b>{{ $side }}</b>:
                                            @foreach ($issues as $issue)
                                                <span class="badge">{{ strtoupper($issue) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
