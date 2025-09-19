# Send-It 📧

A modern, scalable message scheduling and delivery platform built with Laravel 12, Vue 3, and Inertia.js.

## Installation

### 🐳 Docker

### Monotilhic Mode

For testing or small scale usage, you can run Send-It in a monolithic mode where both the web server and worker run in a single container.
1. Create environment file:
```bash
cp .env.example .env
```

> don't forget to edit the `.env` file to set your database and other configurations

#### Monolithic Mode

```bash
git clone
# Execute the following command to run the monolithic container (server + worker)
docker run -d \
  --name send-it-app \
  -p 8000:80 \
  -e CONTAINER_TYPE=monolith \
  --env-file .env
  -f ./docker/Dockerfile .
```
```bash
```

## ✨ Features

- **📅 Message Scheduling**: Schedule messages for future delivery with precise timing
- **🔌 Provider Integration**: Support for multiple message providers with configurable rate limits per providers
- **🏷️ Tag Management**: Organize and categorize messages with custom tags
- **📊 Event Tracking**: Comprehensive event logging for message lifecycle tracking
- **⚡ Queue Processing**: Asynchronous message processing with job queues
- **🔄 Retry Logic**: Built-in retry mechanism for failed message deliveries
- **🎛️ Admin Dashboard**: Full administrative interface for managing messages and providers
- **🔐 Authentication**: Complete user authentication and authorization system
- **📱 Responsive UI**: Modern, responsive interface built with Tailwind CSS v4

## 🛠️ Tech Stack

### Stack
- **Laravel 12** - Latest Laravel framework
- **Inertia.js Laravel** - Server-side rendering adapter
- **Vue 3** - Frontend framework
- **SQLite** - Lightweight database for development

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or your preferred database)

## 🏃 Running the Application

### Development
```bash
# Start the Laravel development server
composer dev
```

## 📊 Database Schema

The application includes several key models:

- **Messages**: Core message entities with scheduling, status tracking, and provider relationships
- **Message Providers**: Configurable delivery providers with rate limiting
- **Tags**: Message categorization system
- **Message Events**: Comprehensive event logging for audit trails
- **Users**: Authentication and user management

## 🔌 API Usage

### Send a Message

```bash
POST /api/messages
Content-Type: application/json

{
  "recipient": "user@example.com",
  "subject": "Hello World",
  "body": "This is a test message",
  "scheduled_at": "2024-12-25 10:00:00",
  "tags": ["welcome", "promotion"],
  "provider_slug": "smtp-provider"
}
```

## 🎛️ Admin Features

Access the admin panel at `/admin` to:

- **📧 Email Management** (`/admin/emails`): View and manage all messages
- **🔌 Provider Management** (`/admin/providers`): Configure message providers and rate limits
- **📊 Analytics**: Track message delivery statistics and performance

## ⚙️ Configuration

### Message Providers

Configure message providers in the admin panel or via database seeding. Each provider supports:

- Custom rate limiting (messages per minute)
- Provider-specific configuration
- Default provider designation

### Queue Configuration

The application uses Laravel's queue system for asynchronous message processing. Configure your queue driver in `.env`:

```env
QUEUE_CONNECTION=database  # or redis, sqs, etc.
```

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test types
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

## 🔧 Development

### Code Style

The project uses Laravel Pint for PHP code formatting:

```bash
# Fix code style issues
vendor/bin/pint

# Check for style issues only
vendor/bin/pint --test
```

For frontend code formatting:

```bash
# Format JavaScript/Vue files
npm run format

# Check formatting
npm run format:check

# Lint JavaScript/Vue files
npm run lint
```

### File Structure

```
app/
├── Http/Controllers/
│   ├── Api/MessageController.php    # API endpoints
│   └── Admin/                       # Admin controllers
├── Jobs/                           # Queue jobs
│   ├── ScheduleMessageJob.php
│   └── SendMessageJob.php
├── Models/                         # Eloquent models
└── Services/Message/               # Business logic

resources/
├── js/                            # Vue components
└── views/                         # Inertia pages

database/
├── migrations/                    # Database migrations
├── factories/                     # Model factories
└── seeders/                      # Database seeders
```

## 📝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes and add tests
4. Run the test suite (`php artisan test`)
5. Format your code (`vendor/bin/pint && npm run format`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## 📄 License

This project is licensed under the **GNU General Public License v3 (GPL-3.0)**.

### You are free to:
- Use this software for **commercial or non-commercial purposes**.
- Modify and redistribute the software, **as long as any modified version is also released under the GPL-3.0 license**.

### You may not:
- Sell a modified version under a different license.
- Use this code in a proprietary project without releasing your modifications under the GPL-3.0.

### Full License Text
See the [LICENSE](LICENSE) file for the complete legal terms.

---
**Note:** This license ensures that all improvements remain open and accessible to the community.
## 🤝 Support

If you encounter any issues or have questions:

1. Check the Issues page
2. Create a new issue with detailed information
3. Include steps to reproduce any bugs

## 🙏 Acknowledgments

Built with the amazing Laravel ecosystem:

- [Laravel Framework](https://laravel.com)
- [Inertia.js](https://inertiajs.com)
- [Vue.js](https://vuejs.org)
- [Tailwind CSS](https://tailwindcss.com)

---

**Send-It** - Making message scheduling simple and reliable 🚀
