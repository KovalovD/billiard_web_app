# B2B League Management System

A comprehensive platform for managing billiard leagues, player challenges, and ratings.

## Overview

The B2B League Management System is a web application for billiard enthusiasts to create, join, and participate in various types of billiard leagues. The application offers:

- League management (creation, editing, membership)
- Player rating system with Elo-based calculations
- Match challenges between players
- Match result tracking with rating updates
- User profile and statistics

## Tech Stack

### Backend
- PHP 8.4
- Laravel 12.0
- Laravel Sanctum for API authentication
- Domain-Driven Design (DDD) structure

### Frontend
- Vue.js 3.5
- Inertia.js for SPA-like experience
- TypeScript
- Tailwind CSS
- ShadCN UI components

## Features

### Authentication
- Login/register with email, password
- API token-based authentication
- Session management

### League Management
- Create and configure leagues (admin only)
- Join/leave leagues
- Configure rating calculation rules
- Support for multiple game types (Pool, Pyramid, Snooker)

### Player Rating System
- Elo-based rating system
- Rating changes based on match results
- Player rankings within leagues

### Match System
- Challenge other players
- Accept/decline challenges
- Record match results
- Automatic rating recalculation

### User Profiles
- Personal information management
- Match history and statistics
- League membership tracking

## Project Structure

The application follows a domain-driven design approach with the following structure:

```
app/
├── Auth/         # Authentication functionality
├── Core/         # Base application classes
├── Leagues/      # League management features
└── Matches/      # Match handling functionality
resources/
├── js/
│   ├── Components/    # Reusable Vue components
│   ├── Pages/         # Main application pages
│   ├── Layouts/       # Page layouts
│   ├── Types/         # TypeScript type definitions
│   └── Composables/   # Reusable Vue composables
└── css/            # Tailwind CSS configuration
```

## Getting Started

### Requirements
- PHP 8.4+
- Composer
- Node.js 16+
- MySQL/PostgreSQL

### Installation

1. Clone the repository
   ```
   git clone https://github.com/yourusername/b2b-league.git
   cd b2b-league
   ```

2. Install PHP dependencies
   ```
   composer install
   ```

3. Set up environment
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database connection in `.env`

5. Run migrations and seed database
   ```
   php artisan migrate --seed
   ```

6. Install frontend dependencies
   ```
   npm install
   ```

7. Build frontend assets
   ```
   npm run dev
   ```

8. Start the local server
   ```
   php artisan serve
   ```

## Development

### Useful Commands

- `composer dev` - Run development server with queue listener, PAIL logs, and Vite
- `composer test` - Run all tests
- `npm run build` - Build production assets

### Docker

A Docker setup is included for easy development:

```
docker-compose up -d
```

This will start a PHP 8.4 container with Apache, a MySQL database, and PHPMyAdmin.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
