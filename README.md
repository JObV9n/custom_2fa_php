# Custom 2FA - TOTP Two-Factor Authentication

A lightweight PHP implementation of Time-Based One-Time Password (TOTP) authentication with a clean web interface for testing and verification.

## Features

- **Pure PHP Implementation** - No external dependencies for TOTP generation
- **RFC 6238 Compliant** - Standard TOTP implementation using SHA1
- **QR Code Generation** - Scan with any authenticator app
- **Hot Reload** - Automatic browser refresh on file changes during development
- **Clean UI** - Modern, responsive interface

## Requirements

- PHP 8.2 or higher
- Composer

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd custom_2fa
```

2. Install dependencies:
```bash
composer install
```

3. Start the development server:
```bash
composer serve
```

4. Open your browser and navigate to:
```
http://localhost:8089
```

## Usage

### Basic Usage

1. **Generate Secret**: The application automatically generates a unique secret key when you first visit
2. **Scan QR Code**: Use any authenticator app (Google Authenticator, Microsoft Authenticator, Authy, etc.) to scan the QR code
3. **Enter Code**: Type the 6-digit code from your authenticator app
4. **Verify**: Click "Verify Code" to test authentication

### Authenticator Apps Compatible

- Google Authenticator
- Microsoft Authenticator
- Authy
- 1Password
- LastPass Authenticator
- Any RFC 6238 compliant TOTP app

## Security Considerations

 **Important**: This is a demonstration/testing application. For production use:

## TOTP Specification

- **Algorithm**: HMAC-SHA1
- **Digits**: 6
- **Time Step**: 30 seconds
- **Window**: ±1 step (90 seconds total validity)

## Author
**jobv9n**