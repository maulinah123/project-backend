@php
    $item = $booking->service?->name ?? $booking->bridalPackage?->name ?? 'your service';
@endphp

<h2>New booking request</h2>

<p>Hello {{ $booking->saloon->owner->name }},</p>

<p>You have received a new booking request that needs your review.</p>

<ul>
    <li><strong>Client:</strong> {{ $booking->client->name }} ({{ $booking->client->email }})</li>
    <li><strong>Saloon:</strong> {{ $booking->saloon->name }}</li>
    <li><strong>Item:</strong> {{ $item }}</li>
    <li><strong>Date:</strong> {{ $booking->booking_date }}</li>
    <li><strong>Time:</strong> {{ $booking->start_time }} - {{ $booking->end_time }}</li>
    <li><strong>Total:</strong> {{ $booking->total_price }}</li>
    @if ($booking->client_notes)
        <li><strong>Client notes:</strong> {{ $booking->client_notes }}</li>
    @endif
</ul>

<p>Please confirm or reject this request from your owner dashboard.</p>
