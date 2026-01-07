<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report</title>
    <style>
        @page { size: A4 landscape; margin: 3mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 9px; color: #111; }

        table { border-collapse: collapse; }
        .page { width: 100%; border: 0.45mm solid #333; page-break-inside: avoid; }
        .layout { width: 100%; table-layout: fixed; }
        .layout td { vertical-align: top; padding: 0; }

        /* Use separate border model for consistent gaps */
        .grid { border-collapse: separate; border-spacing: 1.2mm 1.2mm; width: 100%; table-layout: fixed; margin: 0; page-break-inside: avoid; }
        .grid tr { page-break-inside: avoid; }
        .grid td { padding: 0; vertical-align: top; page-break-inside: avoid; }

        .slot { border: 0.30mm solid #333; position: relative; overflow: hidden; background: #fff; page-break-inside: avoid; }
        .slot-inner { width: 100%; height: 100%; table-layout: fixed; }
        .slot-cell { text-align: center; vertical-align: middle; padding: 0; }

        /* Minimize whitespace: fill frame without stretching (crop is OK) */
        .photo-bg {
            width: 100%;
            height: 100%;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            background-color: #f2f2f2;
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

        .pad { padding: 1.2mm; }
        .kvs { width: 100%; table-layout: fixed; }
        .kvs td { padding: 0.4mm 0; vertical-align: top; }
        .k { width: 26mm; font-weight: bold; }
        .v { padding-left: 1mm; }

        .sig-divider { margin-top: 2.2mm; padding-top: 1.6mm; border-top: 0.25mm solid #333; }
        .sig-grid { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 2mm 0; }
        .sig-box { border: 0.25mm solid #333; height: 15mm; position: relative; padding: 1.2mm; }
        .sig-title { font-weight: bold; font-size: 8px; }
        .sig-line { position: absolute; left: 1.6mm; right: 1.6mm; bottom: 6.8mm; border-top: 0.25mm solid #333; }
        .sig-name { position: absolute; left: 0; right: 0; bottom: 1.2mm; text-align: center; font-weight: bold; font-size: 8px; }

        .page-break { page-break-after: always; }

        /* Fixed slot heights: fit 1 A4 landscape page */
        .h-top { height: 42mm; }      /* top photo row */
        .h-mid { height: 52mm; }      /* middle row (Dalam/No.Seal) */
        .h-kiri { height: 40mm; }     /* Kiri */
        .h-kanan { height: 40mm; }    /* Kanan */
        .h-ttd { height: 40mm; }      /* TTD */
        .h-ket { height: 92mm; }      /* Keterangan (rowspan 2: mid+kiri) */

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
        <table class="grid">
            <colgroup>
                <col style="width:20%">
                <col style="width:20%">
                <col style="width:20%">
                <col style="width:20%">
                <col style="width:20%">
            </colgroup>

            <tr>
                <td>
                    <div class="slot h-top">
                        <div class="label">Depan</div>
                        @if ($p = $photo('front'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Foto Depan</div></td></tr></table>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="slot h-top">
                        <div class="label">Belakang</div>
                        @if ($p = $photo('back'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Foto Belakang</div></td></tr></table>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="slot h-top">
                        <div class="label">Detail Kerusakan</div>
                        @if ($p = $photo('damage1'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Detail Kerusakan (If case)</div></td></tr></table>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="slot h-top">
                        <div class="label">Detail Kerusakan</div>
                        @if ($p = $photo('damage2'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Detail Kerusakan (If case)</div></td></tr></table>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="slot h-top">
                        <div class="label">Detail Kerusakan</div>
                        @if ($p = $photo('damage3'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Detail Kerusakan (If case)</div></td></tr></table>
                        @endif
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="slot h-mid">
                        <div class="label">Dalam</div>
                        @if ($p = $photo('inside'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Foto Interior</div></td></tr></table>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="slot h-mid">
                        <div class="label">No.Seal</div>
                        @if ($p = $photo('seal'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner">
                                <tr><td class="slot-cell">
                                    <div class="seal-code">{{ $sealCode ?: '-' }}</div>
                                    <div class="empty" style="margin-top:2mm;">Foto No Seal</div>
                                </td></tr>
                            </table>
                        @endif
                    </div>
                </td>
                <td colspan="3" rowspan="2">
                    <div class="slot h-ket">
                        <div class="pad">
                            <div class="info-title">Keterangan</div>
                            <table class="kvs">
                                <tr><td class="k">No Invoice</td><td class="v">: {{ $arrivalNo }}</td></tr>
                                <tr><td class="k">No Container</td><td class="v">: {{ $containerNo }}</td></tr>
                                <tr><td class="k">No Seal</td><td class="v">: {{ $sealCode ?: '-' }}</td></tr>
                                <tr><td class="k">Tanggal Tiba</td><td class="v">: {{ $dateText }}</td></tr>
                                <tr><td class="k">Catatan</td><td class="v">: {{ $inspection?->notes ?: '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <div class="slot h-kiri">
                        <div class="label">Kiri</div>
                        @if ($p = $photo('left'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Foto Kiri</div></td></tr></table>
                        @endif
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <div class="slot h-kanan">
                        <div class="label">Kanan</div>
                        @if ($p = $photo('right'))
                            <div class="photo-bg" style="background-image: url('{{ $p['src'] }}');"></div>
                        @else
                            <table class="slot-inner"><tr><td class="slot-cell"><div class="empty">Foto Kanan</div></td></tr></table>
                        @endif
                    </div>
                </td>
                <td colspan="3">
                    <div class="slot h-ttd">
                        <div class="pad">
                            <div class="info-title">TTD</div>
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
