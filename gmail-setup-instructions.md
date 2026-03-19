# Gmail SMTP Setup Instructions

## Issue: Email not sending despite 200 response

This usually means PHP is executing but SMTP authentication is failing.

## Gmail Setup Steps:

### 1. Enable 2-Factor Authentication
- Go to: https://myaccount.google.com/security
- Enable "2-Step Verification"

### 2. Generate App Password
- Go to: https://myaccount.google.com/apppasswords
- Select "Mail" for app
- Select "Other (Custom name)" and enter "HFABS Password Reset"
- Click "Generate"
- Copy the 16-character password (format: xxxx xxxx xxxx xxxx)

### 3. Update Email Configuration
In `backend/config/email.php`:
```php
'password' => 'your-16-character-app-password',  // Use App Password, NOT regular password
```

### 4. Check Gmail Settings
- Make sure "Allow less secure apps" is OFF (you don't need this with App Passwords)
- Check if Gmail is blocking suspicious login attempts

## Common Issues:

### "Authentication failed" or "535-5.7.8 Username and Password not accepted"
- **Cause**: Using regular password instead of App Password
- **Fix**: Generate and use App Password

### "Could not connect to SMTP host"
- **Cause**: Firewall, network issues, or wrong port
- **Fix**: Check port 587 is open, try SSL on port 465

### "Connection timed out"
- **Cause**: Network connectivity or Gmail blocking
- **Fix**: Check internet connection, try different network

## Testing:

1. Run the test script: `http://localhost/HFABS/test-email.php`
2. Check the PHP error logs: `C:\xampp\apache\logs\error.log`
3. Check the detailed SMTP debug output

## Debug Steps:

1. **Check PHP Error Log**:
   ```
   tail -f C:\xampp\apache\logs\error.log
   ```

2. **Test with Debug Script**:
   Visit: `http://localhost/HFABS/test-email.php`

3. **Check Gmail Activity**:
   - Go to: https://myaccount.google.com/activity
   - Look for suspicious login attempts

## Alternative SMTP Providers:

If Gmail doesn't work, try:
- **SendGrid**: Free tier available
- **Mailgun**: Free tier available  
- **Outlook/Hotmail**: Similar App Password setup

## Quick Fix:

Most likely the issue is using your regular Gmail password instead of an App Password.

1. Generate App Password: https://myaccount.google.com/apppasswords
2. Update `backend/config/email.php` with the App Password
3. Test again with: `http://localhost/HFABS/test-email.php`
