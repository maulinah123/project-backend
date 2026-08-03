@php
    $item = $booking->service?->name ?? $booking->bridalPackage?->name ?? 'your service';
@endphp

<h2>Your booking request was rejected</h2>

<p>Hello {{ $booking->client->name }},</p>

<p>Unfortunately, your booking request was <strong>rejected</strong> by the saloon and will not proceed.</p>

<ul>
    <li><strong>Saloon:</strong> {{ $booking->saloon->name }}</li>
    <li><strong>Item:</strong> {{ $item }}</li>
    <li><strong>Date:</strong> {{ $booking->booking_date }}</li>
    <li><strong>Time:</strong> {{ $booking->start_time }} - {{ $booking->end_time }}</li>
</ul>

@if ($booking->owner_notes)
    <p><strong>Note from the saloon:</strong> {{ $booking->owner_notes }}</p>
@endif

<p>Feel free to browse other saloons or choose a different time.</p>
