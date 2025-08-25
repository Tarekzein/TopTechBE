# Store Analytics API Documentation

This document describes the analytics API endpoints for the Store module, providing comprehensive vendor analytics for e-commerce dashboards.

## Overview

The Analytics API provides real-time data and insights for vendors to monitor their store performance, including revenue, orders, customers, products, and performance metrics.

## Authentication

All analytics endpoints require authentication using Laravel Sanctum. Include the bearer token in the Authorization header:

```
Authorization: Bearer {your-token}
```

## Base URL

```
/api/store/analytics
```

## Endpoints

### 1. Dashboard Analytics

Get comprehensive dashboard analytics for the authenticated vendor.

**Endpoint:** `GET /api/store/analytics/dashboard`

**Query Parameters:**
- `period` (optional): Time period for analytics. Options: `7d`, `30d`, `90d`, `1y`. Default: `30d`

**Response:**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_revenue": {
        "value": 45678.90,
        "change": 12.5,
        "currency": "USD"
      },
      "total_orders": {
        "value": 1234,
        "change": -2.3
      },
      "total_customers": {
        "value": 567,
        "change": 8.7
      },
      "total_products": {
        "value": 89,
        "change": 15.2
      }
    },
    "performance": {
      "average_order_value": {
        "value": 37.02,
        "currency": "USD"
      },
      "conversion_rate": {
        "value": 3.2,
        "unit": "%"
      },
      "return_rate": {
        "value": 1.8,
        "unit": "%"
      },
      "customer_satisfaction": {
        "value": 4.6,
        "unit": "/5"
      }
    },
    "recent_orders": [
      {
        "id": "#ORD-001",
        "customer": "John Doe",
        "amount": 129.99,
        "status": "completed",
        "date": "2024-01-15"
      }
    ],
    "top_products": [
      {
        "name": "Wireless Headphones",
        "sales": 234,
        "revenue": 23400.00,
        "growth": 15.2
      }
    ],
    "sales_trend": [
      {
        "month": "Jan",
        "sales": 12000.00,
        "orders": 320
      }
    ],
    "period": "30d"
  }
}
```

### 2. Revenue Analytics

Get detailed revenue analytics.

**Endpoint:** `GET /api/store/analytics/revenue`

**Query Parameters:**
- `period` (optional): Time period. Options: `7d`, `30d`, `90d`, `1y`. Default: `30d`

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue": {
      "total": 45678.90,
      "change": 12.5,
      "currency": "USD"
    },
    "sales_trend": [
      {
        "month": "Jan",
        "sales": 12000.00,
        "orders": 320
      }
    ]
  }
}
```

### 3. Orders Analytics

Get orders analytics and recent orders.

**Endpoint:** `GET /api/store/analytics/orders`

**Query Parameters:**
- `period` (optional): Time period. Options: `7d`, `30d`, `90d`, `1y`. Default: `30d`

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": {
      "total": 1234,
      "change": -2.3
    },
    "recent_orders": [
      {
        "id": "#ORD-001",
        "customer": "John Doe",
        "amount": 129.99,
        "status": "completed",
        "date": "2024-01-15"
      }
    ]
  }
}
```

### 4. Products Analytics

Get products analytics and top performing products.

**Endpoint:** `GET /api/store/analytics/products`

**Query Parameters:**
- `period` (optional): Time period. Options: `7d`, `30d`, `90d`, `1y`. Default: `30d`

**Response:**
```json
{
  "success": true,
  "data": {
    "products": {
      "total": 89,
      "change": 15.2
    },
    "top_products": [
      {
        "name": "Wireless Headphones",
        "sales": 234,
        "revenue": 23400.00,
        "growth": 15.2
      }
    ]
  }
}
```

### 5. Customers Analytics

Get customers analytics.

**Endpoint:** `GET /api/store/analytics/customers`

**Query Parameters:**
- `period` (optional): Time period. Options: `7d`, `30d`, `90d`, `1y`. Default: `30d`

**Response:**
```json
{
  "success": true,
  "data": {
    "customers": {
      "total": 567,
      "change": 8.7
    }
  }
}
```

### 6. Analytics Summary

Get a quick summary of key metrics.

**Endpoint:** `GET /api/store/analytics/summary`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_revenue": 45678.90,
    "total_orders": 1234,
    "total_customers": 567,
    "total_products": 89,
    "average_order_value": 37.02,
    "recent_orders_count": 5
  }
}
```

### 7. Export Analytics Report

Export analytics data in various formats.

**Endpoint:** `POST /api/store/analytics/export`

**Request Body:**
```json
{
  "period": "30d",
  "format": "json"
}
```

**Parameters:**
- `period` (optional): Time period. Options: `7d`, `30d`, `90d`, `1y`. Default: `30d`
- `format` (optional): Export format. Options: `json`, `csv`. Default: `json`

**Response:**
```json
{
  "success": true,
  "data": {
    "vendor_id": 1,
    "period": "30d",
    "generated_at": "2024-01-15T10:30:00Z",
    "overview": { ... },
    "performance": { ... },
    "recent_orders": [ ... ],
    "top_products": [ ... ],
    "sales_trend": [ ... ]
  }
}
```

## Admin Endpoints

### 1. Get Vendor Analytics (Admin Only)

Get analytics for a specific vendor.

**Endpoint:** `GET /api/store/admin/analytics/vendors/{vendorId}`

**Query Parameters:**
- `period` (optional): Time period. Options: `7d`, `30d`, `90d`, `1y`. Default: `30d`

**Response:** Same structure as vendor dashboard analytics.

### 2. Get All Vendors Analytics (Admin Only)

Get analytics overview for all vendors.

**Endpoint:** `GET /api/store/admin/analytics/vendors`

**Response:**
```json
{
  "success": true,
  "message": "All vendors analytics endpoint - implementation pending",
  "data": []
}
```

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Vendor ID not found"
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized access"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to fetch analytics data",
  "error": "Error details"
}
```

## Data Sources

The analytics are calculated from the following data sources:

- **Orders**: Revenue, order counts, customer counts
- **Order Items**: Product sales, revenue per product
- **Products**: Product counts, product performance
- **Users**: Customer analytics

## Performance Considerations

- Analytics queries are optimized with proper database joins
- Data is aggregated at the database level for better performance
- Caching can be implemented for frequently accessed metrics
- Large datasets are paginated where appropriate

## Rate Limiting

Analytics endpoints are subject to rate limiting to prevent abuse. The current limits are:

- 100 requests per minute per authenticated user
- 1000 requests per hour per authenticated user

## Testing

Run the analytics tests with:

```bash
php artisan test --filter=AnalyticsTest
```

## Implementation Notes

- All monetary values are returned in USD
- Date ranges are calculated based on the selected period
- Growth percentages are calculated compared to the previous period
- Mock data is used for some metrics (conversion rate, customer satisfaction) until real data sources are available
- The API is designed to be vendor-specific, ensuring data isolation
