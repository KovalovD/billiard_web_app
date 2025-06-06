# Billiard League - Game Management System

A comprehensive platform for managing billiard leagues, tournaments, player ratings, and matches. This application
offers a modern web interface for players to join leagues, challenge opponents, track statistics, and participate in
multiplayer game events.

## Overview

This full-stack web application is built with Laravel 12 and Vue 3, providing an intuitive interface for billiard clubs
and leagues. The system features customizable ELO-based rating systems, match challenge mechanics, result confirmations,
and detailed player statistics.

## Key Features

### League Management

- Create multiple leagues for different billiard game types (Pool, Pyramid, Snooker)
- Customizable league settings (rating rules, match configurations, player limits)
- Separate leaderboards and statistics for each league

### Rating System

- ELO-based rating calculations with customizable rule sets
- Position-based rankings with automatic recalculation
- Challenge restrictions based on player positions (±10 positions)
- Admin confirmation required for new player registrations

### Match System

- Challenge system for arranging 1v1 matches
- Match invitation and confirmation workflow
- Score submission with mutual confirmation to prevent disputes
- Statistics tracking for wins, losses, and rating changes

### Multiplayer Games

- Support for "Killer Pool" and other multiplayer game formats
- Player lives tracking and elimination mechanics
- Prize pool distribution with customizable percentages
- Time fund for table reservation with penalty fee system

### User Management

- Registration and authentication
- Profile management with personal statistics
- Club and city affiliations
- Mobile-responsive interface for on-the-go access

### Admin Features

- Player approval and management
- League creation and configuration
- Override capabilities for disputed results
- Game moderation tools

## Technology Stack

- **Backend**: PHP 8.4, Laravel 12, Laravel Sanctum for authentication
- **Frontend**: Vue 3, TypeScript, Tailwind CSS 4, Vite
- **UI Components**: Custom component library built on Tailwind
- **Database**: MySQL/MariaDB (or SQLite for development)
- **Architecture**: Domain-driven design with modular components
- **API**: RESTful API with DTO pattern and Resources

## Project Structure

```
app/
├── Auth/             # Authentication, login, registration
├── Core/             # Base models, middleware, providers
├── Leagues/          # League management, ratings, players
├── Matches/          # Match games, multiplayer games, game logs
└── User/             # User profiles, statistics, cities/clubs

resources/
├── js/
│   ├── components/   # Vue components
│   ├── composables/  # Vue composition API functions
│   ├── layouts/      # Application layouts
│   ├── pages/        # Vue pages corresponding to routes
│   └── types/        # TypeScript type definitions
└── css/              # Tailwind and custom styles
```

## Getting Started

### Prerequisites

- PHP 8.4 or higher
- Composer
- Node.js & npm
- MySQL or SQLite

### Installation

1. Clone the repository

```bash
git clone https://github.com/KovalovD/billiard_web_app.git
cd billiard_web_app
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
# Run in separate terminals, or use the combined command
php artisan serve
npm run dev

# Or use the combined development command
composer dev
```

8. Access the application at `http://localhost:8001`

### Development Commands

```bash
# Run application with all services (server, queue, logs, vite)
composer dev

# Run with SSR (Server-Side Rendering)
composer dev:ssr

# Run tests
composer test

# Lint code
npm run lint

# Format code
npm run format
```

## ELO Rating System

The system uses a customizable ELO rating algorithm with configurable parameters:

- Initial rating value (default: 1000)
- Different rating adjustments based on points difference between players
- Separate rules for winners and losers
- Players can only challenge others within ±10 positions of their rank
- Special rating rules for multiplayer "Killer Pool" games

## Multiplayer Game Features

The Billiard League application supports multiplayer game formats with special rules:

- **Killer Pool**: Players start with a set number of lives and are eliminated when they run out
- **Card System**: Players have special cards (Skip Turn, Pass Turn, Hand Shot) they can use strategically
- **Financial System**: Entrance fees, prize distribution, and time fund for table costs
- **Player Targeting**: Option to allow/disallow targeting other players

## License

This project is licensed under the Business Source License 1.1.

- **Source available** for review, modification, and non-production use
- **Change date**: May 19, 2029 (or when specified version is 4 years old)
- **Change license**: GPL-2.0-or-later (after change date)
- **Commercial use** requires a separate agreement before the change date

See the [LICENSE](LICENSE) file for complete details.

## Contributors

- Dmytro Kovalov - Project Lead

## Acknowledgements

- [Laravel](https://laravel.com) - The PHP framework used
- [Vue.js](https://vuejs.org) - Frontend framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Inertia.js](https://inertiajs.com) - The modern monolith framework
