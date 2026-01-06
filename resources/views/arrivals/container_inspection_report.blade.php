<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report</title>
    <style>
        @page { size: A4 landscape; margin: 4mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 9px; color: #111; }

        .page { width: 100%; border: 0.45mm solid #333; }

        /* Stable Dompdf layout: table only */
        table { border-collapse: collapse; }
        .layout { width: 100%; table-layout: fixed; }
        .layout > tbody > tr > td { vertical-align: top; }

        .slot { border: 0.35mm solid #333; position: relative; overflow: hidden; }
        .slot-inner { width: 100%; height: 100%; table-layout: fixed; }
        .slot-cell { width: 100%; height: 100%; text-align: center; vertical-align: middle; }

        .img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
        }

        /* Option B fallback hook (disabled by default). Add class rotate-portrait to img to enable. */
        .img.rotate-portrait {
            transform: rotate(90deg);
            transform-origin: center center;
        }

        .label-vert {
            position: absolute;
            right: 1.8mm;
            top: 50%;
            transform: translateY(-50%) rotate(90deg);
            transform-origin: center;
            font-weight: bold;
            font-size: 10px;
            letter-spacing: 0.2px;
            color: #111;
            white-space: nowrap;
            background: #fff;
            border: 0.25mm solid #333;
            padding: 0.8mm 1.8mm;
            z-index: 2;
        }

        .empty { color: #777; font-size: 11px; }
        .seal-code { font-weight: bold; font-size: 18px; letter-spacing: 0.6px; color: #111; }

        .pad { padding: 2.2mm; }
        .kvs { width: 100%; table-layout: fixed; }
        .kvs td { padding: 0.6mm 0; vertical-align: top; }
        .k { width: 26mm; font-weight: bold; }
        .v { padding-left: 1mm; }

        .sig-divider { margin-top: 4mm; padding-top: 2mm; border-top: 0.25mm solid #333; }
        .sig-grid { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 2mm 0; }
        .sig-box { border: 0.25mm solid #333; height: 18mm; position: relative; padding: 1.6mm; }
        .sig-title { font-weight: bold; font-size: 8.5px; }
        .sig-line { position: absolute; left: 1.6mm; right: 1.6mm; bottom: 6.8mm; border-top: 0.25mm solid #333; }
        .sig-name { position: absolute; left: 0; right: 0; bottom: 1.6mm; text-align: center; font-weight: bold; font-size: 8.5px; }

        .page-break { page-break-after: always; }

        /* Fixed slot heights (1 page A4 landscape) */
        .h-left-top { height: 78mm; }
        .h-left-mid { height: 52mm; }
        .h-left-bot { height: 66mm; }

        .h-right-top { height: 66mm; }
        .h-right-mid { height: 66mm; }
        .h-right-bot-row { height: 32mm; } /* bottom area is 2 rows (2 photos + 1 photo) */
    </style>
</head>
<body>
@foreach ($containers as $container)
    @php
        $inspection = $container->inspection;
        $containerNo = strtoupper(trim((string) ($container->container_no ?? '-')));
        $sealCode = strtoupper(trim((string) (($inspection?->seal_code) ?: ($container->seal_code ?? '-'))));
        $photos = $photosByContainerId[$container->id] ?? [];
        $rotatePortrait = false;

        $photo = function (string $key) use ($photos): ?array {
            $p = $photos[$key] ?? null;
            return is_array($p) && !empty($p['src']) ? $p : null;
        };
    @endphp

    <div class="page">
        <table class="layout">
            <tr>
                <td style="width:46%;">
                    <table class="layout">
                        <tr>
                            <td class="slot h-left-top">
                                <div class="label-vert">Dalam</div>
                                <table class="slot-inner">
                                    <tr>
                                        <td class="slot-cell">
                                            @if ($p = $photo('inside'))
                                                <img class="img {{ $p['class'] }}{{ $rotatePortrait && $p['class'] === 'is-portrait' ? ' rotate-portrait' : '' }}" src="{{ $p['src'] }}" alt="Dalam">
                                            @else
                                                <div class="empty">Foto Dalam (PORTRAIT)</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="slot h-left-mid">
                                <div class="label-vert">No.Seal</div>
                                <table class="slot-inner">
                                    <tr>
                                        <td class="slot-cell">
                                            @if ($p = $photo('seal'))
                                                <img class="img {{ $p['class'] }}{{ $rotatePortrait && $p['class'] === 'is-portrait' ? ' rotate-portrait' : '' }}" src="{{ $p['src'] }}" alt="No Seal">
                                            @else
                                                <div class="seal-code">{{ $sealCode ?: '-' }}</div>
                                                <div class="empty" style="margin-top:2mm;">Foto Seal (PORTRAIT)</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="slot h-left-bot">
                                <div class="pad">
                                    <table class="kvs">
                                        <tr><td class="k">No Invoice</td><td class="v">: {{ $arrival->invoice_no }}</td></tr>
                                        <tr><td class="k">No Container</td><td class="v">: {{ $containerNo }}</td></tr>
                                        <tr><td class="k">No Seal</td><td class="v">: {{ $sealCode ?: '-' }}</td></tr>
                                        <tr><td class="k">Tanggal</td><td class="v">: {{ $inspection?->updated_at?->format('Y-m-d') ?? '-' }}</td></tr>
                                        <tr><td class="k">Keterangan</td><td class="v">: {{ $inspection?->notes ?: '-' }}</td></tr>
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
                                                        <div class="sig-title">Driver / Sopir</div>
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
                </td>
                <td style="width:54%;">
                    <table class="layout">
                        <tr>
                            <td class="slot h-right-top">
                                <div class="label-vert">Depan</div>
                                <table class="slot-inner">
                                    <tr>
                                        <td class="slot-cell">
                                            @if ($p = $photo('front'))
                                                <img class="img {{ $p['class'] }}{{ $rotatePortrait && $p['class'] === 'is-portrait' ? ' rotate-portrait' : '' }}" src="{{ $p['src'] }}" alt="Depan">
                                            @else
                                                <div class="empty">Foto Depan (PORTRAIT)</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="slot h-right-mid">
                                <div class="label-vert">Belakang</div>
                                <table class="slot-inner">
                                    <tr>
                                        <td class="slot-cell">
                                            @if ($p = $photo('back'))
                                                <img class="img {{ $p['class'] }}{{ $rotatePortrait && $p['class'] === 'is-portrait' ? ' rotate-portrait' : '' }}" src="{{ $p['src'] }}" alt="Belakang">
                                            @else
                                                <div class="empty">Foto Belakang (PORTRAIT)</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table class="layout">
                                    <tr>
                                        <td class="slot h-right-bot-row" style="width:50%;">
                                            <div class="label-vert">Kiri</div>
                                            <table class="slot-inner">
                                                <tr>
                                                    <td class="slot-cell">
                                                        @if ($p = $photo('left'))
                                                            <img class="img {{ $p['class'] }}{{ $rotatePortrait && $p['class'] === 'is-portrait' ? ' rotate-portrait' : '' }}" src="{{ $p['src'] }}" alt="Kiri">
                                                        @else
                                                            <div class="empty">Foto Kiri (LANDSCAPE)</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td class="slot h-right-bot-row" style="width:50%; border-left: none;">
                                            <div class="label-vert">Kanan</div>
                                            <table class="slot-inner">
                                                <tr>
                                                    <td class="slot-cell">
                                                        @if ($p = $photo('right'))
                                                            <img class="img {{ $p['class'] }}{{ $rotatePortrait && $p['class'] === 'is-portrait' ? ' rotate-portrait' : '' }}" src="{{ $p['src'] }}" alt="Kanan">
                                                        @else
                                                            <div class="empty">Foto Kanan (LANDSCAPE)</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="slot h-right-bot-row" colspan="2" style="border-top:none;">
                                            <div class="label-vert">Detail</div>
                                            <table class="slot-inner">
                                                <tr>
                                                    <td class="slot-cell">
                                                        @if ($p = $photo('damage'))
                                                            <img class="img {{ $p['class'] }}{{ $rotatePortrait && $p['class'] === 'is-portrait' ? ' rotate-portrait' : '' }}" src="{{ $p['src'] }}" alt="Detail Kerusakan">
                                                        @else
                                                            <div class="empty">Foto Detail Kerusakan (OPTIONAL)</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
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
