<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>B2B League API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
        body .content .bash-example code {
            display: none;
        }

        body .content .javascript-example code {
            display: none;
        }
    </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8001";
        var useCsrf = Boolean(1);
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.2.1.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.2.1.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">

    <div class="lang-selector">
        <button type="button" class="lang-button" data-language-name="bash">bash</button>
        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
    </div>

    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
        <ul id="tocify-header-introduction" class="tocify-header">
            <li class="tocify-item level-1" data-unique="introduction">
                <a href="#introduction">Introduction</a>
            </li>
        </ul>
        <ul id="tocify-header-authenticating-requests" class="tocify-header">
            <li class="tocify-item level-1" data-unique="authenticating-requests">
                <a href="#authenticating-requests">Authenticating requests</a>
            </li>
        </ul>
        <ul id="tocify-header-auth" class="tocify-header">
            <li class="tocify-item level-1" data-unique="auth">
                <a href="#auth">Auth</a>
            </li>
            <ul id="tocify-subheader-auth" class="tocify-subheader">
                <li class="tocify-item level-2" data-unique="auth-GETapi-auth-user">
                    <a href="#auth-GETapi-auth-user">Get authenticated user</a>
                </li>
                <li class="tocify-item level-2" data-unique="auth-POSTapi-auth-logout">
                    <a href="#auth-POSTapi-auth-logout">Logout user</a>
                </li>
                <li class="tocify-item level-2" data-unique="auth-POSTapi-auth-login">
                    <a href="#auth-POSTapi-auth-login">Login user</a>
                </li>
                <li class="tocify-item level-2" data-unique="auth-POSTapi-auth-register">
                    <a href="#auth-POSTapi-auth-register">Register a new user</a>
                </li>
            </ul>
        </ul>
        <ul id="tocify-header-endpoints" class="tocify-header">
            <li class="tocify-item level-1" data-unique="endpoints">
                <a href="#endpoints">Endpoints</a>
            </li>
            <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                <li class="tocify-item level-2" data-unique="endpoints-GETapi-leagues--league_id--multiplayer-games">
                    <a href="#endpoints-GETapi-leagues--league_id--multiplayer-games">GET
                        api/leagues/{league_id}/multiplayer-games</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-">
                    <a href="#endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-">GET
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join">POST
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/join</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave">POST
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/leave</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action">POST
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/action</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish">POST
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/finish</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator">POST
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/set-moderator</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary">
                    <a href="#endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary">Get
                        financial summary for a game</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary">
                    <a href="#endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary">Get
                        rating summary for a game</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games">POST
                        api/leagues/{league_id}/multiplayer-games</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start">POST
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/start</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel">
                    <a href="#endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel">POST
                        api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/cancel</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-admin-leagues--league_id--add-player">
                    <a href="#endpoints-POSTapi-admin-leagues--league_id--add-player">Add a new player to a league with
                        automatic registration</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player">
                    <a href="#endpoints-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player">Add
                        a new player to a multiplayer game with automatic registration</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-GETapi-cities">
                    <a href="#endpoints-GETapi-cities">Get list of cities</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-GETapi-clubs">
                    <a href="#endpoints-GETapi-clubs">Get list of clubs, optionally filtered by city</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-GETapi-leagues--league_id--admin-pending-players">
                    <a href="#endpoints-GETapi-leagues--league_id--admin-pending-players">Get all pending (unconfirmed)
                        players for a league</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--admin-confirm-player--rating_id-">
                    <a href="#endpoints-POSTapi-leagues--league_id--admin-confirm-player--rating_id-">Confirm a player
                        in the league</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--admin-reject-player--rating_id-">
                    <a href="#endpoints-POSTapi-leagues--league_id--admin-reject-player--rating_id-">Reject a player
                        from the league</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-leagues--league_id--admin-bulk-confirm">
                    <a href="#endpoints-POSTapi-leagues--league_id--admin-bulk-confirm">Bulk confirm multiple
                        players</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-GETapi-leagues--league_id--admin-confirmed-players">
                    <a href="#endpoints-GETapi-leagues--league_id--admin-confirmed-players">Get all confirmed players
                        for a league</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-">
                    <a href="#endpoints-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-">Deactivate a
                        confirmed player</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="endpoints-POSTapi-leagues--league_id--admin-bulk-deactivate">
                    <a href="#endpoints-POSTapi-leagues--league_id--admin-bulk-deactivate">Bulk deactivate multiple
                        players</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-profile">
                    <a href="#endpoints-PUTapi-profile">Update the authenticated user's profile</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-profile-password">
                    <a href="#endpoints-PUTapi-profile-password">Update the authenticated user's password</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-profile">
                    <a href="#endpoints-DELETEapi-profile">Delete the authenticated user's account</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-GETapi-user-ratings">
                    <a href="#endpoints-GETapi-user-ratings">Get the authenticated user's ratings across all leagues</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-GETapi-user-matches">
                    <a href="#endpoints-GETapi-user-matches">Get the authenticated user's match history</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-GETapi-user-stats">
                    <a href="#endpoints-GETapi-user-stats">Get overall statistics for the authenticated user</a>
                </li>
                <li class="tocify-item level-2" data-unique="endpoints-GETapi-user-game-type-stats">
                    <a href="#endpoints-GETapi-user-game-type-stats">Get game type statistics for the authenticated
                        user</a>
                </li>
            </ul>
        </ul>
        <ul id="tocify-header-leagues" class="tocify-header">
            <li class="tocify-item level-1" data-unique="leagues">
                <a href="#leagues">Leagues</a>
            </li>
            <ul id="tocify-subheader-leagues" class="tocify-subheader">
                <li class="tocify-item level-2" data-unique="leagues-GETapi-leagues">
                    <a href="#leagues-GETapi-leagues">Get a list of leagues</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-GETapi-leagues--id-">
                    <a href="#leagues-GETapi-leagues--id-">Get league by id</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-POSTapi-leagues">
                    <a href="#leagues-POSTapi-leagues">Create a new league</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-PUTapi-leagues--id-">
                    <a href="#leagues-PUTapi-leagues--id-">Update league by id</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-DELETEapi-leagues--id-">
                    <a href="#leagues-DELETEapi-leagues--id-">Delete league by id</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-POSTapi-my-leagues-and-challenges">
                    <a href="#leagues-POSTapi-my-leagues-and-challenges">Load leagues and challenges for logged user</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-GETapi-games">
                    <a href="#leagues-GETapi-games">GET api/games</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-GETapi-leagues--league_id--players">
                    <a href="#leagues-GETapi-leagues--league_id--players">Get a list of players in a league</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-GETapi-leagues--league_id--games">
                    <a href="#leagues-GETapi-leagues--league_id--games">Get a list of games in a league</a>
                </li>
                <li class="tocify-item level-2" data-unique="leagues-GETapi-leagues--league_id--load-user-rating">
                    <a href="#leagues-GETapi-leagues--league_id--load-user-rating">GET
                        api/leagues/{league_id}/load-user-rating</a>
                </li>
            </ul>
        </ul>
        <ul id="tocify-header-matchgames" class="tocify-header">
            <li class="tocify-item level-1" data-unique="matchgames">
                <a href="#matchgames">MatchGames</a>
                </li>
            <ul id="tocify-subheader-matchgames" class="tocify-subheader">
                <li class="tocify-item level-2"
                    data-unique="matchgames-POSTapi-leagues--league_id--players--user_id--send-match-game">
                    <a href="#matchgames-POSTapi-leagues--league_id--players--user_id--send-match-game">Send a challenge
                        to the player</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept">
                    <a href="#matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept">Accept
                        the challenge</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline">
                    <a href="#matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline">Decline
                        the challenge</a>
                </li>
                <li class="tocify-item level-2"
                    data-unique="matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result">
                    <a href="#matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result">Send
                        Result of a game match</a>
                </li>
            </ul>
        </ul>
        <ul id="tocify-header-players" class="tocify-header">
            <li class="tocify-item level-1" data-unique="players">
                <a href="#players">Players</a>
            </li>
            <ul id="tocify-subheader-players" class="tocify-subheader">
                <li class="tocify-item level-2" data-unique="players-POSTapi-leagues--league_id--players-enter">
                    <a href="#players-POSTapi-leagues--league_id--players-enter">Enter to the League as a player</a>
                </li>
                <li class="tocify-item level-2" data-unique="players-POSTapi-leagues--league_id--players-leave">
                    <a href="#players-POSTapi-leagues--league_id--players-leave">Leave the League as a player</a>
                </li>
            </ul>
        </ul>
    </div>

    <ul class="toc-footer" id="toc-footer">
        <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
        <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
        <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: May 21, 2025</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
        <aside>
            <strong>Base URL</strong>: <code>http://localhost:8001</code>
        </aside>
        <pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
        <p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value
            <strong><code>"Bearer 123456"</code></strong>.</p>
        <p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation
            below.</p>
        <p>You can retrieve your token by visiting your dashboard and clicking <b>Generate API token</b>.</p>

        <h1 id="auth">Auth</h1>


        <h2 id="auth-GETapi-auth-user">Get authenticated user</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-GETapi-auth-user">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/auth/user" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/auth/user"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-auth-user">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-auth-user" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-auth-user"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-user"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-auth-user" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-user">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-auth-user" data-method="GET"
              data-path="api/auth/user"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-user', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-auth-user"
                        onclick="tryItOut('GETapi-auth-user');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-auth-user"
                        onclick="cancelTryOut('GETapi-auth-user');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-auth-user"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/auth/user</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value" data-endpoint="GETapi-auth-user"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-auth-user"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-auth-user"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="auth-POSTapi-auth-logout">Logout user</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/auth/logout" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"deviceName\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/auth/logout"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "deviceName": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-auth-logout">
</span>
        <span id="execution-results-POSTapi-auth-logout" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-logout"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-auth-logout" data-method="POST"
              data-path="api/auth/logout"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-logout', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-auth-logout"
                        onclick="tryItOut('POSTapi-auth-logout');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-auth-logout"
                        onclick="cancelTryOut('POSTapi-auth-logout');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-auth-logout"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/auth/logout</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value" data-endpoint="POSTapi-auth-logout"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-auth-logout"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-auth-logout"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>deviceName</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="deviceName" data-endpoint="POSTapi-auth-logout"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
        </form>

        <h2 id="auth-POSTapi-auth-login">Login user</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-auth-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/auth/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"password\": \"|]|{+-\",
    \"deviceName\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/auth/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "password": "|]|{+-",
    "deviceName": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-auth-login">
</span>
        <span id="execution-results-POSTapi-auth-login" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-auth-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-login"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-auth-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-auth-login" data-method="POST"
              data-path="api/auth/login"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-login', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-auth-login"
                        onclick="tryItOut('POSTapi-auth-login');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-auth-login"
                        onclick="cancelTryOut('POSTapi-auth-login');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-auth-login"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/auth/login</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-auth-login"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-auth-login"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="email" data-endpoint="POSTapi-auth-login"
                       value="gbailey@example.net"
                       data-component="body">
                <br>
                <p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="password" data-endpoint="POSTapi-auth-login"
                       value="|]|{+-"
                       data-component="body">
                <br>
                <p>Example: <code>|]|{+-</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>deviceName</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="deviceName" data-endpoint="POSTapi-auth-login"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
        </form>

        <h2 id="auth-POSTapi-auth-register">Register a new user</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-auth-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/auth/register" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"firstname\": \"b\",
    \"lastname\": \"n\",
    \"email\": \"ashly64@example.com\",
    \"phone\": \"v\",
    \"password\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/auth/register"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "firstname": "b",
    "lastname": "n",
    "email": "ashly64@example.com",
    "phone": "v",
    "password": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-auth-register">
</span>
        <span id="execution-results-POSTapi-auth-register" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-auth-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-register"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-auth-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-auth-register" data-method="POST"
              data-path="api/auth/register"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-register', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-auth-register"
                        onclick="tryItOut('POSTapi-auth-register');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-auth-register"
                        onclick="cancelTryOut('POSTapi-auth-register');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-auth-register"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/auth/register</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-auth-register"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-auth-register"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>firstname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="firstname" data-endpoint="POSTapi-auth-register"
                       value="b"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>b</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>lastname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="lastname" data-endpoint="POSTapi-auth-register"
                       value="n"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>n</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="email" data-endpoint="POSTapi-auth-register"
                       value="ashly64@example.com"
                       data-component="body">
                <br>
                <p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>ashly64@example.com</code>
                </p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="phone" data-endpoint="POSTapi-auth-register"
                       value="v"
                       data-component="body">
                <br>
                <p>Must not be greater than 15 characters. Example: <code>v</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="password" data-endpoint="POSTapi-auth-register"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
        </form>

        <h1 id="endpoints">Endpoints</h1>


        <h2 id="endpoints-GETapi-leagues--league_id--multiplayer-games">GET
            api/leagues/{league_id}/multiplayer-games</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--multiplayer-games">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/multiplayer-games" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--multiplayer-games">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--multiplayer-games" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--multiplayer-games"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues--league_id--multiplayer-games"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--multiplayer-games" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--league_id--multiplayer-games">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--multiplayer-games" data-method="GET"
              data-path="api/leagues/{league_id}/multiplayer-games"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--multiplayer-games', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--multiplayer-games"
                        onclick="tryItOut('GETapi-leagues--league_id--multiplayer-games');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--multiplayer-games"
                        onclick="cancelTryOut('GETapi-leagues--league_id--multiplayer-games');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--multiplayer-games"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/multiplayer-games</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues--league_id--multiplayer-games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--league_id--multiplayer-games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="GETapi-leagues--league_id--multiplayer-games"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-">GET
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/multiplayer-games/5" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-" data-method="GET"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
                        onclick="tryItOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-');">Try it
                    out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
                        onclick="cancelTryOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id-"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join">POST
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/join</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/join" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/join"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join" data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/join"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/join</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--join"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave">POST
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/leave</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/leave" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/leave"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave" data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/leave"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/leave</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--leave"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action">POST
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/action</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/action" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"action\": \"record_turn\",
    \"target_user_id\": 16,
    \"acting_user_id\": 16,
    \"card_type\": \"hand_shot\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/action"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "action": "record_turn",
    "target_user_id": 16,
    "acting_user_id": 16,
    "card_type": "hand_shot"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action" data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/action"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/action</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>action</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="action"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="record_turn"
                       data-component="body">
                <br>
                <p>Example: <code>record_turn</code></p>
                Must be one of:
                <ul style="list-style-type: square;">
                    <li><code>increment_lives</code></li>
                    <li><code>decrement_lives</code></li>
                    <li><code>use_card</code></li>
                    <li><code>record_turn</code></li>
                </ul>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>target_user_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="target_user_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="16"
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the users table. Example: <code>16</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>acting_user_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="acting_user_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="16"
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the users table. Example: <code>16</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>card_type</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="card_type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--action"
                       value="hand_shot"
                       data-component="body">
                <br>
                <p>This field is required when <code>action</code> is <code>use_card</code>. Example:
                    <code>hand_shot</code></p>
                Must be one of:
                <ul style="list-style-type: square;">
                    <li><code>skip_turn</code></li>
                    <li><code>pass_turn</code></li>
                    <li><code>hand_shot</code></li>
                </ul>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish">POST
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/finish</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/finish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/finish"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish" data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/finish"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/finish</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--finish"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator">POST
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/set-moderator</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/set-moderator" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/set-moderator"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
              hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
              hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code
            id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
              data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/set-moderator"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/set-moderator</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--set-moderator"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary">Get
            financial summary for a game</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/multiplayer-games/5/financial-summary" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/financial-summary"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span
            id="example-responses-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
              hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
              hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code
            id="execution-error-message-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
              data-method="GET"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/financial-summary"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
                        onclick="tryItOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
                        onclick="cancelTryOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/financial-summary</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--financial-summary"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary">Get rating
            summary for a game</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/multiplayer-games/5/rating-summary" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/rating-summary"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
              hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
              hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code
            id="execution-error-message-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
              data-method="GET"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/rating-summary"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
                        onclick="tryItOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
                        onclick="cancelTryOut('GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/rating-summary</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="GETapi-leagues--league_id--multiplayer-games--multiplayerGame_id--rating-summary"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games">POST
            api/leagues/{league_id}/multiplayer-games</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"b\",
    \"max_players\": 4,
    \"registration_ends_at\": \"2051-06-14\",
    \"allow_player_targeting\": false,
    \"entrance_fee\": 39,
    \"first_place_percent\": 7,
    \"second_place_percent\": 16,
    \"grand_final_percent\": 17,
    \"penalty_fee\": 8
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "b",
    "max_players": 4,
    "registration_ends_at": "2051-06-14",
    "allow_player_targeting": false,
    "entrance_fee": 39,
    "first_place_percent": 7,
    "second_place_percent": 16,
    "grand_final_percent": 17,
    "penalty_fee": 8
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games" data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="name" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="b"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>b</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>max_players</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="max_players" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="4"
                       data-component="body">
                <br>
                <p>Must be at least 2. Must not be greater than 24. Example: <code>4</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>registration_ends_at</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="registration_ends_at" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="2051-06-14"
                       data-component="body">
                <br>
                <p>Must be a valid date. Must be a date after <code>now</code>. Example: <code>2051-06-14</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>allow_player_targeting</code></b>&nbsp;&nbsp;
                <small>boolean</small>&nbsp;
                <i>optional</i> &nbsp;
                <label data-endpoint="POSTapi-leagues--league_id--multiplayer-games" style="display: none">
                    <input type="radio" name="allow_player_targeting"
                           value="true"
                           data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                           data-component="body">
                    <code>true</code>
                </label>
                <label data-endpoint="POSTapi-leagues--league_id--multiplayer-games" style="display: none">
                    <input type="radio" name="allow_player_targeting"
                           value="false"
                           data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                           data-component="body">
                    <code>false</code>
                </label>
                <br>
                <p>Example: <code>false</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>entrance_fee</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="entrance_fee" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="39"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>39</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>first_place_percent</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="first_place_percent"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="7"
                       data-component="body">
                <br>
                <p>Must be at least 0. Must not be greater than 100. Example: <code>7</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>second_place_percent</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="second_place_percent"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="16"
                       data-component="body">
                <br>
                <p>Must be at least 0. Must not be greater than 100. Example: <code>16</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>grand_final_percent</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="grand_final_percent"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="17"
                       data-component="body">
                <br>
                <p>Must be at least 0. Must not be greater than 100. Example: <code>17</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>penalty_fee</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="penalty_fee" data-endpoint="POSTapi-leagues--league_id--multiplayer-games"
                       value="8"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>8</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start">POST
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/start</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/start" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/start"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start" data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/start"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/start</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--start"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel">POST
            api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/cancel</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/cancel" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/multiplayer-games/5/cancel"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel" data-method="POST"
              data-path="api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/cancel"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
                        onclick="tryItOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/cancel</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-leagues--league_id--multiplayer-games--multiplayerGame_id--cancel"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-admin-leagues--league_id--add-player">Add a new player to a league with automatic
            registration</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-admin-leagues--league_id--add-player">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/admin/leagues/1/add-player" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"firstname\": \"b\",
    \"lastname\": \"n\",
    \"email\": \"ashly64@example.com\",
    \"phone\": \"v\",
    \"password\": \"BNvYgxwmi\\/#iw\\/kX\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/admin/leagues/1/add-player"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "firstname": "b",
    "lastname": "n",
    "email": "ashly64@example.com",
    "phone": "v",
    "password": "BNvYgxwmi\/#iw\/kX"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-admin-leagues--league_id--add-player">
</span>
        <span id="execution-results-POSTapi-admin-leagues--league_id--add-player" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-admin-leagues--league_id--add-player"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-admin-leagues--league_id--add-player"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-admin-leagues--league_id--add-player" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-admin-leagues--league_id--add-player">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-admin-leagues--league_id--add-player" data-method="POST"
              data-path="api/admin/leagues/{league_id}/add-player"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-admin-leagues--league_id--add-player', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-admin-leagues--league_id--add-player"
                        onclick="tryItOut('POSTapi-admin-leagues--league_id--add-player');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-admin-leagues--league_id--add-player"
                        onclick="cancelTryOut('POSTapi-admin-leagues--league_id--add-player');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-admin-leagues--league_id--add-player"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/admin/leagues/{league_id}/add-player</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>firstname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="firstname" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="b"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>b</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>lastname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="lastname" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="n"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>n</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="email" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="ashly64@example.com"
                       data-component="body">
                <br>
                <p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>ashly64@example.com</code>
                </p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="phone" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="v"
                       data-component="body">
                <br>
                <p>Must not be greater than 15 characters. Example: <code>v</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="password" data-endpoint="POSTapi-admin-leagues--league_id--add-player"
                       value="BNvYgxwmi/#iw/kX"
                       data-component="body">
                <br>
                <p>Must be at least 8 characters. Example: <code>BNvYgxwmi/#iw/kX</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player">Add a new
            player to a multiplayer game with automatic registration</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/admin/leagues/1/multiplayer-games/5/add-player" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"firstname\": \"b\",
    \"lastname\": \"n\",
    \"email\": \"ashly64@example.com\",
    \"phone\": \"v\",
    \"password\": \"BNvYgxwmi\\/#iw\\/kX\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/admin/leagues/1/multiplayer-games/5/add-player"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "firstname": "b",
    "lastname": "n",
    "email": "ashly64@example.com",
    "phone": "v",
    "password": "BNvYgxwmi\/#iw\/kX"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span
            id="example-responses-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player">
</span>
        <span id="execution-results-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
              hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
              hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code
            id="execution-error-message-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
              data-method="POST"
              data-path="api/admin/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/add-player"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                        onclick="tryItOut('POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                        onclick="cancelTryOut('POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/admin/leagues/{league_id}/multiplayer-games/{multiplayerGame_id}/add-player</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>multiplayerGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="multiplayerGame_id"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="5"
                       data-component="url">
                <br>
                <p>The ID of the multiplayerGame. Example: <code>5</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>firstname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="firstname"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="b"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>b</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>lastname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="lastname"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="n"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>n</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="email"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="ashly64@example.com"
                       data-component="body">
                <br>
                <p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>ashly64@example.com</code>
                </p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="phone"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="v"
                       data-component="body">
                <br>
                <p>Must not be greater than 15 characters. Example: <code>v</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="password"
                       data-endpoint="POSTapi-admin-leagues--league_id--multiplayer-games--multiplayerGame_id--add-player"
                       value="BNvYgxwmi/#iw/kX"
                       data-component="body">
                <br>
                <p>Must be at least 8 characters. Example: <code>BNvYgxwmi/#iw/kX</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-cities">Get list of cities</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-cities">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/cities" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/cities"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-cities">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">[
    {
        &quot;id&quot;: 12,
        &quot;name&quot;: &quot;Вінниця&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 4,
        &quot;name&quot;: &quot;Дніпро&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 5,
        &quot;name&quot;: &quot;Донецьк&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 18,
        &quot;name&quot;: &quot;Житомир&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 6,
        &quot;name&quot;: &quot;Запоріжжя&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 21,
        &quot;name&quot;: &quot;Івано-Франківськ&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Київ&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 8,
        &quot;name&quot;: &quot;Кривий Ріг&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 11,
        &quot;name&quot;: &quot;Луганськ&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 23,
        &quot;name&quot;: &quot;Луцьк&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 7,
        &quot;name&quot;: &quot;Львів&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 10,
        &quot;name&quot;: &quot;Маріуполь&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 9,
        &quot;name&quot;: &quot;Миколаїв&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 3,
        &quot;name&quot;: &quot;Одеса&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 14,
        &quot;name&quot;: &quot;Полтава&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 20,
        &quot;name&quot;: &quot;Рівне&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 19,
        &quot;name&quot;: &quot;Суми&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 22,
        &quot;name&quot;: &quot;Тернопіль&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 24,
        &quot;name&quot;: &quot;Ужгород&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 2,
        &quot;name&quot;: &quot;Харків&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 13,
        &quot;name&quot;: &quot;Херсон&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 17,
        &quot;name&quot;: &quot;Хмельницький&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 16,
        &quot;name&quot;: &quot;Черкаси&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 25,
        &quot;name&quot;: &quot;Чернівці&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    },
    {
        &quot;id&quot;: 15,
        &quot;name&quot;: &quot;Чернігів&quot;,
        &quot;country&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Україна&quot;
        }
    }
]</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-cities" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-cities"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-cities"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-cities" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-cities">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-cities" data-method="GET"
              data-path="api/cities"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-cities', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-cities"
                        onclick="tryItOut('GETapi-cities');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-cities"
                        onclick="cancelTryOut('GETapi-cities');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-cities"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/cities</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-cities"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-cities"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-clubs">Get list of clubs, optionally filtered by city</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-clubs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/clubs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/clubs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-clubs">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">[
    {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;B2B&quot;,
        &quot;city&quot;: &quot;Львів&quot;,
        &quot;country&quot;: &quot;Україна&quot;
    }
]</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-clubs" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-clubs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-clubs"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-clubs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-clubs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-clubs" data-method="GET"
              data-path="api/clubs"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-clubs', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-clubs"
                        onclick="tryItOut('GETapi-clubs');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-clubs"
                        onclick="cancelTryOut('GETapi-clubs');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-clubs"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/clubs</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-clubs"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-clubs"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-leagues--league_id--admin-pending-players">Get all pending (unconfirmed) players for a
            league</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--admin-pending-players">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/admin/pending-players" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/admin/pending-players"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--admin-pending-players">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--admin-pending-players" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--admin-pending-players"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues--league_id--admin-pending-players"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--admin-pending-players" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--league_id--admin-pending-players">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--admin-pending-players" data-method="GET"
              data-path="api/leagues/{league_id}/admin/pending-players"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--admin-pending-players', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--admin-pending-players"
                        onclick="tryItOut('GETapi-leagues--league_id--admin-pending-players');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--admin-pending-players"
                        onclick="cancelTryOut('GETapi-leagues--league_id--admin-pending-players');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--admin-pending-players"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/admin/pending-players</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues--league_id--admin-pending-players"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--league_id--admin-pending-players"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="GETapi-leagues--league_id--admin-pending-players"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--admin-confirm-player--rating_id-">Confirm a player in the
            league</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--admin-confirm-player--rating_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/admin/confirm-player/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/admin/confirm-player/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--admin-confirm-player--rating_id-">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--admin-confirm-player--rating_id-" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--admin-confirm-player--rating_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--admin-confirm-player--rating_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--admin-confirm-player--rating_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--admin-confirm-player--rating_id-" data-method="POST"
              data-path="api/leagues/{league_id}/admin/confirm-player/{rating_id}"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--admin-confirm-player--rating_id-', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                        onclick="tryItOut('POSTapi-leagues--league_id--admin-confirm-player--rating_id-');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--admin-confirm-player--rating_id-');" hidden>
                    Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/admin/confirm-player/{rating_id}</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>rating_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="rating_id"
                       data-endpoint="POSTapi-leagues--league_id--admin-confirm-player--rating_id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the rating. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--admin-reject-player--rating_id-">Reject a player from the
            league</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--admin-reject-player--rating_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/admin/reject-player/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/admin/reject-player/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--admin-reject-player--rating_id-">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--admin-reject-player--rating_id-" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--admin-reject-player--rating_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--admin-reject-player--rating_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--admin-reject-player--rating_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--admin-reject-player--rating_id-" data-method="POST"
              data-path="api/leagues/{league_id}/admin/reject-player/{rating_id}"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--admin-reject-player--rating_id-', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                        onclick="tryItOut('POSTapi-leagues--league_id--admin-reject-player--rating_id-');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--admin-reject-player--rating_id-');" hidden>
                    Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/admin/reject-player/{rating_id}</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>rating_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="rating_id"
                       data-endpoint="POSTapi-leagues--league_id--admin-reject-player--rating_id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the rating. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--admin-bulk-confirm">Bulk confirm multiple players</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--admin-bulk-confirm">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/admin/bulk-confirm" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"rating_ids\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/admin/bulk-confirm"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "rating_ids": [
        16
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--admin-bulk-confirm">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--admin-bulk-confirm" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--admin-bulk-confirm"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues--league_id--admin-bulk-confirm"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--admin-bulk-confirm" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--admin-bulk-confirm">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--admin-bulk-confirm" data-method="POST"
              data-path="api/leagues/{league_id}/admin/bulk-confirm"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--admin-bulk-confirm', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--admin-bulk-confirm"
                        onclick="tryItOut('POSTapi-leagues--league_id--admin-bulk-confirm');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--admin-bulk-confirm"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--admin-bulk-confirm');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--admin-bulk-confirm"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/admin/bulk-confirm</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--admin-bulk-confirm"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--admin-bulk-confirm"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="POSTapi-leagues--league_id--admin-bulk-confirm"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>rating_ids</code></b>&nbsp;&nbsp;
                <small>integer[]</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="rating_ids[0]" data-endpoint="POSTapi-leagues--league_id--admin-bulk-confirm"
                       data-component="body">
                <input type="number" style="display: none"
                       name="rating_ids[1]" data-endpoint="POSTapi-leagues--league_id--admin-bulk-confirm"
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the ratings table.</p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-leagues--league_id--admin-confirmed-players">Get all confirmed players for a
            league</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--admin-confirmed-players">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/admin/confirmed-players" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/admin/confirmed-players"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--admin-confirmed-players">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--admin-confirmed-players" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--admin-confirmed-players"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues--league_id--admin-confirmed-players"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--admin-confirmed-players" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--league_id--admin-confirmed-players">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--admin-confirmed-players" data-method="GET"
              data-path="api/leagues/{league_id}/admin/confirmed-players"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--admin-confirmed-players', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--admin-confirmed-players"
                        onclick="tryItOut('GETapi-leagues--league_id--admin-confirmed-players');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--admin-confirmed-players"
                        onclick="cancelTryOut('GETapi-leagues--league_id--admin-confirmed-players');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--admin-confirmed-players"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/admin/confirmed-players</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues--league_id--admin-confirmed-players"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--league_id--admin-confirmed-players"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="GETapi-leagues--league_id--admin-confirmed-players"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-">Deactivate a confirmed
            player</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/admin/deactivate-player/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/admin/deactivate-player/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-" data-method="POST"
              data-path="api/leagues/{league_id}/admin/deactivate-player/{rating_id}"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--admin-deactivate-player--rating_id-', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
                        onclick="tryItOut('POSTapi-leagues--league_id--admin-deactivate-player--rating_id-');">Try it
                    out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--admin-deactivate-player--rating_id-');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/admin/deactivate-player/{rating_id}</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>rating_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="rating_id"
                       data-endpoint="POSTapi-leagues--league_id--admin-deactivate-player--rating_id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the rating. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="endpoints-POSTapi-leagues--league_id--admin-bulk-deactivate">Bulk deactivate multiple players</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--admin-bulk-deactivate">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/admin/bulk-deactivate" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"rating_ids\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/admin/bulk-deactivate"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "rating_ids": [
        16
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--admin-bulk-deactivate">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--admin-bulk-deactivate" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--admin-bulk-deactivate"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues--league_id--admin-bulk-deactivate"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--admin-bulk-deactivate" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--admin-bulk-deactivate">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--admin-bulk-deactivate" data-method="POST"
              data-path="api/leagues/{league_id}/admin/bulk-deactivate"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--admin-bulk-deactivate', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--admin-bulk-deactivate"
                        onclick="tryItOut('POSTapi-leagues--league_id--admin-bulk-deactivate');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--admin-bulk-deactivate"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--admin-bulk-deactivate');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--admin-bulk-deactivate"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/admin/bulk-deactivate</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--admin-bulk-deactivate"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--admin-bulk-deactivate"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="POSTapi-leagues--league_id--admin-bulk-deactivate"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>rating_ids</code></b>&nbsp;&nbsp;
                <small>integer[]</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="rating_ids[0]" data-endpoint="POSTapi-leagues--league_id--admin-bulk-deactivate"
                       data-component="body">
                <input type="number" style="display: none"
                       name="rating_ids[1]" data-endpoint="POSTapi-leagues--league_id--admin-bulk-deactivate"
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the ratings table.</p>
            </div>
        </form>

        <h2 id="endpoints-PUTapi-profile">Update the authenticated user&#039;s profile</h2>

        <p>
        </p>


        <span id="example-requests-PUTapi-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8001/api/profile" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"firstname\": \"b\",
    \"lastname\": \"n\",
    \"email\": \"ashly64@example.com\",
    \"phone\": \"v\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/profile"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "firstname": "b",
    "lastname": "n",
    "email": "ashly64@example.com",
    "phone": "v"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-PUTapi-profile">
</span>
        <span id="execution-results-PUTapi-profile" hidden>
    <blockquote>Received response<span
            id="execution-response-status-PUTapi-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-profile"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-PUTapi-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-PUTapi-profile" data-method="PUT"
              data-path="api/profile"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('PUTapi-profile', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-PUTapi-profile"
                        onclick="tryItOut('PUTapi-profile');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-PUTapi-profile"
                        onclick="cancelTryOut('PUTapi-profile');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-PUTapi-profile"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-darkblue">PUT</small>
                <b><code>api/profile</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="PUTapi-profile"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="PUTapi-profile"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>firstname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="firstname" data-endpoint="PUTapi-profile"
                       value="b"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>b</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>lastname</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="lastname" data-endpoint="PUTapi-profile"
                       value="n"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>n</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="email" data-endpoint="PUTapi-profile"
                       value="ashly64@example.com"
                       data-component="body">
                <br>
                <p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>ashly64@example.com</code>
                </p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="phone" data-endpoint="PUTapi-profile"
                       value="v"
                       data-component="body">
                <br>
                <p>Must not be greater than 15 characters. Example: <code>v</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>home_city_id</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="home_city_id" data-endpoint="PUTapi-profile"
                       value=""
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the cities table.</p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>home_club_id</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="home_club_id" data-endpoint="PUTapi-profile"
                       value=""
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the clubs table.</p>
            </div>
        </form>

        <h2 id="endpoints-PUTapi-profile-password">Update the authenticated user&#039;s password</h2>

        <p>
        </p>


        <span id="example-requests-PUTapi-profile-password">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8001/api/profile/password" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"current_password\": \"architecto\",
    \"password\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/profile/password"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "current_password": "architecto",
    "password": "architecto"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-PUTapi-profile-password">
</span>
        <span id="execution-results-PUTapi-profile-password" hidden>
    <blockquote>Received response<span
            id="execution-response-status-PUTapi-profile-password"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-profile-password"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-PUTapi-profile-password" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-profile-password">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-PUTapi-profile-password" data-method="PUT"
              data-path="api/profile/password"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('PUTapi-profile-password', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-PUTapi-profile-password"
                        onclick="tryItOut('PUTapi-profile-password');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-PUTapi-profile-password"
                        onclick="cancelTryOut('PUTapi-profile-password');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-PUTapi-profile-password"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-darkblue">PUT</small>
                <b><code>api/profile/password</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="PUTapi-profile-password"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="PUTapi-profile-password"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>current_password</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="current_password" data-endpoint="PUTapi-profile-password"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="password" data-endpoint="PUTapi-profile-password"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
        </form>

        <h2 id="endpoints-DELETEapi-profile">Delete the authenticated user&#039;s account</h2>

        <p>
        </p>


        <span id="example-requests-DELETEapi-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8001/api/profile" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"password\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/profile"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "password": "architecto"
};

fetch(url, {
    method: "DELETE",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-DELETEapi-profile">
</span>
        <span id="execution-results-DELETEapi-profile" hidden>
    <blockquote>Received response<span
            id="execution-response-status-DELETEapi-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-profile"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-DELETEapi-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-DELETEapi-profile" data-method="DELETE"
              data-path="api/profile"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('DELETEapi-profile', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-DELETEapi-profile"
                        onclick="tryItOut('DELETEapi-profile');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-DELETEapi-profile"
                        onclick="cancelTryOut('DELETEapi-profile');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-DELETEapi-profile"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-red">DELETE</small>
                <b><code>api/profile</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="DELETEapi-profile"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="DELETEapi-profile"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="password" data-endpoint="DELETEapi-profile"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-user-ratings">Get the authenticated user&#039;s ratings across all leagues</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-user-ratings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/user/ratings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/user/ratings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-user-ratings">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-user-ratings" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-user-ratings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-user-ratings"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-user-ratings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-user-ratings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-user-ratings" data-method="GET"
              data-path="api/user/ratings"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-user-ratings', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-user-ratings"
                        onclick="tryItOut('GETapi-user-ratings');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-user-ratings"
                        onclick="cancelTryOut('GETapi-user-ratings');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-user-ratings"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/user/ratings</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-user-ratings"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-user-ratings"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-user-matches">Get the authenticated user&#039;s match history</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-user-matches">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/user/matches" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/user/matches"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-user-matches">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-user-matches" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-user-matches"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-user-matches"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-user-matches" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-user-matches">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-user-matches" data-method="GET"
              data-path="api/user/matches"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-user-matches', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-user-matches"
                        onclick="tryItOut('GETapi-user-matches');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-user-matches"
                        onclick="cancelTryOut('GETapi-user-matches');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-user-matches"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/user/matches</code></b>
        </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-user-matches"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-user-matches"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-user-stats">Get overall statistics for the authenticated user</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-user-stats">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/user/stats" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/user/stats"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-user-stats">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-user-stats" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-user-stats"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-user-stats"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-user-stats" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-user-stats">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-user-stats" data-method="GET"
              data-path="api/user/stats"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-user-stats', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-user-stats"
                        onclick="tryItOut('GETapi-user-stats');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-user-stats"
                        onclick="cancelTryOut('GETapi-user-stats');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-user-stats"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/user/stats</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-user-stats"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-user-stats"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="endpoints-GETapi-user-game-type-stats">Get game type statistics for the authenticated user</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-user-game-type-stats">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/user/game-type-stats" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/user/game-type-stats"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-user-game-type-stats">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-user-game-type-stats" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-user-game-type-stats"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-user-game-type-stats"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-user-game-type-stats" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-user-game-type-stats">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-user-game-type-stats" data-method="GET"
              data-path="api/user/game-type-stats"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-user-game-type-stats', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-user-game-type-stats"
                        onclick="tryItOut('GETapi-user-game-type-stats');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-user-game-type-stats"
                        onclick="cancelTryOut('GETapi-user-game-type-stats');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-user-game-type-stats"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/user/game-type-stats</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-user-game-type-stats"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-user-game-type-stats"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h1 id="leagues">Leagues</h1>


        <h2 id="leagues-GETapi-leagues">Get a list of leagues</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">[
    {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;B2B Pool League&quot;,
        &quot;picture&quot;: null,
        &quot;details&quot;: null,
        &quot;has_rating&quot;: true,
        &quot;started_at&quot;: null,
        &quot;finished_at&quot;: null,
        &quot;start_rating&quot;: 1000,
        &quot;rating_change_for_winners_rule&quot;: [
            {
                &quot;weak&quot;: 25,
                &quot;range&quot;: [
                    0,
                    50
                ],
                &quot;strong&quot;: 25
            },
            {
                &quot;weak&quot;: 30,
                &quot;range&quot;: [
                    51,
                    100
                ],
                &quot;strong&quot;: 20
            },
            {
                &quot;weak&quot;: 35,
                &quot;range&quot;: [
                    101,
                    200
                ],
                &quot;strong&quot;: 15
            },
            {
                &quot;weak&quot;: 40,
                &quot;range&quot;: [
                    201,
                    1000000
                ],
                &quot;strong&quot;: 10
            }
        ],
        &quot;rating_change_for_losers_rule&quot;: [
            {
                &quot;weak&quot;: -25,
                &quot;range&quot;: [
                    0,
                    50
                ],
                &quot;strong&quot;: -25
            },
            {
                &quot;weak&quot;: -30,
                &quot;range&quot;: [
                    51,
                    100
                ],
                &quot;strong&quot;: -20
            },
            {
                &quot;weak&quot;: -35,
                &quot;range&quot;: [
                    101,
                    200
                ],
                &quot;strong&quot;: -15
            },
            {
                &quot;weak&quot;: -40,
                &quot;range&quot;: [
                    201,
                    1000000
                ],
                &quot;strong&quot;: -10
            }
        ],
        &quot;created_at&quot;: null,
        &quot;updated_at&quot;: null,
        &quot;matches_count&quot;: 1,
        &quot;invite_days_expire&quot;: 3,
        &quot;max_players&quot;: 0,
        &quot;max_score&quot;: 0,
        &quot;active_players&quot;: 3,
        &quot;game_id&quot;: 3,
        &quot;game&quot;: &quot;Пул 8&quot;,
        &quot;game_type&quot;: &quot;pool&quot;,
        &quot;game_multiplayer&quot;: false
    },
    {
        &quot;id&quot;: 2,
        &quot;name&quot;: &quot;B2B Killer Pool League&quot;,
        &quot;picture&quot;: null,
        &quot;details&quot;: null,
        &quot;has_rating&quot;: true,
        &quot;started_at&quot;: null,
        &quot;finished_at&quot;: null,
        &quot;start_rating&quot;: 0,
        &quot;rating_change_for_winners_rule&quot;: [
            {
                &quot;weak&quot;: 25,
                &quot;range&quot;: [
                    0,
                    50
                ],
                &quot;strong&quot;: 25
            },
            {
                &quot;weak&quot;: 30,
                &quot;range&quot;: [
                    51,
                    100
                ],
                &quot;strong&quot;: 20
            },
            {
                &quot;weak&quot;: 35,
                &quot;range&quot;: [
                    101,
                    200
                ],
                &quot;strong&quot;: 15
            },
            {
                &quot;weak&quot;: 40,
                &quot;range&quot;: [
                    201,
                    1000000
                ],
                &quot;strong&quot;: 10
            }
        ],
        &quot;rating_change_for_losers_rule&quot;: [
            {
                &quot;weak&quot;: -25,
                &quot;range&quot;: [
                    0,
                    50
                ],
                &quot;strong&quot;: -25
            },
            {
                &quot;weak&quot;: -30,
                &quot;range&quot;: [
                    51,
                    100
                ],
                &quot;strong&quot;: -20
            },
            {
                &quot;weak&quot;: -35,
                &quot;range&quot;: [
                    101,
                    200
                ],
                &quot;strong&quot;: -15
            },
            {
                &quot;weak&quot;: -40,
                &quot;range&quot;: [
                    201,
                    1000000
                ],
                &quot;strong&quot;: -10
            }
        ],
        &quot;created_at&quot;: null,
        &quot;updated_at&quot;: null,
        &quot;matches_count&quot;: 0,
        &quot;invite_days_expire&quot;: 3,
        &quot;max_players&quot;: 0,
        &quot;max_score&quot;: 0,
        &quot;active_players&quot;: 9,
        &quot;game_id&quot;: 5,
        &quot;game&quot;: &quot;Killer pool&quot;,
        &quot;game_type&quot;: &quot;pool&quot;,
        &quot;game_multiplayer&quot;: true
    }
]</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues" data-method="GET"
              data-path="api/leagues"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues"
                        onclick="tryItOut('GETapi-leagues');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues"
                        onclick="cancelTryOut('GETapi-leagues');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="leagues-GETapi-leagues--id-">Get league by id</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 1,
    &quot;name&quot;: &quot;B2B Pool League&quot;,
    &quot;picture&quot;: null,
    &quot;details&quot;: null,
    &quot;has_rating&quot;: true,
    &quot;started_at&quot;: null,
    &quot;finished_at&quot;: null,
    &quot;start_rating&quot;: 1000,
    &quot;rating_change_for_winners_rule&quot;: [
        {
            &quot;weak&quot;: 25,
            &quot;range&quot;: [
                0,
                50
            ],
            &quot;strong&quot;: 25
        },
        {
            &quot;weak&quot;: 30,
            &quot;range&quot;: [
                51,
                100
            ],
            &quot;strong&quot;: 20
        },
        {
            &quot;weak&quot;: 35,
            &quot;range&quot;: [
                101,
                200
            ],
            &quot;strong&quot;: 15
        },
        {
            &quot;weak&quot;: 40,
            &quot;range&quot;: [
                201,
                1000000
            ],
            &quot;strong&quot;: 10
        }
    ],
    &quot;rating_change_for_losers_rule&quot;: [
        {
            &quot;weak&quot;: -25,
            &quot;range&quot;: [
                0,
                50
            ],
            &quot;strong&quot;: -25
        },
        {
            &quot;weak&quot;: -30,
            &quot;range&quot;: [
                51,
                100
            ],
            &quot;strong&quot;: -20
        },
        {
            &quot;weak&quot;: -35,
            &quot;range&quot;: [
                101,
                200
            ],
            &quot;strong&quot;: -15
        },
        {
            &quot;weak&quot;: -40,
            &quot;range&quot;: [
                201,
                1000000
            ],
            &quot;strong&quot;: -10
        }
    ],
    &quot;created_at&quot;: null,
    &quot;updated_at&quot;: null,
    &quot;matches_count&quot;: 1,
    &quot;invite_days_expire&quot;: 3,
    &quot;max_players&quot;: 0,
    &quot;max_score&quot;: 0,
    &quot;active_players&quot;: 3,
    &quot;game_id&quot;: 3,
    &quot;game&quot;: &quot;Пул 8&quot;,
    &quot;game_type&quot;: &quot;pool&quot;,
    &quot;game_multiplayer&quot;: false
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--id-" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues--id-"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--id-" data-method="GET"
              data-path="api/leagues/{id}"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--id-', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--id-"
                        onclick="tryItOut('GETapi-leagues--id-');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--id-"
                        onclick="cancelTryOut('GETapi-leagues--id-');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--id-"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{id}</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues--id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="id" data-endpoint="GETapi-leagues--id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="leagues-POSTapi-leagues">Create a new league</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-leagues">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"b\",
    \"picture\": \"n\",
    \"details\": \"architecto\",
    \"has_rating\": false,
    \"started_at\": \"2025-05-21T09:41:14\",
    \"finished_at\": \"2051-06-14\",
    \"start_rating\": 39,
    \"rating_change_for_winners_rule\": \"architecto\",
    \"rating_change_for_losers_rule\": \"architecto\",
    \"max_players\": 39,
    \"max_score\": 84,
    \"invite_days_expire\": 66
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "b",
    "picture": "n",
    "details": "architecto",
    "has_rating": false,
    "started_at": "2025-05-21T09:41:14",
    "finished_at": "2051-06-14",
    "start_rating": 39,
    "rating_change_for_winners_rule": "architecto",
    "rating_change_for_losers_rule": "architecto",
    "max_players": 39,
    "max_score": 84,
    "invite_days_expire": 66
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues">
</span>
        <span id="execution-results-POSTapi-leagues" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues" data-method="POST"
              data-path="api/leagues"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues"
                        onclick="tryItOut('POSTapi-leagues');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues"
                        onclick="cancelTryOut('POSTapi-leagues');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value" data-endpoint="POSTapi-leagues"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="name" data-endpoint="POSTapi-leagues"
                       value="b"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>b</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>game_id</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="game_id" data-endpoint="POSTapi-leagues"
                       value=""
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the games table.</p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>picture</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="picture" data-endpoint="POSTapi-leagues"
                       value="n"
                       data-component="body">
                <br>
                <p>Must be a valid URL. Must not be greater than 255 characters. Example: <code>n</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>details</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="details" data-endpoint="POSTapi-leagues"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>has_rating</code></b>&nbsp;&nbsp;
                <small>boolean</small>&nbsp;
                <i>optional</i> &nbsp;
                <label data-endpoint="POSTapi-leagues" style="display: none">
                    <input type="radio" name="has_rating"
                           value="true"
                           data-endpoint="POSTapi-leagues"
                           data-component="body">
                    <code>true</code>
                </label>
                <label data-endpoint="POSTapi-leagues" style="display: none">
                    <input type="radio" name="has_rating"
                           value="false"
                           data-endpoint="POSTapi-leagues"
                           data-component="body">
                    <code>false</code>
                </label>
                <br>
                <p>Example: <code>false</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>started_at</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="started_at" data-endpoint="POSTapi-leagues"
                       value="2025-05-21T09:41:14"
                       data-component="body">
                <br>
                <p>Must be a valid date. Example: <code>2025-05-21T09:41:14</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>finished_at</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="finished_at" data-endpoint="POSTapi-leagues"
                       value="2051-06-14"
                       data-component="body">
                <br>
                <p>Must be a valid date. Must be a date after or equal to <code>started_at</code>. Example: <code>2051-06-14</code>
                </p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>start_rating</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="start_rating" data-endpoint="POSTapi-leagues"
                       value="39"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>39</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>rating_change_for_winners_rule</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="rating_change_for_winners_rule" data-endpoint="POSTapi-leagues"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>rating_change_for_losers_rule</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="rating_change_for_losers_rule" data-endpoint="POSTapi-leagues"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>max_players</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="max_players" data-endpoint="POSTapi-leagues"
                       value="39"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>39</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>max_score</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="max_score" data-endpoint="POSTapi-leagues"
                       value="84"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>84</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>invite_days_expire</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="invite_days_expire" data-endpoint="POSTapi-leagues"
                       value="66"
                       data-component="body">
                <br>
                <p>Must be at least 1. Example: <code>66</code></p>
            </div>
        </form>

        <h2 id="leagues-PUTapi-leagues--id-">Update league by id</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-PUTapi-leagues--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8001/api/leagues/1" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"b\",
    \"picture\": \"n\",
    \"details\": \"architecto\",
    \"has_rating\": false,
    \"started_at\": \"2025-05-21T09:41:14\",
    \"finished_at\": \"2051-06-14\",
    \"start_rating\": 39,
    \"rating_change_for_winners_rule\": \"architecto\",
    \"rating_change_for_losers_rule\": \"architecto\",
    \"max_players\": 39,
    \"max_score\": 84,
    \"invite_days_expire\": 66
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "b",
    "picture": "n",
    "details": "architecto",
    "has_rating": false,
    "started_at": "2025-05-21T09:41:14",
    "finished_at": "2051-06-14",
    "start_rating": 39,
    "rating_change_for_winners_rule": "architecto",
    "rating_change_for_losers_rule": "architecto",
    "max_players": 39,
    "max_score": 84,
    "invite_days_expire": 66
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-PUTapi-leagues--id-">
</span>
        <span id="execution-results-PUTapi-leagues--id-" hidden>
    <blockquote>Received response<span
            id="execution-response-status-PUTapi-leagues--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-leagues--id-"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-PUTapi-leagues--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-leagues--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-PUTapi-leagues--id-" data-method="PUT"
              data-path="api/leagues/{id}"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('PUTapi-leagues--id-', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-PUTapi-leagues--id-"
                        onclick="tryItOut('PUTapi-leagues--id-');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-PUTapi-leagues--id-"
                        onclick="cancelTryOut('PUTapi-leagues--id-');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-PUTapi-leagues--id-"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-darkblue">PUT</small>
                <b><code>api/leagues/{id}</code></b>
            </p>
            <p>
                <small class="badge badge-purple">PATCH</small>
                <b><code>api/leagues/{id}</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value" data-endpoint="PUTapi-leagues--id-"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="PUTapi-leagues--id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="PUTapi-leagues--id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="id" data-endpoint="PUTapi-leagues--id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="name" data-endpoint="PUTapi-leagues--id-"
                       value="b"
                       data-component="body">
                <br>
                <p>Must not be greater than 255 characters. Example: <code>b</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>game_id</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="game_id" data-endpoint="PUTapi-leagues--id-"
                       value=""
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the games table.</p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>picture</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="picture" data-endpoint="PUTapi-leagues--id-"
                       value="n"
                       data-component="body">
                <br>
                <p>Must be a valid URL. Must not be greater than 255 characters. Example: <code>n</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>details</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="details" data-endpoint="PUTapi-leagues--id-"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>has_rating</code></b>&nbsp;&nbsp;
                <small>boolean</small>&nbsp;
                <i>optional</i> &nbsp;
                <label data-endpoint="PUTapi-leagues--id-" style="display: none">
                    <input type="radio" name="has_rating"
                           value="true"
                           data-endpoint="PUTapi-leagues--id-"
                           data-component="body">
                    <code>true</code>
                </label>
                <label data-endpoint="PUTapi-leagues--id-" style="display: none">
                    <input type="radio" name="has_rating"
                           value="false"
                           data-endpoint="PUTapi-leagues--id-"
                           data-component="body">
                    <code>false</code>
                </label>
                <br>
                <p>Example: <code>false</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>started_at</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="started_at" data-endpoint="PUTapi-leagues--id-"
                       value="2025-05-21T09:41:14"
                       data-component="body">
                <br>
                <p>Must be a valid date. Example: <code>2025-05-21T09:41:14</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>finished_at</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="finished_at" data-endpoint="PUTapi-leagues--id-"
                       value="2051-06-14"
                       data-component="body">
                <br>
                <p>Must be a valid date. Must be a date after or equal to <code>started_at</code>. Example: <code>2051-06-14</code>
                </p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>start_rating</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="start_rating" data-endpoint="PUTapi-leagues--id-"
                       value="39"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>39</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>rating_change_for_winners_rule</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="rating_change_for_winners_rule" data-endpoint="PUTapi-leagues--id-"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>rating_change_for_losers_rule</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="rating_change_for_losers_rule" data-endpoint="PUTapi-leagues--id-"
                       value="architecto"
                       data-component="body">
                <br>
                <p>Example: <code>architecto</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>max_players</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="max_players" data-endpoint="PUTapi-leagues--id-"
                       value="39"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>39</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>max_score</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="max_score" data-endpoint="PUTapi-leagues--id-"
                       value="84"
                       data-component="body">
                <br>
                <p>Must be at least 0. Example: <code>84</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>invite_days_expire</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="invite_days_expire" data-endpoint="PUTapi-leagues--id-"
                       value="66"
                       data-component="body">
                <br>
                <p>Must be at least 1. Example: <code>66</code></p>
            </div>
        </form>

        <h2 id="leagues-DELETEapi-leagues--id-">Delete league by id</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-DELETEapi-leagues--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8001/api/leagues/1" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-DELETEapi-leagues--id-">
</span>
        <span id="execution-results-DELETEapi-leagues--id-" hidden>
    <blockquote>Received response<span
            id="execution-response-status-DELETEapi-leagues--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-leagues--id-"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-DELETEapi-leagues--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-leagues--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-DELETEapi-leagues--id-" data-method="DELETE"
              data-path="api/leagues/{id}"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('DELETEapi-leagues--id-', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-DELETEapi-leagues--id-"
                        onclick="tryItOut('DELETEapi-leagues--id-');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-DELETEapi-leagues--id-"
                        onclick="cancelTryOut('DELETEapi-leagues--id-');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-DELETEapi-leagues--id-"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-red">DELETE</small>
                <b><code>api/leagues/{id}</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value" data-endpoint="DELETEapi-leagues--id-"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="DELETEapi-leagues--id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="DELETEapi-leagues--id-"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="id" data-endpoint="DELETEapi-leagues--id-"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="leagues-POSTapi-my-leagues-and-challenges">Load leagues and challenges for logged user</h2>

        <p>
        </p>


        <span id="example-requests-POSTapi-my-leagues-and-challenges">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/my-leagues-and-challenges" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/my-leagues-and-challenges"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-my-leagues-and-challenges">
</span>
        <span id="execution-results-POSTapi-my-leagues-and-challenges" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-my-leagues-and-challenges"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-my-leagues-and-challenges"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-my-leagues-and-challenges" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-my-leagues-and-challenges">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-my-leagues-and-challenges" data-method="POST"
              data-path="api/my-leagues-and-challenges"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-my-leagues-and-challenges', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-my-leagues-and-challenges"
                        onclick="tryItOut('POSTapi-my-leagues-and-challenges');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-my-leagues-and-challenges"
                        onclick="cancelTryOut('POSTapi-my-leagues-and-challenges');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-my-leagues-and-challenges"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/my-leagues-and-challenges</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-my-leagues-and-challenges"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-my-leagues-and-challenges"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="leagues-GETapi-games">GET api/games</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-games">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/games" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/games"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-games">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-games" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-games"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-games"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-games" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-games">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-games" data-method="GET"
              data-path="api/games"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-games', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-games"
                        onclick="tryItOut('GETapi-games');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-games"
                        onclick="cancelTryOut('GETapi-games');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-games"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/games</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
        </form>

        <h2 id="leagues-GETapi-leagues--league_id--players">Get a list of players in a league</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--players">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/players" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/players"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--players">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--players" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--players"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues--league_id--players"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--players" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--league_id--players">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--players" data-method="GET"
              data-path="api/leagues/{league_id}/players"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--players', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--players"
                        onclick="tryItOut('GETapi-leagues--league_id--players');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--players"
                        onclick="cancelTryOut('GETapi-leagues--league_id--players');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--players"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/players</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues--league_id--players"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--league_id--players"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="GETapi-leagues--league_id--players"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="leagues-GETapi-leagues--league_id--games">Get a list of games in a league</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--games">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/games" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/games"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--games">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--games" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--games"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues--league_id--games"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--games" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--league_id--games">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--games" data-method="GET"
              data-path="api/leagues/{league_id}/games"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--games', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--games"
                        onclick="tryItOut('GETapi-leagues--league_id--games');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--games"
                        onclick="cancelTryOut('GETapi-leagues--league_id--games');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--games"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/games</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues--league_id--games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--league_id--games"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="GETapi-leagues--league_id--games"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="leagues-GETapi-leagues--league_id--load-user-rating">GET api/leagues/{league_id}/load-user-rating</h2>

        <p>
        </p>


        <span id="example-requests-GETapi-leagues--league_id--load-user-rating">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8001/api/leagues/1/load-user-rating" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/load-user-rating"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-GETapi-leagues--league_id--load-user-rating">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:8001
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
        <span id="execution-results-GETapi-leagues--league_id--load-user-rating" hidden>
    <blockquote>Received response<span
            id="execution-response-status-GETapi-leagues--league_id--load-user-rating"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-leagues--league_id--load-user-rating"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-GETapi-leagues--league_id--load-user-rating" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-leagues--league_id--load-user-rating">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-GETapi-leagues--league_id--load-user-rating" data-method="GET"
              data-path="api/leagues/{league_id}/load-user-rating"
              data-authed="0"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('GETapi-leagues--league_id--load-user-rating', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-GETapi-leagues--league_id--load-user-rating"
                        onclick="tryItOut('GETapi-leagues--league_id--load-user-rating');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-GETapi-leagues--league_id--load-user-rating"
                        onclick="cancelTryOut('GETapi-leagues--league_id--load-user-rating');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-GETapi-leagues--league_id--load-user-rating"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-green">GET</small>
                <b><code>api/leagues/{league_id}/load-user-rating</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="GETapi-leagues--league_id--load-user-rating"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="GETapi-leagues--league_id--load-user-rating"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="GETapi-leagues--league_id--load-user-rating"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h1 id="matchgames">MatchGames</h1>


        <h2 id="matchgames-POSTapi-leagues--league_id--players--user_id--send-match-game">Send a challenge to the
            player</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--players--user_id--send-match-game">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/players/1/send-match-game" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"stream_url\": \"http:\\/\\/www.bailey.biz\\/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html\",
    \"details\": \"i\",
    \"club_id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/players/1/send-match-game"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "stream_url": "http:\/\/www.bailey.biz\/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html",
    "details": "i",
    "club_id": 16
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--players--user_id--send-match-game">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--players--user_id--send-match-game" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--players--user_id--send-match-game"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--players--user_id--send-match-game"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--players--user_id--send-match-game" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--players--user_id--send-match-game">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--players--user_id--send-match-game" data-method="POST"
              data-path="api/leagues/{league_id}/players/{user_id}/send-match-game"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--players--user_id--send-match-game', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--players--user_id--send-match-game"
                        onclick="tryItOut('POSTapi-leagues--league_id--players--user_id--send-match-game');">Try it out
                    ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--players--user_id--send-match-game"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--players--user_id--send-match-game');" hidden>
                    Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--players--user_id--send-match-game"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/players/{user_id}/send-match-game</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value"
                       data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="user_id"
                       data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the user. Example: <code>1</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>stream_url</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="stream_url" data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="http://www.bailey.biz/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html"
                       data-component="body">
                <br>
                <p>Must be a valid URL. Must not be greater than 2048 characters. Example: <code>http://www.bailey.biz/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html</code>
                </p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>details</code></b>&nbsp;&nbsp;
                <small>string</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="text" style="display: none"
                       name="details" data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="i"
                       data-component="body">
                <br>
                <p>Must not be greater than 1000 characters. Example: <code>i</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>club_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                <i>optional</i> &nbsp;
                <input type="number" style="display: none"
                       step="any" name="club_id"
                       data-endpoint="POSTapi-leagues--league_id--players--user_id--send-match-game"
                       value="16"
                       data-component="body">
                <br>
                <p>The <code>id</code> of an existing record in the clubs table. Example: <code>16</code></p>
            </div>
        </form>

        <h2 id="matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept">Accept the
            challenge</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/players/match-games/2/accept" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/players/match-games/2/accept"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept" data-method="POST"
              data-path="api/leagues/{league_id}/players/match-games/{matchGame_id}/accept"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--accept', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                        onclick="tryItOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--accept');">Try
                    it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--accept');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/players/match-games/{matchGame_id}/accept</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matchGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="matchGame_id"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--accept"
                       value="2"
                       data-component="url">
                <br>
                <p>The ID of the matchGame. Example: <code>2</code></p>
            </div>
        </form>

        <h2 id="matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline">Decline the
            challenge</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/players/match-games/2/decline" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/players/match-games/2/decline"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline" data-method="POST"
              data-path="api/leagues/{league_id}/players/match-games/{matchGame_id}/decline"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--decline', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                        onclick="tryItOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--decline');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--decline');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/players/match-games/{matchGame_id}/decline</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matchGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="matchGame_id"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--decline"
                       value="2"
                       data-component="url">
                <br>
                <p>The ID of the matchGame. Example: <code>2</code></p>
            </div>
        </form>

        <h2 id="matchgames-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result">Send Result of a
            game match</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/players/match-games/2/send-result" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"first_user_score\": 16,
    \"second_user_score\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/players/match-games/2/send-result"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "first_user_score": 16,
    "second_user_score": 16
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"></span>:
    </blockquote>
    <pre class="json"><code
            id="execution-response-content-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result" data-method="POST"
              data-path="api/leagues/{league_id}/players/match-games/{matchGame_id}/send-result"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                        onclick="tryItOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result');">
                    Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result');"
                        hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/players/match-games/{matchGame_id}/send-result</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>matchGame_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="matchGame_id"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                       value="2"
                       data-component="url">
                <br>
                <p>The ID of the matchGame. Example: <code>2</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>first_user_score</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="first_user_score"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                       value="16"
                       data-component="body">
                <br>
                <p>Example: <code>16</code></p>
            </div>
            <div style=" padding-left: 28px;  clear: unset;">
                <b style="line-height: 2;"><code>second_user_score</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="second_user_score"
                       data-endpoint="POSTapi-leagues--league_id--players-match-games--matchGame_id--send-result"
                       value="16"
                       data-component="body">
                <br>
                <p>Example: <code>16</code></p>
            </div>
        </form>

        <h1 id="players">Players</h1>


        <h2 id="players-POSTapi-leagues--league_id--players-enter">Enter to the League as a player</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--players-enter">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/players/enter" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/players/enter"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--players-enter">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--players-enter" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--players-enter"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues--league_id--players-enter"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--players-enter" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--players-enter">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--players-enter" data-method="POST"
              data-path="api/leagues/{league_id}/players/enter"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--players-enter', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--players-enter"
                        onclick="tryItOut('POSTapi-leagues--league_id--players-enter');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--players-enter"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--players-enter');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--players-enter"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/players/enter</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value" data-endpoint="POSTapi-leagues--league_id--players-enter"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--players-enter"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--players-enter"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="POSTapi-leagues--league_id--players-enter"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>

        <h2 id="players-POSTapi-leagues--league_id--players-leave">Leave the League as a player</h2>

        <p>
            <small class="badge badge-darkred">requires authentication</small>
        </p>


        <span id="example-requests-POSTapi-leagues--league_id--players-leave">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8001/api/leagues/1/players/leave" \
    --header "Authorization: Bearer 123456" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8001/api/leagues/1/players/leave"
);

const headers = {
    "Authorization": "Bearer 123456",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

        <span id="example-responses-POSTapi-leagues--league_id--players-leave">
</span>
        <span id="execution-results-POSTapi-leagues--league_id--players-leave" hidden>
    <blockquote>Received response<span
            id="execution-response-status-POSTapi-leagues--league_id--players-leave"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-leagues--league_id--players-leave"
                            data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
        <span id="execution-error-POSTapi-leagues--league_id--players-leave" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-leagues--league_id--players-leave">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
        <form id="form-POSTapi-leagues--league_id--players-leave" data-method="POST"
              data-path="api/leagues/{league_id}/players/leave"
              data-authed="1"
              data-hasfiles="0"
              data-isarraybody="0"
              autocomplete="off"
              onsubmit="event.preventDefault(); executeTryOut('POSTapi-leagues--league_id--players-leave', this);">
            <h3>
                Request&nbsp;&nbsp;&nbsp;
                <button type="button"
                        style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-tryout-POSTapi-leagues--league_id--players-leave"
                        onclick="tryItOut('POSTapi-leagues--league_id--players-leave');">Try it out ⚡
                </button>
                <button type="button"
                        style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-canceltryout-POSTapi-leagues--league_id--players-leave"
                        onclick="cancelTryOut('POSTapi-leagues--league_id--players-leave');" hidden>Cancel 🛑
                </button>&nbsp;&nbsp;
                <button type="submit"
                        style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                        id="btn-executetryout-POSTapi-leagues--league_id--players-leave"
                        data-initial-text="Send Request 💥"
                        data-loading-text="⏱ Sending..."
                        hidden>Send Request 💥
                </button>
            </h3>
            <p>
                <small class="badge badge-black">POST</small>
                <b><code>api/leagues/{league_id}/players/leave</code></b>
            </p>
            <h4 class="fancy-heading-panel"><b>Headers</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Authorization" class="auth-value" data-endpoint="POSTapi-leagues--league_id--players-leave"
                       value="Bearer 123456"
                       data-component="header">
                <br>
                <p>Example: <code>Bearer 123456</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Content-Type" data-endpoint="POSTapi-leagues--league_id--players-leave"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
                &nbsp;
                &nbsp;
                <input type="text" style="display: none"
                       name="Accept" data-endpoint="POSTapi-leagues--league_id--players-leave"
                       value="application/json"
                       data-component="header">
                <br>
                <p>Example: <code>application/json</code></p>
            </div>
            <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
            <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>league_id</code></b>&nbsp;&nbsp;
                <small>integer</small>&nbsp;
                &nbsp;
                <input type="number" style="display: none"
                       step="any" name="league_id" data-endpoint="POSTapi-leagues--league_id--players-leave"
                       value="1"
                       data-component="url">
                <br>
                <p>The ID of the league. Example: <code>1</code></p>
            </div>
        </form>


    </div>
    <div class="dark-box">
        <div class="lang-selector">
            <button type="button" class="lang-button" data-language-name="bash">bash</button>
            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
        </div>
    </div>
</div>
</body>
</html>
