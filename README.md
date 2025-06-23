# WinnerBreak - Billiard League Management System

A comprehensive web application for managing billiard leagues, tournaments, and player ratings. Built with Laravel 12,
Vue 3, and modern web technologies to provide a seamless experience for players, league administrators, and tournament
organizers.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Development](#development)
- [Project Structure](#project-structure)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

## Overview

WinnerBreak is a full-stack billiard league management platform that enables clubs and communities to organize leagues,
tournaments, and track player statistics. The system features an advanced ELO-based rating system, real-time match
tracking, and comprehensive tournament management capabilities.

## Features

### League Management

- **Multiple League Support**: Create and manage different leagues for Pool, Pyramid, Snooker, and other billiard games
- **Customizable Rating Systems**: Configure ELO-based ratings with custom rules for winners and losers
- **Player Rankings**: Automatic position calculations with challenge restrictions (±10 positions)
- **Match Management**: Challenge system with invitation workflow and result confirmation

### Tournament System

- **Flexible Formats**: Support for single elimination, double elimination, round-robin, and hybrid formats
- **Team Tournaments**: Organize team-based competitions with customizable team sizes
- **Bracket Generation**: Automatic bracket creation with seeding options
- **Live Updates**: Real-time tournament progress tracking and table widgets

### Multiplayer Games

- **Killer Pool**: Special game mode with lives system and elimination mechanics
- **Card System**: Strategic gameplay with Skip Turn, Pass Turn, and Hand Shot cards
- **Prize Distribution**: Configurable prize pools with percentage-based distribution
- **Time Fund Management**: Table reservation system with penalty fees

### User Features

- **Player Profiles**: Personal statistics, match history, and rating progression
- **Club Affiliations**: Connect with local clubs and cities
- **Mobile Responsive**: Optimized for on-the-go access
- **Multi-language Support**: Built-in translation system

### Administrative Tools

- **Player Approval**: New registration confirmation system
- **League Configuration**: Detailed settings for rating rules and match parameters
- **Tournament Management**: Full control over tournament structure and progression
- **Analytics Dashboard**: Comprehensive statistics and reporting

## Technology Stack

### Backend

- **PHP 8.4** with latest language features
- **Laravel 12** - Modern PHP framework
- **Laravel Sanctum** - API authentication
- **Domain-Driven Design** - Modular architecture
- **RESTful API** with DTO pattern

### Frontend

- **Vue 3** with Composition API
- **TypeScript** - Type-safe development
- **Inertia.js** - Modern monolith architecture
- **Tailwind CSS 4** - Utility-first styling
- **Vite** - Lightning-fast build tool
- **Pinia** - State management

### Infrastructure

- **Database**: MySQL/MariaDB (SQLite for development)
- **Queue System**: Database driver (Redis ready)
- **Cache**: Database driver (Redis ready)
- **Session**: Database storage

### Development Tools

- **Pest** - Modern PHP testing framework
- **ESLint** - JavaScript linting
- **Prettier** - Code formatting
- **Scribe** - API documentation generator

## Requirements

- PHP >= 8.4
- Composer >= 2.0
- Node.js >= 22.0.0
- NPM >= 11.0.0
- MySQL >= 8.0 or MariaDB >= 10.3

## Installation

1. **Clone the repository**
```bash
git clone https://github.com/KovalovD/billiard_web_app.git
cd billiard_web_app
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install JavaScript dependencies**
```bash
npm install
```

4. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configure database**
   Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=winnerbreak
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. **Run migrations and seeders**
```bash
php artisan migrate --seed
```

7. **Generate Ziggy routes**
```bash
php artisan ziggy:generate
```

8. **Start development servers**

```bash
# Using the combined command
composer dev

# Or run separately
php artisan serve
npm run dev
```

## Development

### Available Commands

```bash
# Development
composer dev              # Run all services (server, queue, logs, vite)
composer dev:ssr         # Run with Server-Side Rendering
npm run dev              # Start Vite dev server
php artisan serve        # Start Laravel server

# Building
npm run build            # Build for production
npm run build:ssr        # Build with SSR

# Code Quality
npm run lint             # Run ESLint
npm run format           # Format code with Prettier
npm run format:check     # Check formatting
composer test            # Run PHP tests

# Database
php artisan migrate      # Run migrations
php artisan db:seed      # Seed database
php artisan migrate:fresh --seed  # Fresh install

# API Documentation
php artisan scribe:generate  # Generate API docs
```

### Development Workflow

1. **Feature Development**
    - Create feature branch from `main`
    - Follow domain-driven design principles
    - Write tests for new functionality
    - Ensure code passes linting and formatting

2. **Code Style**
    - PHP: PSR-12 with Laravel conventions
    - JavaScript/TypeScript: ESLint + Prettier
    - Vue: Composition API with TypeScript
    - Use translation function `t('')` for all UI text

3. **Testing**
    - Unit tests with Pest
    - Feature tests for API endpoints
    - Browser testing for critical flows

## Project Structure

```
billiard_web_app/
├── app/
│   ├── Auth/              # Authentication logic
│   ├── Core/              # Core models and services
│   ├── Leagues/           # League management domain
│   ├── Matches/           # Match and game logic
│   ├── OfficialRatings/   # Official rating system
│   ├── Tournaments/       # Tournament management
│   └── User/              # User profiles and cities
├── database/
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── resources/
│   ├── js/
│   │   ├── components/    # Vue components
│   │   ├── composables/   # Composition API utilities
│   │   ├── layouts/       # Page layouts
│   │   ├── pages/         # Inertia pages
│   │   ├── stores/        # Pinia stores
│   │   └── types/         # TypeScript definitions
│   └── css/               # Stylesheets
├── routes/
│   ├── web.php            # Web routes
│   └── api.php            # API routes
├── tests/                 # Test suites
└── public/                # Public assets
```

### Key Directories

- **app/**: Domain-driven modules with models, services, and controllers
- **resources/js/**: Vue 3 frontend with TypeScript
- **database/**: Migrations and seeders for data structure
- **routes/**: Separated web and API route definitions

## API Documentation

API documentation is auto-generated using Scribe and available at `/docs` when running locally.

Generate documentation:

```bash
php artisan scribe:generate
```

Key API endpoints:

- `/api/auth/*` - Authentication endpoints
- `/api/leagues/*` - League management
- `/api/matches/*` - Match operations
- `/api/tournaments/*` - Tournament management
- `/api/users/*` - User profiles

## Testing

Run the test suite:
```bash
# Run all tests
composer test

# Run specific test file
php artisan test tests/Feature/LeagueTest.php

# Run with coverage
php artisan test --coverage
```

## Deployment

### Production Build

1. **Build assets**

```bash
npm run build
```

2. **Optimize Laravel**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

3. **Database**

```bash
php artisan migrate --force
```

### Environment Variables

Key production environment variables:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Configure database, cache, queue drivers
# Set up mail configuration
# Configure Telegram bot integration (if used)
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Contribution Guidelines

- Follow existing code style and patterns
- Write tests for new features
- Update documentation as needed
- Ensure all tests pass before submitting PR
- Use meaningful commit messages

## License

This project is licensed under the Business Source License 1.1 (BUSL-1.1).

- **Change Date**: May 19, 2029
- **Change License**: GPL-2.0-or-later

Until the Change Date, commercial use requires a separate license agreement. After the Change Date, the software becomes
available under GPL-2.0-or-later.

See [LICENSE](LICENSE) file for full details.

---

**Project Lead**: Dmytro Kovalov  
**Repository**: [github.com/KovalovD/billiard_web_app](https://github.com/KovalovD/billiard_web_app)
