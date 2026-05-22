# Crypto Payment Gateway API Documentation

## Endpoints

### Payment Endpoints

#### 1. Checkout Page
```
GET /payments/checkout/{order}
```

**Description:** Display crypto payment checkout page

**Parameters:**
- `order` (required): Order ID

**Response:** HTML page with gateway and cryptocurrency selection

**Example:**
```
GET /payments/checkout/1
```

---

#### 2. Create Payment
```
POST /payments/create/{order}
```

**Description:** Create a new crypto payment request

**Parameters:**
- `order` (required): Order ID

**Request Body:**
```json
{
    "gateway_id": 1,
    "cryptocurrency": "BTC"
}
```

**Response:**
```json
{
    "success": true,
    "transaction_id": 1,
    "payment_address": "1A1z7agoat...",
    "crypto_amount": "0.00234",
    "cryptocurrency": "BTC",
    "qr_code": "https://api.qrserver.com/...",
    "expires_at": "2024-01-15T12:30:00Z",
    "redirect_url": "/payments/confirm/1"
}
```

**Errors:**
```json
{
    "success": false,
    "error": "Gateway not found"
}
```

---

#### 3. Confirm Payment
```
GET /payments/confirm/{transaction}
```

**Description:** Display payment confirmation page with wallet address and QR code

**Parameters:**
- `transaction` (required): Transaction ID

**Response:** HTML page with payment details and real-time status updates

---

#### 4. Get Payment Status
```
GET /payments/status/{transaction}
```

**Description:** Get current payment status (AJAX)

**Parameters:**
- `transaction` (required): Transaction ID

**Response:**
```json
{
    "id": 1,
    "status": "pending",
    "cryptocurrency": "BTC",
    "crypto_amount": "0.00234",
    "fiat_amount": "100.00",
    "payment_address": "1A1z7agoat...",
    "confirmations": 0,
    "required_confirmations": 1,
    "amount_received": "0.00234",
    "transaction_hash": "abc123...",
    "expires_at": "2024-01-15T12:30:00Z",
    "confirmed_at": null
}
```

---

#### 5. Check Payment Status
```
POST /payments/check-status/{transaction}
```

**Description:** Verify payment status with gateway (AJAX)

**Parameters:**
- `transaction` (required): Transaction ID

**Response:**
```json
{
    "success": true,
    "status": "confirmed",
    "confirmations": 3,
    "amount_received": "0.00234"
}
```

---

#### 6. Payment Success
```
GET /payments/success
```

**Description:** Display payment success page

**Query Parameters:**
- `transaction_id` (optional): Transaction ID

**Response:** HTML success page

---

#### 7. Payment Cancel
```
GET /payments/cancel
```

**Description:** Display payment cancelled page

**Query Parameters:**
- `transaction_id` (optional): Transaction ID

**Response:** HTML cancel page

---

#### 8. Payment Pending
```
GET /payments/pending
```

**Description:** Display payment pending page

**Query Parameters:**
- `transaction_id` (optional): Transaction ID

**Response:** HTML pending page with auto-refresh

---

### Webhook Endpoints

#### 1. Coinbase Commerce Webhook
```
POST /webhooks/coinbase-commerce
```

**Description:** Handle Coinbase Commerce webhook events

**Headers:**
```
X-CC-Webhook-Signature: <signature>
```

**Payload:**
```json
{
    "event": {
        "id": "event_id",
        "type": "charge:confirmed",
        "data": {
            "id": "charge_id",
            "address": "1A1z7agoat...",
            "timeline": [
                {
                    "status": "confirmed"
                }
            ],
            "payments": [
                {
                    "value": {
                        "crypto": {
                            "amount": "0.00234"
                        }
                    },
                    "transaction_id": "abc123..."
                }
            ]
        }
    }
}
```

**Response:**
```json
{
    "success": true
}
```

---

#### 2. NOWPayments Webhook
```
POST /webhooks/nowpayments
```

**Description:** Handle NOWPayments webhook events

**Headers:**
```
X-Nowpayments-Signature: <signature>
```

**Payload:**
```json
{
    "payment_id": 12345,
    "payment_status": "finished",
    "pay_amount": "0.00234",
    "pay_currency": "BTC",
    "tx_hash": "abc123..."
}
```

**Response:**
```json
{
    "success": true
}
```

---

### Admin Endpoints

#### 1. List Gateways
```
GET /admin/crypto-gateways
```

**Description:** List all crypto payment gateways

**Response:** HTML admin panel

---

#### 2. Create Gateway
```
GET /admin/crypto-gateways/create
POST /admin/crypto-gateways
```

**Description:** Create a new gateway

**Request Body:**
```json
{
    "name": "Coinbase Commerce",
    "slug": "coinbase-commerce",
    "description": "Accept crypto via Coinbase",
    "api_endpoint": "https://api.commerce.coinbase.com",
    "webhook_endpoint": "https://yourapp.com/webhooks/coinbase-commerce",
    "supported_currencies": ["BTC", "ETH"],
    "is_active": true,
    "priority": 1,
    "transaction_fee_percentage": 1.0,
    "min_transaction_amount": 0.01,
    "max_transaction_amount": null,
    "confirmation_required": 1,
    "api_key": "your_api_key",
    "webhook_secret": "your_webhook_secret"
}
```

---

#### 3. Edit Gateway
```
GET /admin/crypto-gateways/{gateway}/edit
PUT /admin/crypto-gateways/{gateway}
```

**Description:** Edit gateway configuration

---

#### 4. Delete Gateway
```
DELETE /admin/crypto-gateways/{gateway}
```

**Description:** Delete a gateway

---

#### 5. View Transactions
```
GET /admin/crypto-gateways/{gateway}/transactions
```

**Description:** View all transactions for a gateway

**Query Parameters:**
- `page` (optional): Page number (default: 1)

**Response:** HTML transaction list

---

#### 6. View Webhook Logs
```
GET /admin/crypto-gateways/{gateway}/webhook-logs
```

**Description:** View webhook logs for a gateway

**Query Parameters:**
- `page` (optional): Page number (default: 1)

**Response:** HTML webhook log list

---

## Data Models

### CryptoGateway
```json
{
    "id": 1,
    "name": "Coinbase Commerce",
    "slug": "coinbase-commerce",
    "description": "Accept crypto via Coinbase",
    "api_endpoint": "https://api.commerce.coinbase.com",
    "webhook_endpoint": "https://yourapp.com/webhooks/coinbase-commerce",
    "supported_currencies": ["BTC", "ETH"],
    "is_active": true,
    "priority": 1,
    "transaction_fee_percentage": 1.0,
    "min_transaction_amount": 0.01,
    "max_transaction_amount": null,
    "confirmation_required": 1,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
}
```

### CryptoTransaction
```json
{
    "id": 1,
    "crypto_gateway_id": 1,
    "order_id": 1,
    "transaction_reference": "TXN-ABC123DEF456",
    "gateway_transaction_id": "charge_id",
    "payment_address": "1A1z7agoat...",
    "cryptocurrency": "BTC",
    "crypto_amount": "0.00234",
    "fiat_amount": "100.00",
    "fiat_currency": "USD",
    "exchange_rate": 42735.04,
    "status": "confirmed",
    "confirmations": 3,
    "required_confirmations": 1,
    "transaction_hash": "abc123...",
    "amount_received": "0.00234",
    "expires_at": "2024-01-15T12:30:00Z",
    "confirmed_at": "2024-01-15T12:15:00Z",
    "webhook_status": "verified",
    "webhook_verified_at": "2024-01-15T12:15:00Z",
    "created_at": "2024-01-15T11:30:00Z",
    "updated_at": "2024-01-15T12:15:00Z"
}
```

### Order
```json
{
    "id": 1,
    "order_number": "ORD-001",
    "user_id": 1,
    "total_amount": "100.00",
    "currency": "USD",
    "status": "paid",
    "payment_method": "crypto",
    "description": "Order description",
    "paid_at": "2024-01-15T12:15:00Z",
    "expires_at": "2024-01-15T12:30:00Z",
    "created_at": "2024-01-15T11:30:00Z",
    "updated_at": "2024-01-15T12:15:00Z"
}
```

### PaymentLog
```json
{
    "id": 1,
    "crypto_transaction_id": 1,
    "action": "create_payment",
    "status": "success",
    "message": "Payment request created",
    "data": {},
    "ip_address": "192.168.1.1",
    "created_at": "2024-01-15T11:30:00Z",
    "updated_at": "2024-01-15T11:30:00Z"
}
```

### WebhookLog
```json
{
    "id": 1,
    "crypto_gateway_id": 1,
    "event_type": "charge:confirmed",
    "gateway_webhook_id": "event_id",
    "payload": {},
    "signature": "signature_hash",
    "signature_verified": true,
    "status": "processed",
    "error_message": null,
    "processed_at": "2024-01-15T12:15:00Z",
    "created_at": "2024-01-15T12:15:00Z",
    "updated_at": "2024-01-15T12:15:00Z"
}
```

---

## Payment Statuses

| Status | Description |
|--------|-------------|
| `pending` | Waiting for payment to be sent |
| `waiting_confirmation` | Payment received, waiting for blockchain confirmation |
| `partially_paid` | Partial payment received |
| `confirmed` | Payment fully confirmed |
| `failed` | Payment failed |
| `expired` | Payment request expired |
| `refunded` | Payment refunded |

---

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Webhook signature verification failed |
| 403 | Forbidden | User not authorized (admin only) |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation error |
| 500 | Server Error | Internal server error |

---

## Rate Limiting

Payment endpoints are rate limited to prevent abuse:

```
60 requests per minute per IP
```

---

## Authentication

All endpoints except webhooks require authentication:

```
Authorization: Bearer {token}
```

Webhooks use signature verification instead of bearer tokens.

---

## CORS

CORS is not enabled by default. Configure in `config/cors.php` if needed.

---

## Pagination

List endpoints support pagination:

```
GET /admin/crypto-gateways/{gateway}/transactions?page=2
```

Default: 20 items per page

---

## Sorting

Transactions are sorted by creation date (newest first).

---

## Filtering

Webhook logs can be filtered by status:

```
GET /admin/crypto-gateways/{gateway}/webhook-logs?status=failed
```

---

## Examples

### Create Payment with cURL
```bash
curl -X POST https://yourapp.com/payments/create/1 \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: token" \
  -d '{
    "gateway_id": 1,
    "cryptocurrency": "BTC"
  }'
```

### Check Status with JavaScript
```javascript
fetch('/payments/check-status/1', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Handle Webhook with Node.js
```javascript
const crypto = require('crypto');

function verifySignature(payload, signature, secret) {
    const hash = crypto
        .createHmac('sha256', secret)
        .update(JSON.stringify(payload))
        .digest('hex');
    return hash === signature;
}
```
