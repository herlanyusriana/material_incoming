<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report</title>
    <style>
        @page { size: A4 landscape; margin: 4mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9.2px; color: #111; word-break: break-word; }
        .container { width: 100%; }
        .layout { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 2mm 2mm; }
        .layout td { vertical-align: top; }
        .card { border: 0.35mm solid #333; padding: 1.8mm; position: relative; page-break-inside: avoid; overflow: hidden; }
        .card.h82 { height: 82mm; }
        .card.h36 { height: 36mm; }
        .card.h80 { height: 80mm; }
        .label { position: absolute; top: 2mm; left: 2mm; background: rgba(255,255,255,0.92); padding: 1mm 2mm; font-weight: bold; font-size: 9.5px; }
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            image-orientation: from-image;
            background: #f2f2f2;
        }
        .photo-portrait .photo { object-fit: contain; background: #fff; }
        .photo-landscape .photo { object-fit: cover; }
        /* wkhtmltopdf flexbox is flaky; use table layout for centering */
        .empty { width: 100%; height: 100%; display: table; color: #777; font-size: 11px; text-align: center; }
        .empty > div { display: table-cell; vertical-align: middle; padding: 6mm 4mm; }
        .notes-body { padding-top: 2mm; line-height: 1.2; }
        .notes-text { max-height: 14mm; overflow: hidden; }
        .badges { margin-top: 1mm; max-height: 12mm; overflow: hidden; }
        .badge-row { margin-top: 1mm; }
        .badge { display: inline-block; border: 0.25mm solid #333; padding: 0.6mm 1.6mm; margin: 0 1.2mm 1.2mm 0; font-size: 8.7px; }
        .kvs { width: 100%; border-collapse: collapse; margin-top: 0; table-layout: fixed; }
        .kvs td { padding: 0.6mm 0; vertical-align: top; }
        .k { width: 25mm; font-weight: bold; }
        .v { padding-left: 1mm; }
        .ket { padding-top: 7mm; font-size: 8.8px; line-height: 1.2; }
        .ket .notes-text { max-height: 12mm; }
        .ket .badges { max-height: 10mm; }
        .sig-divider { margin-top: 4mm; padding-top: 2mm; border-top: 0.25mm solid #333; }
        .sig-grid { width: 100%; border-collapse: separate; border-spacing: 2mm 0; table-layout: fixed; }
        .sig-box { border: 0.25mm solid #333; height: 18mm; position: relative; padding: 1.6mm; }
        .sig-title { font-weight: bold; font-size: 8.5px; }
        .sig-line { position: absolute; left: 1.6mm; right: 1.6mm; bottom: 6.8mm; border-top: 0.25mm solid #333; }
        .sig-name { position: absolute; left: 0; right: 0; bottom: 1.6mm; text-align: center; font-weight: bold; font-size: 8.5px; }
    </style>
</head>
<body>
	    <div class="container">
	        @php
	            $firstContainer = '-';
                $firstSeal = '-';
                $driverName = '-';

                if ($arrival->containers && $arrival->containers->count()) {
                    $first = $arrival->containers->first();
                    $firstContainer = strtoupper(trim($first->container_no ?? '-'));
                    $firstSeal = !empty($first->seal_code) ? strtoupper(trim($first->seal_code)) : '-';
                    $driverName = strtoupper(trim((string) ($first?->inspection?->driver_name ?? '-')));
                } elseif (!empty($arrival->container_numbers)) {
                    $lines = preg_split('/\r\n|\r|\n/', $arrival->container_numbers);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $firstContainer = strtoupper($line);
                            break;
                        }
                    }
                    $firstSeal = $arrival->seal_code ? strtoupper(trim($arrival->seal_code)) : '-';
                } else {
                    $firstSeal = $arrival->seal_code ? strtoupper(trim($arrival->seal_code)) : '-';
                }
	        @endphp

        <table class="layout">
            <tr>
                <td style="width: 33%;">
                    <div class="card h82 photo-portrait">
                        <div class="label">Depan</div>
                        @if (!empty($photos['front']))
                            <img class="photo" src="{{ $photos['front'] }}" alt="Depan">
                        @else
                            <div class="empty"><div>Foto Depan (PORTRAIT)</div></div>
                        @endif
                    </div>
                </td>
                <td style="width: 33%;">
                    <div class="card h82 photo-portrait">
                        <div class="label">Belakang</div>
                        @if (!empty($photos['back']))
                            <img class="photo" src="{{ $photos['back'] }}" alt="Belakang">
                        @else
                            <div class="empty"><div>Foto Belakang (PORTRAIT)</div></div>
                        @endif
                    </div>
                </td>
                <td style="width: 34%;">
                    <div class="card h36 photo-landscape">
                        <div class="label">Kiri</div>
                        @if (!empty($photos['left']))
                            <img class="photo" src="{{ $photos['left'] }}" alt="Kiri">
                        @else
                            <div class="empty"><div>Foto Kiri (LANDSCAPE)</div></div>
                        @endif
                    </div>
                    <div style="height:2mm;"></div>
                    <div class="card h36 photo-landscape">
                        <div class="label">Kanan</div>
                        @if (!empty($photos['right']))
                            <img class="photo" src="{{ $photos['right'] }}" alt="Kanan">
                        @else
                            <div class="empty"><div>Foto Kanan (LANDSCAPE)</div></div>
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="card h80 photo-portrait">
                        <div class="label">Dalam</div>
                        @if (!empty($photos['inside']))
                            <img class="photo" src="{{ $photos['inside'] }}" alt="Dalam">
                        @else
                            <div class="empty"><div>Foto Dalam (PORTRAIT)</div></div>
                        @endif
                    </div>
                </td>
                <td>
	                    <div class="card h80">
	                        <div class="label">No. Seal</div>
	                        <div style="padding-top: 22mm; font-size: 18px; font-weight: bold; letter-spacing: 0.5px; text-align: center;">
	                            {{ $firstSeal }}
	                        </div>
	                    </div>
	                </td>
                <td>
                    <div class="card h80">
                        <div class="label">Keterangan</div>
                        <div class="ket">
                            <table class="kvs">
                                <tr><td class="k">No Invoice</td><td class="v">: {{ $arrival->invoice_no }}</td></tr>
                                <tr><td class="k">No Container</td><td class="v">: {{ $firstContainer }}</td></tr>
                                <tr><td class="k">Invoice</td><td class="v">: {{ $arrival->invoice_no ?? '-' }}</td></tr>
                                <tr><td class="k">Vendor</td><td class="v">: {{ $arrival->vendor->vendor_name ?? '-' }}</td></tr>
                                <tr><td class="k">Tanggal</td><td class="v">: {{ $inspection->updated_at?->format('Y-m-d') ?? '-' }}</td></tr>
                                <tr><td class="k">Status</td><td class="v">: {{ strtoupper($inspection->status) }}</td></tr>
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
                            <div class="sig-divider">
                                <table class="sig-grid">
                                    <tr>
                                        <td>
                                            <div class="sig-box">
                                                <div class="sig-title">Diperiksa Oleh</div>
                                                <div class="sig-line"></div>
                                                <div class="sig-name">Nurwahid/Ida</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="sig-box">
                                                <div class="sig-title">Mengetahui</div>
                                                <div class="sig-line"></div>
                                                <div class="sig-name">Fadri/Dita</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="sig-box">
                                                <div class="sig-title">Driver / Sopir</div>
                                                <div class="sig-line"></div>
                                                <div class="sig-name">{{ $driverName ?: '-' }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
