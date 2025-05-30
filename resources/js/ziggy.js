const Ziggy = {
    "url": "http:\/\/localhost:8001", "port": 8001, "defaults": {}, "routes": {
        "multiplayer-games.index": {
            "uri": "api\/leagues\/{league}\/multiplayer-games",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "multiplayer-games.show": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}",
            "methods": ["GET", "HEAD"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.join": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/join",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.leave": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/leave",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.action": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/action",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.finish": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/finish",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.set-moderator": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/set-moderator",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.financial-summary": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/financial-summary",
            "methods": ["GET", "HEAD"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.rating-summary": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/rating-summary",
            "methods": ["GET", "HEAD"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.store": {
            "uri": "api\/leagues\/{league}\/multiplayer-games",
            "methods": ["POST"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "multiplayer-games.start": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/start",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "multiplayer-games.cancel": {
            "uri": "api\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/cancel",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "auth.user": {"uri": "api\/auth\/user", "methods": ["GET", "HEAD"]},
        "auth.logout": {"uri": "api\/auth\/logout", "methods": ["POST"]},
        "auth.login": {"uri": "api\/auth\/login", "methods": ["POST"]},
        "auth.register": {"uri": "api\/auth\/register", "methods": ["POST"]},
        "admin.search-users": {"uri": "api\/admin\/search-users", "methods": ["GET", "HEAD"]},
        "admin.add-existing-player": {
            "uri": "api\/admin\/leagues\/{league}\/players\/add-existing",
            "methods": ["POST"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "admin.add-new-player": {
            "uri": "api\/admin\/leagues\/{league}\/players\/add-new",
            "methods": ["POST"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "admin.add-existing-player-to-game": {
            "uri": "api\/admin\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/players\/add-existing",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "admin.add-new-player-to-game": {
            "uri": "api\/admin\/leagues\/{league}\/multiplayer-games\/{multiplayerGame}\/players\/add-new",
            "methods": ["POST"],
            "parameters": ["league", "multiplayerGame"],
            "bindings": {"league": "id", "multiplayerGame": "id"}
        },
        "official-ratings.index.api": {"uri": "api\/official-ratings", "methods": ["GET", "HEAD"]},
        "official-ratings.active": {"uri": "api\/official-ratings\/active", "methods": ["GET", "HEAD"]},
        "official-ratings.show.api": {
            "uri": "api\/official-ratings\/{officialRating}",
            "methods": ["GET", "HEAD"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "official-ratings.players": {
            "uri": "api\/official-ratings\/{officialRating}\/players",
            "methods": ["GET", "HEAD"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "official-ratings.tournaments": {
            "uri": "api\/official-ratings\/{officialRating}\/tournaments",
            "methods": ["GET", "HEAD"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "official-ratings.top-players": {
            "uri": "api\/official-ratings\/{officialRating}\/top-players",
            "methods": ["GET", "HEAD"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "official-ratings.player-rating": {
            "uri": "api\/official-ratings\/{officialRating}\/players\/{userId}",
            "methods": ["GET", "HEAD"],
            "parameters": ["officialRating", "userId"],
            "bindings": {"officialRating": "id"}
        },
        "admin.official-ratings.store": {"uri": "api\/admin\/official-ratings", "methods": ["POST"]},
        "admin.official-ratings.update": {
            "uri": "api\/admin\/official-ratings\/{officialRating}",
            "methods": ["PUT"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "admin.official-ratings.destroy": {
            "uri": "api\/admin\/official-ratings\/{officialRating}",
            "methods": ["DELETE"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "admin.official-ratings.add-tournament": {
            "uri": "api\/admin\/official-ratings\/{officialRating}\/tournaments",
            "methods": ["POST"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "admin.official-ratings.remove-tournament": {
            "uri": "api\/admin\/official-ratings\/{officialRating}\/tournaments\/{tournament}",
            "methods": ["DELETE"],
            "parameters": ["officialRating", "tournament"],
            "bindings": {"officialRating": "id", "tournament": "id"}
        },
        "admin.official-ratings.add-player": {
            "uri": "api\/admin\/official-ratings\/{officialRating}\/players",
            "methods": ["POST"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "admin.official-ratings.remove-player": {
            "uri": "api\/admin\/official-ratings\/{officialRating}\/players\/{userId}",
            "methods": ["DELETE"],
            "parameters": ["officialRating", "userId"],
            "bindings": {"officialRating": "id"}
        },
        "admin.official-ratings.recalculate-positions": {
            "uri": "api\/admin\/official-ratings\/{officialRating}\/recalculate",
            "methods": ["POST"],
            "parameters": ["officialRating"],
            "bindings": {"officialRating": "id"}
        },
        "admin.official-ratings.update-from-tournament": {
            "uri": "api\/admin\/official-ratings\/{officialRating}\/update-from-tournament\/{tournament}",
            "methods": ["POST"],
            "parameters": ["officialRating", "tournament"],
            "bindings": {"officialRating": "id", "tournament": "id"}
        },
        "tournaments.index": {"uri": "api\/tournaments", "methods": ["GET", "HEAD"]},
        "tournaments.upcoming": {"uri": "api\/tournaments\/upcoming", "methods": ["GET", "HEAD"]},
        "tournaments.active": {"uri": "api\/tournaments\/active", "methods": ["GET", "HEAD"]},
        "tournaments.completed": {"uri": "api\/tournaments\/completed", "methods": ["GET", "HEAD"]},
        "tournaments.show": {
            "uri": "api\/tournaments\/{tournament}",
            "methods": ["GET", "HEAD"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "tournaments.players": {
            "uri": "api\/tournaments\/{tournament}\/players",
            "methods": ["GET", "HEAD"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "tournaments.results": {
            "uri": "api\/tournaments\/{tournament}\/results",
            "methods": ["GET", "HEAD"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "tournaments.apply": {
            "uri": "api\/tournaments\/{tournament}\/apply",
            "methods": ["POST"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "tournaments.cancel-application": {
            "uri": "api\/tournaments\/{tournament}\/cancel-application",
            "methods": ["DELETE"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "tournaments.application-status": {
            "uri": "api\/tournaments\/{tournament}\/application-status",
            "methods": ["GET", "HEAD"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.store": {"uri": "api\/admin\/tournaments", "methods": ["POST"]},
        "admin.tournaments.update": {
            "uri": "api\/admin\/tournaments\/{tournament}",
            "methods": ["PUT"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.destroy": {
            "uri": "api\/admin\/tournaments\/{tournament}",
            "methods": ["DELETE"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.add-player": {
            "uri": "api\/admin\/tournaments\/{tournament}\/players",
            "methods": ["POST"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.add-new-player": {
            "uri": "api\/admin\/tournaments\/{tournament}\/players\/new",
            "methods": ["POST"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.remove-player": {
            "uri": "api\/admin\/tournaments\/{tournament}\/players\/{player}",
            "methods": ["DELETE"],
            "parameters": ["tournament", "player"],
            "bindings": {"tournament": "id", "player": "id"}
        },
        "admin.tournaments.update-player": {
            "uri": "api\/admin\/tournaments\/{tournament}\/players\/{player}",
            "methods": ["PUT"],
            "parameters": ["tournament", "player"],
            "bindings": {"tournament": "id", "player": "id"}
        },
        "admin.tournaments.pending-applications": {
            "uri": "api\/admin\/tournaments\/{tournament}\/applications\/pending",
            "methods": ["GET", "HEAD"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.all-applications": {
            "uri": "api\/admin\/tournaments\/{tournament}\/applications\/all",
            "methods": ["GET", "HEAD"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.confirm-application": {
            "uri": "api\/admin\/tournaments\/{tournament}\/applications\/{application}\/confirm",
            "methods": ["POST"],
            "parameters": ["tournament", "application"],
            "bindings": {"tournament": "id", "application": "id"}
        },
        "admin.tournaments.reject-application": {
            "uri": "api\/admin\/tournaments\/{tournament}\/applications\/{application}\/reject",
            "methods": ["POST"],
            "parameters": ["tournament", "application"],
            "bindings": {"tournament": "id", "application": "id"}
        },
        "admin.tournaments.bulk-confirm-applications": {
            "uri": "api\/admin\/tournaments\/{tournament}\/applications\/bulk-confirm",
            "methods": ["POST"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.bulk-reject-applications": {
            "uri": "api\/admin\/tournaments\/{tournament}\/applications\/bulk-reject",
            "methods": ["POST"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.set-results": {
            "uri": "api\/admin\/tournaments\/{tournament}\/results",
            "methods": ["POST"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.change-status": {
            "uri": "api\/admin\/tournaments\/{tournament}\/status",
            "methods": ["POST"],
            "parameters": ["tournament"],
            "bindings": {"tournament": "id"}
        },
        "admin.tournaments.search-users": {"uri": "api\/admin\/tournaments\/search-users", "methods": ["GET", "HEAD"]},
        "scribe": {"uri": "docs", "methods": ["GET", "HEAD"]},
        "scribe.postman": {"uri": "docs.postman", "methods": ["GET", "HEAD"]},
        "scribe.openapi": {"uri": "docs.openapi", "methods": ["GET", "HEAD"]},
        "sanctum.csrf-cookie": {"uri": "sanctum\/csrf-cookie", "methods": ["GET", "HEAD"]},
        "leagues.index": {"uri": "api\/leagues", "methods": ["GET", "HEAD"]},
        "leagues.show": {
            "uri": "api\/leagues\/{league}",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "cities.index": {"uri": "api\/cities", "methods": ["GET", "HEAD"]},
        "clubs.index": {"uri": "api\/clubs", "methods": ["GET", "HEAD"]},
        "leagues.store": {"uri": "api\/leagues", "methods": ["POST"]},
        "leagues.update": {
            "uri": "api\/leagues\/{league}",
            "methods": ["PUT", "PATCH"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "leagues.destroy": {
            "uri": "api\/leagues\/{league}",
            "methods": ["DELETE"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "admin.pending-players": {
            "uri": "api\/leagues\/{league}\/admin\/pending-players",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "admin.confirm-player": {
            "uri": "api\/leagues\/{league}\/admin\/confirm-player\/{rating}",
            "methods": ["POST"],
            "parameters": ["league", "rating"],
            "bindings": {"league": "id", "rating": "id"}
        },
        "admin.reject-player": {
            "uri": "api\/leagues\/{league}\/admin\/reject-player\/{rating}",
            "methods": ["POST"],
            "parameters": ["league", "rating"],
            "bindings": {"league": "id", "rating": "id"}
        },
        "admin.bulk-confirm": {
            "uri": "api\/leagues\/{league}\/admin\/bulk-confirm",
            "methods": ["POST"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "admin.confirmed-players": {
            "uri": "api\/leagues\/{league}\/admin\/confirmed-players",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "admin.deactivate-player": {
            "uri": "api\/leagues\/{league}\/admin\/deactivate-player\/{rating}",
            "methods": ["POST"],
            "parameters": ["league", "rating"],
            "bindings": {"league": "id", "rating": "id"}
        },
        "admin.bulk-deactivate": {
            "uri": "api\/leagues\/{league}\/admin\/bulk-deactivate",
            "methods": ["POST"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "my-leagues-and-challenges": {"uri": "api\/my-leagues-and-challenges", "methods": ["POST"]},
        "available-games": {"uri": "api\/available-games", "methods": ["GET", "HEAD"]},
        "leagues.available-games": {"uri": "api\/games", "methods": ["GET", "HEAD"]},
        "profile.update": {"uri": "api\/profile", "methods": ["PUT"]},
        "profile.update-password": {"uri": "api\/profile\/password", "methods": ["PUT"]},
        "profile.destroy": {"uri": "api\/profile", "methods": ["DELETE"]},
        "user.ratings": {"uri": "api\/user\/ratings", "methods": ["GET", "HEAD"]},
        "user.matches": {"uri": "api\/user\/matches", "methods": ["GET", "HEAD"]},
        "user.stats": {"uri": "api\/user\/stats", "methods": ["GET", "HEAD"]},
        "user.game-type-stats": {"uri": "api\/user\/game-type-stats", "methods": ["GET", "HEAD"]},
        "leagues.players": {
            "uri": "api\/leagues\/{league}\/players",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "leagues.games": {
            "uri": "api\/leagues\/{league}\/games",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "leagues.load-user-rating": {
            "uri": "api\/leagues\/{league}\/load-user-rating",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "players.enter": {
            "uri": "api\/leagues\/{league}\/players\/enter",
            "methods": ["POST"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "players.leave": {
            "uri": "api\/leagues\/{league}\/players\/leave",
            "methods": ["POST"],
            "parameters": ["league"],
            "bindings": {"league": "id"}
        },
        "players.send-match-game": {
            "uri": "api\/leagues\/{league}\/players\/{user}\/send-match-game",
            "methods": ["POST"],
            "parameters": ["league", "user"],
            "bindings": {"league": "id", "user": "id"}
        },
        "players.accept-match": {
            "uri": "api\/leagues\/{league}\/players\/match-games\/{matchGame}\/accept",
            "methods": ["POST"],
            "parameters": ["league", "matchGame"],
            "bindings": {"league": "id", "matchGame": "id"}
        },
        "players.decline-match": {
            "uri": "api\/leagues\/{league}\/players\/match-games\/{matchGame}\/decline",
            "methods": ["POST"],
            "parameters": ["league", "matchGame"],
            "bindings": {"league": "id", "matchGame": "id"}
        },
        "players.send-result": {
            "uri": "api\/leagues\/{league}\/players\/match-games\/{matchGame}\/send-result",
            "methods": ["POST"],
            "parameters": ["league", "matchGame"],
            "bindings": {"league": "id", "matchGame": "id"}
        },
        "login": {"uri": "login", "methods": ["GET", "HEAD"]},
        "register": {"uri": "register", "methods": ["GET", "HEAD"]},
        "home": {"uri": "\/", "methods": ["GET", "HEAD"]},
        "profile.edit": {"uri": "profile\/edit", "methods": ["GET", "HEAD"]},
        "profile.stats": {"uri": "profile\/stats", "methods": ["GET", "HEAD"]},
        "dashboard": {"uri": "dashboard", "methods": ["GET", "HEAD"]},
        "leagues.index.page": {"uri": "leagues", "methods": ["GET", "HEAD"]},
        "leagues.show.page": {
            "uri": "leagues\/{league}",
            "methods": ["GET", "HEAD"],
            "wheres": {"league": "[0-9]+"},
            "parameters": ["league"]
        },
        "leagues.multiplayer-games.index": {
            "uri": "leagues\/{leagueId}\/multiplayer-games",
            "methods": ["GET", "HEAD"],
            "parameters": ["leagueId"]
        },
        "leagues.multiplayer-games.show": {
            "uri": "leagues\/{leagueId}\/multiplayer-games\/{gameId}",
            "methods": ["GET", "HEAD"],
            "parameters": ["leagueId", "gameId"]
        },
        "admin.leagues.confirmed-players": {
            "uri": "admin\/leagues\/{league}\/confirmed-players",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"]
        },
        "leagues.create": {"uri": "admin\/leagues\/create", "methods": ["GET", "HEAD"]},
        "leagues.edit": {
            "uri": "admin\/leagues\/{league}\/edit",
            "methods": ["GET", "HEAD"],
            "wheres": {"league": "[0-9]+"},
            "parameters": ["league"]
        },
        "admin.leagues.pending-players": {
            "uri": "admin\/leagues\/{league}\/pending-players",
            "methods": ["GET", "HEAD"],
            "parameters": ["league"]
        },
        "tournaments.index.page": {"uri": "tournaments", "methods": ["GET", "HEAD"]},
        "tournaments.show.page": {
            "uri": "tournaments\/{tournamentId}",
            "methods": ["GET", "HEAD"],
            "wheres": {"tournamentId": "[0-9]+"},
            "parameters": ["tournamentId"]
        },
        "official-ratings.index": {"uri": "official-ratings", "methods": ["GET", "HEAD"]},
        "official-ratings.show": {
            "uri": "official-ratings\/{ratingId}",
            "methods": ["GET", "HEAD"],
            "wheres": {"ratingId": "[0-9]+"},
            "parameters": ["ratingId"]
        },
        "admin.tournaments.create": {"uri": "admin\/tournaments\/create", "methods": ["GET", "HEAD"]},
        "admin.tournaments.edit": {
            "uri": "admin\/tournaments\/{tournamentId}\/edit",
            "methods": ["GET", "HEAD"],
            "wheres": {"tournamentId": "[0-9]+"},
            "parameters": ["tournamentId"]
        },
        "admin.tournaments.players": {
            "uri": "admin\/tournaments\/{tournamentId}\/players",
            "methods": ["GET", "HEAD"],
            "wheres": {"tournamentId": "[0-9]+"},
            "parameters": ["tournamentId"]
        },
        "admin.tournaments.results": {
            "uri": "admin\/tournaments\/{tournamentId}\/results",
            "methods": ["GET", "HEAD"],
            "wheres": {"tournamentId": "[0-9]+"},
            "parameters": ["tournamentId"]
        },
        "admin.tournaments.applications": {
            "uri": "admin\/tournaments\/{tournamentId}\/applications",
            "methods": ["GET", "HEAD"],
            "parameters": ["tournamentId"]
        },
        "admin.official-ratings.create": {"uri": "admin\/official-ratings\/create", "methods": ["GET", "HEAD"]},
        "admin.official-ratings.edit": {
            "uri": "admin\/official-ratings\/{ratingId}\/edit",
            "methods": ["GET", "HEAD"],
            "wheres": {"ratingId": "[0-9]+"},
            "parameters": ["ratingId"]
        },
        "admin.official-ratings.manage": {
            "uri": "admin\/official-ratings\/{ratingId}\/manage",
            "methods": ["GET", "HEAD"],
            "wheres": {"ratingId": "[0-9]+"},
            "parameters": ["ratingId"]
        },
        "admin.official-ratings.tournaments": {
            "uri": "admin\/official-ratings\/{ratingId}\/tournaments",
            "methods": ["GET", "HEAD"],
            "wheres": {"ratingId": "[0-9]+"},
            "parameters": ["ratingId"]
        },
        "admin.official-ratings.players": {
            "uri": "admin\/official-ratings\/{ratingId}\/players",
            "methods": ["GET", "HEAD"],
            "wheres": {"ratingId": "[0-9]+"},
            "parameters": ["ratingId"]
        },
        "error.404": {"uri": "404", "methods": ["GET", "HEAD"]},
        "error.403": {"uri": "403", "methods": ["GET", "HEAD"]},
        "error.500": {"uri": "500", "methods": ["GET", "HEAD"]},
        "error.custom": {
            "uri": "error\/{status}",
            "methods": ["GET", "HEAD"],
            "wheres": {"status": "[0-9]+"},
            "parameters": ["status"]
        },
        "storage.local": {
            "uri": "storage\/{path}",
            "methods": ["GET", "HEAD"],
            "wheres": {"path": ".*"},
            "parameters": ["path"]
        }
    }
};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
    Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export {Ziggy};
