# B2B League - Pool League Management System

A comprehensive league management system for billiard clubs, allowing players to register, join leagues, challenge
opponents, and track their ratings.

## Overview

B2B League is a full-stack web application built with Laravel 12 and Vue 3, providing an intuitive interface for
billiard leagues management. The system features an ELO-based rating system, match challenges, confirmations, and
comprehensive player statistics tracking.

## Features

- **User Management**: Registration, authentication, and profile management
- **League System**: Create and manage multiple leagues with different game types
- **Rating System**: ELO-based player rating calculations
- **Match Management**: Challenge players, accept/decline matches, submit and confirm results
- **Admin Panel**: Manage players, confirm registrations, and oversee leagues
- **Statistics**: Track player performance, win rates, and rating changes

## Technologies

- **Backend**: PHP 8.4, Laravel 12, Laravel Sanctum
- **Frontend**: Vue 3, TypeScript, Tailwind CSS, Vite
- **Database**: MySQL/MariaDB (or SQLite for local development)
- **API**: RESTful API with proper DTO pattern and Resources

## Project Structure

The application follows a modular architecture with domain-specific components:

- **Auth Module**: Handles authentication, registration, and user sessions
- **Core Module**: Contains shared functionality, base models, and middleware
- **Leagues Module**: Manages leagues, ratings, and player rankings
- **Matches Module**: Handles match creation, results, and rating calculations
- **User Module**: User profiles, statistics, and preferences

## Getting Started

### Prerequisites

- PHP 8.4 or higher
- Composer
- Node.js & npm
- MySQL or SQLite

### Installation

1. Clone the repository

```bash
git clone https://github.com/yourusername/b2b-league.git
cd b2b-league
```

2. Install PHP dependencies

```bash
composer install
```

3. Install JavaScript dependencies

```bash
npm install
```

4. Copy environment file and generate application key

```bash
cp .env.example .env
php artisan key:generate
```

5. Configure database settings in `.env` file

6. Run database migrations and seed data

```bash
php artisan migrate --seed
```

7. Start development server

```bash
# Run in separate terminals
php artisan serve
npm run dev
```

8. Access the application at `http://localhost:8000`

### Default Admin Account

- Email: kovalov@b2bleague.com
- Password: nudik_number_one

## Development

### Development Commands

```bash
# Run application with all services
composer dev

# Run tests
composer test

# Lint code
npm run lint

# Format code
npm run format
```

### Directory Structure

- `app/` - Application core code
    - `Auth/` - Authentication modules
    - `Core/` - Core functionality
    - `Leagues/` - League management
    - `Matches/` - Match handling
    - `User/` - User profiles and statistics
- `resources/` - Frontend code and assets
    - `js/` - Vue components and TypeScript code
    - `css/` - Stylesheets
    - `views/` - Blade templates

## API Documentation

The API follows RESTful conventions with the following main endpoints:

- `/api/auth/*` - Authentication endpoints
- `/api/leagues/*` - League management
- `/api/user/*` - User data and statistics

## ELO Rating System

The system uses a customizable ELO rating algorithm with configurable parameters:

- Initial rating value (default: 1000)
- Rating change rules for different rating differences
- Position-based challenge restrictions (within ±10 positions)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- [Laravel](https://laravel.com) - The PHP framework used
- [Vue.js](https://vuejs.org) - Frontend framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Inertia.js](https://inertiajs.com) - The modern monolith framework

## License
Released under the Business Source License 1.1 (see LICENSE).
Commercial/production use requires a separate agreement with the author.
