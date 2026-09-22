<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; }
        .canvas { position: relative; width: {{ $imageWidth }}px; height: {{ $imageHeight }}px; }
        .canvas img.bg { position: absolute; top: 0; left: 0; width: {{ $imageWidth }}px; height: {{ $imageHeight }}px; }
        .field {
            position: absolute;
            font-family: 'DejaVu Sans', sans-serif;
            white-space: pre-wrap;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <div class="canvas">
        <img class="bg" src="{{ $imagePath }}">
        @foreach ($boxes as $box)
            <div class="field" style="
                left: {{ $box['x'] }}px;
                top: {{ $box['y'] }}px;
                width: {{ $box['width'] }}px;
                height: {{ $box['height'] }}px;
                font-size: {{ $box['font_size'] }}px;
                text-align: {{ $box['font_align'] }};
                font-weight: {{ $box['font_weight'] }};
            ">{{ $box['value'] }}</div>
        @endforeach
    </div>
</body>
</html>
