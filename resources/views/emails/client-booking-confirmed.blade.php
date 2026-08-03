@php
    $item = $booking->service?->name ?? $booking->bridalPackage?->name ?? 'your service';
@endphp

<h2>Your booking is confirmed</h2>

<p>Hello {{ $booking->client->name }},</p>

<p>Good news! Your booking request has been <strong>confirmed</strong> by the saloon.</p>

<ul>
    <li><strong>Saloon:</strong> {{ $booking->saloon->name }}</li>
    <li><strong>Item:</strong> {{ $item }}</li>
    <li><strong>Date:</strong> {{ $booking->booking_date }}</li>
    <li><strong>Time:</strong> {{ $booking->start_time }} - {{ $booking->end_time }}</li>
</ul>

<p>We look forward to seeing you.</p>
