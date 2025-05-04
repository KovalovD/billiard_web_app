const Ziggy = {
    "url": "http:\/\/localhost:8001", "port": 8001, "defaults": {}, "routes": {
        "auth.user": {"uri": "api\/auth\/user", "methods": ["GET", "HEAD"]},
        "auth.logout": {"uri": "api\/auth\/logout", "methods": ["POST"]},
        "auth.login": {"uri": "api\/auth\/login", "methods": ["POST"]},
        "scribe": {"uri": "docs", "methods": ["GET", "HEAD"]},
        "scribe.postman": {"uri": "docs.postman", "methods": ["GET", "HEAD"]},
        "scribe.openapi": {"uri": "docs.openapi", "methods": ["GET", "HEAD"]},
        "sanctum.csrf-cookie": {"uri": "sanctum\/csrf-cookie", "methods": ["GET", "HEAD"]},
        "leagues.index": {"uri": "leagues", "methods": ["GET", "HEAD"]},
        "leagues.show": {
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
        "leagues.create": {"uri": "leagues\/create", "methods": ["GET", "HEAD"]},
        "leagues.edit": {
            "uri": "leagues\/{league}\/edit",
            "methods": ["GET", "HEAD"],
            "wheres": {"league": "[0-9]+"},
            "parameters": ["league"]
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
