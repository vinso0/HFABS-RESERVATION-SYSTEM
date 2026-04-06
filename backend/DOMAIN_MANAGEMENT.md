# Domain Blacklist Management System

This system automatically maintains an up-to-date list of disposable email domains by fetching from the GitHub repository `disposable-email-domains/disposable-email-domains`.

## Features

- **Automatic Updates**: Fetches latest disposable email domains from GitHub
- **Allowlist Approach**: Only allows trusted domains (Gmail, Outlook, Yahoo, .edu.ph, .gov.ph)
- **Dual Protection**: Both frontend and backend validation
- **Cron Job Support**: Automated daily updates
- **Manual Control**: Superadmin can update lists manually
- **Status Monitoring**: Track last update and domain counts

## Setup Instructions

### 1. Cron Job Setup

Add this cron job to run daily (recommended at midnight):

```bash
0 0 * * * /usr/bin/php /path/to/your/backend/cron-update-domains.php >> /path/to/your/backend/logs/domain-updates.log 2>&1
```

### 2. Storage Directory

Create the storage directory if it doesn't exist:

```bash
mkdir -p backend/storage
chmod 755 backend/storage
```

### 3. Manual Update (Superadmin)

As a superadmin, you can:

- **Check Status**: `GET /backend/public/index.php?url=superadmin/domainStatus`
- **Update Manually**: `POST /backend/public/index.php?url=superadmin/updateDomains`

### 4. File Locations

- **Blacklist**: `backend/storage/domains_blacklist.json`
- **Allowlist**: `backend/storage/domains_allowlist.json`
- **Timestamp**: `backend/storage/domains_last_updated.txt`
- **Service**: `backend/app/services/DomainBlacklistService.php`
- **Cron Script**: `backend/cron-update-domains.php`

## How It Works

1. **Automatic Check**: System checks if lists need updating (older than 24 hours)
2. **GitHub Fetch**: Downloads latest disposable domains from:
   `https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/domains.txt`
3. **Local Storage**: Saves both blacklist and allowlist to JSON files
4. **Validation**: Uses these lists for email validation in registration
5. **Fallback**: If GitHub is unreachable, uses cached lists

## API Endpoints

### Domain Status
```
GET /backend/public/index.php?url=superadmin/domainStatus
```

Response:
```json
{
  "success": true,
  "data": {
    "blacklisted_count": 2500,
    "allowed_count": 25,
    "wildcard_domains": [".edu.ph", ".gov.ph"],
    "last_updated": "2026-03-27 10:30:00",
    "needs_update": false,
    "source": "https://github.com/disposable-email-domains/disposable-email-domains"
  }
}
```

### Update Domains
```
POST /backend/public/index.php?url=superadmin/updateDomains
```

Response:
```json
{
  "success": true,
  "blacklisted_count": 2500,
  "allowed_count": 25,
  "message": "Domain lists updated successfully"
}
```

## Allowed Domains

### Exact Match
- Gmail (gmail.com, googlemail.com)
- Outlook (outlook.com, hotmail.com, live.com, msn.com)
- Yahoo (yahoo.com, ymail.com, rocketmail.com)
- iCloud (icloud.com, me.com, mac.com)
- AOL (aol.com)
- Secure Email (protonmail.com, tutanota.com)

### Wildcard Support
- **All .edu.ph domains** (Philippine educational institutions)
- **All .gov.ph domains** (Philippine government institutions)

### Blocked Domains
All domains from the disposable-email-domains GitHub repository, including:
- 10minutemail.com
- mailinator.com
- yopmail.com
- guerrillamail.com
- And 2000+ more...

## Troubleshooting

### If updates fail:
1. Check internet connection
2. Verify GitHub repository is accessible
3. Check file permissions in storage directory
4. Review logs for error messages

### If emails are incorrectly blocked:
1. Check domain status via API
2. Manually update lists
3. Verify domain is in allowlist or wildcard

### Performance Considerations:
- Lists are cached locally (fast access)
- GitHub fetch only happens when needed
- Auto-update runs in background during validation
