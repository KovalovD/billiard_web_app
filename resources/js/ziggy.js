const Ziggy = {
    "url": "https:\/\/winnerbreak.com", "port": 8001, "defaults": {}, "routes": {
        "auth.user": {"uri": "api\/auth\/user", "methods": ["GET", "HEAD"]},
        "auth.logout": {"uri": "api\/auth\/logout", "methods": ["POST"]},
        "auth.login": {"uri": "api\/auth\/login", "methods": ["POST"]},
        "auth.register": {"uri": "api\/auth\/register", "methods": ["POST"]},
        "scribe": {"uri": "docs", "methods": ["GET", "HEAD"]},
        "scribe.postman": {"uri": "docs.postman", "methods": ["GET", "HEAD"]},
        "scribe.openapi": {"uri": "docs.openapi", "methods": ["GET", "HEAD"]},
        "sanctum.csrf-cookie": {"uri": "sanctum\/csrf-cookie", "methods": ["GET", "HEAD"]},
        "leagues.index.page": {"uri": "leagues", "methods": ["GET", "HEAD"]},
        "leagues.show.page": {
            "uri": "leagues\/{league}",
            "methods": ["GET", "HEAD"],
            "wheres": {"league": "[0-9]+"},
            "parameters": ["league"]
        },
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
        "login": {"uri": "login", "methods": ["GET", "HEAD"]},
        "register": {"uri": "register", "methods": ["GET", "HEAD"]},
        "home": {"uri": "\/", "methods": ["GET", "HEAD"]},
        "profile.edit": {"uri": "profile", "methods": ["GET", "HEAD"]},
        "profile.stats": {"uri": "profile\/stats", "methods": ["GET", "HEAD"]},
        "dashboard": {"uri": "dashboard", "methods": ["GET", "HEAD"]},
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
export { Ziggy };
