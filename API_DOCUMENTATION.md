# Saloon Booking API Documentation

## Overview

This Laravel API supports authentication, owner saloon management, client browsing, client booking creation, booking acceptance notifications, owner dashboards, and client dashboards.

Authentication uses Laravel Sanctum bearer tokens. All routes are prefixed with `/api`.

## Base URL

```
/api
```

## Roles

| Role | Description |
| --- | --- |
| `client` | Browses saloons, services, packages, creates bookings, and views dashboard/notifications. |
| `owner` | Creates saloons, services, packages, views saloon bookings, updates booking status, and views owner dashboard. |

Public registration only allows `client` and `owner`.

## Common Headers

- `Content-Type: application/json` (for JSON requests)
- `Content-Type: multipart/form-data` (for file uploads)
- `Authorization: Bearer <sanctum_token>` (for every protected route)

---

## Authentication Routes

### Register

`POST /api/register`

**Request body**

```json
{
  "name": "Jane Client",
  "email": "jane@example.com",
  "password": "secret123",
  "role": "client"
}
```

Allowed roles: `client`, `owner`.

**Response (201)**

```json
{
  "message": "User registered successfully",
  "user": {}
}
```

### Login

`POST /api/login`

**Request body**

```json
{
  "email": "jane@example.com",
  "password": "secret123"
}
```

**Response**

```json
{
  "success": true,
  "message": "Login successful",
  "token": "plain-text-token",
  "user": {}
}
```

---

## Shared Browsing Routes (Authenticated)

### List Saloons

`GET /api/saloons`

No request body. Returns all saloons with owner, services, and packages.

**Response (200)**

```json
[{}]
```

### Show Saloon

`GET /api/saloons/{id}`

No request body. Returns one saloon or 404 if missing.

**Response (200)**

```json
{}
```

---

## Client Routes

Requires `Authorization: Bearer <token>` and role `client`.

### Create Booking

`POST /api/bookings`

**Request body (service booking)**

```json
{
  "saloon_id": 1,
  "booking_date": "2026-06-01",
  "start_time": "10:00",
  "end_time": "11:00",
  "service_id": 1,
  "client_notes": "Please prepare bridal makeup options."
}
```

**Request body (bridal package booking)**

```json
{
  "saloon_id": 1,
  "booking_date": "2026-06-01",
  "start_time": "10:00",
  "end_time": "12:00",
  "bridal_package_id": 3
}
```

Rules:

- Use either `service_id` or `bridal_package_id`, not both.
- `booking_date` must be today or later.
- `end_time` must be after `start_time`.
- The selected service must belong to the selected saloon.
- The selected bridal package must belong to the selected saloon.
- A saloon is unavailable when it already has an overlapping `pending` or `confirmed` booking.

**Response (201)**

```json
{}
```

**Booking Conflict Response (409)**

```json
{
  "message": "The selected saloon is already booked for this time. Please choose another time or try one of the recommended saloons.",
  "conflict": {
    "saloon_id": 1,
    "saloon_name": "Booked Beauty",
    "booking_date": "2026-06-01",
    "start_time": "10:30",
    "end_time": "11:30"
  },
  "recommended_saloons": []
}
```

Recommended saloons are returned for service bookings when another saloon offers the same service and has no overlapping booking.

### My Bookings

`GET /api/my-bookings`

No request body.

**Response (200)**

```json
[{}]
```

### Client Dashboard

`GET /api/client/dashboard`

No request body.

**Response (200)**

```json
{
  "total_bookings": 0,
  "pending_bookings": 0,
  "confirmed_bookings": 0,
  "completed_bookings": 0,
  "cancelled_bookings": 0,
  "unread_notifications": 0,
  "recent_bookings": [],
  "notifications": []
}
```

### List Notifications

`GET /api/notifications`

No request body.

**Response (200)**

```json
[{}]
```

### Mark Notification as Read

`PATCH /api/notifications/{id}/read`

Path variable: notification `id`. No request body.

**Response (200)**

```json
{}
```

---

## Owner Routes

Requires `Authorization: Bearer <token>` and role `owner`.

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/owner/dashboard` | Show owner totals and recent bookings. |
| `POST` | `/api/saloons` | Create a saloon profile. |
| `GET` | `/api/owner/saloons` | List the current owner's saloons with services and packages. |
| `GET` | `/api/owner/saloons/{id}/services` | List services for a specific saloon owned by the current owner. |
| `POST` | `/api/saloons/{id}/create-service` | Create a service directly under a saloon. |
| `POST` | `/api/packages` | Create a bridal package directly under a saloon. |
| `GET` | `/api/owner/packages` | List bridal packages across all saloons owned by the current owner. |
| `GET` | `/api/saloon/bookings` | List bookings for the owner's saloons. Optional query: `status`. |
| `PATCH` | `/api/saloon/bookings/{id}/status` | Update booking status and notify the client when accepted. |

### Owner Dashboard

`GET /api/owner/dashboard`

No request body.

**Response (200)**

```json
{
  "owner_name": "Owner Name",
  "total_bookings": 0,
  "total_services": 0,
  "total_saloons": 0,
  "total_packages": 0,
  "recent_bookings": []
}
```

### Create Saloon

`POST /api/saloons`

`multipart/form-data`

| Field | Type | Required |
| `name` | string | yes |
| `location` | string | yes |
| `description` | string | no |
| `image` | image file | no |

**Response (201)**

```json
{}
```

### My Saloons

`GET /api/owner/saloons`

No request body. Returns all saloons where `owner_id` matches the authenticated owner, with loaded services and packages.

**Response (200)**

```json
[{}]
```

### My Services

`GET /api/owner/saloons/{id}/services`

Path variable: saloon `id`.

Returns `404` if the saloon does not exist or does not belong to the current owner.

**Response (200)**

```json
[{}]
```

### Create Service

`POST /api/saloons/{id}/create-service`

Path variable: saloon `id`. `multipart/form-data`

| Field | Type | Required |
| `name` | string | yes |
| `description` | string | no |
| `price` | number | yes |
| `duration` | integer (minutes) | yes |
| `image` | image file | no |

Returns `403 Unauthorized` if the saloon does not belong to the current owner.

**Response (201)**

```json
{
  "message": "Service created and added to your saloon.",
  "service": {}
}
```

### Create Bridal Package

`POST /api/packages`

`multipart/form-data`

| Field | Type | Required |
| `saloon_id` | integer | yes |
| `name` | string | yes |
| `description` | string | no |
| `package_price` | number | yes |
| `image` | image file | no |

Returns `403 Unauthorized` if the saloon does not belong to the current owner.

**Response (201)**

```json
{}
```

### My Packages

`GET /api/owner/packages`

No request body. Returns all bridal packages belonging to saloons owned by the authenticated owner.

**Response (200)**

```json
[{}]
```

### Saloon Bookings

`GET /api/saloon/bookings`

Optional query parameter:

| Query | Type | Allowed values |
| `status` | string | `pending`, `confirmed`, `completed`, `cancelled` |

No request body.

**Response (200)**

```json
[{}]
```

### Update Booking Status

`PATCH /api/saloon/bookings/{id}/status`

Path variable: booking `id`.

**Request body**

```json
{
  "status": "confirmed",
  "owner_notes": "Your booking has been accepted."
}
```

Allowed statuses: `confirmed`, `completed`, `cancelled`.

When a booking changes from `pending` to `confirmed`, the client receives a notification with title `Booking accepted`.

Returns `403 Unauthorized` if the booking does not belong to the owner's saloon.

**Response**

```json
{
  "message": "Booking status updated.",
  "booking": {}
}
```

---

## Notes

- Routes labeled `auth:sanctum` require a valid Sanctum bearer token.
- Routes under `role:client` or `role:owner` additionally enforce that the authenticated user has the required role.
- The backend no longer uses pivot tables for saloon services, package services, or booking items.
- Services belong directly to a saloon through `services.saloon_id`.
- Bridal packages belong directly to a saloon through `bridal_packages.saloon_id`.
- Bookings use either `bookings.service_id` or `bookings.bridal_package_id`, but not both.
