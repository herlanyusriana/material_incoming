<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report</title>
    <style>
        @page { size: A4 landscape; margin: 4mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 9px; color: #111; }

        table { border-collapse: collapse; }
        .page { width: 100%; border: 0.45mm solid #333; }
        .layout { width: 100%; table-layout: fixed; }
        .layout td { vertical-align: top; padding: 0; }

        /* Use separate border model for consistent gaps */
        /* Tight gaps so everything fits on 1 page */
        .gap-table { border-collapse: separate; border-spacing: 1.2mm 1.2mm; width: 100%; table-layout: fixed; }
        .gap-table td { padding: 0; vertical-align: top; }

        .slot { border: 0.30mm solid #333; position: relative; overflow: hidden; background: #fff; }
        .slot-inner { width: 100%; height: 100%; table-layout: fixed; }
        .slot-cell { text-align: center; vertical-align: middle; padding: 0; }

        /* Dompdf-friendly "contain" behavior */
        .img {
            display: inline-block;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
        }

        .label {
            position: absolute;
            left: 2mm;
            top: 2mm;
            font-weight: bold;
            font-size: 9.5px;
            color: #111;
            white-space: nowrap;
            background: #fff;
            border: 0.25mm solid #333;
            padding: 0.8mm 2mm;
            z-index: 2;
        }

        .empty { color: #777; font-size: 11px; }
        .seal-code { font-weight: bold; font-size: 18px; letter-spacing: 0.6px; color: #111; }

        .pad { padding: 1.6mm; }
        .kvs { width: 100%; table-layout: fixed; }
        .kvs td { padding: 0.4mm 0; vertical-align: top; }
        .k { width: 26mm; font-weight: bold; }
        .v { padding-left: 1mm; }

        .sig-divider { margin-top: 2.2mm; padding-top: 1.6mm; border-top: 0.25mm solid #333; }
        .sig-grid { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 2mm 0; }
        .sig-box { border: 0.25mm solid #333; height: 16mm; position: relative; padding: 1.4mm; }
        .sig-title { font-weight: bold; font-size: 8px; }
        .sig-line { position: absolute; left: 1.6mm; right: 1.6mm; bottom: 6.8mm; border-top: 0.25mm solid #333; }
        .sig-name { position: absolute; left: 0; right: 0; bottom: 1.2mm; text-align: center; font-weight: bold; font-size: 8px; }

        .page-break { page-break-after: always; }

        /* Fixed slot heights: fit 1 A4 landscape page */
        .h-left { height: 82mm; }         /* left 2x2 blocks */
        .h-right-land { height: 50mm; }   /* Kiri/Kanan landscape blocks */
        .h-right-info { height: 82mm; }   /* info block */

        .info-title { font-weight: bold; font-size: 11px; text-align: center; margin-bottom: 2mm; }
    </style>
</head>
<body>
@foreach ($containers as $container)
    @php
        $inspection = $container->inspection;
        $containerNo = strtoupper(trim((string) ($container->container_no ?? '-')));
        $sealCode = strtoupper(trim((string) (($inspection?->seal_code) ?: ($container->seal_code ?? '-'))));
        $photos = $photosByContainerId[$container->id] ?? [];

        $photo = function (string $key) use ($photos): ?array {
            $p = $photos[$key] ?? null;
            return is_array($p) && !empty($p['src']) ? $p : null;
        };

        $arrivalNo = $arrival->invoice_no ?? '-';
        $dateText = $inspection?->updated_at
            ? $inspection->updated_at->locale('id')->translatedFormat('d F Y')
            : '-';
    @endphp

    <div class="page">
        <table class="layout">
            <tr>
                <td style="width:60%;">
                    <table class="gap-table">
                        <tr>
                            <td>
                                <div class="slot h-left">
                                    <div class="label">Depan</div>
                                    <table class="slot-inner">
                                        <tr><td class="slot-cell">
                                            @if ($p = $photo('front'))
                                                <img class="img" src="{{ $p['src'] }}" alt="Depan">
                                            @else
                                                <div class="empty">Foto Depan</div>
                                            @endif
                                        </td></tr>
                                    </table>
                                </div>
                            </td>
                            <td>
                                <div class="slot h-left">
                                    <div class="label">Belakang</div>
                                    <table class="slot-inner">
                                        <tr><td class="slot-cell">
                                            @if ($p = $photo('back'))
                                                <img class="img" src="{{ $p['src'] }}" alt="Belakang">
                                            @else
                                                <div class="empty">Foto Belakang</div>
                                            @endif
                                        </td></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="slot h-left">
                                    <div class="label">Dalam</div>
                                    <table class="slot-inner">
                                        <tr><td class="slot-cell">
                                            @if ($p = $photo('inside'))
                                                <img class="img" src="{{ $p['src'] }}" alt="Dalam">
                                            @else
                                                <div class="empty">Foto Interior</div>
                                            @endif
                                        </td></tr>
                                    </table>
                                </div>
                            </td>
                            <td>
                                <div class="slot h-left">
                                    <div class="label">No Seal</div>
                                    <table class="slot-inner">
                                        <tr><td class="slot-cell">
                                            @if ($p = $photo('seal'))
                                                <img class="img" src="{{ $p['src'] }}" alt="No Seal">
                                            @else
                                                <div class="seal-code">{{ $sealCode ?: '-' }}</div>
                                                <div class="empty" style="margin-top:2mm;">Foto No Seal</div>
                                            @endif
                                        </td></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:40%;">
                    <table class="gap-table">
                        <tr>
                            <td>
                                <div class="slot h-right-land">
                                    <div class="label">Kiri</div>
                                    <table class="slot-inner">
                                        <tr><td class="slot-cell">
                                            @if ($p = $photo('left'))
                                                <img class="img" src="{{ $p['src'] }}" alt="Kiri">
                                            @else
                                                <div class="empty">Foto Kiri (LANDSCAPE)</div>
                                            @endif
                                        </td></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="slot h-right-land">
                                    <div class="label">Kanan</div>
                                    <table class="slot-inner">
                                        <tr><td class="slot-cell">
                                            @if ($p = $photo('right'))
                                                <img class="img" src="{{ $p['src'] }}" alt="Kanan">
                                            @else
                                                <div class="empty">Foto Kanan (LANDSCAPE)</div>
                                            @endif
                                        </td></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="slot h-right-info">
                                    <div class="pad">
                                        <div class="info-title">Keterangan</div>
                                        <table class="kvs">
                                            <tr><td class="k">No Invoice</td><td class="v">: {{ $arrivalNo }}</td></tr>
                                            <tr><td class="k">No Container</td><td class="v">: {{ $containerNo }}</td></tr>
                                            <tr><td class="k">No Seal</td><td class="v">: {{ $sealCode ?: '-' }}</td></tr>
                                            <tr><td class="k">Tanggal Tiba</td><td class="v">: {{ $dateText }}</td></tr>
                                            <tr><td class="k">Catatan</td><td class="v">: {{ $inspection?->notes ?: '-' }}</td></tr>
                                        </table>

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
                                                            <div class="sig-title">Driver</div>
                                                            <div class="sig-line"></div>
                                                            <div class="sig-name">{{ $inspection?->driver_name ?: '-' }}</div>
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
                </td>
            </tr>
        </table>
    </div>

    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
