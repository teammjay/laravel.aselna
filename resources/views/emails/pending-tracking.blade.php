<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pending Tracking Numbers</title>
</head>
<body>
<h2>Pending Tracking Numbers (Older than 10 days)</h2>
<ul>
    @foreach($trackingNumbers as $number)
        <li>{{ $number }}</li>
    @endforeach
</ul>
</body>
</html>
