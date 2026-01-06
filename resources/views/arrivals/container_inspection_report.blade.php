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
        .page { width: 100%; border: 0.45mm solid #333; }

        .grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .grid td { vertical-align: top; padding: 0; }
        .cell { border: 0.35mm solid #333; position: relative; overflow: hidden; }
        .pad { padding: 2.2mm; }

        .label-vert {
            position: absolute;
            right: -14mm;
            top: 50%;
            transform: translateY(-50%) rotate(90deg);
            transform-origin: center;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #111;
            white-space: nowrap;
        }

        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            image-orientation: from-image;
            background: #f2f2f2;
        }
        .portrait .photo { object-fit: contain; background: #fff; }
        .landscape .photo { object-fit: cover; }

        .empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #777; font-size: 11px; text-align: center; }
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
        .page-break { page-break-after: always; }

        /* Heights tuned to fit 1 A4 landscape page */
        .h-top { height: 63mm; }
        .h-mid { height: 63mm; }
        .h-bot { height: 58mm; }
        .h-left-top { height: 70mm; }
        .h-left-mid { height: 45mm; }
        .h-left-bot { height: 69mm; }
    </style>
</head>
<body>
    <div class="container">
        @foreach ($containers as $container)
            @php
                $inspection = $container->inspection;
                $containerNo = strtoupper(trim((string) ($container->container_no ?? '-')));
                $sealCode = strtoupper(trim((string) (($inspection?->seal_code) ?: ($container->seal_code ?? '-'))));
                $photos = $photosByContainerId[$container->id] ?? [];
            @endphp

            <div class="page">
                <table class="grid">
                    <tr>
                        <td style="width: 46%;">
                            <table class="grid">
                                <tr>
                                    <td>
                                        <div class="cell portrait h-left-top">
                                            <div class="label-vert">Dalam</div>
                                            @if (!empty($photos['inside']))
                                                <img class="photo" src="{{ $photos['inside'] }}" alt="Dalam">
                                            @else
                                                <div class="empty">Foto Dalam (PORTRAIT)</div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="cell portrait h-left-mid">
                                            <div class="label-vert">No.Seal</div>
                                            @if (!empty($photos['seal']))
                                                <img class="photo" src="{{ $photos['seal'] }}" alt="No.Seal">
                                            @else
                                                <div class="empty">Foto Seal (PORTRAIT)</div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="cell pad h-left-bot">
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
                        <td style="width: 54%;">
                            <table class="grid">
                                <tr>
                                    <td>
                                        <div class="cell portrait h-top">
                                            <div class="label-vert">Depan</div>
                                            @if (!empty($photos['front']))
                                                <img class="photo" src="{{ $photos['front'] }}" alt="Depan">
                                            @else
                                                <div class="empty">Foto Depan (PORTRAIT)</div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="cell portrait h-mid">
                                            <div class="label-vert">Belakang</div>
                                            @if (!empty($photos['back']))
                                                <img class="photo" src="{{ $photos['back'] }}" alt="Belakang">
                                            @else
                                                <div class="empty">Foto Belakang (PORTRAIT)</div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table class="grid">
                                            <tr>
                                                <td style="width: 50%;">
                                                    <div class="cell landscape h-bot">
                                                        <div class="label-vert">Kiri</div>
                                                        @if (!empty($photos['left']))
                                                            <img class="photo" src="{{ $photos['left'] }}" alt="Kiri">
                                                        @else
                                                            <div class="empty">Foto Kiri (LANDSCAPE)</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td style="width: 50%;">
                                                    <div class="cell landscape h-bot">
                                                        <div class="label-vert">Kanan</div>
                                                        @if (!empty($photos['right']))
                                                            <img class="photo" src="{{ $photos['right'] }}" alt="Kanan">
                                                        @else
                                                            <div class="empty">Foto Kanan (LANDSCAPE)</div>
                                                        @endif
                                                    </div>
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
    </div>
</body>
</html>
