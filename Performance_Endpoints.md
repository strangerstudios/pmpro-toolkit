# Performance Testing Endpoints

The PMPro Toolkit includes REST API endpoints that time core membership operations (login, checkout, level changes, reports, exports, search) inside a live WordPress request, so you can profile a site and compare it before and after a change.

Since 1.1.3 every endpoint requires an authenticated user (an administrator, or the logged-in member for the two member endpoints). Use a version of 1.1.3 or later on any site where the endpoints are enabled.

## Configuration

1. Go to **Memberships > Toolkit > Toolkit Options**.
2. Set **Performance Testing Endpoints**:
   - **Disabled** (default): no routes are registered.
   - **Read Only**: routes are registered, but only `GET` requests run. In practice that is `GET /test-general` alone; every other endpoint is `POST` and is refused.
   - **Read and Write**: all endpoints run. **Testing sites only**: several endpoints create users, orders and membership changes.
3. Optional: add `define( 'SAVEQUERIES', true );` to `wp-config.php` to get per-block database time (`db_time_ms`). Without it you still get query counts, but no query timing.

## Authentication

| Endpoints | Who may call them |
|---|---|
| `test-general`, `test-login`, `test-checkout`, `test-change-level`, `test-report`, `test-member-export`, `test-search` | An administrator (`manage_options`) |
| `test-account-page`, `test-cancel-level` | Any logged-in user; the test runs as that user |

Authenticate with HTTP Basic Auth. An Application Password is preferred (Users > Profile > Application Passwords); the Toolkit also accepts the user's regular password over Basic Auth on its own routes. A logged-in browser session with a REST nonce works too.

```bash
curl -u 'admin:xxxx xxxx xxxx xxxx xxxx xxxx' "https://example.com/wp-json/toolkit/v1/test-general?detailed=true"
```

### Allowing unauthenticated requests

`test-general`, `test-login`, `test-checkout` and `test-change-level` can be opened to unauthenticated callers with a filter. Nothing is unauthenticated by default:

```php
add_filter( 'pmpro_toolkit_allow_unauthenticated_requests', '__return_true' );
```

The filter also receives the endpoint instance, so you can open individual endpoints. When it is on, the **Enable IP Throttling** setting limits each IP to 5 requests per 30 seconds (HTTP 429 after that). Throttling applies only to these opened endpoints; authenticated requests are never throttled.

Only use this filter on a testing site. With Read and Write enabled, an opened `test-checkout` lets anyone create users and memberships.

## Endpoints

All routes live under `/wp-json/toolkit/v1/`. Request parameters for `POST` endpoints are sent as JSON.

| Endpoint | Method | Mode | Writes data? | What it times |
|---|---|---|---|---|
| `test-general` | GET | Read Only | No | Counts of users, posts, active members and levels; a memory test; with `detailed=true`, a forced reload of all levels |
| `test-general` | POST | Read and Write | Yes, cleaned up | Creates and deletes a test post, and writes, reads and deletes a test option |
| `test-login` | POST | Read and Write | No | A password check for `username` / `password` (does not call `wp_signon()` or set cookies) |
| `test-account-page` | POST | Read and Write | No | Renders the Membership Account page as the current user |
| `test-checkout` | POST | Read and Write | Yes | A full PMPro checkout for a newly generated user |
| `test-change-level` | POST | Read and Write | Yes | A checkout to a new level for an existing user |
| `test-cancel-level` | POST | Read and Write | Yes | Cancels a level for the current user |
| `test-report` | POST | Read and Write | No | Generates a PMPro admin report |
| `test-member-export` | POST | Read and Write | No | Generates a members list CSV export |
| `test-search` | POST | Read and Write | No | A front-end post search (`WP_Query`) or a member search (`WP_User_Query`) |

### test-general

- `detailed` (bool, optional): also time `pmpro_getAllLevels()` with a forced refresh.

### test-login

- `username` (string, required), `password` (string, required).

### test-account-page

No parameters. Runs as the authenticated user, so authenticate as the member you want to profile.

### test-checkout

- `membership_level` (int, default `1`)
- `gateway` (string, default `check`)
- `skip_gateway` (bool, default `false`): skip remote gateway calls for local profiling.
- `cleanup` (bool, default `false`): delete the generated user and its membership and orders after the run.
- `checkout_date` (MySQL datetime, optional): backdate the checkout.
- `user_login`, `user_email`, `user_pass`, `first_name`, `last_name`, `baddress1`, `bcity`, `bstate`, `bzipcode`, `bphone` (optional): supply test user details instead of generated ones.

```json
{ "membership_level": 2, "gateway": "check", "skip_gateway": true, "cleanup": true }
```

### test-change-level

- `user_login` (string, required): username or email of an existing user.
- `membership_level` (int, required)
- `gateway` (string, default `check`), `skip_gateway` (bool, default `false`)
- `cleanup` (bool, default `false`): restore the user's original level afterwards.

### test-cancel-level

- `membership_level` (int, required): the level to cancel for the authenticated user.
- `cleanup` (bool, default `false`): restore the original level afterwards.

### test-report

- `report` (string, required): `sales`, `memberships`, `login` or `memberslist`.
- Optional filters matching the report screens: `type`, `period`, `month`, `year`, `discount_code`, `level`, `startdate`, `enddate`, `custom_start_date`, `custom_end_date`, `show_parts`, `s`, `l`.

### test-member-export

- Optional filters matching the Members list: `s` (search; supports `*` and `field:value`, e.g. `email:@example.com`), `l` (`all`, a level ID, `oldmembers`, `expired`, `cancelled`), `pn`, `limit`.

### test-search

- `query` (string, required)
- `type` (string, default `post`): `post` or `member`.

## Metrics

Each timed block returns a `metrics` object:

| Key | Meaning |
|---|---|
| `duration_sec` | Wall time of the block |
| `queries_in_block` | Database queries run inside the block |
| `db_time_ms` | Total time of those queries; `null` unless `SAVEQUERIES` is on |
| `block_memory_kb` | Memory allocated inside the block |
| `peak_memory_kb` | Peak memory for the whole request so far |
| `savequeries_on` | Whether `SAVEQUERIES` was on |

`test-general` (GET) returns a block per test plus `overall_request_performance` (`total_execution_time_ms`, `total_memory_used_kb`, `script_peak_memory_kb`):

```json
{
  "success": true,
  "mode": "read_only",
  "detailed_pmpro_requested": false,
  "savequeries_enabled": true,
  "data": {
    "site_info": { "site_name": "My Site", "wp_version": "6.8", "php_version": "8.3.0", "pmpro_version": "3.5" },
    "database_test": {
      "users_count": 150,
      "posts_count": 250,
      "pmpro_active_members": 75,
      "pmpro_levels_count": 3,
      "metrics": { "queries_in_block": 4, "duration_sec": 0.0025, "db_time_ms": 1.8, "block_memory_kb": 12.5, "peak_memory_kb": 20480, "savequeries_on": true }
    },
    "memory_test": { "array_elements_created": 1000, "metrics": { "...": "..." } },
    "overall_request_performance": { "total_execution_time_ms": 15.8, "total_memory_used_kb": 128.5, "script_peak_memory_kb": 20480 }
  }
}
```

## What the numbers do and don't cover

The timings cover only the work inside each endpoint. They exclude WordPress bootstrap, plugin and theme loading, and network time, so a lower `duration_sec` does not by itself mean a faster page. To compare hosts or configurations end to end, also time real page requests (for example, time to first byte for a logged-in member) and use these endpoints to explain where the time goes.
