# Bingo backend authentication and authorization

## Authentication mechanism

The regular PlayRKE web application authenticates through Laravel's `web` guard and server-side sessions, not by returning a JWT from the login endpoint.

1. `POST /login` is declared in `routes/auth.php` with request throttling.
2. `LoginRequest::authenticate()` validates credentials with `Auth::attempt()`, rate-limits failures, and rejects system or inactive accounts.
3. `AuthenticatedSessionController::store()` regenerates the session ID after authentication and stores the primary role prefix in the session. The response contains only `success: true`; the browser's session cookie is the credential.
4. `POST /logout` logs out the `web` guard, invalidates the session, and regenerates the CSRF token.
5. SPA/API requests can use Sanctum's stateful-cookie flow. `config/sanctum.php` checks the `web` guard first; the configured 10-minute Sanctum token expiration applies to personal-access tokens, not the first-party session cookie.

## Bingo request authorization

- Room management routes use `auth` plus `multiPermission:games.manage,games.bingo.manage`; global Bingo configuration/patterns and automatic rooms require `games.manage`.
- Card purchase/generation and game routes require `auth`, `verified`, and the Bingo module permission.
- Game state and lobby participant endpoints must also pass the `BingoRoomPolicy`; a general module permission is not a substitute for room membership.
- VIP/DEMO/private-room checks are performed by the room service/controller before sensitive lobby details are returned.
- External players have no `User` session. Their public Bingo URL uses a 28-character random link as a bearer credential, with active-status/expiry checks and route throttling. Treat this link like a password: do not log it, expose it to analytics, or share it publicly.

## WebSocket channels

- `bingo.room.{roomId}`, `bingo.lobby`, and `platform.win-events` are intentionally public broadcast channels. Do not broadcast private purchase details or personal data on them.
- `bingo.player.{userId}` is private and checks that the authenticated ID matches the channel ID.
- `bingo.room.{roomId}.presence` is a private presence channel. Its authorization must check the room policy before returning player identity fields; authentication alone does not grant access to every room.
- External-player URLs are bearer credentials and do not authenticate a normal WebSocket identity. Any external-player-specific real-time feature should use a separately designed, narrowly scoped authorization mechanism.

## Regression coverage

`tests/Feature/Games/Bingo/BingoRoomAuthorizationTest.php` protects against broad business roles accidentally granting access to another room's draw state/participant list and preserves creator access.
