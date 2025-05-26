# Performance Testing Endpoints

The PMPro Toolkit now includes performance testing REST API endpoints that can be used to test and monitor site performance.

## Configuration

1. Go to **Memberships > Toolkit > Toolkit Options**
2. Find the "Performance Testing Endpoints" setting in the Gateway/Checkout Debugging section
3. Choose from three options:
   - **No**: Endpoints are disabled (default)
   - **Read Only**: Endpoints are enabled for read-only performance tests
   - **Read and Write**: Endpoint are enabled for both read and write operations (⚠️ **TESTING ONLY**)

## Usage

### Endpoint URLs
```
GET/POST /wp-json/toolkit/v1/performance-test
```

```
GET/POST /wp-json/toolkit/v1/test-login
```

```
GET/POST /wp-json/toolkit/v1/test-checkout
```

```
GET/POST /wp-json/toolkit/v1/test-change-level
```


### Read-Only Mode (GET)
Safe for production use. Returns performance metrics without modifying any data.

**Basic request:**
```bash
curl "https://yoursite.com/wp-json/toolkit/v1/performance-test"
```

**Detailed request:**
```bash
curl "https://yoursite.com/wp-json/toolkit/v1/performance-test?detailed=true"
```

**Response includes:**
- Site information (WordPress version, PHP version, PMPro version)
- Database query performance (user count, post count, PMPro member count)
- Memory usage and processing time
- PMPro-specific metrics (if detailed=true)

### Read and Write Mode (POST)
⚠️ **WARNING: Only use on development/testing sites!**

This mode performs write operations to test database performance:
- Creates and immediately deletes test posts
- Creates, reads, and deletes test options
- Measures write operation performance

```bash
curl -X POST "https://yoursite.com/wp-json/toolkit/v1/performance-test"
```

## Rate Limiting

The endpoint is rate-limited to 100 requests per minute per IP address to prevent abuse.

## Security

- No authentication required, but rate-limited by IP
- Read-only mode is safe for production
- Write mode should only be used on development/testing environments
- All write operations are immediately cleaned up (test data is deleted)

## Example Response

```json
{
  "success": true,
  "mode": "read_only",
  "detailed": false,
  "data": {
    "site_info": {
      "site_name": "My Site",
      "wp_version": "6.4",
      "php_version": "8.1.0",
      "timestamp": "2024-01-01 12:00:00",
      "pmpro_version": "3.0"
    },
    "database_test": {
      "users_count": 150,
      "posts_count": 250,
      "pmpro_active_members": 75,
      "pmpro_levels_count": 3,
      "query_time_ms": 2.5
    },
    "memory_test": {
      "array_size": 1000,
      "processing_time_ms": 1.2
    },
    "performance": {
      "total_execution_time_ms": 15.8,
      "memory_used_kb": 128.5,
      "peak_memory_kb": 2048.3
    }
  }
}
```
