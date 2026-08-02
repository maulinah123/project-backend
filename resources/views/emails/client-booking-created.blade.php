@php
    $item = $booking->service?->name ?? $booking->bridalPackage?->name ?? 'your service';
@endphp

<h2>Booking request received</h2>

<p>Hello {{ $booking->client->name }},</p>

<p>We have received your booking request and it is now pending review by the saloon.</p>

<ul>
    <li><strong>Saloon:</strong> {{ $booking->saloon->name }}</li>
    <li><strong>Item:</strong> {{ $item }}</li>
    <li><strong>Date:</strong> {{ $booking->booking_date }}</li>
    <li><strong>Time:</strong> {{ $booking->start_time }} - {{ $booking->end_time }}</li>
    <li><strong>Total:</strong> {{ $booking->total_price }}</li>
</ul>

<p>You will receive another email once the saloon owner confirms or rejects your request.</p>
