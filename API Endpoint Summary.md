# API Endpoint Summary

**Notes:**
- Endpoints support **Basic Auth** or **WP App Passwords** for authenticated endpoints.
- Unauthenticated endpoints are currently **IP limit throttled** (a setting/filter will be added later).
- Endpoints work with or without `SAVEQUERIES`, and the returned JSON lets you know if it's on:

````json
{
  "success": true,
  "mode": "read_only",
  "detailed_pmpro_requested": false,
  "savequeries_enabled": true,
  "data": "..."
}
````

- All endpoints share a **PHP Trait**, which includes:
  - `start_performance_tracking()`
  - `end_performance_tracking()`

This makes it easy to measure performance consistently across endpoints.

---

## Endpoints

### `/wp-json/toolkit/v1/test-report`

**Method:** `POST`  
**Description:** Simulates generation of PMPro admin reports (e.g., Sales, Memberships, Login) and collects basic output metrics by requesting the backend CSV export page. Supports various filtering parameters.

**Request Body Example:**
````json
{
  "report": "sales",
  "type": "revenue",
  "period": "daily",
  "month": 5,
  "year": 2025,
  "custom_start_date": "2025-05-01",
  "custom_end_date": "2025-05-31",
  "show_parts": "new_renewals"
}
````

**Request Parameters:**
- `report` (string, required): 'sales', 'memberships', or 'login'
- `type` (string, optional): Sub-type or graph selection
- `period` (string, optional): e.g., 'daily', 'monthly', 'annual'
- `month` (int|string, optional): Month filter
- `year` (int|string, optional): Year filter
- `discount_code` (string, optional): Filter by discount code
- `level` (int|string, optional): Membership level ID or 'all'
- `startdate`, `enddate` (string, optional): Date range (YYYY-MM-DD)
- `custom_start_date`, `custom_end_date` (string, optional): Alternative date range
- `show_parts` (string, optional): Additional sales data breakdown (e.g., 'new_renewals')
- `s` (string, optional): Search query (login report)
- `l` (string|int, optional): Level filter for login report ('all', 1, 2, etc.)

---

### `/wp-json/toolkit/v1/test-login`

**Method:** `POST`  
**Description:** Automates testing of the WordPress login/authentication process, using `wp_signon`. Logs out user immediately after test.

**Request Body Example:**
````json
{
  "username": "pmpro",
  "password": "password"
}
````

**Request Parameters:**
- `username` (string, required): Username/login
- `password` (string, required): User's password

---

### `/wp-json/toolkit/v1/test-checkout`

**Method:** `POST`  
**Description:** Tests membership checkout performance by simulating a user registering and checking out. Can generate or use provided user data.

**Request Body Example:**
````json
{
  "membership_level": 1,
  "gateway": "check",
  "skip_gateway": true,
  "cleanup": true
}
````

**Request Parameters:**
- `membership_level` (int, optional): Membership level ID (default: 1)
- `gateway` (string, optional): Payment gateway (default: 'check')
- `skip_gateway` (bool, optional): Skip remote gateway calls (default: false)
- `cleanup` (bool, optional): Deletes test user after run (default: false)
- Additional optional test user details:
  - `user_login`
  - `user_email`
  - `user_pass`
  - `first_name`
  - `last_name`
  - `baddress1`
  - `bcity`
  - `bstate`
  - `bzipcode`
  - `bphone`

---

### `/wp-json/toolkit/v1/test-cancel-level`

**Method:** `POST`  
**Description:** Simulates cancelling a membership level and profiles performance. Requires authentication.

**Request Body Example:**
````json
{
  "membership_level": 2,
  "cleanup": true
}
````

**Request Parameters:**
- `membership_level` (int, required): Level ID to cancel
- `cleanup` (bool, optional): Restores user's membership level after test (default: false)

---

### `/wp-json/toolkit/v1/test-change-level`

**Method:** `POST`  
**Description:** Simulates changing a membership level for an existing user. Profiles performance.

**Request Body Example:**
````json
{
  "user_login": "testing",
  "membership_level": 2,
  "gateway": "check",
  "skip_gateway": true,
  "cleanup": true
}
````

**Request Parameters:**
- `user_login` (string, required): Username/email
- `membership_level` (int, required): Level ID to change to
- `gateway` (string, optional): Gateway (default: 'check')
- `skip_gateway` (bool, optional): Skip remote calls (default: false)
- `cleanup` (bool, optional): Restores user's membership level after test (default: false)

---

### `/wp-json/toolkit/v1/test-general`

**Methods:**
- `GET`: Read-only mode. Returns site information, database query performance, and memory usage.
- `POST`: Read/write mode (if enabled). Performs actions like creating/deleting test posts/options. **Use only on test sites.**

**Request Parameters:**
- `detailed` (boolean, optional): If true, includes detailed PMPro metrics (default: false)

---

### `/wp-json/toolkit/v1/test-account-page`

**Method:** `POST`  
**Description:** Simulates viewing the Membership Account page for the authenticated user. Requires authentication.

**Request Body Example:**  
*(None; operates on the current user context)*

**Request Parameters:**  
*(None explicitly)*

---


### `/wp-json/toolkit/v1/test-search`

**Method:** `POST`  
**Description:** Simulates a frontend search operation and profiles performance.

**Request Body Example:**
````json
{
  "query": "john",
  "type": "member"
}
````

**Request Parameters:**
- `query` (string, required): Search term to test
- `type` (string, optional): 'post' (default) or 'member'

---

### `/wp-json/toolkit/v1/test-member-export`

**Method:** `POST`  
**Description:** Generates a simulated Paid Memberships Pro member export (CSV). Runs the full PMPro member export script with test parameters (e.g., search string, membership level) and captures performance metrics. No file download is triggered by default.

**Request Body Example:**
````json
{
  "s": "john",
  "l": 2,
  "pn": 1,
  "limit": 100
}
````

**Request Parameters:**
- `s` (string, optional): Search query (username, email, or other usermeta)
- `l` (int|string, optional): Membership level ID, 'oldmembers', or 'all'
- `pn` (int, optional): Page number for paginated export
- `limit` (int, optional): Number of records per page