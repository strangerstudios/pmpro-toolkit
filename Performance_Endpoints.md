# Performance Testing Endpoints

The PMPro Toolkit now includes performance testing REST API endpoints that can be used to test and monitor site performance.

## Configuration

1. Go to **Memberships > Toolkit > Toolkit Options**
2. Find the "Performance Testing Endpoints" setting
3. Choose from three options:
   - **No**: Endpoints are disabled (default)
   - **Read Only**: Endpoints are enabled for read-only performance tests
   - **Read and Write**: Endpoints are enabled for both read and write operations (⚠️ **TESTING ONLY**)

## Available Endpoints

### General Performance Testing
```
GET/POST /wp-json/toolkit/v1/test-general
```

### Authentication & Account Testing
```
POST /wp-json/toolkit/v1/test-login
POST /wp-json/toolkit/v1/test-account-page
```

### Membership Management Testing
```
POST /wp-json/toolkit/v1/test-checkout
POST /wp-json/toolkit/v1/test-change-level
POST /wp-json/toolkit/v1/test-cancel-level
```

### Report & Export Testing
```
POST /wp-json/toolkit/v1/test-report
POST /wp-json/toolkit/v1/test-member-export
POST /wp-json/toolkit/v1/test-search
```

## Usage

### Read-Only Mode (GET)
Safe for production use. Returns performance metrics without modifying any data.

**Basic request:**
```bash
curl "https://yoursite.com/wp-json/toolkit/v1/test-general"
```

**Detailed request:**
```bash
curl "https://yoursite.com/wp-json/toolkit/v1/test-general?detailed=true"
```

**Response includes:**
- Site information (WordPress version, PHP version, PMPro version)
- Database query performance (user count, post count, PMPro member count)
- Memory usage and processing time
- PMPro-specific metrics (if detailed=true)

### Read and Write Mode (POST)
⚠️ **WARNING: Only use on development/testing sites!**

This mode performs write operations to test database performance:
- Creates and immediately deletes test posts/users
- Creates, reads, and deletes test options
- Simulates real membership operations
- Measures write operation performance

```bash
curl -X POST "https://yoursite.com/wp-json/toolkit/v1/test-general"
```

### Authentication Required Endpoints

Some endpoints require user authentication:
- `/test-account-page` - Must be logged in
- `/test-cancel-level` - Must be logged in
- `/test-change-level` - User lookup by login/email

### Throttled Endpoints

Most endpoints use IP-based throttling for unauthenticated requests (100 requests per minute per IP).

## Rate Limiting

The endpoints are rate-limited to 100 requests per minute per IP address to prevent abuse.

## Security

- Most endpoints allow unauthenticated access but are rate-limited by IP
- Some endpoints require authentication (account page, cancel level)
- Read-only mode is safe for production
- Write mode should only be used on development/testing environments
- All write operations include cleanup options (test data is deleted)

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
      "metrics": {
        "execution_time_sec": 0.0025,
        "memory_used_kb": 12.5,
        "query_count": 3,
        "query_time_sec": 0.0018
      }
    },
    "memory_test": {
      "array_size": 10000,
      "metrics": {
        "execution_time_sec": 0.0012,
        "memory_used_kb": 128.5,
        "query_count": 0,
        "query_time_sec": 0
      }
    },
    "performance": {
      "total_execution_time_sec": 0.0158,
      "memory_used_kb": 128.5,
      "peak_memory_kb": 2048.3,
      "total_query_count": 3,
      "total_query_time_sec": 0.0018
    }
  }
}
```

## Performance Tracking

All endpoints use the [`PerformanceTrackingTrait`](classes/traits/Performance_Tracking_Trait.php) to provide consistent metrics:

- **Execution Time**: Total time in seconds
- **Memory Usage**: Memory consumed by the operation in KB  
- **Query Count**: Number of database queries executed
- **Query Time**: Total database query time in seconds (requires `SAVEQUERIES` constant)

## Endpoint-Specific Notes

- **test-checkout**: Can generate test users with cleanup option
- **test-change-level**: Requires existing user, supports cleanup to restore original level
- **test-cancel-level**: Requires authentication, supports cleanup to restore membership
- **test-report**: Generates PMPro admin reports (sales, memberships, login)
- **test-member-export**: Simulates member CSV export with filtering options
