# Test Files

This folder contains test pages for the Study is Funny educational platform.

## Test Files

### `test-grade-qr.html`
- **Purpose**: Test QR code generation and scanning functionality
- **Usage**: Access via http://localhost:8000/tests/test-grade-qr.html
- **npm script**: `npm run test-qr`

### `test-mongodb.html`
- **Purpose**: Test MongoDB Atlas Data API connectivity
- **Usage**: Access via http://localhost:8000/tests/test-mongodb.html
- **npm script**: `npm run test-mongo`

## Running Tests

### Using npm scripts:
```bash
# Test QR functionality
npm run test-qr

# Test MongoDB connection
npm run test-mongo
```

### Manual access:
- Open http://localhost:8000/tests/test-grade-qr.html
- Open http://localhost:8000/tests/test-mongodb.html

## Development Notes

- These test files are isolated from the main application
- They help verify specific functionality before integration
- Test files can be safely modified or removed during development